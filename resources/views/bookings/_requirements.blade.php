{{--
    Zusätzliche Pflicht-Bestätigungen des Auftraggebers (Issue #14), z. B. die
    Einverständniserklärung der Jugendförderung Gießen. Der Titel öffnet ein Popup
    mit der formatierten Beschreibung (#requirement-modal im Layout); ist eine PDF
    hinterlegt, steht dahinter ein PDF-Symbol zum Herunterladen.
    Erwartet: $adventure, optional $idPrefix (eindeutige IDs je Formular).
--}}
@php($idPrefix = $idPrefix ?? 'req')
@foreach ($adventure->activeRequirements() as $requirement)
    @php($fieldId = $idPrefix.'_'.$requirement->id)
    @php($pdfUrl = $requirement->pdf_path ? route('requirements.pdf', $requirement) : null)
    <div class="field required mt-3">
        <div class="flex items-start gap-2">
            <input type="checkbox" id="{{ $fieldId }}"
                   name="requirements[{{ $requirement->id }}]" value="1" required class="mt-1 shrink-0 cursor-pointer">
            <span>
                <label for="{{ $fieldId }}" class="font-normal cursor-pointer" style="display: inline; font-weight: normal;">Ich habe die</label>
                <a href="#" role="button" class="text-waldritter underline"
                   data-requirement-modal="{{ $fieldId }}_text"
                   data-title="{{ $requirement->title }}"
                   data-download="{{ $pdfUrl }}">{{ $requirement->title }}</a>
                <label for="{{ $fieldId }}" class="font-normal cursor-pointer" style="display: inline; font-weight: normal;">gelesen und stimme ihr zu.</label>
                @if ($pdfUrl)
                    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" download
                       class="text-red-700 hover:text-red-900 whitespace-nowrap"
                       title="PDF herunterladen" aria-label="{{ $requirement->title }} als PDF herunterladen">
                        <i class="file pdf outline icon" aria-hidden="true"></i>
                    </a>
                @endif
            </span>
        </div>
        {{-- Formatierte Beschreibung für das Popup (bereits beim Speichern bereinigt, hier nochmals) --}}
        <template id="{{ $fieldId }}_text">{!! $requirement->description_html !!}</template>
    </div>
@endforeach
