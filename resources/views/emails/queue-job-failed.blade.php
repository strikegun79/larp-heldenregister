<x-mail::message>
# Queue-Job fehlgeschlagen

Ein Hintergrund-Job ist auf dem Server fehlgeschlagen und wurde in der `failed_jobs`-Tabelle gespeichert.

| Feld | Wert |
|---|---|
| **Job** | `{{ $jobName }}` |
| **Queue** | `{{ $queueName }}` |
| **Zeitpunkt** | {{ $failedAt }} |

**Fehlermeldung:**

> {{ $exceptionMessage }}

Bitte prüfe die `failed_jobs`-Tabelle sowie die Logdatei unter `storage/logs/` für weitere Details.

```
php artisan queue:failed
php artisan queue:retry all
```

<x-mail::button :url="config('app.url')">
Zum Heldenregister
</x-mail::button>

{{ config('app.name') }} – Automatische Systembenachrichtigung
</x-mail::message>
