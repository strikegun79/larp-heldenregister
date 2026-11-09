<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anschrift der erziehungsberechtigten Person (AUTH-10 / ORGA-01).
     * Pflichtdaten vor der Eventanmeldung, im Profil optional befüllbar.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('street', 100)->nullable()->after('phone');
            $table->string('house_number', 10)->nullable()->after('street');
            $table->string('zip', 10)->nullable()->after('house_number');
            $table->string('city', 100)->nullable()->after('zip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['street', 'house_number', 'zip', 'city']);
        });
    }
};
