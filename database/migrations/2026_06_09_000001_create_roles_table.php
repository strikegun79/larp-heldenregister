<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ersetzt die Legacy-Tabelle `type_role`. Die IDs werden bewusst
     * aus dem Altsystem übernommen (10 = Admin, 20 = Registrar, ...),
     * damit die ETL-Migration der Zuordnungen (user2role) 1:1 funktioniert.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            // Maschinen-Schlüssel für Code/Gates (z.B. 'admin')
            $table->string('slug', 50)->unique();
            // Deutsche Bezeichnung aus dem Legacy-System (type_role.description)
            $table->string('label', 50);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
    }
};
