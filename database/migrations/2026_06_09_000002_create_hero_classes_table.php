<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lookup der Heldenklassen. Ersetzt die Legacy-Tabelle `type_classes`.
     * IDs werden aus dem Altsystem übernommen (1 = Krieger ... 5 = Alchemist),
     * da `skills.masterclass` und die Pivots numerisch darauf verweisen.
     */
    public function up(): void
    {
        Schema::create('hero_classes', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            // Maschinen-Schlüssel (Legacy: type_classes.idname, z.B. 'warrior').
            // hero2classes referenziert genau diesen Wert.
            $table->string('slug', 50)->unique();
            // Deutsche Bezeichnung (Legacy: type_classes.name).
            $table->string('name', 50);
            $table->boolean('disabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_classes');
    }
};
