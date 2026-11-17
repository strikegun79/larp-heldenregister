<?php

namespace Database\Seeders;

use App\Models\EventClient;
use App\Models\EventClientRequirement;
use Illuminate\Database\Seeder;

/**
 * Beispiel-Anforderung (Issue #14): Einverständniserklärung der Jugendförderung
 * Landkreis Gießen. Texte formatiert aus der Papier-Vorlage übernommen; eine PDF
 * kann optional in der Verwaltung angehängt werden.
 * Bewusst nicht im DatabaseSeeder, da eine aktive Anforderung sofort Pflicht
 * bei allen Anmeldungen zu Events des Auftraggebers wird.
 * Aufruf: php artisan db:seed --class=EventClientRequirementSeeder
 */
class EventClientRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $client = EventClient::where('name', 'like', 'Jugendförderung%')->first();
        if (! $client) {
            $this->command?->warn('Auftraggeber „Jugendförderung …“ nicht gefunden – nichts angelegt.');

            return;
        }

        EventClientRequirement::firstOrCreate(
            ['event_client_id' => $client->id, 'title' => 'Einverständniserklärung und Hinweise zur Verkehrssicherheit im Wald'],
            [
                'description' => $this->description(),
                'active' => true,
                'sort_order' => 0,
            ]
        );
    }

    /** Formatierter Text, gegliedert wie die Papier-Vorlage. */
    private function description(): string
    {
        return <<<'HTML'
            <p><strong>Ohne diese Einverständniserklärung ist eine Teilnahme nicht möglich.</strong></p>
            <p>Ich habe die Hinweise zur Verkehrssicherheit im Wald verstanden und zur Kenntnis genommen. Die Betretung von Waldflächen durch mein/e Kind/er geschieht auf eigenes Risiko.</p>
            <p><span style="font-size: 18px;"><strong>Hinweise zur Verkehrssicherheit im Wald</strong></span></p>
            <p>Aufgrund des Klimawandels sind unsere Wälder von Trockenheit betroffen. Die Vitalität vieler Bäume ist hiervon stark beeinträchtigt. Tote Bäume oder abgestorbene Äste stellen für alle Menschen eine Gefährdung dar, die sich im Wald bewegen. Durch umgefallene Bäume und die Gefahr von Astbruch muss die Verkehrssicherheit im Wald von Seiten der zuständigen Forstämter bzw. Waldbesitzer immer wieder geprüft und sichergestellt werden.</p>
            <p>Die Betretung von Waldflächen rund um unsere LARP-Spielorte lassen wir uns von den zuständigen Kommunen gestatten. Teil des Gestattungsprozesses ist eine Freigabe der Waldflächen durch das zuständige Forstamt bzw. den zuständigen Forstbetrieb. Wir versuchen so das Risiko einer Gefährdung der Teilnehmenden an unseren Live-Rollenspielen, soweit das möglich ist, zu minimieren.</p>
            <p><strong>Wir weisen ausdrücklich darauf hin, dass mit waldtypischen Gefährdungen (z. B. herabfallende Äste, umfallende Bäume, Windbruch, Schneebruch, Waldbrandgefahr) zu rechnen ist und immer Restrisiken beim Betreten von Wäldern bestehen. Die Betretung des Waldes Ihrer Kinder erfolgt also ausdrücklich mit Ihrer Einwilligung und auf eigenes Risiko. Etwaige Haftungen aufgrund von Unfällen teilnehmender Kinder durch waldtypische Gefährdungen tragen die Eltern. Die Alternative wäre, dass pädagogische Angebote im Wald nicht mehr stattfinden könnten. Wir bitten daher um Ihr Verständnis und Ihre Einwilligung.</strong></p>
            <p>Der Waldritter-Gießen e. V. ist über die gestatteten Flächen in Kenntnis gesetzt. Alle Teamer*innen sind über waldtypische Gefährdungen informiert und untersagen die Betretung von nicht gestatteten Waldflächen. Als durchführender Verein ist der Waldritter-Gießen e. V. vor Ort für die Einhaltung dieser Vorgabe seitens des Veranstalters verantwortlich.</p>
            <p>Für Rückfragen zu diesem Thema stehen wir Ihnen gerne zur Verfügung.</p>
            <p><em>Das Einverständnis zu Bild- und Tonaufnahmen wird separat über die Fotoerlaubnis im Anmeldeformular abgefragt.</em></p>
            HTML;
    }
}
