<link rel="stylesheet" href="/vendor/summernote/summernote-lite.min.css">
<link rel="stylesheet" href="/vendor/quill/quill.snow.css">
<style>
    /* Quill-Container ans Summernote-Look angleichen */
    #quill-container .ql-container { font-size: 1rem; min-height: 200px; }
    #quill-container .ql-editor    { min-height: 370px; font-family: inherit; }
</style>
<script>
(function () {
    var saveStatus    = document.getElementById('editor-save-status');
    var autoSaveTimer = null;
    var activeEditor  = 'summernote'; // 'summernote' | 'quill'
    var quillInstance = null;

    /* ── Status-Anzeige ─────────────────────────────────── */
    function showSaved() {
        if (!saveStatus) return;
        var now = new Date();
        saveStatus.textContent = 'Entwurf gespeichert '
            + now.getHours().toString().padStart(2, '0') + ':'
            + now.getMinutes().toString().padStart(2, '0');
    }

    /* ── Aktuellen HTML-Inhalt aus dem aktiven Editor holen ─ */
    function getEditorHtml() {
        if (activeEditor === 'quill' && quillInstance) {
            return quillInstance.root.innerHTML;
        }
        var ta = document.getElementById('body_html');
        return ta ? ta.value : '';
    }

    /* ── Textarea vor Submit / Auto-Save synchronisieren ──── */
    function syncTextarea() {
        var ta = document.getElementById('body_html');
        if (ta) ta.value = getEditorHtml();
    }

    /* ── Auto-Save (nur Edit-Seite, PATCH) ──────────────── */
    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function () {
            var form = document.getElementById('newsletter-form');
            if (!form) return;
            var methodField = form.querySelector('input[name="_method"]');
            if (!methodField || methodField.value !== 'PATCH') return;
            syncTextarea();
            var data = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: data,
            }).then(function (r) { if (r.ok) showSaved(); })
              .catch(function () {});
        }, 3000);
    }

    /* ── Summernote initialisieren ───────────────────────── */
    function initSummernote() {
        var ta = document.getElementById('body_html');
        if (!ta || window.$('#body_html').data('summernote')) return;
        window.$('#body_html').summernote({
            height: 400,
            minHeight: 200,
            toolbar: [
                ['style',  ['style']],
                ['font',   ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['para',   ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'hr']],
                ['misc',   ['undo', 'redo', 'fullscreen', 'codeview']],
            ],
            styleTags: ['p', 'h2', 'h3', 'blockquote'],
            disableDragAndDrop: true,
            callbacks: { onChange: scheduleAutoSave },
        });
    }

    /* ── Quill initialisieren ────────────────────────────── */
    function initQuill() {
        if (quillInstance) return;
        var container = document.getElementById('quill-editor');
        if (!container) return;
        quillInstance = new Quill(container, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'image', 'blockquote'],
                    ['clean'],
                ],
            },
        });
        // Vorhandenen Inhalt aus Textarea laden
        var ta = document.getElementById('body_html');
        if (ta && ta.value.trim()) {
            quillInstance.clipboard.dangerouslyPasteHTML(ta.value);
        }
        quillInstance.on('text-change', scheduleAutoSave);
    }

    /* ── Editor-Tab wechseln ─────────────────────────────── */
    function switchEditor(to) {
        if (to === activeEditor) return;

        var html = getEditorHtml();  // Inhalt aus aktuellem Editor sichern

        var snContainer = document.getElementById('summernote-container');
        var qlContainer = document.getElementById('quill-container');
        var btnSn = document.getElementById('tab-summernote');
        var btnQl = document.getElementById('tab-quill');

        if (to === 'quill') {
            // Summernote deaktivieren
            if (window.$ && window.$('#body_html').data('summernote')) {
                window.$('#body_html').summernote('destroy');
            }
            snContainer.style.display = 'none';
            qlContainer.style.display = 'block';
            btnSn.classList.remove('active');
            btnQl.classList.add('active');
            activeEditor = 'quill';
            initQuill();
            // Inhalt übernehmen
            if (quillInstance) quillInstance.clipboard.dangerouslyPasteHTML(html);
        } else {
            // Quill → Summernote
            qlContainer.style.display = 'none';
            snContainer.style.display = 'block';
            btnQl.classList.remove('active');
            btnSn.classList.add('active');
            activeEditor = 'summernote';
            // Inhalt in Textarea schreiben, dann Summernote neu initialisieren
            var ta = document.getElementById('body_html');
            if (ta) ta.value = html;
            initSummernote();
            if (window.$) window.$('#body_html').summernote('code', html);
        }
    }

    /* ── Beim Absenden Textarea synchronisieren ──────────── */
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('newsletter-form');
        if (form) {
            form.addEventListener('submit', syncTextarea);
        }

        // Tab-Buttons verdrahten
        var btnSn = document.getElementById('tab-summernote');
        var btnQl = document.getElementById('tab-quill');
        if (btnSn) btnSn.addEventListener('click', function () { switchEditor('summernote'); });
        if (btnQl) btnQl.addEventListener('click', function () { switchEditor('quill'); });

        // Summernote dynamisch laden (braucht window.$)
        var script = document.createElement('script');
        script.src = '/vendor/summernote/summernote-lite.min.js';
        script.onload = function () { initSummernote(); };
        document.head.appendChild(script);

        // Quill ist kein jQuery-Plugin → direkt laden
        var qlScript = document.createElement('script');
        qlScript.src = '/vendor/quill/quill.js';
        document.head.appendChild(qlScript);

        @if ($subscriberCount === 0)
        fetch('/admin/newsletter/abonnenten-anzahl', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); })
          .then(function (d) {
              var el = document.getElementById('subscriber-count');
              if (el) el.textContent = d.count;
          }).catch(function () {});
        @endif
    });
})();
</script>
