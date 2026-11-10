<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Startseiten-Nachrichten (MOTD) mit Verlauf: eigene Tabelle statt
 * motd_*-Einträgen in settings. Bestehende Nachricht wird übernommen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motds', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('html')->nullable();
            $table->string('image')->nullable();
            $table->string('image_link', 500)->nullable();
            $table->string('variant', 20)->default('neutral');
            $table->boolean('dismissible')->default(false);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(false)->index();
            // Zeitpunkt der letzten Inhaltsänderung (Text/Bild) – Basis für „wegklicken“.
            $table->timestamp('content_updated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Bisherige Nachricht aus settings übernehmen.
        $old = DB::table('settings')->where('key', 'like', 'motd\_%')->pluck('value', 'key');
        if (filled($old['motd_html'] ?? null) || filled($old['motd_image'] ?? null)) {
            $now = now();
            DB::table('motds')->insert([
                'title' => 'Startseiten-Nachricht',
                'html' => $old['motd_html'] ?? null,
                'image' => $old['motd_image'] ?? null,
                'image_link' => $old['motd_image_link'] ?? null,
                'variant' => $old['motd_variant'] ?? 'neutral',
                'dismissible' => (bool) ($old['motd_dismissible'] ?? false),
                'start_at' => $old['motd_start_at'] ?? null,
                'end_at' => $old['motd_end_at'] ?? null,
                'is_active' => (bool) ($old['motd_enabled'] ?? false),
                'content_updated_at' => isset($old['motd_version']) && (int) $old['motd_version'] > 0
                    ? date('Y-m-d H:i:s', (int) $old['motd_version'])
                    : $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('settings')->where('key', 'like', 'motd\_%')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('motds');
    }
};
