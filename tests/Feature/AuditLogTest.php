<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADM-08: Audit-Log – Grundgerüst.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, LocationSeeder::class, EventLookupSeeder::class]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(10);

        return $user;
    }

    // ── AuditLogger Helper ─────────────────────────────────────────────────

    public function test_logger_creates_entry(): void
    {
        $actor = $this->admin();

        $this->actingAs($actor);
        AuditLogger::log('test.action');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test.action',
            'actor_id' => $actor->id,
            'actor_name' => trim("{$actor->name} {$actor->lastname}"),
        ]);
    }

    public function test_logger_stores_subject_and_changes(): void
    {
        $actor = $this->admin();
        $subject = User::factory()->create(['name' => 'Kurt', 'lastname' => 'Probe']);

        $this->actingAs($actor);
        AuditLogger::log('user.updated', $subject, ['activated' => ['von' => true, 'auf' => false]]);

        $log = AuditLog::where('action', 'user.updated')->first();

        $this->assertNotNull($log);
        $this->assertSame('Kurt', $log->subject_label);
        $this->assertSame($subject->id, $log->subject_id);
        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame(false, $log->changes['activated']['auf']);
    }

    // ── UserController-Integration ─────────────────────────────────────────

    public function test_user_update_is_logged(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->putJson(route('admin.users.update', $target), [
                'roles' => [70],
                'activated' => '1',
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.updated',
            'actor_id' => $admin->id,
            'subject_id' => $target->id,
        ]);
    }

    public function test_user_deleted_is_logged(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target->id));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.deleted',
            'actor_id' => $admin->id,
            'subject_id' => $target->id,
        ]);
    }

    // ── Admin-Ansicht ──────────────────────────────────────────────────────

    public function test_admin_can_view_audit_log(): void
    {
        AuditLog::create([
            'actor_name' => 'Admin Test',
            'action' => 'user.updated',
            'subject_label' => 'Max Muster',
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('user.updated')
            ->assertSee('Max Muster');
    }

    public function test_audit_log_filter_by_action(): void
    {
        AuditLog::create(['actor_name' => 'Akteur Eins', 'action' => 'user.updated', 'created_at' => now()]);
        AuditLog::create(['actor_name' => 'Akteur Zwei', 'action' => 'ep.booked',    'created_at' => now()]);

        // Filter auf ep.booked → nur Akteur Zwei erscheint in der Tabelle.
        $response = $this->actingAs($this->admin())
            ->get(route('admin.audit-logs.index', ['action' => 'ep.booked']))
            ->assertOk();

        $response->assertSee('Akteur Zwei');
        $response->assertDontSee('Akteur Eins');
    }

    public function test_audit_log_filter_by_actor(): void
    {
        AuditLog::create(['actor_name' => 'Maria Muster', 'action' => 'user.updated', 'created_at' => now()]);
        AuditLog::create(['actor_name' => 'Hans Huber',   'action' => 'ep.booked',    'created_at' => now()]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.audit-logs.index', ['actor' => 'Maria']))
            ->assertOk();

        $response->assertSee('Maria Muster');
        $response->assertDontSee('Hans Huber');
    }

    public function test_non_admin_cannot_view_audit_log(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(20); // Bürokrat

        $this->actingAs($user)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    }
}
