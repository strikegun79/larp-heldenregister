<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Hero;
use App\Models\HeroGalleryImage;
use App\Models\Player;
use App\Models\TeamerSignup;
use App\Models\User;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * DSGVO Art. 5 Abs. 1 lit. e: Aufbewahrungsfristen des Befehls dsgvo:prune.
 * Jede Frist wird mit einem abgelaufenen und einem noch gültigen Datensatz geprüft.
 */
class DsgvoPruneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, EventLookupSeeder::class]);
        Storage::fake('public');
    }

    /** Abenteuer, das vor der angegebenen Zeitspanne endete. */
    private function adventureEndedAgo(string $interval): Adventure
    {
        $end = now()->sub($interval);

        return Adventure::factory()->create([
            'start_at' => $end->copy()->subDays(2),
            'end_at' => $end,
            'event_status_id' => 60,
        ]);
    }

    private function prune(array $options = []): void
    {
        $this->artisan('dsgvo:prune', $options)->assertSuccessful();
    }

    // ── Unterschriften: 30 Tage ─────────────────────────────────────────────

    public function test_signatures_are_removed_30_days_after_event(): void
    {
        $old = Booking::factory()->for($this->adventureEndedAgo('31 days'))->create(['signature' => 'data:image/png;base64,ALT']);
        $recent = Booking::factory()->for($this->adventureEndedAgo('10 days'))->create(['signature' => 'data:image/png;base64,NEU']);

        $this->prune();

        $this->assertNull($old->fresh()->signature);
        $this->assertSame('data:image/png;base64,NEU', $recent->fresh()->signature);
    }

    // ── Gesundheitsdaten: 2 Jahre ───────────────────────────────────────────

    public function test_booking_health_data_is_removed_after_two_years(): void
    {
        $health = ['allergien' => 'Nüsse', 'medikamente' => 'Spray', 'health_data_consent_at' => now()->subYears(3)];
        $old = Booking::factory()->for($this->adventureEndedAgo('25 months'))->create($health);
        $recent = Booking::factory()->for($this->adventureEndedAgo('13 months'))->create($health);

        $this->prune();

        $old->refresh();
        $this->assertNull($old->allergien);
        $this->assertNull($old->medikamente);
        $this->assertNull($old->health_data_consent_at);
        $this->assertSame('Nüsse', $recent->fresh()->allergien);
    }

    public function test_teamer_health_data_is_removed_after_two_years(): void
    {
        $health = ['allergien' => 'Pollen', 'medikamente' => 'Tabletten', 'health_data_consent_at' => now()->subYears(3)];
        $old = TeamerSignup::factory()->create(['adventure_id' => $this->adventureEndedAgo('25 months')->id] + $health);
        $recent = TeamerSignup::factory()->create(['adventure_id' => $this->adventureEndedAgo('13 months')->id] + $health);

        $this->prune();

        $old->refresh();
        $this->assertNull($old->allergien);
        $this->assertNull($old->medikamente);
        $this->assertNull($old->health_data_consent_at);
        $this->assertSame('Pollen', $recent->fresh()->allergien);
    }

    // ── Buchungen: 3 Jahre anonymisieren ────────────────────────────────────

    public function test_bookings_are_anonymized_after_three_years_but_kept_for_statistics(): void
    {
        $oldAdventure = $this->adventureEndedAgo('37 months');
        $old = Booking::factory()->for($oldAdventure)->create([
            'player_id' => Player::factory()->create()->id,
            'booked_by_user_id' => User::factory()->create()->id,
            'kontakt_telefon' => '+49 123 456789',
            'erreichbarkeit' => 'abends',
            'status' => 'bestaetigt',
            'paid' => true,
        ]);
        $guest = Booking::factory()->for($oldAdventure)->create([
            'player_id' => null, 'guest_name' => 'Gerd', 'guest_lastname' => 'Gast',
            'guest_age' => 9, 'guest_place' => 'Gießen',
        ]);
        $recentPlayer = Player::factory()->create();
        $recent = Booking::factory()->for($this->adventureEndedAgo('35 months'))->create([
            'player_id' => $recentPlayer->id,
            'kontakt_telefon' => '+49 987 654321',
        ]);

        $this->prune();

        $old->refresh();
        $this->assertNull($old->player_id);
        $this->assertNull($old->booked_by_user_id);
        $this->assertNull($old->kontakt_telefon);
        $this->assertNull($old->erreichbarkeit);
        // Statistische Felder bleiben erhalten.
        $this->assertSame($oldAdventure->id, $old->adventure_id);
        $this->assertSame('bestaetigt', $old->status);
        $this->assertTrue((bool) $old->paid);

        $guest->refresh();
        $this->assertNull($guest->guest_name);
        $this->assertNull($guest->guest_lastname);
        $this->assertNull($guest->guest_age);
        $this->assertNull($guest->guest_place);

        $recent->refresh();
        $this->assertSame($recentPlayer->id, $recent->player_id);
        $this->assertSame('+49 987 654321', $recent->kontakt_telefon);
    }

    // ── Spieler und Benutzer: 3 Jahre nach Soft-Delete ──────────────────────

    public function test_players_soft_deleted_over_three_years_ago_are_purged_with_files(): void
    {
        Storage::disk('public')->put('players/p.jpg', 'x');
        Storage::disk('public')->put('heroes/h.jpg', 'x');
        Storage::disk('public')->put('gallery/g.jpg', 'x');

        $player = Player::factory()->create(['image' => 'players/p.jpg']);
        $hero = Hero::factory()->create(['player_id' => $player->id, 'image' => 'heroes/h.jpg']);
        HeroGalleryImage::factory()->create(['hero_id' => $hero->id, 'path' => 'gallery/g.jpg']);
        // Buchung auf einem jüngeren Event: Referenz wird gekappt, Buchung bleibt.
        $booking = Booking::factory()->for($this->adventureEndedAgo('1 year'))->create(['player_id' => $player->id]);
        $player->delete();
        Player::withTrashed()->whereKey($player->id)->update(['deleted_at' => now()->subYears(3)->subDay()]);

        $recentlyDeleted = Player::factory()->create();
        $recentlyDeleted->delete();

        $this->prune();

        $this->assertNull(Player::withTrashed()->find($player->id));
        $this->assertDatabaseMissing('heroes', ['id' => $hero->id]);
        Storage::disk('public')->assertMissing(['players/p.jpg', 'heroes/h.jpg', 'gallery/g.jpg']);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'player_id' => null]);

        $this->assertNotNull(Player::withTrashed()->find($recentlyDeleted->id));
    }

    public function test_users_soft_deleted_over_three_years_ago_are_purged(): void
    {
        $old = User::factory()->create();
        $old->delete();
        User::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subYears(3)->subDay()]);

        $recent = User::factory()->create();
        $recent->delete();

        $active = User::factory()->create();

        $this->prune();

        $this->assertNull(User::withTrashed()->find($old->id));
        $this->assertNotNull(User::withTrashed()->find($recent->id));
        $this->assertNotNull(User::find($active->id));
    }

    // ── Protokolle und Benachrichtigungen ───────────────────────────────────

    public function test_audit_logs_are_deleted_after_three_years(): void
    {
        $old = AuditLog::create(['action' => 'alt', 'actor_name' => 'System']);
        $old->forceFill(['created_at' => now()->subYears(3)->subDay()])->saveQuietly();
        $recent = AuditLog::create(['action' => 'neu', 'actor_name' => 'System']);

        $this->prune();

        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);
    }

    public function test_notifications_are_deleted_after_one_year(): void
    {
        $user = User::factory()->create();
        $notification = fn ($createdAt) => [
            'id' => (string) Str::uuid(),
            'type' => 'Test',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => '{}',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
        $old = $notification(now()->subYear()->subDay());
        $recent = $notification(now()->subMonths(6));
        DB::table('notifications')->insert([$old, $recent]);

        $this->prune();

        $this->assertDatabaseMissing('notifications', ['id' => $old['id']]);
        $this->assertDatabaseHas('notifications', ['id' => $recent['id']]);
    }

    // ── Umfragen ────────────────────────────────────────────────────────────

    /** Legt eine Umfrageantwort mit Freitext- und Bewertungsantwort an. */
    private function surveyResponse(\DateTimeInterface $submittedAt): array
    {
        $creator = User::factory()->create();
        $templateId = DB::table('survey_templates')->insertGetId([
            'name' => 'Feedback', 'target_group' => 'parent', 'created_by' => $creator->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $textQuestion = DB::table('survey_template_questions')->insertGetId([
            'survey_template_id' => $templateId, 'question_text' => 'Was war gut?', 'type' => 'text',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $ratingQuestion = DB::table('survey_template_questions')->insertGetId([
            'survey_template_id' => $templateId, 'question_text' => 'Note?', 'type' => 'rating',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $surveyId = DB::table('surveys')->insertGetId([
            'adventure_id' => Adventure::factory()->create()->id, 'survey_template_id' => $templateId,
            'title' => 'Umfrage', 'created_by' => $creator->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $linkId = DB::table('survey_links')->insertGetId([
            'survey_id' => $surveyId, 'token' => (string) Str::uuid(), 'target_type' => 'parent',
            'name' => 'Eltern', 'email' => 'eltern@example.test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $responseId = DB::table('survey_responses')->insertGetId([
            'survey_link_id' => $linkId, 'submitted_at' => $submittedAt, 'ip_address' => '192.0.2.1',
            'created_at' => $submittedAt, 'updated_at' => $submittedAt,
        ]);
        $textAnswer = DB::table('survey_answers')->insertGetId([
            'survey_response_id' => $responseId, 'survey_template_question_id' => $textQuestion,
            'text_answer' => 'Toller Tag', 'created_at' => $submittedAt, 'updated_at' => $submittedAt,
        ]);
        $ratingAnswer = DB::table('survey_answers')->insertGetId([
            'survey_response_id' => $responseId, 'survey_template_question_id' => $ratingQuestion,
            'rating_answer' => 9, 'created_at' => $submittedAt, 'updated_at' => $submittedAt,
        ]);

        return [$responseId, $textAnswer, $ratingAnswer];
    }

    public function test_survey_ip_addresses_are_removed_after_90_days(): void
    {
        [$old] = $this->surveyResponse(now()->subDays(91));
        [$recent] = $this->surveyResponse(now()->subDays(30));

        $this->prune();

        $this->assertNull(DB::table('survey_responses')->where('id', $old)->value('ip_address'));
        $this->assertSame('192.0.2.1', DB::table('survey_responses')->where('id', $recent)->value('ip_address'));
    }

    public function test_survey_free_texts_are_removed_after_two_years_ratings_are_kept(): void
    {
        [, $oldText, $oldRating] = $this->surveyResponse(now()->subYears(2)->subDay());
        [, $recentText] = $this->surveyResponse(now()->subYear());

        $this->prune();

        $this->assertNull(DB::table('survey_answers')->where('id', $oldText)->value('text_answer'));
        $this->assertSame(9, (int) DB::table('survey_answers')->where('id', $oldRating)->value('rating_answer'));
        $this->assertSame('Toller Tag', DB::table('survey_answers')->where('id', $recentText)->value('text_answer'));
    }

    // ── Dry-Run ─────────────────────────────────────────────────────────────

    public function test_dry_run_changes_nothing(): void
    {
        $booking = Booking::factory()->for($this->adventureEndedAgo('37 months'))->create([
            'player_id' => Player::factory()->create()->id,
            'signature' => 'data:image/png;base64,ALT',
            'allergien' => 'Nüsse',
        ]);
        $player = Player::factory()->create();
        $player->delete();
        Player::withTrashed()->whereKey($player->id)->update(['deleted_at' => now()->subYears(4)]);
        $log = AuditLog::create(['action' => 'alt', 'actor_name' => 'System']);
        $log->forceFill(['created_at' => now()->subYears(4)])->saveQuietly();

        $this->artisan('dsgvo:prune', ['--dry-run' => true])
            ->expectsOutputToContain('Dry-run')
            ->assertSuccessful();

        $booking->refresh();
        $this->assertNotNull($booking->player_id);
        $this->assertSame('data:image/png;base64,ALT', $booking->signature);
        $this->assertSame('Nüsse', $booking->allergien);
        $this->assertNotNull(Player::withTrashed()->find($player->id));
        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }
}
