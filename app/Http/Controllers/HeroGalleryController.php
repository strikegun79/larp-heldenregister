<?php

namespace App\Http\Controllers;

use App\Models\Hero;
use App\Models\HeroGalleryImage;
use App\Support\GalleryImageStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * HERO-24: Helden-Galerie – bis zu 4 Bilder pro Held.
 */
class HeroGalleryController extends Controller
{
    private const MAX_IMAGES = 4;

    /**
     * Galerie-Bild hochladen (POST heroes/{hero}/gallery).
     * Erlaubt für heldenregister.edit ODER Betreuer des Spielers.
     */
    public function store(Request $request, Hero $hero): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess($request, $hero);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($hero->galleryImages()->count() >= self::MAX_IMAGES) {
            $error = 'Maximal '.self::MAX_IMAGES.' Galerie-Bilder pro Held erlaubt.';

            return $request->expectsJson()
                ? response()->json(['message' => $error], 422)
                : back()->with('error', $error);
        }

        $path = GalleryImageStorage::store($request->file('image'), 'heroes/gallery');

        $hero->galleryImages()->create([
            'path'       => $path,
            'sort_order' => $hero->galleryImages()->max('sort_order') + 1,
        ]);

        $message = 'Galerie-Bild hinzugefügt.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    /**
     * Galerie-Bild löschen (DELETE heroes/{hero}/gallery/{image}).
     */
    public function destroy(Request $request, Hero $hero, HeroGalleryImage $image): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess($request, $hero);

        abort_unless($image->hero_id === $hero->id, 403);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        $message = 'Galerie-Bild gelöscht.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : back()->with('status', $message);
    }

    private function authorizeAccess(Request $request, Hero $hero): void
    {
        if ($request->user()->can('heldenregister.edit')) {
            return;
        }

        $isGuardian = $hero->player_id && \Illuminate\Support\Facades\DB::table('player_user')
            ->where('player_id', $hero->player_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_unless($isGuardian, 403);
    }
}
