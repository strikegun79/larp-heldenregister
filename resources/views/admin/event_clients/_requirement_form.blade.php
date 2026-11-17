{{-- Anforderung eines Auftraggebers anlegen/bearbeiten (Issue #14) --}}
<span data-modal-title hidden>
    {{ $requirement->exists ? 'Anforderung bearbeiten: '.$requirement->title : 'Neue Anforderung für '.$client->name }}
</span>

<form id="requirement-form" method="POST" enctype="multipart/form-data"
      action="{{ $requirement->exists ? route('admin.event-client-requirements.update', $requirement) : route('admin.event-clients.requirements.store', $client) }}"
      class="ui form space-y-4">
    @csrf
    @if ($requirement->exists) @method('PUT') @endif

    <div class="field required">
        <label for="req_title">Titel</label>
        <input type="text" id="req_title" name="title" maxlength="150" required
               value="{{ old('title', $requirement->title) }}"
               placeholder="z. B. Einverständniserklärung Jugendförderung Gießen">
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="field required">
        <label for="req_description">Beschreibung</label>
        <p class="text-xs text-stone-500 mb-1">
            Wird bei der Anmeldung im Popup angezeigt. Formatierung (fett, Überschriften per Schriftgröße, Farben, Listen) wie in der Papier-Vorlage möglich.
        </p>
        @include('partials._rich_editor', [
            'id' => 'req_description',
            'name' => 'description',
            'value' => old('description', $requirement->description),
            'maxText' => 20000,
            'links' => true,
        ])
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="field">
        <label for="req_pdf">PDF-Dokument (optional)</label>
        @if ($requirement->pdf_path)
            <p class="text-sm mb-1">
                Aktuell:
                <a href="{{ route('requirements.pdf', $requirement) }}" target="_blank" rel="noopener" class="text-waldritter underline">
                    <i class="file pdf outline icon"></i>{{ $requirement->pdf_name ?: 'PDF' }}
                </a>
            </p>
            <div class="ui checkbox mb-2">
                <input type="checkbox" id="req_remove_pdf" name="remove_pdf" value="1">
                <label for="req_remove_pdf">PDF entfernen</label>
            </div>
        @endif
        <input type="file" id="req_pdf" name="pdf" accept="application/pdf,.pdf">
        <p class="text-xs text-stone-500 mt-1">
            Nur PDF, max. 10 MB. Wird bei der Anmeldung mit einem PDF-Symbol zum Herunterladen angeboten.{{ $requirement->pdf_path ? ' Leer lassen, um die vorhandene Datei zu behalten.' : '' }}
        </p>
    </div>

    <div class="field">
        <label for="req_sort">Reihenfolge</label>
        <input type="number" id="req_sort" name="sort_order" min="0" value="{{ old('sort_order', $requirement->sort_order ?? 0) }}">
    </div>

    <div class="field">
        <div class="ui toggle checkbox">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" id="req_active" name="active" value="1" @checked(old('active', $requirement->active ?? true))>
            <label for="req_active">Aktiv – muss bei der Anmeldung zu Events dieses Auftraggebers bestätigt werden</label>
        </div>
    </div>
</form>

<div data-modal-actions hidden>
    <button type="submit" form="requirement-form" class="ui primary button">Speichern</button>
</div>
