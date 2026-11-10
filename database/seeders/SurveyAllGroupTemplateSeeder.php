<?php

namespace Database\Seeders;

use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Einheitliche Feedback-Vorlage für alle Teilnehmergruppen (Kinder & Jugendliche).
 * Geeignet für Jugendförderungs-Dokumentation: deckt Sicherheit, soziale Teilhabe
 * und persönliche Entwicklung ab. Fragen funktionieren für 8–17 Jahre.
 */
class SurveyAllGroupTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent: nur anlegen wenn noch keine 'all'-Vorlage mit diesem Namen existiert
        if (SurveyTemplate::where('target_group', 'all')->where('name', 'Teilnehmer-Feedback (Alle Gruppen)')->exists()) {
            $this->command->info('Vorlage "Alle Gruppen" bereits vorhanden, übersprungen.');
            return;
        }

        $adminId = User::first()?->id ?? 1;

        $template = SurveyTemplate::create([
            'name'         => 'Teilnehmer-Feedback (Alle Gruppen)',
            'description'  => 'Einheitlicher Feedback-Bogen für Kinder (8–12) und Jugendliche (13–17). '
                            . 'Kinder erhalten den Wizard (eine Frage pro Schritt), Jugendliche ebenfalls. '
                            . 'Geeignet zur Jugendförderungs-Dokumentation: Sicherheit, soziale Teilhabe, persönliche Entwicklung.',
            'target_group' => 'all',
            'active'       => true,
            'created_by'   => $adminId,
        ]);

        $questions = [
            // ----------------------------------------------------------------
            // Gesamteindruck – erster Eindruck, einstimmend
            // ----------------------------------------------------------------
            [
                'question_text' => 'Wie hat dir das Abenteuer insgesamt gefallen?',
                'type'          => 'rating',
                'required'      => true,
                'sort_order'    => 0,
            ],

            // ----------------------------------------------------------------
            // Sicherheit – Pflichtfeld für Jugendschutz-Dokumentation
            // ----------------------------------------------------------------
            [
                'question_text' => 'Hast du dich die ganze Zeit sicher gefühlt?',
                'type'          => 'yes_no',
                'required'      => true,
                'sort_order'    => 1,
            ],

            // ----------------------------------------------------------------
            // Geschichte & Spielqualität
            // ----------------------------------------------------------------
            [
                'question_text' => 'Wie gut hat dir die Geschichte und das Abenteuer gefallen?',
                'type'          => 'rating',
                'required'      => false,
                'sort_order'    => 2,
            ],

            // ----------------------------------------------------------------
            // Betreuung durch Teamer – Qualität der pädagogischen Begleitung
            // ----------------------------------------------------------------
            [
                'question_text' => 'Wie gut haben sich die Teamer um euch gekümmert?',
                'type'          => 'rating',
                'required'      => false,
                'sort_order'    => 3,
            ],

            // ----------------------------------------------------------------
            // Partizipation – Jugendförderung: Mitbestimmung & Teilhabe
            // ----------------------------------------------------------------
            [
                'question_text' => 'Konntest du gut mitspielen und eigene Ideen einbringen?',
                'type'          => 'yes_no',
                'required'      => false,
                'sort_order'    => 4,
            ],

            // ----------------------------------------------------------------
            // Soziale Teilhabe – Jugendförderung: Gemeinschaft & Freundschaft
            // ----------------------------------------------------------------
            [
                'question_text' => 'Hast du neue Leute kennengelernt oder Freundschaften vertieft?',
                'type'          => 'yes_no',
                'required'      => false,
                'sort_order'    => 5,
            ],

            // ----------------------------------------------------------------
            // Persönliche Entwicklung – Jugendförderung: Mut & Neues ausprobieren
            // ----------------------------------------------------------------
            [
                'question_text' => 'Hast du beim Abenteuer etwas Neues ausprobiert oder dich in etwas getraut?',
                'type'          => 'yes_no',
                'required'      => false,
                'sort_order'    => 6,
            ],

            // ----------------------------------------------------------------
            // Atmosphäre & Gemeinschaft
            // ----------------------------------------------------------------
            [
                'question_text' => 'Wie war die Stimmung und das Miteinander beim Event?',
                'type'          => 'rating',
                'required'      => false,
                'sort_order'    => 7,
            ],

            // ----------------------------------------------------------------
            // Highlight – offene Reflexion, wichtig für Qualitätsdokumentation
            // ----------------------------------------------------------------
            [
                'question_text' => 'Was war dein schönster oder aufregendster Moment beim Abenteuer?',
                'type'          => 'text',
                'required'      => false,
                'sort_order'    => 8,
            ],

            // ----------------------------------------------------------------
            // Verbesserungsvorschläge – konstruktives Feedback
            // ----------------------------------------------------------------
            [
                'question_text' => 'Was würdest du dir beim nächsten Mal wünschen oder anders haben wollen?',
                'type'          => 'text',
                'required'      => false,
                'sort_order'    => 9,
            ],

            // ----------------------------------------------------------------
            // Wiederkommen – Net Promoter / Bindung
            // ----------------------------------------------------------------
            [
                'question_text' => 'Würdest du gerne wieder mitmachen?',
                'type'          => 'yes_no',
                'required'      => true,
                'sort_order'    => 10,
            ],
        ];

        foreach ($questions as $q) {
            SurveyTemplateQuestion::create([
                'survey_template_id' => $template->id,
                'question_text'      => $q['question_text'],
                'type'               => $q['type'],
                'sort_order'         => $q['sort_order'],
                'required'           => $q['required'],
            ]);
        }

        $this->command->info("Vorlage erstellt: {$template->name} ({$template->questions()->count()} Fragen)");
    }
}
