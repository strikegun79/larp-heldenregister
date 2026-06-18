<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Editierbare Portal-Einstellungen (ADM-09).
 */
class SettingController extends Controller
{
    /** Bekannte editierbare Keys mit Label und Validierungsregel. */
    private const FIELDS = [
        'association_name' => ['label' => 'Vereinsname',     'rules' => ['required', 'string', 'max:100']],
        'contact_email' => ['label' => 'Kontakt-E-Mail',  'rules' => ['required', 'email', 'max:255']],
        'portal_logo' => ['label' => 'Logo-Dateiname',  'rules' => ['required', 'string', 'max:100']],
    ];

    public function index(): View
    {
        $values = Setting::whereIn('key', array_keys(self::FIELDS))->pluck('value', 'key');

        return view('admin.settings.index', [
            'fields' => self::FIELDS,
            'values' => $values,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = array_map(fn ($f) => $f['rules'], self::FIELDS);
        $data = $request->validate($rules);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings.index')->with('status', 'Einstellungen wurden gespeichert.');
    }
}
