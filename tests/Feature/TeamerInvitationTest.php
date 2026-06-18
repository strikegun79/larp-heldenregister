<?php

namespace Tests\Feature;

use App\Models\Adventure;
use App\Models\User;
use App\Notifications\TeamerInvitation;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamerInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function userWithRole(int $roleId, array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['activated' => true], $attrs));
        $user->roles()->attach($roleId);

        return $user;
    }

    private function admin(): User
    {
        return $this->userWithRole(10);
    }

    private function teamer(array $attrs = []): User
    {
        return $this->userWithRole(50, $attrs);
    }

    private function lehrmeister(array $attrs = []): User
    {
        return $this->userWithRole(45, $attrs);
    }

    private function adventure(): Adventure
    {
        return Adventure::factory()->create(['event_status_id' => 30]);
    }

    // ── Zugang ───────────────────────────────────────────────────────────────

    public function test_teamer_cannot_send_invitation(): void
    {
        Notification::fake();

        $this->actingAs($this->teamer())
            ->post(route('adventures.teamer.invite', $this->adventure()))
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    public function test_admin_can_send_invitation(): void
    {
        Notification::fake();

        $this->actingAs($this->admin())
            ->post(route('adventures.teamer.invite', $this->adventure()))
            ->assertSessionDoesntHaveErrors();
    }

    // ── Empfänger ─────────────────────────────────────────────────────────────

    public function test_active_teamers_with_notifications_receive_invitation(): void
    {
        Notification::fake();
        $adventure = $this->adventure();

        $teamer1 = $this->teamer();
        $teamer2 = $this->teamer();
        $lehrmeister = $this->lehrmeister();

        $this->actingAs($this->admin())
            ->post(route('adventures.teamer.invite', $adventure));

        Notification::assertSentTo([$teamer1, $teamer2, $lehrmeister], TeamerInvitation::class);
    }

    public function test_opted_out_teamer_does_not_receive_invitation(): void
    {
        Notification::fake();
        $adventure = $this->adventure();

        $optedOut = $this->teamer(['teamer_notifications' => false]);
        $active = $this->teamer(['teamer_notifications' => true]);

        $this->actingAs($this->admin())
            ->post(route('adventures.teamer.invite', $adventure));

        Notification::assertNotSentTo($optedOut, TeamerInvitation::class);
        Notification::assertSentTo($active, TeamerInvitation::class);
    }

    public function test_deactivated_teamer_does_not_receive_invitation(): void
    {
        Notification::fake();
        $adventure = $this->adventure();

        $deactivated = $this->teamer(['activated' => false]);
        $active = $this->teamer(['activated' => true]);

        $this->actingAs($this->admin())
            ->post(route('adventures.teamer.invite', $adventure));

        Notification::assertNotSentTo($deactivated, TeamerInvitation::class);
        Notification::assertSentTo($active, TeamerInvitation::class);
    }

    public function test_regular_user_without_teamer_role_does_not_receive_invitation(): void
    {
        Notification::fake();
        $adventure = $this->adventure();

        $participant = $this->userWithRole(70);

        $this->actingAs($this->admin())
            ->post(route('adventures.teamer.invite', $adventure));

        Notification::assertNotSentTo($participant, TeamerInvitation::class);
    }

    // ── Profil-Einstellung ────────────────────────────────────────────────────

    public function test_teamer_can_opt_out_of_notifications(): void
    {
        $teamer = $this->teamer(['teamer_notifications' => true]);

        $this->actingAs($teamer)
            ->patch(route('profile.update'), [
                'name' => $teamer->name,
                'lastname' => $teamer->lastname,
                'email' => $teamer->email,
                'phone' => $teamer->phone,
                // kein teamer_notifications → unchecked → false
            ]);

        $this->assertFalse($teamer->fresh()->teamer_notifications);
    }

    public function test_teamer_can_opt_in_to_notifications(): void
    {
        $teamer = $this->teamer(['teamer_notifications' => false]);

        $this->actingAs($teamer)
            ->patch(route('profile.update'), [
                'name' => $teamer->name,
                'lastname' => $teamer->lastname,
                'email' => $teamer->email,
                'phone' => $teamer->phone,
                'teamer_notifications' => '1',
            ]);

        $this->assertTrue($teamer->fresh()->teamer_notifications);
    }
}
