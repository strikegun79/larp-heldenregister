<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * DSGVO Art. 30: Verzeichnis der Verarbeitungstätigkeiten (VVT).
 * Lesezugriff für portal.manage (Bürokrat/Admin).
 */
class ProcessingActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified', 'can:portal.manage']);
    }

    public function index(): View
    {
        return view('admin.processing-activities.index', [
            'generatedAt' => now(),
        ]);
    }
}
