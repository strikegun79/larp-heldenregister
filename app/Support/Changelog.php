<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Liest den Versionsverlauf aus CHANGELOG.md und stellt ihn als HTML sowie
 * die aktuelle Versionsnummer bereit. Das Ergebnis wird gecacht, bis sich
 * die Datei ändert (Cache-Schlüssel enthält den Änderungszeitpunkt).
 */
class Changelog
{
    public function __construct(private readonly ?string $path = null) {}

    /**
     * Pfad zur Changelog-Datei.
     */
    public function path(): string
    {
        return $this->path ?? base_path('CHANGELOG.md');
    }

    /**
     * Aktuelle Version aus der ersten Überschrift „## [x.y.z]“, sonst null.
     */
    public function currentVersion(): ?string
    {
        return $this->parsed()['version'];
    }

    /**
     * Versionsverlauf als HTML. Die Haupt-Überschrift der Datei entfällt,
     * da die Seite eine eigene Überschrift hat.
     */
    public function html(): string
    {
        return $this->parsed()['html'];
    }

    /**
     * @return array{version: ?string, html: string}
     */
    private function parsed(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return ['version' => null, 'html' => ''];
        }

        $key = 'changelog:'.md5($path).':'.filemtime($path);

        return Cache::rememberForever($key, function () use ($path) {
            $markdown = (string) file_get_contents($path);

            preg_match('/^##\s*\[([^\]]+)\]/m', $markdown, $match);

            // Haupt-Überschrift („# Versionsverlauf“) entfernen
            $markdown = preg_replace('/\A\s*#\s[^\n]*\n/', '', $markdown);

            return [
                'version' => $match[1] ?? null,
                // Changelog stammt aus dem Repository; HTML trotzdem entschärfen
                'html' => Str::markdown($markdown, [
                    'html_input' => 'escape',
                    'allow_unsafe_links' => false,
                ]),
            ];
        });
    }
}
