<?php

namespace Database\Seeders;

use App\Models\SurveyTemplate;
use App\Models\SurveyTemplateQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Grundgerüst-Fragenvorlagen für das Umfrage-System (SURV-01).
 * Empfehlungen aus child-experience-reviewer und ui-ux-reviewer eingearbeitet.
 */
class SurveyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder ist idempotent – bei erneutem Aufruf nichts doppelt anlegen
        if (SurveyTemplate::count() > 0) {
            $this->command->info('Survey-Vorlagen bereits vorhanden, übersprungen.');
            return;
        }

        $adminId = User::first()?->id ?? 1;

        $templates = [

            // ----------------------------------------------------------------
            // Teilnehmer – Kinder (8–12), Schildskala (5-stufig)
            // ----------------------------------------------------------------
            [
                'name'         => 'Teilnehmer-Feedback (Kinder)',
                'description'  => 'Für Spieler im Alter von 8–12 Jahren. Verwendet die Schildskala (5 Stufen).',
                'target_group' => 'participant_child',
                'questions'    => [
                    ['question_text' => 'Wie hat dir das Abenteuer gefallen?',                                 'type' => 'rating', 'required' => true],
                    ['question_text' => 'Was hat dir am meisten Spaß gemacht?',                                'type' => 'text',   'required' => false],
                    ['question_text' => 'Was fandst du nicht so toll?',                                        'type' => 'text',   'required' => false],
                    ['question_text' => 'Hast du dich wohl und sicher gefühlt?',                               'type' => 'rating', 'required' => true],
                    ['question_text' => 'Hast du neue Freunde getroffen?',                                     'type' => 'yes_no', 'required' => false],
                    ['question_text' => 'Hast du beim Abenteuer etwas Neues gelernt oder dich getraut?',      'type' => 'yes_no', 'required' => false],
                    ['question_text' => 'Konntest du zu jemandem gehen, wenn etwas nicht stimmte?',           'type' => 'yes_no', 'required' => false],
                    ['question_text' => 'Würdest du gerne wieder kommen?',                                     'type' => 'rating', 'required' => true],
                ],
            ],

            // ----------------------------------------------------------------
            // Teilnehmer – Jugendliche (13–17), 1–10 Zahlen-Buttons
            // ----------------------------------------------------------------
            [
                'name'         => 'Teilnehmer-Feedback (Jugendliche)',
                'description'  => 'Für Spieler im Alter von 13–17 Jahren. Verwendet 1–10 Zahlen-Buttons.',
                'target_group' => 'participant_teen',
                'questions'    => [
                    ['question_text' => 'Wie hat dir das Abenteuer insgesamt gefallen? (1 = gar nicht, 10 = super)',        'type' => 'rating', 'required' => true],
                    ['question_text' => 'Was war dein persönliches Highlight?',                                              'type' => 'text',   'required' => false],
                    ['question_text' => 'Was sollten wir beim nächsten Mal verbessern?',                                     'type' => 'text',   'required' => false],
                    ['question_text' => 'Hast du dich sicher und gut aufgehoben gefühlt? (1 = nein, 10 = absolut)',         'type' => 'rating', 'required' => true],
                    ['question_text' => 'Konntest du beim Abenteuer mitbestimmen und mitmachen?',                           'type' => 'yes_no', 'required' => false],
                    ['question_text' => 'Hast du etwas Neues gelernt oder dich in etwas getraut?',                         'type' => 'yes_no', 'required' => false],
                    ['question_text' => 'Atmosphäre und Stimmung im Team: (1 = schlecht, 10 = top)',                        'type' => 'rating', 'required' => false],
                    ['question_text' => 'Würdest du wieder teilnehmen? (1 = nein, 10 = auf jeden Fall)',                    'type' => 'rating', 'required' => true],
                ],
            ],

            // ----------------------------------------------------------------
            // Teamer
            // ----------------------------------------------------------------
            [
                'name'         => 'Teamer-Feedback',
                'description'  => 'Für Teamer und Mitarbeiter. Verwendet 1–10 Zahlen-Buttons auf einer Scroll-Seite.',
                'target_group' => 'teamer',
                'questions'    => [
                    ['question_text' => 'Wie bewertest du die Organisation der Veranstaltung? (1–10)',          'type' => 'rating', 'required' => true],
                    ['question_text' => 'Wie zufrieden warst du mit der Unterstützung durch die Leitung? (1–10)', 'type' => 'rating', 'required' => true],
                    ['question_text' => 'Wie war die Kommunikation im Team? (1–10)',                             'type' => 'rating', 'required' => false],
                    ['question_text' => 'Was lief besonders gut?',                                               'type' => 'text',   'required' => false],
                    ['question_text' => 'Was sollten wir beim nächsten Mal verbessern?',                         'type' => 'text',   'required' => false],
                    ['question_text' => 'Fühltest du dich im Team gut aufgenommen und unterstützt?',             'type' => 'yes_no', 'required' => false],
                    ['question_text' => 'Würdest du wieder als Teamer dabei sein?',                              'type' => 'yes_no', 'required' => true],
                    ['question_text' => 'Weitere Anmerkungen oder Wünsche:',                                     'type' => 'text',   'required' => false],
                ],
            ],

            // ----------------------------------------------------------------
            // Eltern / Erziehungsberechtigte
            // ----------------------------------------------------------------
            [
                'name'         => 'Eltern-Feedback',
                'description'  => 'Für Eltern/Erziehungsberechtigte. Scroll-Seite mit 1–10 Zahlen-Buttons.',
                'target_group' => 'parent',
                'questions'    => [
                    ['question_text' => 'War Ihr Kind mit der Veranstaltung zufrieden? (1 = gar nicht, 10 = sehr)',      'type' => 'rating', 'required' => true],
                    ['question_text' => 'Fühlte sich Ihr Kind sicher und gut betreut? (1 = nein, 10 = absolut)',          'type' => 'rating', 'required' => true],
                    ['question_text' => 'Wie bewerten Sie die Kommunikation mit den Veranstaltern? (1–10)',                'type' => 'rating', 'required' => false],
                    ['question_text' => 'Wurde Ihr Kind altersgerecht begleitet? (z. B. passende Aufgaben, verständliche Erklärungen)', 'type' => 'rating', 'required' => false],
                    ['question_text' => 'Gab es kritische Vorfälle oder Sicherheitsbedenken?',                            'type' => 'yes_no', 'required' => false],
                    ['question_text' => 'Gibt es Anmerkungen, Wünsche oder Hinweise zu Vorfällen?',                      'type' => 'text',   'required' => false],
                    ['question_text' => 'Würden Sie Ihr Kind wieder anmelden?',                                           'type' => 'yes_no', 'required' => true],
                ],
            ],

        ];

        foreach ($templates as $templateData) {
            $questionData = $templateData['questions'];
            unset($templateData['questions']);

            $template = SurveyTemplate::create([
                ...$templateData,
                'active'     => true,
                'created_by' => $adminId,
            ]);

            foreach ($questionData as $index => $q) {
                SurveyTemplateQuestion::create([
                    'survey_template_id' => $template->id,
                    'question_text'      => $q['question_text'],
                    'type'               => $q['type'],
                    'sort_order'         => $index,
                    'required'           => $q['required'],
                ]);
            }

            $this->command->info("  Vorlage erstellt: {$template->name} ({$template->questions()->count()} Fragen)");
        }
    }
}
