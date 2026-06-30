<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hero;
use App\Models\IdCardCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * PUB-10: Generator für Heldenausweise (Admin/Bürokrat).
 */
class IdCardController extends Controller
{
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Pool-Übersicht + Generator-Formular.
     */
    public function index(): View
    {
        $unassigned = IdCardCode::whereNull('hero_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $assigned = IdCardCode::whereNotNull('hero_id')
            ->with('hero')
            ->orderBy('assigned_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin.id-cards.index', compact('unassigned', 'assigned'));
    }

    /**
     * N Codes generieren und als PDF herunterladen.
     * Karte: 7,52 cm × 10 cm, 3×2 querformat auf A4.
     */
    public function generate(Request $request): Response|RedirectResponse
    {
        $request->validate(['count' => ['required', 'integer', 'min:1', 'max:200']]);
        $count = (int) $request->integer('count');

        $codes = [];
        $attempts = 0;
        while (count($codes) < $count && $attempts < $count * 10) {
            $code = $this->randomCode();
            if (
                ! isset($codes[$code])
                && ! Hero::where('public_code', $code)->exists()
                && ! IdCardCode::where('code', $code)->exists()
            ) {
                IdCardCode::create(['code' => $code, 'created_by' => $request->user()->id]);
                $codes[$code] = true;
            }
            $attempts++;
        }

        if (empty($codes)) {
            return back()->withErrors(['count' => 'Codes konnten nicht generiert werden.']);
        }

        $cardData = array_map(fn ($code) => [
            'code' => $code,
            'url'  => route('public.hero', $code),
            'qr'   => $this->qrDataUri(route('public.hero', $code)),
        ], array_keys($codes));

        $pdf = Pdf::loadView('admin.id-cards.card-pdf', [
            'cards'           => $cardData,
            'backTemplateUri' => $this->backTemplateDataUri(),
        ])->setPaper('a4', 'landscape')
          ->setOption('dpi', 150)
          ->setOption('isFontSubsettingEnabled', true);

        return $pdf->stream('heldenausweise-'.date('Ymd').'.pdf');
    }

    /**
     * Ausweis eines einzelnen Helden neu drucken (Verlust).
     */
    public function reprint(Hero $hero): Response
    {
        abort_unless((bool) $hero->public_code, 404);

        $cardData = [[
            'code'           => $hero->public_code,
            'character_name' => $hero->character_name,
            'url'            => route('public.hero', $hero->public_code),
            'qr'             => $this->qrDataUri(route('public.hero', $hero->public_code)),
        ]];

        $pdf = Pdf::loadView('admin.id-cards.card-pdf', [
            'cards'           => $cardData,
            'backTemplateUri' => $this->backTemplateDataUri(),
        ])->setPaper('a4', 'landscape')
          ->setOption('dpi', 150)
          ->setOption('isFontSubsettingEnabled', true);

        return $pdf->stream('ausweis-'.$hero->id.'.pdf');
    }

    /**
     * Pool-Code einem Helden zuweisen (setzt heroes.public_code).
     */
    public function assign(Request $request, Hero $hero): RedirectResponse|JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/'],
        ]);

        $code = strtoupper(trim($request->string('code')));

        // Prüfen ob der Code bereits vergeben ist (anderem Helden)
        $existingHero = Hero::where('public_code', $code)->where('id', '!=', $hero->id)->first();
        if ($existingHero) {
            $error = 'Dieser Code ist bereits einem anderen Helden zugewiesen.';

            return $request->expectsJson()
                ? response()->json(['errors' => ['code' => [$error]]], 422)
                : back()->withErrors(['code' => $error]);
        }

        // Pool-Eintrag suchen oder anlegen
        $poolEntry = IdCardCode::firstOrCreate(
            ['code' => $code],
            ['created_by' => $request->user()->id]
        );

        if ($poolEntry->hero_id && $poolEntry->hero_id !== $hero->id) {
            $error = 'Dieser Pool-Code ist bereits einem anderen Helden zugewiesen.';

            return $request->expectsJson()
                ? response()->json(['errors' => ['code' => [$error]]], 422)
                : back()->withErrors(['code' => $error]);
        }

        $hero->update(['public_code' => $code]);
        $poolEntry->update(['hero_id' => $hero->id, 'assigned_at' => now()]);

        $message = 'Helden-Siegel zugewiesen: '.$code;

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'refresh_modal' => true])
            : redirect()->route('heroes.show', $hero)->with('status', $message);
    }

    /**
     * Nicht zugewiesenes Siegel aus dem Pool löschen.
     * Bereits zugewiesene Siegel (hero_id gesetzt) werden abgelehnt.
     */
    public function destroy(string $code): RedirectResponse
    {
        $entry = IdCardCode::where('code', $code)->firstOrFail();

        abort_if($entry->hero_id !== null, 403, 'Zugewiesene Siegel können nicht gelöscht werden.');

        $entry->delete();

        return redirect()->route('admin.id-cards.index')
            ->with('status', 'Siegel '.$code.' gelöscht.');
    }

    private function randomCode(): string
    {
        $len  = strlen(self::CODE_ALPHABET);
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $len - 1)];
        }
        return $code;
    }

    /** QR-Code als base64-PNG-Data-URI für DOMPDF (400 px für gute Druckqualität). */
    private function qrDataUri(string $url): string
    {
        $qr     = new QrCode($url, size: 400);
        $writer = new PngWriter();
        $result = $writer->write($qr);
        return 'data:image/png;base64,'.base64_encode($result->getString());
    }

    /**
     * Rückseiten-Template um 180° drehen und als Base64-DataURI zurückgeben.
     * Wird für das Klappformat benötigt: Rückseite kopfüber unter der Vorderseite.
     * Gibt null zurück wenn das Template fehlt oder GD nicht verfügbar ist.
     */
    private function backTemplateDataUri(): ?string
    {
        $path = resource_path('images/template_helden_ausweis_rueckseite.png');
        if (! file_exists($path) || ! function_exists('imagecreatefrompng')) {
            return null;
        }
        $img = @imagecreatefrompng($path);
        if ($img === false) {
            return null;
        }
        $rotated = imagerotate($img, 180, 0);
        ob_start();
        imagepng($rotated);
        $data = ob_get_clean();
        imagedestroy($img);
        imagedestroy($rotated);
        return 'data:image/png;base64,'.base64_encode($data);
    }
}
