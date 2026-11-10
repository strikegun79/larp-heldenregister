<?php

namespace Database\Seeders;

use App\Models\Adventure;
use App\Models\Booking;
use App\Models\EpTransaction;
use App\Models\Hero;
use App\Models\HeroClass;
use App\Models\Player;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * QA-07: Demo-Datensatz für Schulung und lokale Entwicklung.
 *
 * Erzeugt:
 * - 1 Admin-Konto (admin@waldritter.de / password)
 * - 2 Teamer-Konten (teamer1@waldritter.de, teamer2@waldritter.de / password)
 * - 5 Spieler (3 Minderjährige beim Admin, 2 Erwachsene)
 * - Je 1 Held pro Spieler mit Klassen, Skills und EP-Buchungen
 * - 1 vergangenes Abenteuer (abgeschlossen, mit Buchungen)
 * - 1 kommendes Abenteuer (Anmeldung offen)
 *
 * Voraussetzung: Stammdaten müssen bereits vorhanden sein (db:seed).
 * Aufruf: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Demo-Datensatz wird angelegt…');

        // Stammdaten sicherstellen
        $this->call([
            RoleSeeder::class,
            HeroClassSeeder::class,
            PerlColorSeeder::class,
            EpTransactionTypeSeeder::class,
            LocationSeeder::class,
            EventLookupSeeder::class,
        ]);

        $adminRole    = Role::where('slug', 'admin')->first();
        $teamerRole   = Role::where('slug', 'teamer')->first();
        $registrarRole = Role::where('slug', 'registrar')->first();

        // 1. Admin
        $admin = User::factory()->create([
            'name'     => 'Anna',
            'lastname' => 'Admin',
            'email'    => 'admin@waldritter.de',
        ]);
        $admin->roles()->sync([$adminRole->id]);

        // 2. Bürokrat
        $registrar = User::factory()->create([
            'name'     => 'Bernd',
            'lastname' => 'Bürokrat',
            'email'    => 'burokrat@waldritter.de',
        ]);
        $registrar->roles()->sync([$registrarRole->id]);

        // 3. Teamer
        $teamer1 = User::factory()->create([
            'name'     => 'Tim',
            'lastname' => 'Teamer',
            'email'    => 'teamer1@waldritter.de',
        ]);
        $teamer1->roles()->sync([$teamerRole->id]);

        $teamer2 = User::factory()->create([
            'name'     => 'Sandra',
            'lastname' => 'Spielleiterin',
            'email'    => 'teamer2@waldritter.de',
        ]);
        $teamer2->roles()->sync([$teamerRole->id]);

        // 4. Spieler und Helden anlegen
        $heroClasses = HeroClass::all()->keyBy('slug');
        $skills      = Skill::all();

        // 3 Minderjährige Spieler → gehören zum Admin
        $minors = Player::factory()->minor()->count(3)->create();
        foreach ($minors as $player) {
            $admin->players()->attach($player->id, ['self' => false]);
            $this->createHeroForPlayer($player, $heroClasses, $skills);
        }

        // 2 Erwachsene Spieler mit eigenem Konto
        foreach ([['Moritz', 'Muster', 'moritz@example.de'], ['Lisa', 'Lang', 'lisa@example.de']] as [$first, $last, $mail]) {
            $user = User::factory()->create(['name' => $first, 'lastname' => $last, 'email' => $mail]);
            $user->roles()->sync([$registrarRole->id > 0 ? [] : []]);
            $player = Player::factory()->create(['name' => $first, 'lastname' => $last]);
            $user->players()->attach($player->id, ['self' => true]);
            $this->createHeroForPlayer($player, $heroClasses, $skills);
        }

        $allPlayers = Player::with('heroes')->get();

        // 5. Vergangenes Abenteuer (abgeschlossen)
        $past = Adventure::factory()->create([
            'name'              => 'Das Geheimnis des alten Turms',
            'start_at'          => now()->subMonths(2)->setTime(10, 0),
            'end_at'            => now()->subMonths(2)->setTime(17, 0),
            'event_status_id'   => 60, // Abgeschlossen
            'event_category_id' => 2,  // Samstagsspiel
            'loot_ep_day'       => 3,
            'max_player'        => 20,
            'fee'               => 10,
        ]);

        foreach ($allPlayers->take(4) as $player) {
            $booking = Booking::factory()->create([
                'adventure_id' => $past->id,
                'player_id'    => $player->id,
                'hero_id'      => $player->heroes->first()?->id,
                'event_role_id' => 1,
                'status'       => 'bestaetigt',
            ]);

            // EP-Buchung für das Abenteuer
            if ($hero = $player->heroes->first()) {
                EpTransaction::factory()->adventure()->create([
                    'hero_id'       => $hero->id,
                    'adventure_id'  => $past->id,
                    'ep_count'      => $past->loot_ep_day,
                    'transacted_at' => $past->end_at,
                ]);
            }
        }

        // 6. Kommendes Abenteuer (Anmeldung offen)
        Adventure::factory()->create([
            'name'              => 'Die Waldritter und der Nebelwald',
            'start_at'          => now()->addMonths(1)->setTime(10, 0),
            'end_at'            => now()->addMonths(1)->setTime(17, 0),
            'event_status_id'   => 30, // Anmeldung offen
            'event_category_id' => 2,
            'loot_ep_day'       => 4,
            'max_player'        => 15,
            'fee'               => 10,
        ]);

        $this->command->info('Demo-Datensatz angelegt.');
        $this->command->table(
            ['Rolle', 'E-Mail', 'Passwort'],
            [
                ['Admin',    'admin@waldritter.de',    'password'],
                ['Bürokrat', 'burokrat@waldritter.de', 'password'],
                ['Teamer',   'teamer1@waldritter.de',  'password'],
                ['Teamer',   'teamer2@waldritter.de',  'password'],
                ['Spieler',  'moritz@example.de',      'password'],
                ['Spieler',  'lisa@example.de',         'password'],
            ]
        );
    }

    private function createHeroForPlayer(
        Player $player,
        \Illuminate\Support\Collection $heroClasses,
        \Illuminate\Support\Collection $skills
    ): Hero {
        $hero = Hero::factory()->create(['player_id' => $player->id]);

        // Klasse zuweisen
        $class = $heroClasses->random();
        DB::table('hero_hero_class')->insert([
            'hero_id'       => $hero->id,
            'hero_class_id' => $class->id,
        ]);

        // EP: Initiale Buchung
        EpTransaction::factory()->initial()->create(['hero_id' => $hero->id]);

        // Fertigkeiten aus der Klasse zuweisen (falls vorhanden)
        $classSkills = $skills->where('hero_class_id', $class->id)->take(3);
        foreach ($classSkills as $skill) {
            DB::table('hero_skill')->insertOrIgnore([
                'hero_id'     => $hero->id,
                'skill_id'    => $skill->id,
                'trained_at'  => now()->subMonths(rand(1, 12)),
            ]);

            EpTransaction::factory()->debit(20)->create([
                'hero_id'   => $hero->id,
                'ep_count'  => $skill->ep_costs ?: 1,
            ]);
        }

        $player->update(['active_hero_id' => $hero->id]);

        return $hero;
    }
}
