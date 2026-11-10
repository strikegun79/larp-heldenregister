<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_template_question_id')->constrained()->restrictOnDelete();
            // Genau eines der beiden Felder wird befüllt je nach Fragetyp
            $table->unsignedTinyInteger('rating_answer')->nullable(); // 1–10
            $table->text('text_answer')->nullable();                   // Freitext
            $table->boolean('yes_no_answer')->nullable();              // Ja/Nein
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
