<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_template_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_template_id')->constrained()->cascadeOnDelete();
            $table->text('question_text');
            // rating = 1–10 Zahlen-Buttons oder Schildskala; text = Freitext; yes_no = Ja/Nein
            $table->enum('type', ['rating', 'text', 'yes_no']);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('required')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_template_questions');
    }
};
