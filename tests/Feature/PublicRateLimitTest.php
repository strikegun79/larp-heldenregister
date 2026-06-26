<?php

namespace Tests\Feature;

use App\Models\Hero;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** PUB-06: Tests für Rate-Limiting der öffentlichen Helden-Endpunkte. */
class PublicRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_profil_liefert_rate_limit_header(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'RLTEST']);

        $this->get(route('public.hero', 'RLTEST'))
             ->assertOk()
             ->assertHeader('X-RateLimit-Limit');
    }

    public function test_suchseite_liefert_rate_limit_header(): void
    {
        $this->get(route('public.hero.search'))
             ->assertOk()
             ->assertHeader('X-RateLimit-Limit');
    }

    public function test_zu_viele_anfragen_an_profil_liefern_429(): void
    {
        Hero::factory()->create(['public_code' => 'RL429A']);

        // Limit für diesen Test auf 1 Anfrage pro Minute senken.
        RateLimiter::for('public-hero', fn () => Limit::perMinute(1)->by('127.0.0.1'));

        $this->get(route('public.hero', 'RL429A'))->assertOk();
        $this->get(route('public.hero', 'RL429A'))->assertStatus(429);
    }

    public function test_zu_viele_suchanfragen_liefern_429(): void
    {
        RateLimiter::for('public-hero', fn () => Limit::perMinute(1)->by('127.0.0.1'));

        $this->get(route('public.hero.search'))->assertOk();
        $this->get(route('public.hero.search'))->assertStatus(429);
    }

    public function test_rate_limit_ist_30_pro_minute(): void
    {
        $hero = Hero::factory()->create(['public_code' => 'RLCNT3']);

        $response = $this->get(route('public.hero', 'RLCNT3'));

        $response->assertHeader('X-RateLimit-Limit', 30);
    }

    protected function tearDown(): void
    {
        // Limiter nach jedem Test zurücksetzen, damit andere Tests nicht beeinflusst werden.
        RateLimiter::for('public-hero', function (\Illuminate\Http\Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        parent::tearDown();
    }
}
