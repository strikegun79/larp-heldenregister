<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #34: Erweiterter Steckbrief für Plot-Charaktere (Kopfdaten, Listen,
 * Chronik, Plothintergrund mit öffentlichem und geheimem Teil).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plot_characters', function (Blueprint $table) {
            $table->string('character_type', 10)->default('nsc')->after('title');
            $table->string('status', 20)->default('unknown')->after('character_type');
            $table->string('class_name', 100)->nullable()->after('status');
            $table->string('home', 150)->nullable()->after('class_name');
            $table->string('species', 20)->default('unknown')->after('home');
            $table->string('faction', 150)->nullable()->after('species');
            $table->string('alignment', 20)->default('unknown')->after('faction');
            $table->string('location', 150)->nullable()->after('alignment');
            $table->string('first_seen', 150)->nullable()->after('location');
            $table->string('missing_since', 150)->nullable()->after('first_seen');
            $table->string('last_seen', 150)->nullable()->after('missing_since');

            $table->json('known_titles')->nullable()->after('description');
            $table->json('abilities')->nullable()->after('known_titles');
            $table->json('artifacts')->nullable()->after('abilities');
            $table->json('connections')->nullable()->after('artifacts');
            $table->json('chronicle')->nullable()->after('connections');

            $table->string('quote', 500)->nullable()->after('chronicle');
            $table->string('motto', 500)->nullable()->after('quote');
            $table->text('public_lore')->nullable()->after('motto');
            $table->text('gm_notes')->nullable()->after('public_lore');
        });
    }

    public function down(): void
    {
        Schema::table('plot_characters', function (Blueprint $table) {
            $table->dropColumn([
                'character_type', 'status', 'class_name', 'home', 'species', 'faction',
                'alignment', 'location', 'first_seen', 'missing_since', 'last_seen',
                'known_titles', 'abilities', 'artifacts', 'connections', 'chronicle',
                'quote', 'motto', 'public_lore', 'gm_notes',
            ]);
        });
    }
};
