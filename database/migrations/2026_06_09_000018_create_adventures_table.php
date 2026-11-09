<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Abenteuer / Events. Ersetzt die Legacy-Tabelle `event`.
     * eventStartDate/eventEndeDate -> start_at/end_at, `created` -> created_at.
     * gamemaster_id / eventleader_id verweisen auf users (nullable).
     */
    public function up(): void
    {
        Schema::create('adventures', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->unsignedInteger('loot_ep_day')->default(0);
            $table->foreignId('gamemaster_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('eventleader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('event_status_id')->default(0)->constrained('event_statuses');
            $table->foreignId('event_client_id')->default(1)->constrained('event_clients');
            $table->foreignId('event_category_id')->default(0)->constrained('event_categories');
            $table->unsignedInteger('max_player')->default(10);
            $table->unsignedInteger('waitlist')->default(0);
            $table->decimal('fee', 8, 2)->default(0);
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventures');
    }
};
