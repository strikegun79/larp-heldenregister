<?php

use App\Models\EventRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $teamerIds = EventRole::teamerLike()->pluck('id');

        DB::table('bookings')
            ->whereIn('event_role_id', $teamerIds)
            ->where('waitlisted', true)
            ->update([
                'waitlisted'  => false,
                'status'      => 'offen',
                'approved_at' => null,
            ]);
    }

    public function down(): void
    {
        // Nicht umkehrbar – ursprünglicher Zustand nicht bekannt.
    }
};
