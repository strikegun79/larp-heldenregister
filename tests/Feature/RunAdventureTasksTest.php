<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\AdventureTask;
use App\Models\Booking;
use App\Models\EventStatus;
use App\Models\Player;
use App\Models\User;
use App\Notifications\EventReminder;
use App\Notifications\WaitlistPromoted;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * TASK-01: Befehl adventures:run-tasks – führt fällige Event-Aufgaben aus.
 */
class RunAdventureTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
        Notification::fake();
    }

    /** Abenteuer in drei Tagen. */
    private function adventure(array $attributes = []): Adventure
    {
        return Adventure::factory()->create($attributes + [
            'start_at' => now()->addDays(3),
            'end_at' => now()->addDays(4),
        ]);
    }

    /** Aufgabe, die standardmäßig heute fällig ist (7 Tage vor Start). */
    private function task(Adventure $adventure, string $type, array $attributes = []): AdventureTask
    {
        return AdventureTask::create($attributes + [
            'adventure_id' => $adventure->id,
            'task_type' => $type,
            'trigger_reference' => 'start_at',
            'trigger_direction' => 'before',
            'trigger_days' => 7,
            'is_active' => true,
        ]);
    }

    private function runTasks(array $options = []): void
    {
        $this->artisan('adventures:run-tasks', $options)->assertSuccessful();
    }

    /** Über den array-Mailer versendete Rohmails (Mail::raw). */
    private function sentMails(): Collection
    {
        return collect(app('mailer')->getSymfonyTransport()->messages());
    }

    private function playerWithEmail(string $email, array $attributes = []): Player
    {
        return Player::factory()->create(['email' => $email] + $attributes);
    }

    // ── Fälligkeit und Ablauf ───────────────────────────────────────────────

    public function test_due_task_is_executed_and_marked(): void
    {
        $adventure = $this->adventure(['event_status_id' => EventStatus::REGISTRATION_OPEN]);
        $task = $this->task($adventure, 'close_registration');

        $this->runTasks();

        $task->refresh();
        $this->assertNotNull($task->executed_at);
        $this->assertStringContainsString('Status auf 40 gesetzt', $task->result);
        $this->assertSame(EventStatus::REGISTRATION_CLOSED, $adventure->fresh()->event_status_id);
    }

    public function test_tasks_not_due_inactive_or_executed_are_skipped(): void
    {
        // Je Abenteuer ist jeder Aufgabentyp nur einmal erlaubt (Unique-Index).
        $open = ['event_status_id' => EventStatus::REGISTRATION_OPEN];
        $notDue = $this->task($this->adventure($open), 'close_registration', ['trigger_days' => 1]);
        $inactive = $this->task($this->adventure($open), 'close_registration', ['is_active' => false]);
        $executed = $this->task($this->adventure($open), 'close_registration', ['executed_at' => now()->subDay(), 'result' => 'alt']);

        $this->runTasks();

        foreach ([$notDue, $inactive, $executed] as $task) {
            $this->assertSame(EventStatus::REGISTRATION_OPEN, $task->adventure->fresh()->event_status_id);
        }
        $this->assertNull($notDue->fresh()->executed_at);
        $this->assertNull($inactive->fresh()->executed_at);
        $this->assertSame('alt', $executed->fresh()->result);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $adventure = $this->adventure(['event_status_id' => EventStatus::REGISTRATION_OPEN]);
        $task = $this->task($adventure, 'close_registration');

        $this->artisan('adventures:run-tasks', ['--dry-run' => true])
            ->expectsOutputToContain('Dry-Run')
            ->assertSuccessful();

        $this->assertNull($task->fresh()->executed_at);
        $this->assertSame(EventStatus::REGISTRATION_OPEN, $adventure->fresh()->event_status_id);
    }

    public function test_task_after_event_end_uses_end_date(): void
    {
        // complete_event 1 Tag nach Ende: Ende war vor zwei Tagen → fällig.
        $adventure = Adventure::factory()->create([
            'start_at' => now()->subDays(4),
            'end_at' => now()->subDays(2),
            'event_status_id' => 50,
        ]);
        $task = $this->task($adventure, 'complete_event', [
            'trigger_reference' => 'end_at', 'trigger_direction' => 'after', 'trigger_days' => 1,
        ]);

        $this->runTasks();

        $this->assertNotNull($task->fresh()->executed_at);
        $this->assertSame(EventStatus::COMPLETED, $adventure->fresh()->event_status_id);
    }

    // ── Statuswechsel ───────────────────────────────────────────────────────

    public function test_status_transition_is_skipped_for_cancelled_event(): void
    {
        $adventure = $this->adventure(['event_status_id' => EventStatus::CANCELLED]);
        $task = $this->task($adventure, 'open_registration');

        $this->runTasks();

        $this->assertStringContainsString('abgesagt', $task->fresh()->result);
        $this->assertSame(EventStatus::CANCELLED, $adventure->fresh()->event_status_id);
    }

    public function test_disallowed_status_transition_is_not_applied(): void
    {
        // 30 → 60 ist laut Übergangstabelle nicht erlaubt.
        $adventure = $this->adventure(['event_status_id' => EventStatus::REGISTRATION_OPEN]);
        $task = $this->task($adventure, 'complete_event');

        $this->runTasks();

        $this->assertStringContainsString('nicht erlaubt', $task->fresh()->result);
        $this->assertSame(EventStatus::REGISTRATION_OPEN, $adventure->fresh()->event_status_id);
    }

    public function test_status_already_set_is_reported(): void
    {
        $adventure = $this->adventure(['event_status_id' => EventStatus::REGISTRATION_OPEN]);
        $task = $this->task($adventure, 'open_registration');

        $this->runTasks();

        $this->assertStringContainsString('bereits gesetzt', $task->fresh()->result);
    }

    // ── Erinnerungsmail ─────────────────────────────────────────────────────

    public function test_reminder_is_sent_only_to_confirmed_regular_participants(): void
    {
        $adventure = $this->adventure();
        Booking::factory()->for($adventure)->create([
            'player_id' => $this->playerWithEmail('bestaetigt@example.test')->id,
            'status' => 'bestaetigt', 'waitlisted' => false,
        ]);
        Booking::factory()->for($adventure)->create([
            'player_id' => $this->playerWithEmail('offen@example.test')->id,
            'status' => 'offen', 'waitlisted' => false,
        ]);
        Booking::factory()->for($adventure)->create([
            'player_id' => $this->playerWithEmail('warteliste@example.test')->id,
            'status' => 'bestaetigt', 'waitlisted' => true,
        ]);
        $task = $this->task($adventure, 'send_reminder');

        $this->runTasks();

        Notification::assertSentOnDemandTimes(EventReminder::class, 1);
        Notification::assertSentOnDemand(
            EventReminder::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'bestaetigt@example.test'
        );
        $this->assertSame('1 Erinnerungsmail(s) versendet.', $task->fresh()->result);
        $this->assertNotNull($adventure->fresh()->reminder_sent_at);

    }

    public function test_reminder_is_not_sent_twice(): void
    {
        $adventure = $this->adventure(['reminder_sent_at' => now()->subDay()]);
        Booking::factory()->for($adventure)->create([
            'player_id' => $this->playerWithEmail('bestaetigt@example.test')->id,
            'status' => 'bestaetigt', 'waitlisted' => false,
        ]);
        $task = $this->task($adventure, 'send_reminder');

        $this->runTasks();

        Notification::assertNothingSent();
        $this->assertStringContainsString('bereits versendet', $task->fresh()->result);
    }

    public function test_reminder_respects_notification_setting(): void
    {
        $adventure = $this->adventure();
        $player = $this->playerWithEmail('kein-reminder@example.test');
        User::factory()->create(['notify_event_reminder' => false])->players()->attach($player->id);
        Booking::factory()->for($adventure)->create([
            'player_id' => $player->id, 'status' => 'bestaetigt', 'waitlisted' => false,
        ]);
        $this->task($adventure, 'send_reminder');

        $this->runTasks();

        Notification::assertNothingSent();
    }

    // ── Warteliste ──────────────────────────────────────────────────────────

    public function test_promote_waitlist_fills_free_slots_in_order_and_skips_age_mismatch(): void
    {
        $adventure = $this->adventure(['max_player' => 2, 'min_age' => 8]);
        Booking::factory()->for($adventure)->create(['status' => 'bestaetigt', 'waitlisted' => false]);
        // Zu jung (ältester Eintrag), wird übersprungen.
        $tooYoung = Booking::factory()->for($adventure)->create([
            'player_id' => $this->playerWithEmail('jung@example.test', ['dayofbirth' => now()->subYears(5)])->id,
            'waitlisted' => true, 'created_at' => now()->subDays(3),
        ]);
        $first = Booking::factory()->for($adventure)->create([
            'player_id' => $this->playerWithEmail('erster@example.test', ['dayofbirth' => now()->subYears(10)])->id,
            'waitlisted' => true, 'created_at' => now()->subDays(2),
        ]);
        $second = Booking::factory()->for($adventure)->create([
            'player_id' => $this->playerWithEmail('zweiter@example.test', ['dayofbirth' => now()->subYears(10)])->id,
            'waitlisted' => true, 'created_at' => now()->subDay(),
        ]);
        $task = $this->task($adventure, 'promote_waitlist');

        $this->runTasks();

        $this->assertTrue($tooYoung->fresh()->waitlisted);
        $this->assertFalse($first->fresh()->waitlisted);
        $this->assertTrue($second->fresh()->waitlisted);
        $this->assertStringStartsWith('1 Nachrücker', $task->fresh()->result);
        Notification::assertSentOnDemandTimes(WaitlistPromoted::class, 1);
    }

    public function test_promote_waitlist_without_candidates(): void
    {
        $task = $this->task($this->adventure(), 'promote_waitlist');

        $this->runTasks();

        $this->assertStringContainsString('Keine Nachrücker', $task->fresh()->result);
    }

    public function test_disable_waitlist_turns_mode_off_and_promotes(): void
    {
        $adventure = $this->adventure(['max_player' => 5, 'waitlist_mode' => true]);
        $waiting = Booking::factory()->for($adventure)->create([
            'player_id' => $this->playerWithEmail('warte@example.test')->id,
            'waitlisted' => true,
        ]);
        $task = $this->task($adventure, 'disable_waitlist');

        $this->runTasks();

        $this->assertFalse($adventure->fresh()->waitlist_mode);
        $this->assertFalse($waiting->fresh()->waitlisted);
        $this->assertStringContainsString('Nachgerückt', $task->fresh()->result);
        Notification::assertSentOnDemand(WaitlistPromoted::class);
    }

    public function test_disable_waitlist_when_already_disabled(): void
    {
        $task = $this->task($this->adventure(['waitlist_mode' => false]), 'disable_waitlist');

        $this->runTasks();

        $this->assertStringContainsString('bereits deaktiviert', $task->fresh()->result);
    }

    // ── Mails an Spielleitung / Projektleitung ──────────────────────────────

    public function test_ep_entry_reminder_is_mailed_to_game_master(): void
    {
        $gamemaster = User::factory()->create(['email' => 'spielleitung@example.test']);
        $adventure = $this->adventure(['gamemaster_id' => $gamemaster->id, 'name' => 'Burgsturm']);
        $task = $this->task($adventure, 'notify_ep_entry');

        $this->runTasks();

        $this->assertStringContainsString('spielleitung@example.test', $task->fresh()->result);
        $mail = $this->sentMails()->first();
        $this->assertNotNull($mail);
        $this->assertStringContainsString('EP-Vergabe: Burgsturm', $mail->getOriginalMessage()->getSubject());
    }

    public function test_ep_entry_reminder_without_game_master(): void
    {
        $task = $this->task($this->adventure(['gamemaster_id' => null]), 'notify_ep_entry');

        $this->runTasks();

        $this->assertStringContainsString('Kein Spielleiter', $task->fresh()->result);
        $this->assertCount(0, $this->sentMails());
    }

    public function test_participants_pdf_link_is_mailed_to_event_leader(): void
    {
        $leader = User::factory()->create(['email' => 'projektleitung@example.test']);
        $adventure = $this->adventure(['eventleader_id' => $leader->id]);
        $task = $this->task($adventure, 'send_participants_pdf');

        $this->runTasks();

        $this->assertStringContainsString('projektleitung@example.test', $task->fresh()->result);
        $mail = $this->sentMails()->first();
        $this->assertNotNull($mail);
        $this->assertStringContainsString(
            route('adventures.participants-pdf', $adventure),
            $mail->getOriginalMessage()->getTextBody()
        );
    }

    public function test_participants_pdf_without_event_leader(): void
    {
        $task = $this->task($this->adventure(['eventleader_id' => null]), 'send_participants_pdf');

        $this->runTasks();

        $this->assertStringContainsString('Kein Eventleiter', $task->fresh()->result);
    }

    // ── Hinweis-Aufgaben ────────────────────────────────────────────────────

    public function test_survey_task_reports_missing_survey(): void
    {
        $task = $this->task($this->adventure(), 'send_survey');

        $this->runTasks();

        $this->assertStringContainsString('Keine Umfrage', $task->fresh()->result);
    }

    public function test_archive_task_counts_cancelled_bookings(): void
    {
        $adventure = $this->adventure();
        Booking::factory()->for($adventure)->count(2)->create(['status' => 'storniert']);
        Booking::factory()->for($adventure)->create(['status' => 'bestaetigt']);
        $task = $this->task($adventure, 'archive_bookings');
        $empty = $this->task($this->adventure(), 'archive_bookings');

        $this->runTasks();

        $this->assertStringStartsWith('2 stornierte Buchung(en)', $task->fresh()->result);
        $this->assertStringContainsString('Keine stornierten', $empty->fresh()->result);
    }

    public function test_unknown_task_type_is_reported(): void
    {
        $task = $this->task($this->adventure(), 'gibt_es_nicht');

        $this->runTasks();

        $this->assertStringContainsString('Unbekannter Task-Typ', $task->fresh()->result);
    }
}
