<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [

    'backup' => [
        'name' => env('APP_NAME', 'Heldenregister'),

        'source' => [
            'files' => [
                // Nur Nutzer-Uploads sichern – Code liegt in Git.
                'include' => [
                    storage_path('app'),
                ],

                'exclude' => [
                    storage_path('app/backups'),
                    storage_path('app/backup-temp'),
                ],

                'follow_links' => false,
                'ignore_unreadable_directories' => true,
                'relative_path' => null,
            ],

            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        // Gzip-Komprimierung des SQL-Dumps vor dem Einpacken in ZIP.
        'database_dump_compressor' => \Spatie\DbDumper\Compressors\GzipCompressor::class,
        'database_dump_file_timestamp_format' => null,
        'database_dump_filename_base' => 'database',
        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,
            'compression_level' => 6,
            'filename_prefix' => '',
            'disks' => [
                'backup',
            ],
            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        // Optionale ZIP-Verschlüsselung: BACKUP_ARCHIVE_PASSWORD in .env setzen.
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'encryption' => 'default',

        'verify_backup' => true,
        'tries' => 2,
        'retry_delay' => 60,
    ],

    // Nur Fehler-Benachrichtigungen – kein täglicher Erfolgs-Spam.
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class    => ['mail'],
            UnhealthyBackupWasFoundNotification::class => ['mail'],
            CleanupHasFailedNotification::class   => ['mail'],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => env('BACKUP_MAIL_TO', env('MAIL_FROM_ADDRESS', 'admin@example.de')),

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'heldenregister@example.de'),
                'name'    => env('MAIL_FROM_NAME', 'Heldenregister'),
            ],
        ],

        'slack'   => ['webhook_url' => '', 'channel' => null, 'username' => null, 'icon' => null],
        'discord' => ['webhook_url' => '', 'username' => '', 'avatar_url' => ''],
        'webhook' => ['url' => ''],
    ],

    'log_channel' => null,

    'monitor_backups' => [
        [
            'name'  => env('APP_NAME', 'Heldenregister'),
            'disks' => ['backup'],
            'health_checks' => [
                MaximumAgeInDays::class          => 2,
                MaximumStorageInMegabytes::class => 2000,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days'                         => 7,
            'keep_daily_backups_for_days'                       => 30,
            'keep_weekly_backups_for_weeks'                     => 8,
            'keep_monthly_backups_for_months'                   => 6,
            'keep_yearly_backups_for_years'                     => 2,
            'delete_oldest_backups_when_using_more_megabytes_than' => 2000,
        ],

        'tries'        => 1,
        'retry_delay'  => 0,
    ],

];
