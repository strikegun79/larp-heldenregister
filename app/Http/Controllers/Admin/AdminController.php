<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Admin-Startseite (Legacy: pages/admin/index.php) mit den Bereichen
     * Portal-Nutzer, Spieler und Veranstaltungen.
     */
    public function index(): View
    {
        return view('admin.index');
    }
}
