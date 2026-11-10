<span data-modal-title hidden>Unterschrift · {{ $booking->player?->full_name }}</span>

<p class="text-stone-600 mb-2">{{ $adventure->name }}</p>
<p class="text-sm text-stone-500 mb-3">Mit Tablet &amp; Stift im Feld unterschreiben.</p>

<form method="POST" action="{{ route('adventures.bookings.signature.update', [$adventure, $booking]) }}"
      data-refresh-modal
      onsubmit="document.getElementById('sig-data').value = document.getElementById('signature-pad').toDataURL('image/png');">
    @csrf @method('PUT')
    <input type="hidden" name="signature" id="sig-data">

    <canvas id="signature-pad" width="600" height="220"
            style="border:2px solid #5a3a22; border-radius:.4rem; touch-action:none; background:#fff; max-width:100%; width:600px; height:220px;"></canvas>

    <div class="flex items-center gap-2 mt-3">
        <button type="submit" class="ui primary button">Unterschrift speichern</button>
        <button type="button" class="ui button" onclick="clearSignaturePad()">Löschen</button>
        @if ($booking->signature)
            <form method="POST" action="{{ route('adventures.bookings.signature.destroy', [$adventure, $booking]) }}"
                  data-refresh-modal class="inline" data-confirm="Vorhandene Unterschrift entfernen?">
                @csrf @method('DELETE')
                <button type="submit" class="ui basic red button">Entfernen</button>
            </form>
        @endif
        <a href="{{ route('adventures.manage', $adventure) }}" data-modal-subview="{{ route('adventures.manage', $adventure) }}"
           class="ui basic button">Zurück</a>
    </div>
</form>

@if ($booking->signature)
    <div class="mt-3">
        <p class="text-sm text-stone-500">Aktuell hinterlegt:</p>
        <img src="{{ $booking->signature }}" alt="Unterschrift" style="max-height:90px; border:1px solid #ccc; background:#fff" loading="lazy">
    </div>
@endif
