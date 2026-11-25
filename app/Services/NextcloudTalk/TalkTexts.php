<?php

namespace App\Services\NextcloudTalk;

use App\Models\Setting;

/**
 * Issue #31 (Phase 3): Texte, denen die Eltern vor der Talk-Nutzung zustimmen.
 * In der Verwaltung editierbar; ohne gespeicherten Text gilt der Entwurf unten.
 */
class TalkTexts
{
    /** Einstellungs-Schlüssel → Überschrift. */
    public const SECTIONS = [
        'talk_terms_text' => 'Teilnahmebedingungen',
        'talk_safety_text' => 'Sicherheitshinweise',
        'talk_rules_text' => 'Verhaltensregeln',
    ];

    /** Entwurf – vor dem Start vom Verein anzupassen. */
    public const DEFAULTS = [
        'talk_terms_text' => "Nextcloud Talk ist der Chat der Waldritter für Absprachen rund um unsere Veranstaltungen.\n"
            ."Die Teilnahme ist freiwillig und ab 14 Jahren möglich. Für Minderjährige ist die Erlaubnis eines Elternteils nötig.\n"
            ."Das Konto wird vom Verein angelegt und kann jederzeit von den Eltern oder vom Verein beendet werden.\n"
            .'Im Chat gespeichert werden nur Name, E-Mail-Adresse und die geschriebenen Nachrichten.',
        'talk_safety_text' => "Gib keine Adresse, Telefonnummer oder Passwörter im Chat weiter.\n"
            ."Triff dich nicht allein mit Personen, die du nur aus dem Chat kennst.\n"
            ."Wenn dir etwas komisch vorkommt oder dich jemand bedrängt: Sag es deinen Eltern oder einer Betreuungsperson der Waldritter.\n"
            .'Betreuungspersonen schreiben mit Kindern und Jugendlichen nur in Gruppen, nicht in privaten Einzelchats.',
        'talk_rules_text' => "Wir gehen freundlich und respektvoll miteinander um – wie am Lagerfeuer.\n"
            ."Keine Beleidigungen, kein Mobbing, keine Gewalt- oder Erwachseneninhalte.\n"
            ."Fotos von anderen nur mit deren Erlaubnis teilen.\n"
            .'Wer sich nicht an die Regeln hält, kann vom Chat ausgeschlossen werden.',
    ];

    /**
     * Aktuelle Texte (gespeichert oder Entwurf).
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $texts = [];
        foreach (array_keys(self::SECTIONS) as $key) {
            $value = Setting::get($key);
            $texts[$key] = filled($value) ? (string) $value : self::DEFAULTS[$key];
        }

        return $texts;
    }

    /** Prüfsumme der aktuellen Fassung – wird mit der Elternerlaubnis gespeichert. */
    public static function version(): string
    {
        return hash('sha256', implode("\n\u{1F}\n", self::all()));
    }
}
