<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Motd;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Mews\Purifier\Facades\Purifier;

/**
 * Verwaltung der Startseiten-Nachrichten (MOTD) mit Verlauf.
 */
class MotdController extends Controller
{
    public function index(): View
    {
        $motds = Motd::with('updater')
            ->withSum('linkClicks as clicks_total', 'clicks')
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.motd.index', compact('motds'));
    }

    public function create(): View
    {
        return view('admin.motd.create', [
            'motd' => new Motd(['variant' => 'neutral']),
            'variants' => Motd::VARIANTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $motd = new Motd($data['attributes']);
        $motd->image = $request->hasFile('image') ? $request->file('image')->store('motd', 'public') : null;
        $motd->content_updated_at = now();
        $motd->created_by = $request->user()->id;
        $motd->updated_by = $request->user()->id;
        $motd->is_active = false;
        $motd->save();

        if ($data['activate']) {
            $motd->activate();
        }

        AuditLogger::log('motd.created', $motd, $this->auditData($motd));

        return redirect()->route('admin.motd.index')->with('status', "Nachricht „{$motd->title}“ wurde angelegt.");
    }

    public function edit(Motd $motd): View
    {
        return view('admin.motd.edit', [
            'motd' => $motd,
            'variants' => Motd::VARIANTS,
            'linkClicks' => $motd->linkClicks()->orderByDesc('clicks')->get(),
        ]);
    }

    public function update(Request $request, Motd $motd): RedirectResponse
    {
        $data = $this->validated($request);
        $oldHtml = $motd->html;
        $oldImage = $motd->image;

        $motd->fill($data['attributes']);

        // Bild ersetzen bzw. entfernen.
        if ($motd->image && ($request->hasFile('image') || $request->boolean('remove_image'))) {
            Storage::disk('public')->delete($motd->image);
            $motd->image = null;
        }
        if ($request->hasFile('image')) {
            $motd->image = $request->file('image')->store('motd', 'public');
        }

        // Neue Inhaltsversion nur bei geändertem Text/Bild → weggeklickte Nachricht erscheint wieder.
        if ($oldHtml !== $motd->html || $oldImage !== $motd->image) {
            $motd->content_updated_at = now();
        }
        $motd->updated_by = $request->user()->id;

        DB::transaction(function () use ($motd, $data) {
            if ($data['activate']) {
                $motd->save();
                $motd->activate();
            } else {
                $motd->is_active = false;
                $motd->save();
            }
        });

        AuditLogger::log('motd.updated', $motd, $this->auditData($motd));

        return redirect()->route('admin.motd.index')->with('status', "Nachricht „{$motd->title}“ wurde gespeichert.");
    }

    /** Nachricht aktivieren (alle anderen werden deaktiviert). */
    public function activate(Request $request, Motd $motd): RedirectResponse
    {
        $motd->updated_by = $request->user()->id;
        $motd->activate();

        AuditLogger::log('motd.activated', $motd, ['titel' => $motd->title]);

        return redirect()->route('admin.motd.index')->with('status', "Nachricht „{$motd->title}“ ist jetzt aktiv.");
    }

    /** Nachricht deaktivieren. */
    public function deactivate(Request $request, Motd $motd): RedirectResponse
    {
        $motd->update(['is_active' => false, 'updated_by' => $request->user()->id]);

        AuditLogger::log('motd.deactivated', $motd, ['titel' => $motd->title]);

        return redirect()->route('admin.motd.index')->with('status', "Nachricht „{$motd->title}“ wurde deaktiviert.");
    }

    /** Kopie als inaktiven Entwurf anlegen (Bild wird mitkopiert). */
    public function duplicate(Request $request, Motd $motd): RedirectResponse
    {
        $copy = $motd->replicate(['is_active', 'created_by', 'updated_by', 'content_updated_at']);
        $copy->title = mb_substr('Kopie von '.$motd->title, 0, 150);
        $copy->is_active = false;
        $copy->created_by = $request->user()->id;
        $copy->updated_by = $request->user()->id;
        $copy->content_updated_at = now();

        if ($motd->image && Storage::disk('public')->exists($motd->image)) {
            $newPath = 'motd/'.uniqid().'.'.pathinfo($motd->image, PATHINFO_EXTENSION);
            Storage::disk('public')->copy($motd->image, $newPath);
            $copy->image = $newPath;
        }
        $copy->save();

        AuditLogger::log('motd.duplicated', $copy, ['quelle' => $motd->title]);

        return redirect()->route('admin.motd.edit', $copy)->with('status', 'Kopie angelegt – jetzt anpassen und speichern.');
    }

    public function destroy(Motd $motd): RedirectResponse
    {
        if ($motd->image) {
            Storage::disk('public')->delete($motd->image);
        }
        AuditLogger::log('motd.deleted', $motd, ['titel' => $motd->title]);
        $motd->delete();

        return redirect()->route('admin.motd.index')->with('status', "Nachricht „{$motd->title}“ wurde gelöscht.");
    }

    /**
     * Gemeinsame Validierung für Anlegen und Bearbeiten.
     *
     * @return array{attributes: array<string, mixed>, activate: bool}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
            'dismissible' => ['nullable', 'boolean'],
            'variant' => ['required', Rule::in(array_keys(Motd::VARIANTS))],
            'html' => ['nullable', 'string', 'max:20000'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', Rule::when($request->filled('start_at'), 'after:start_at')],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
            'image_link' => ['nullable', 'url:http,https', 'max:500'],
        ], [
            'end_at.after' => 'Das Ende muss nach dem Beginn liegen.',
            'image_link.url' => 'Bitte eine gültige Adresse mit http:// oder https:// angeben.',
        ]);

        $html = filled($data['html'] ?? null) ? Purifier::clean($data['html'], 'motd') : null;
        // Leere Editor-Inhalte (z. B. „<p><br></p>“) nicht speichern.
        if ($html !== null && trim(strip_tags($html)) === '') {
            $html = null;
        }

        return [
            'attributes' => [
                'title' => $data['title'],
                'html' => $html,
                'variant' => $data['variant'],
                'dismissible' => $request->boolean('dismissible'),
                'start_at' => $data['start_at'] ?? null,
                'end_at' => $data['end_at'] ?? null,
                'image_link' => filled($data['image_link'] ?? null) ? $data['image_link'] : null,
            ],
            'activate' => $request->boolean('is_active'),
        ];
    }

    /** @return array<string, mixed> */
    private function auditData(Motd $motd): array
    {
        return [
            'titel' => $motd->title,
            'aktiv' => $motd->is_active,
            'variante' => $motd->variant,
            'von' => $motd->start_at?->toDateTimeString(),
            'bis' => $motd->end_at?->toDateTimeString(),
            'bild' => $motd->image !== null,
            'bild_link' => $motd->image_link,
        ];
    }
}
