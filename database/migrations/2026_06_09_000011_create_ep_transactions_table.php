<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * EP-Transaktionsbuch eines Helden. Ersetzt `hero2ep`.
     * `ep_count` ist stets positiv; das Vorzeichen ergibt sich aus
     * ep_transaction_types.is_credit. `date_trans` -> transacted_at.
     */
    public function up(): void
    {
        Schema::create('ep_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hero_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ep_transaction_type_id')->constrained('ep_transaction_types');
            $table->decimal('ep_count', 8, 2)->default(0);
            $table->timestamp('transacted_at')->nullable();
            $table->unsignedBigInteger('legacy_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ep_transactions');
    }
};
