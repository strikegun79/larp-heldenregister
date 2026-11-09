<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PerlColor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verwaltung der Perlenfarben (EP-05).
 * Perlenfarben kennzeichnen Fertigkeiten optisch (Bändchen).
 */
class PerlColorController extends Controller
{
    public function index(): View
    {
        $colors = PerlColor::withCount('skills')->orderBy('name')->get();

        return view('admin.perl_colors.index', compact('colors'));
    }

    public function create(Request $request): View
    {
        $data = ['color' => new PerlColor];

        return $request->expectsJson()
            ? view('admin.perl_colors._form', $data)
            : view('admin.perl_colors.create', $data);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        PerlColor::create($this->validated($request));

        return $this->respond($request, 'Perlenfarbe wurde angelegt.');
    }

    public function edit(PerlColor $perlColor, Request $request): View
    {
        $data = ['color' => $perlColor];

        return $request->expectsJson()
            ? view('admin.perl_colors._form', $data)
            : view('admin.perl_colors.edit', $data);
    }

    public function update(Request $request, PerlColor $perlColor): RedirectResponse|JsonResponse
    {
        $perlColor->update($this->validated($request));

        return $this->respond($request, 'Perlenfarbe wurde aktualisiert.');
    }

    public function destroy(Request $request, PerlColor $perlColor): RedirectResponse|JsonResponse
    {
        if ($perlColor->skills()->exists()) {
            $msg = 'Perlenfarbe wird von Fertigkeiten verwendet und kann nicht gelöscht werden.';

            return $request->expectsJson()
                ? response()->json(['message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $perlColor->delete();

        return $this->respond($request, 'Perlenfarbe wurde gelöscht.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:50'],
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message, 'reload' => true])
            : redirect()->route('admin.perl-colors.index')->with('status', $message);
    }
}
