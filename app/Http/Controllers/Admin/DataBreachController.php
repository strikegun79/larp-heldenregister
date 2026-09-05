<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataBreachLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * DSGVO Art. 33/34: Datenpannen dokumentieren und Meldestatus verwalten.
 * Berechtigung: data-breach.manage (nur Admin).
 */
class DataBreachController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        $this->middleware('can:data-breach.manage');
    }

    public function index(): View
    {
        $logs = DataBreachLog::with('createdBy')
            ->orderByDesc('discovered_at')
            ->paginate(20);

        return view('admin.data-breaches.index', compact('logs'));
    }

    public function create(): View
    {
        return view('admin.data-breaches.create', [
            'log'        => new DataBreachLog(),
            'categories' => DataBreachLog::$dataCategoryLabels,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by_user_id'] = $request->user()->id;

        DataBreachLog::create($data);

        return redirect()->route('admin.data-breaches.index')
            ->with('success', 'Datenpanne dokumentiert.');
    }

    public function show(DataBreachLog $dataBreach): View
    {
        $dataBreach->load('createdBy');

        return view('admin.data-breaches.show', [
            'log'        => $dataBreach,
            'categories' => DataBreachLog::$dataCategoryLabels,
        ]);
    }

    public function edit(DataBreachLog $dataBreach): View
    {
        return view('admin.data-breaches.edit', [
            'log'        => $dataBreach,
            'categories' => DataBreachLog::$dataCategoryLabels,
        ]);
    }

    public function update(Request $request, DataBreachLog $dataBreach): RedirectResponse
    {
        $dataBreach->update($this->validated($request));

        return redirect()->route('admin.data-breaches.show', $dataBreach)
            ->with('success', 'Eintrag aktualisiert.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'discovered_at'            => ['required', 'date'],
            'description'              => ['required', 'string', 'max:5000'],
            'affected_data_categories' => ['required', 'array', 'min:1'],
            'affected_data_categories.*' => ['string', 'in:' . implode(',', array_keys(DataBreachLog::$dataCategoryLabels))],
            'affected_persons_count'   => ['nullable', 'integer', 'min:0'],
            'likely_consequences'      => ['nullable', 'string', 'max:3000'],
            'measures_taken'           => ['required', 'string', 'max:5000'],
            'reportable'               => ['boolean'],
            'reported_to_authority'    => ['boolean'],
            'reported_at'              => ['nullable', 'date', 'required_if:reported_to_authority,true'],
            'authority_reference'      => ['nullable', 'string', 'max:255'],
            'internal_notes'           => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
