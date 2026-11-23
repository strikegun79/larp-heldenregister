<?php

namespace Database\Seeders;

use App\Models\PlotCharacter;
use Illuminate\Database\Seeder;

/**
 * Issue #34: Bekannte Persönlichkeiten Silvaniens als Plot-Charaktere.
 * Legt nur fehlende Charaktere an (Abgleich über den Slug) und überschreibt
 * keine Änderungen aus der Verwaltung.
 */
class PlotCharacterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->characters() as $character) {
            if (! PlotCharacter::where('slug', $character['slug'])->exists()) {
                PlotCharacter::forceCreate($character);
            }
        }
    }

    /**
     * Inhalte aus Issue #34 („Charakterverzeichnis – Persönlichkeiten Silvaniens“).
     * Unbekannte Angaben bleiben leer und werden nicht erfunden.
     *
     * @return list<array<string, mixed>>
     */
    private function characters(): array
    {
        return [
            [
                'slug' => 'babayaga',
                'name' => 'Babayaga',
                'character_type' => 'nsc',
                'status' => 'deceased',
                'species' => 'unknown',
                'alignment' => 'unknown',
                'description' => implode("\n\n", [
                    'Babayaga ist eine geheimnisvolle Gestalt, deren Name einen festen Platz in den Erzählungen Silvaniens verdient.',
                    'Doch was ist Wahrheit und was nur Legende? Über ihre Herkunft, ihre Kräfte und die Ereignisse, die zu ihrem Tod führten, schweigen die bislang bekannten Chroniken.',
                    'Sicher ist nur, dass Babayaga nicht mehr unter den Lebenden weilt. Welche Spuren sie in Silvanien hinterlassen hat und welche Geheimnisse mit ihrem Namen verbunden sind, bleibt vorerst im Dunkeln.',
                ]),
                'motto' => 'Manche Geschichten enden mit dem Tod ihrer Hauptfigur. Andere beginnen erst dann, wirklich interessant zu werden.',
                'public_visible' => true,
            ],
            [
                'slug' => 'koenig-goldlocke',
                'name' => 'König Goldlocke',
                'character_type' => 'nsc',
                'status' => 'active',
                'species' => 'unknown',
                'faction' => 'Königshof',
                'alignment' => 'allied',
                'first_seen' => 'Unbekannt (erste belegte Erwähnung: 26.09.2026)',
                'description' => implode("\n\n", [
                    'König Goldlocke ist ein mächtiger Herrscher, dessen Entscheidungen weit über die Mauern seines Hofes hinausreichen. Doch selbst ein König kann nicht überall zugleich sein. Deshalb vertraut er bisweilen auf mutige Abenteurer, die dort nach der Wahrheit suchen, wo königliche Wachen und Gesandte nicht weiterkommen.',
                    'Als der Verdacht aufkommt, dass das einflussreiche Haus Aldekerk in dunkle Machenschaften verwickelt sein könnte, entsendet Goldlocke einen Ermittler. Dieser soll Beweise sammeln, denn selbst ein König darf nicht ohne ausreichende Beweise über Schuld und Unschuld entscheiden.',
                    'Sechs Waldritter folgen dem königlichen Auftrag und begeben sich auf eine gefährliche Suche nach der Wahrheit.',
                    'Auch an der Akademie zu Silvanien ist König Goldlocke kein Unbekannter. Einige Waldritter wünschen sich sogar, Seine Majestät möge die Akademie eines Tages persönlich besuchen.',
                    'Ob dieser königliche Besuch jemals stattfinden wird, bleibt abzuwarten.',
                ]),
                'motto' => 'Ein König trägt eine Krone. Doch die größte Last eines Herrschers sind die Entscheidungen, die er für sein Reich treffen muss.',
                'connections' => [
                    'Königshof',
                    'Haus Aldekerk – Gegenstand königlicher Ermittlungen',
                    'Waldritter – Unterstützer bei der Suche nach Beweisen',
                ],
                'chronicle' => [
                    ['date' => '26.09.2026', 'title' => 'Auf der Spur von Haus Aldekerk', 'url' => null],
                    ['date' => '14.11.2026', 'title' => 'Ein Akademietag im Wald', 'url' => null],
                ],
                'public_visible' => true,
            ],
            [
                'slug' => 'lehrmeister-bran',
                'name' => 'Lehrmeister Bran',
                'title' => 'Bewahrer der Historie',
                'character_type' => 'nsc',
                'status' => 'active',
                'class_name' => 'Magier',
                'home' => 'Akademie zu Silvanien (Wirkungsort)',
                'species' => 'unknown',
                'faction' => 'Akademie zu Silvanien',
                'alignment' => 'allied',
                'first_seen' => 'Unbekannt (frühester Tagebucheintrag: 05.08.2018)',
                'description' => implode("\n\n", [
                    'Lehrmeister Bran zählt zweifellos zu den bedeutendsten Gelehrten Silvaniens.',
                    'Zumindest ist dies eine Feststellung, der Bran selbst ohne jede Einschränkung zustimmen würde.',
                    'Als Gründungsmitglied der Akademie zu Silvanien, Meister der Elementarmagie, Mitglied im Rat der Druiden, Vorsitzender des akademischen Rates und Bewahrer der Historie verfügt Bran über ein beeindruckendes Wissen und eine noch beeindruckendere Sammlung von Titeln.',
                    'Seine größte Aufgabe sieht er darin, die Geschichte Silvaniens zu bewahren, die Ausbildung der Novizen zu überwachen und die zahllosen Fehlentscheidungen seiner geschätzten Kollegen zu korrigieren.',
                    'Bran ist überzeugt davon, dass die Akademie ohne seine Aufsicht längst im Chaos versunken wäre. Tatsächlich berichtet er in seinen Tagebüchern regelmäßig von gefährlichen Abenteuern, verschwundenen Artefakten, fragwürdigen Entscheidungen und erstaunlich erfolgreichen Novizen.',
                    'Obwohl er sich häufig über die jungen Waldritter beschwert, verfolgt er ihre Entwicklung mit größter Aufmerksamkeit. Besonders mutige oder kluge Taten vermögen ihn gelegentlich sogar zu beeindrucken.',
                    'Selbstverständlich würde Bran dies niemals zugeben, ohne darauf hinzuweisen, dass die betreffenden Novizen vermutlich mehr Glück als Verstand hatten.',
                    'Hinter seiner überheblichen Art verbirgt sich jedoch ein aufrichtiger Wunsch: die Akademie, ihre Schüler und ganz Silvanien vor Gefahren zu bewahren.',
                ]),
                'quote' => 'Darum werde ich mich wohl wieder einmal selbst kümmern müssen!',
                'known_titles' => [
                    'Gründungsmitglied der Akademie zu Silvanien',
                    'Meister der Elementarmagie',
                    'Mitglied im Rat der Druiden Silvaniens',
                    'Vorsitzender im akademischen Rat',
                    'Bewahrer der Historie',
                ],
                'abilities' => ['Elementarmagie'],
                'connections' => [
                    'Waldritter Akademie zu Silvanien',
                    'Akademischer Rat',
                    'Rat der Druiden Silvaniens',
                    'Novizen und Lehrmeister der Akademie',
                ],
                'chronicle' => [
                    ['date' => 'Seit 05.08.2018', 'title' => 'Tagebuch des Bran', 'url' => null],
                    ['date' => null, 'title' => 'Zahlreiche weitere Chroniken und Berichte bis 2026', 'url' => null],
                ],
                'public_visible' => true,
            ],
            [
                'slug' => 'der-schwarzmagier',
                'name' => 'Der Schwarzmagier',
                'character_type' => 'nsc',
                'status' => 'banished',
                'class_name' => 'Magier',
                'species' => 'unknown',
                'alignment' => 'hostile',
                'first_seen' => 'Unbekannt (spätestens am 18.04.2026 erwähnt)',
                'missing_since' => 'Unbekannt',
                'description' => implode("\n\n", [
                    'Einst strebte ein mächtiger Schwarzmagier nach nichts Geringerem als der Herrschaft über die gesamte Welt.',
                    'Im Mittelpunkt seiner Macht stand ein geheimnisvoller dunkler Würfel, ein magisches Artefakt, dessen wahre Kräfte selbst die erfahrensten Gelehrten der Akademie vor Rätsel stellen.',
                    'Den Waldrittern gelang es, den Schwarzmagier zu bannen und ihm seine Machtquelle zu entreißen. Doch mit seinem Verschwinden endete die Bedrohung keineswegs.',
                    'Der dunkle Würfel wechselte mehrfach den Besitzer, veränderte auf unerklärliche Weise seine Gestalt und zog immer neue finstere Gestalten an.',
                    'Während eines mächtigen Rituals gelang es den Waldrittern schließlich, einige Geheimnisse des Artefakts zu entschlüsseln.',
                    'Dabei offenbarte der Würfel eine beunruhigende Wahrheit: Angeblich kann er nur von seinem eigenen Erschaffer zerstört werden.',
                    'Außerdem soll es einen zweiten, hellen Würfel geben, der seinem dunklen Gegenstück gegenübersteht.',
                    'Niemand weiß, welche Verbindung zwischen den beiden Artefakten besteht und welche Rolle der gebannte Schwarzmagier noch spielen könnte.',
                    'Denn solange seine einstige Machtquelle existiert, scheint auch sein dunkles Vermächtnis fortzubestehen.',
                ]),
                'motto' => 'Ein gebannter Feind ist nicht zwangsläufig ein besiegter Feind.',
                'abilities' => [
                    'Dunkle Magie',
                    'Verbindung zu einem mächtigen magischen Artefakt',
                ],
                'artifacts' => ['Der dunkle Würfel – einstige Machtquelle des Schwarzmagiers'],
                'connections' => [
                    'Der dunkle Würfel',
                    'Die Nekromantin – mögliche Verbindung über seine Anhänger',
                    'Schergen des Schwarzmagiers',
                    'Waldritter – Gegner des Schwarzmagiers',
                ],
                'chronicle' => [
                    ['date' => '24.08.2024', 'title' => 'Kunde aus dem Waldritterlager', 'url' => null],
                    ['date' => '18.04.2026', 'title' => 'Ein Versteck für den Würfel', 'url' => null],
                    ['date' => '30.05.2026', 'title' => 'Das Ritual um den Würfel des Schwarzmagiers', 'url' => null],
                ],
                'public_lore' => 'Der Schwarzmagier wurde laut Chronik gebannt. Sein dunkler Würfel ist ein gefährliches Artefakt. Sein gegenwärtiger Aufenthaltsort ist unbekannt.',
                'public_visible' => true,
            ],
            [
                'slug' => 'die-nekromantin',
                'name' => 'Die Nekromantin',
                'character_type' => 'nsc',
                'status' => 'unknown',
                'class_name' => 'Magier',
                'species' => 'unknown',
                'alignment' => 'unknown',
                'first_seen' => 'Unbekannt (spätestens am 18.04.2026 erwähnt)',
                'description' => implode("\n\n", [
                    'Eine geheimnisvolle Frau, deren Name in den Berichten der Waldritter immer wieder im Zusammenhang mit dunkler Magie, unheimlichen Kreaturen und dem verschwundenen Würfel des Schwarzmagiers auftaucht.',
                    'Über ihre Herkunft ist nur wenig bekannt. Auch ihre wahren Absichten liegen bislang im Dunkeln.',
                    'Es wird vermutet, dass sie mit den Anhängern des Schwarzmagiers in Verbindung steht und selbst nach dem mächtigen Artefakt sucht.',
                    'Einer ihrer mutmaßlichen Verbündeten gelang es, das Vertrauen der Waldritter zu erschleichen. Er gab sich als Experte für dunkle Künste aus und brachte den Würfel zeitweise in seinen Besitz.',
                    'Bei einem späteren Ritual wurden die Waldritter von Untoten angegriffen. Augenzeugen berichteten von einer geheimnisvollen Gestalt mit schwarz unterlaufenen Augen, die das Geschehen beobachtet haben soll.',
                    'Einige vermuteten, dass es sich dabei um die Nekromantin handelte.',
                    'Als der dunkle Würfel schließlich erneut verloren ging, richtete sich der Verdacht abermals auf sie.',
                    'Doch welche Ziele verfolgt die geheimnisvolle Magierin wirklich? Dient sie dem Vermächtnis des Schwarzmagiers oder schmiedet sie längst ihre eigenen Pläne?',
                    'Solange diese Fragen unbeantwortet bleiben, müssen die Waldritter auf der Hut sein.',
                ]),
                'motto' => 'Manche Feinde führen ihre Armeen mit Schwertern. Andere benötigen dafür nur die Toten.',
                'abilities' => [
                    'Vermutete Nekromantie',
                    'Mögliche Verbindung zur Beschwörung oder Kontrolle Untoter',
                ],
                'connections' => [
                    'Der Schwarzmagier – mögliche Verbindung',
                    'Der dunkle Würfel – mutmaßliches Ziel ihrer Suche',
                    'Untote – mögliche Verbindung zu den Angriffen',
                    'Waldritter – mögliche Gegner',
                ],
                'chronicle' => [
                    ['date' => '18.04.2026', 'title' => 'Ein Versteck für den Würfel', 'url' => null],
                    ['date' => '30.05.2026', 'title' => 'Das Ritual um den Würfel des Schwarzmagiers', 'url' => null],
                ],
                'public_visible' => true,
            ],
        ];
    }
}
