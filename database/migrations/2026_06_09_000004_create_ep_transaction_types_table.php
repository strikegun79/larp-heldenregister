<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lookup der EP-Buchungsarten. Ersetzt die Legacy-Tabelle `type_transEP`.
     * IDs werden übernommen (10 = Initiale EP ... 70 = Allgemein/Kosten),
     * da hero2ep numerisch darauf verweist.
     *
     * `is_credit` ersetzt das Legacy-Feld `type` ("EP erworben" / "EP Kosten")
     * und bestimmt das Vorzeichen bei der EP-Saldoberechnung.
     */
    public function up(): void
    {
        Schema::create('ep_transaction_types', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('description', 50);
            $table->boolean('is_credit');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ep_transaction_types');
    }
};
