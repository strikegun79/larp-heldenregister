<?php

namespace Tests\Feature;

use App\Http\Controllers\PlayerController;
use App\Models\Player;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Issue #60: Geburtsdatum im Spielerformular als einfache Eingabe TT.MM.JJJJ statt Kalender. */
class PlayerBirthdayInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function user(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(70);

        return $user;
    }

    private function payload(string $birthday): array
    {
        return [
            'name' => 'Lena',
            'lastname' => 'Beispiel',
            'dayofbirth' => $birthday,
            'address_same_as_guardian' => '1',
            'self' => '1',
        ];
    }

    public function test_formular_hat_eingabefeld_ohne_kalender(): void
    {
        $user = $this->user();
        $player = Player::factory()->create(['dayofbirth' => '2012-03-15']);
        $user->players()->attach($player, ['self' => true]);

        $html = $this->actingAs($user)->get(route('players.edit', $player))->assertOk()->getContent();

        $this->assertStringContainsString('name="dayofbirth"', $html);
        $this->assertStringContainsString('value="15.03.2012"', $html);
        $this->assertStringContainsString('placeholder="TT.MM.JJJJ"', $html);
        $this->assertStringNotContainsString('data-cal-type', $html);
    }

    public function test_normalisierung(): void
    {
        $this->assertSame('2012-03-15', PlayerController::normalizeBirthday('15.03.2012'));
        $this->assertSame('2012-03-05', PlayerController::normalizeBirthday('5.3.2012'));
        $this->assertSame('2012-03-15', PlayerController::normalizeBirthday('15032012'));
        $this->assertSame('2012-03-15', PlayerController::normalizeBirthday('2012-03-15'));
        $this->assertSame('31.02.2012', PlayerController::normalizeBirthday('31.02.2012'));
        $this->assertNull(PlayerController::normalizeBirthday('  '));
    }

    public function test_gueltige_eingaben_werden_gespeichert(): void
    {
        $user = $this->user();

        foreach (['15.03.2012', '15032012', '5.3.2012'] as $input) {
            $this->actingAs($user)->post(route('players.store'), $this->payload($input))->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['2012-03-15', '2012-03-15', '2012-03-05'],
            Player::orderBy('id')->get()->map(fn ($p) => $p->dayofbirth->format('Y-m-d'))->all()
        );
    }

    public function test_ungueltige_eingaben_werden_abgelehnt(): void
    {
        $user = $this->user();
        $future = now()->addYear()->format('d.m.Y');

        foreach (['31.02.2012', 'gestern', '15.13.2012', '01.01.1890'] as $input) {
            $this->actingAs($user)
                ->from(route('players.create'))
                ->post(route('players.store'), $this->payload($input))
                ->assertSessionHasErrors(['dayofbirth' => 'Bitte ein gültiges Geburtsdatum im Format TT.MM.JJJJ eingeben.']);
        }

        $this->actingAs($user)
            ->post(route('players.store'), $this->payload($future))
            ->assertSessionHasErrors(['dayofbirth' => 'Das Geburtsdatum darf nicht in der Zukunft liegen.']);

        $this->assertSame(0, Player::count());
    }

    public function test_leeres_geburtsdatum_bleibt_erlaubt(): void
    {
        $this->actingAs($this->user())->post(route('players.store'), $this->payload(''))->assertSessionHasNoErrors();
        $this->assertNull(Player::sole()->dayofbirth);
    }
}
