{{--
    Allgemeiner WYSIWYG-Editor (Summernote-lite).
    Parameter: $id (Textarea-ID), $name (Feldname), $value (HTML-Startwert),
    optional $maxText (Zeichenlimit für Zähler, null = kein Zähler) und
    $links (true = Link-Button in der Werkzeugleiste).
    Funktioniert auf normalen Seiten und in per AJAX geladenen Modalen.
--}}
@php($maxText = $maxText ?? null)
@php($links = $links ?? false)
<textarea id="{{ $id }}" name="{{ $name }}" data-rich-editor
          style="width: 100%;" rows="7">{{ $value }}</textarea>
@if ($maxText)
    <div class="text-xs text-stone-500 mt-1">
        <span data-rich-editor-count="{{ $id }}">0</span> / {{ number_format($maxText, 0, ',', '.') }} Zeichen
    </div>
@endif

<script>
(function () {
    var MAX_TEXT = @json($maxText);
    var id = @json($id);

    // Summernote nur einmal pro Seite nachladen (CSS, Skript, deutsche Sprachdatei).
    function loadSummernote() {
        if (window.summernoteLoading) return window.summernoteLoading;
        if (!document.querySelector('link[href="/vendor/summernote/summernote-lite.min.css"]')) {
            var css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = '/vendor/summernote/summernote-lite.min.css';
            document.head.appendChild(css);
        }
        function load(src) {
            return new Promise(function (resolve, reject) {
                var s = document.createElement('script');
                s.src = src;
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }
        window.summernoteLoading = window.jQuery.fn.summernote
            ? Promise.resolve()
            : load('/vendor/summernote/summernote-lite.min.js')
                .then(function () { return load('/vendor/summernote/lang/summernote-de-DE.min.js'); });
        return window.summernoteLoading;
    }

    function updateCount($ta) {
        var counter = document.querySelector('[data-rich-editor-count="' + id + '"]');
        if (!counter) return;
        var len = window.jQuery('<div>').html($ta.summernote('code')).text().length;
        counter.textContent = len.toLocaleString('de-DE');
        counter.parentNode.style.color = len > MAX_TEXT ? '#b91c1c' : '';
    }

    function init() {
        var $ta = window.jQuery('#' + id);
        if (!$ta.length || $ta.data('summernote')) return;
        $ta.summernote({
            lang: 'de-DE',
            height: 260,
            minHeight: 160,
            disableDragAndDrop: true,
            toolbar: [
                ['font',  ['fontsize', 'bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['color', ['forecolor', 'backcolor']],
                ['para',  ['ul', 'ol', 'paragraph']],
                @if ($links)
                ['insert', ['link']],
                @endif
                ['misc',  ['undo', 'redo']],
            ],
            fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32'],
            callbacks: {
                // Inhalt laufend ins Formularfeld übernehmen (AJAX-Submit liest die Textarea).
                onChange: function (contents) {
                    $ta.val(contents);
                    updateCount($ta);
                },
                // Eingefügten Text ohne fremde Formatierung übernehmen.
                onPaste: function (e) {
                    var clip = (e.originalEvent || e).clipboardData;
                    if (!clip) return;
                    e.preventDefault();
                    document.execCommand('insertText', false, clip.getData('text/plain'));
                },
            },
        });
        updateCount($ta);

        // Sicherheitshalber vor dem Absenden nochmals synchronisieren.
        var form = $ta[0].form;
        if (form) {
            form.addEventListener('submit', function () {
                $ta.val($ta.summernote('isEmpty') ? '' : $ta.summernote('code'));
            }, true);
        }
    }

    function start() {
        loadSummernote().then(init);
    }

    // Auf normalen Seiten laden Vite-Module (jQuery) erst nach diesem Inline-Skript.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
