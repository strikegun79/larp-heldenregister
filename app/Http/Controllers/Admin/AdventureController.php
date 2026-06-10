<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Adventure;
use Illuminate\View\View;

/**
 * Admin-Eventliste (Verwaltung → Abenteuer, ADV-16): Einstieg in das
 * Verwaltungs-Modal (Editor + Anmeldungen + Check-in) je Event.
 */
class AdventureController extends Controller
{
    public function index(): View
    {
        $adventures = Adventure::with('status')
            ->withCount('confirmedBookings')
            ->orderByDesc('start_at')
            ->paginate(25);

        return view('admin.adventures.index', compact('adventures'));
    }
}
