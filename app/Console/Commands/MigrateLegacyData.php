<?php

namespace App\Console\Commands;

use Database\Seeders\EpTransactionTypeSeeder;
use Database\Seeders\EventLookupSeeder;
use Database\Seeders\HeroClassSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\PerlColorSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MigrateLegacyData extends Command
{
    protected $signature = 'migrate:legacy {--fresh : DB vorher migrate:fresh + Lookups seeden}';

    protected $description = 'Migriert die Daten aus der Legacy-DB (larp_buerokrat) ins neue Laravel-Schema';

    /** Anzahl Passwörter, die nicht im bcrypt-Format vorliegen (Reset nötig). */
    private int $nonBcryptPasswords = 0;

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->call('migrate:fresh');
        }

        $this->info('Stelle sicher, dass die Lookups vorhanden sind ...');
        $this->seedLookups();

        $this->info('Starte Daten-Migration aus der Legacy-DB ...');

        DB::transaction(function () {
            $this->migrateUsers();
            $this->migratePlayers();
            $this->migrateUserPlayer();
            $this->migrateUserRoles();
            $this->migrateSkills();
            $this->migrateSkillClasses();
            $this->migrateHeroes();
            $this->migratePlayerActiveHero();
            $this->migrateHeroClasses();
            $this->migrateHeroSkills();
            $this->migrateEpTransactions();
            $this->migrateAdventures();
            $this->migrateBookings();
            $this->migrateVisits();
        });

        $this->newLine();
        $this->report();

        if ($this->nonBcryptPasswords > 0) {
            $this->warn("Achtung: {$this->nonBcryptPasswords} Benutzer haben kein bcrypt-Passwort und müssen es zurücksetzen.");
        }

        $this->info('Daten-Migration abgeschlossen.');

        return self::SUCCESS;
    }

    private function legacy(): ConnectionInterface
    {
        return DB::connection('legacy');
    }

    private function seedLookups(): void
    {
        foreach ([RoleSeeder::class, HeroClassSeeder::class, PerlColorSeeder::class,
            EpTransactionTypeSeeder::class, LocationSeeder::class, EventLookupSeeder::class] as $seeder) {
            app($seeder)->run();
        }
    }

    // ---- Benutzer & Spieler -------------------------------------------------

    private function migrateUsers(): void
    {
        foreach ($this->legacy()->table('portal_user')->get() as $row) {
            if (! $this->looksLikeBcrypt($row->password)) {
                $this->nonBcryptPasswords++;
            }

            DB::table('users')->updateOrInsert(
                ['legacy_id' => $row->id],
                [
                    'name' => $row->name ?? '',
                    'lastname' => $row->lastname,
                    'email' => $row->email,
                    'phone' => $row->phone,
                    // Hash unverändert übernehmen (Model-Cast wird bewusst umgangen).
                    'password' => $row->password ?? '',
                    'activated' => (bool) $row->activated,
                    'email_verified_at' => $row->verified ? $this->cleanDateTime($row->created) : null,
                    'lastlogin_at' => $this->cleanDateTime($row->lastlogin),
                    'deleted_at' => $this->cleanDateTime($row->deleted),
                    'created_at' => $this->cleanDateTime($row->created) ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function migratePlayers(): void
    {
        foreach ($this->legacy()->table('player')->get() as $row) {
            DB::table('players')->updateOrInsert(
                ['legacy_id' => $row->id],
                [
                    'name' => $row->name,
                    'lastname' => $row->lastname,
                    'email' => $row->email,
                    'dayofbirth' => $this->cleanDate($row->dayofbirth),
                    'gender' => $row->gender,
                    'active' => $this->onOff($row->active),
                    'deleted_at' => $this->cleanDateTime($row->deleted),
                    'created_at' => $this->cleanDateTime($row->created) ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function migrateUserPlayer(): void
    {
        $users = $this->map('users');
        $players = $this->map('players');

        foreach ($this->legacy()->table('user2player')->get() as $row) {
            if (! isset($users[$row->user_id], $players[$row->player_id])) {
                continue;
            }
            DB::table('player_user')->updateOrInsert(
                ['user_id' => $users[$row->user_id], 'player_id' => $players[$row->player_id]],
                ['self' => $this->onOff($row->self)]
            );
        }
    }

    private function migrateUserRoles(): void
    {
        $users = $this->map('users');

        foreach ($this->legacy()->table('user2role')->get() as $row) {
            if (! isset($users[$row->user_id]) || ! DB::table('roles')->where('id', $row->role_id)->exists()) {
                continue;
            }
            DB::table('role_user')->updateOrInsert(
                ['user_id' => $users[$row->user_id], 'role_id' => $row->role_id],
                []
            );
        }
    }

    // ---- Fertigkeiten -------------------------------------------------------

    private function migrateSkills(): void
    {
        $perlColors = DB::table('perl_colors')->pluck('id', 'code');
        $classIds = DB::table('hero_classes')->pluck('id', 'id');

        foreach ($this->legacy()->table('skills')->get() as $row) {
            DB::table('skills')->updateOrInsert(
                ['legacy_id' => $row->id],
                [
                    'name' => $row->name,
                    'description' => $row->description,
                    'ep_costs' => (int) $row->epcosts,
                    'level' => $row->level,
                    'hero_class_id' => $classIds[$row->masterclass] ?? null,
                    'perl_color_id' => $perlColors[$row->perlcolor] ?? null,
                    'perl_count' => (int) $row->perlcount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function migrateSkillClasses(): void
    {
        $skills = $this->map('skills');

        foreach ($this->legacy()->table('skills2class')->get() as $row) {
            if (! isset($skills[$row->skill_id]) || ! DB::table('hero_classes')->where('id', $row->class_id)->exists()) {
                continue;
            }
            DB::table('skill_hero_class')->updateOrInsert(
                ['skill_id' => $skills[$row->skill_id], 'hero_class_id' => $row->class_id],
                []
            );
        }
    }

    // ---- Helden -------------------------------------------------------------

    private function migrateHeroes(): void
    {
        $players = $this->map('players');

        foreach ($this->legacy()->table('hero')->get() as $row) {
            if (! isset($players[$row->player_id])) {
                continue;
            }
            DB::table('heroes')->updateOrInsert(
                ['legacy_id' => $row->id],
                [
                    'player_id' => $players[$row->player_id],
                    'character_name' => $row->character_name,
                    'born' => $this->cleanDate($row->born),
                    'died' => $this->cleanDate($row->died),
                    'homeplace' => $row->homeplace,
                    'active' => (bool) $row->active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function migratePlayerActiveHero(): void
    {
        $heroes = $this->map('heroes');

        foreach ($this->legacy()->table('player')->whereNotNull('hero_active')->get() as $row) {
            if (! isset($heroes[$row->hero_active])) {
                continue;
            }
            DB::table('players')->where('legacy_id', $row->id)
                ->update(['active_hero_id' => $heroes[$row->hero_active]]);
        }
    }

    private function migrateHeroClasses(): void
    {
        $heroes = $this->map('heroes');
        // Legacy hero2classes.class_id hält den Klassen-Slug (idname).
        $classBySlug = DB::table('hero_classes')->pluck('id', 'slug');

        foreach ($this->legacy()->table('hero2classes')->get() as $row) {
            $classId = $classBySlug[$row->class_id] ?? null;
            if (! isset($heroes[$row->hero_id]) || $classId === null) {
                continue;
            }
            DB::table('hero_hero_class')->updateOrInsert(
                ['hero_id' => $heroes[$row->hero_id], 'hero_class_id' => $classId],
                []
            );
        }
    }

    private function migrateHeroSkills(): void
    {
        $heroes = $this->map('heroes');
        $skills = $this->map('skills');

        foreach ($this->legacy()->table('hero2skill')->get() as $row) {
            if (! isset($heroes[$row->hero_id], $skills[$row->skill_id])) {
                continue;
            }
            DB::table('hero_skill')->updateOrInsert(
                ['hero_id' => $heroes[$row->hero_id], 'skill_id' => $skills[$row->skill_id]],
                ['trained_at' => $this->cleanDateTime($row->trained)]
            );
        }
    }

    private function migrateEpTransactions(): void
    {
        $heroes = $this->map('heroes');

        foreach ($this->legacy()->table('hero2ep')->get() as $row) {
            if (! isset($heroes[$row->hero_id]) || ! DB::table('ep_transaction_types')->where('id', $row->transEP_id)->exists()) {
                continue;
            }
            DB::table('ep_transactions')->updateOrInsert(
                ['legacy_id' => $row->id],
                [
                    'hero_id' => $heroes[$row->hero_id],
                    'ep_transaction_type_id' => $row->transEP_id,
                    'ep_count' => $row->ep_count,
                    'transacted_at' => $this->cleanDateTime($row->date_trans),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    // ---- Events / Abenteuer -------------------------------------------------

    private function migrateAdventures(): void
    {
        $users = $this->map('users');

        foreach ($this->legacy()->table('event')->get() as $row) {
            DB::table('adventures')->updateOrInsert(
                ['legacy_id' => $row->id],
                [
                    'name' => $row->name,
                    'location_id' => $this->existing('locations', $row->location),
                    'start_at' => $this->cleanDateTime($row->eventStartDate) ?? now(),
                    'end_at' => $this->cleanDateTime($row->eventEndeDate) ?? now(),
                    'loot_ep_day' => (int) $row->loot_ep_day,
                    'gamemaster_id' => $users[$row->gamemaster_id] ?? null,
                    'eventleader_id' => $users[$row->eventleader_id] ?? null,
                    'event_status_id' => $this->existing('event_statuses', $row->status) ?? 0,
                    'event_client_id' => $this->existing('event_clients', $row->auftraggeber) ?? 1,
                    'event_category_id' => $this->existing('event_categories', $row->category) ?? 0,
                    'max_player' => (int) $row->max_player,
                    'waitlist' => (int) $row->waitlist,
                    'fee' => $row->fee,
                    'created_at' => $this->cleanDateTime($row->created) ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function migrateBookings(): void
    {
        $adventures = $this->map('adventures');
        $players = $this->map('players');

        foreach ($this->legacy()->table('event_booking')->get() as $row) {
            if (! isset($adventures[$row->event_id], $players[$row->player_id])) {
                continue;
            }
            DB::table('bookings')->updateOrInsert(
                ['adventure_id' => $adventures[$row->event_id], 'player_id' => $players[$row->player_id]],
                [
                    'event_role_id' => $this->existing('event_roles', $row->event_role) ?? 1,
                    'fotoerlaubnis' => (bool) $row->fotoerlaubnis,
                    'vegetarier' => (bool) $row->vegetarier,
                    'leih_tunika' => (bool) $row->leih_tunika,
                    'leih_waffe' => (bool) $row->leih_waffe,
                    'nsc' => (bool) $row->nsc,
                    'agb' => (bool) $row->agb,
                    'paid' => (bool) $row->paid,
                    'allergien' => $row->allergien,
                    'medikamente' => $row->medikamente,
                    'erreichbarkeit' => $row->erreichbarkeit,
                    'approved_at' => $this->cleanDateTime($row->approved),
                    'created_at' => $this->cleanDateTime($row->created) ?? now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function migrateVisits(): void
    {
        $adventures = $this->map('adventures');
        $players = $this->map('players');

        foreach ($this->legacy()->table('event_visit')->get() as $row) {
            if (! isset($adventures[$row->event_id], $players[$row->player_id])) {
                continue;
            }
            DB::table('event_visits')->updateOrInsert(
                ['adventure_id' => $adventures[$row->event_id], 'player_id' => $players[$row->player_id]],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    // ---- Helfer -------------------------------------------------------------

    /** @return array<int,int> Map [legacy_id => neue id] für eine Tabelle. */
    private function map(string $table): array
    {
        return DB::table($table)->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();
    }

    /** Gibt $id zurück, falls die Zeile in $table existiert, sonst null. */
    private function existing(string $table, $id): ?int
    {
        if ($id === null) {
            return null;
        }

        return DB::table($table)->where('id', $id)->exists() ? (int) $id : null;
    }

    private function onOff(?string $value): bool
    {
        return strtolower((string) $value) === 'on';
    }

    private function looksLikeBcrypt(?string $hash): bool
    {
        return is_string($hash) && (bool) preg_match('/^\$2[aby]\$/', $hash);
    }

    private function cleanDate(?string $value): ?string
    {
        $clean = $this->cleanDateTime($value);

        return $clean ? Carbon::parse($clean)->toDateString() : null;
    }

    private function cleanDateTime(?string $value): ?string
    {
        if (empty($value) || str_starts_with((string) $value, '0000-00-00')) {
            return null;
        }

        return $value;
    }

    private function report(): void
    {
        $tables = [
            'portal_user' => 'users',
            'player' => 'players',
            'user2player' => 'player_user',
            'user2role' => 'role_user',
            'skills' => 'skills',
            'skills2class' => 'skill_hero_class',
            'hero' => 'heroes',
            'hero2classes' => 'hero_hero_class',
            'hero2skill' => 'hero_skill',
            'hero2ep' => 'ep_transactions',
            'event' => 'adventures',
            'event_booking' => 'bookings',
            'event_visit' => 'event_visits',
        ];

        $rows = [];
        foreach ($tables as $source => $target) {
            $rows[] = [
                $source,
                $this->legacy()->table($source)->count(),
                $target,
                DB::table($target)->count(),
            ];
        }

        $this->table(['Legacy', '#', 'Ziel', '#'], $rows);
    }
}
