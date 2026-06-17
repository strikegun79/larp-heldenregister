<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optionale Kinder-Anschrift (PLAY-13 / ORGA-01).
     * Standardmäßig gilt die Anschrift der erziehungsberechtigten Person.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->boolean('address_same_as_guardian')->default(true)->after('active');
            $table->string('street', 100)->nullable()->after('address_same_as_guardian');
            $table->string('house_number', 10)->nullable()->after('street');
            $table->string('zip', 10)->nullable()->after('house_number');
            $table->string('city', 100)->nullable()->after('zip');
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['address_same_as_guardian', 'street', 'house_number', 'zip', 'city']);
        });
    }
};
