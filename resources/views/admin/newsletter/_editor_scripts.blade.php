<link rel="stylesheet" href="/vendor/summernote/summernote-lite.min.css">
<link rel="stylesheet" href="/vendor/quill/quill.snow.css">
<style>
    /* Quill-Container ans Summernote-Look angleichen */
    #quill-container .ql-container { font-size: 1rem; min-height: 200px; }
    #quill-container .ql-editor    { min-height: 370px; font-family: inherit; }
    /* Summernote Tabellen-Grundstil */
    .note-editable table { border-collapse: collapse; width: 100%; }
    .note-editable td,
    .note-editable th  { border: 1px solid #ccc; padding: 6px 10px; }
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

    /* ── Bild-Upload an den Server ──────────────────────── */
    function uploadImage(file) {
        var token = document.querySelector('meta[name="csrf-token"]');
        if (!token) return;
        var data = new FormData();
        data.append('image', file);
        data.append('_token', token.getAttribute('content'));
        fetch('{{ route('admin.newsletter.upload-image') }}', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: data,
        }).then(function (r) { return r.json(); })
          .then(function (d) {
              if (d.url) {
                  window.$('#body_html').summernote('insertImage', d.url);
              }
          })
          .catch(function () {});
    }

    /* ── Summernote initialisieren ───────────────────────── */
    function initSummernote() {
        var ta = document.getElementById('body_html');
        if (!ta || window.$('#body_html').data('summernote')) return;
        window.$('#body_html').summernote({
            height: 420,
            minHeight: 200,
            toolbar: [
                ['style',  ['style']],
                ['font',   ['fontsize', 'bold', 'italic', 'underline', 'strikethrough', 'clear']],
                ['color',  ['forecolor', 'backcolor']],
                ['para',   ['ul', 'ol', 'paragraph']],
                ['table',  ['table']],
                ['insert', ['link', 'picture', 'hr']],
                ['misc',   ['undo', 'redo', 'fullscreen', 'codeview']],
            ],
            styleTags: ['p', 'h2', 'h3', 'blockquote'],
            fontSizes: ['10', '12', '14', '16', '18', '20', '24', '28', '32'],
            disableDragAndDrop: false,
            callbacks: {
                onChange: scheduleAutoSave,
                onImageUpload: function (files) {
                    for (var i = 0; i < files.length; i++) {
                        uploadImage(files[i]);
                    }
                },
            },
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
        var ta = document.getElementById('body_html');
        if (ta && ta.value.trim()) {
            quillInstance.clipboard.dangerouslyPasteHTML(ta.value);
        }
        quillInstance.on('text-change', scheduleAutoSave);
    }

    /* ── Editor-Tab wechseln ─────────────────────────────── */
    function switchEditor(to) {
        if (to === activeEditor) return;

        var html = getEditorHtml();

        var snContainer = document.getElementById('summernote-container');
        var qlContainer = document.getElementById('quill-container');
        var btnSn = document.getElementById('tab-summernote');
        var btnQl = document.getElementById('tab-quill');

        if (to === 'quill') {
            if (window.$ && window.$('#body_html').data('summernote')) {
                window.$('#body_html').summernote('destroy');
            }
            snContainer.style.display = 'none';
            qlContainer.style.display = 'block';
            btnSn.classList.remove('active');
            btnQl.classList.add('active');
            activeEditor = 'quill';
            initQuill();
            if (quillInstance) quillInstance.clipboard.dangerouslyPasteHTML(html);
        } else {
            qlContainer.style.display = 'none';
            snContainer.style.display = 'block';
            btnQl.classList.remove('active');
            btnSn.classList.add('active');
            activeEditor = 'summernote';
            var ta = document.getElementById('body_html');
            if (ta) ta.value = html;
            initSummernote();
            if (window.$) window.$('#body_html').summernote('code', html);
        }
    }

    /* ── DOMContentLoaded ───────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('newsletter-form');
        if (form) {
            form.addEventListener('submit', syncTextarea);
        }

        var btnSn = document.getElementById('tab-summernote');
        var btnQl = document.getElementById('tab-quill');
        if (btnSn) btnSn.addEventListener('click', function () { switchEditor('summernote'); });
        if (btnQl) btnQl.addEventListener('click', function () { switchEditor('quill'); });

        // Summernote dynamisch laden (braucht window.$)
        var script = document.createElement('script');
        script.src = '/vendor/summernote/summernote-lite.min.js';
        script.onload = function () { initSummernote(); };
        document.head.appendChild(script);

        // Quill direkt laden (kein jQuery nötig)
        var qlScript = document.createElement('script');
        qlScript.src = '/vendor/quill/quill.js';
        document.head.appendChild(qlScript);

        @if ($subscriberCount === 0)
        fetch('{{ route('admin.newsletter.subscriber-count') }}', {
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
