<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Helden (Charaktere). Ersetzt die Legacy-Tabelle `hero`.
     * Ergänzt zudem players.active_hero_id (Legacy: player.hero_active),
     * was wegen der zirkulären FK erst hier nach den heroes erfolgen kann.
     */
    public function up(): void
    {
        Schema::create('heroes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('character_name', 150)->nullable();
            $table->date('born')->nullable();
            $table->date('died')->nullable();
            $table->string('homeplace', 150)->nullable();
            $table->boolean('active')->default(false);
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::table('players', function (Blueprint $table) {
            $table->foreignId('active_hero_id')->nullable()->after('active')
                ->constrained('heroes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_hero_id');
        });

        Schema::dropIfExists('heroes');
    }
};
