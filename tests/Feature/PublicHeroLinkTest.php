<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** PUB-09: Heldensuche-Links auf der Login-Seite und der öffentlichen Suchseite. */
class PublicHeroLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_login_seite_erklaert_heldenregister(): void
    {
        $this->get(route('login'))
             ->assertOk()
             ->assertSee('Heldenregister')
             ->assertSee('Heldenausweis')
             ->assertSee('QR-Code');
    }

    public function test_login_seite_enthaelt_link_zur_heldensuche(): void
    {
        $this->get(route('login'))
             ->assertOk()
             ->assertSee(route('public.hero.search'), false);
    }

    public function test_login_seite_zeigt_hinweis_auf_code_quellen(): void
    {
        $this->get(route('login'))
             ->assertOk()
             ->assertSee('Helden-Code eingeben');
    }

    public function test_oeffentliche_suchseite_zeigt_hinweis_auf_ausweis(): void
    {
        $this->get(route('public.hero.search'))
             ->assertOk()
             ->assertSee('Heldenausweis')
             ->assertSee('QR-Code')
             ->assertSee('Bürokraten');
    }
}
