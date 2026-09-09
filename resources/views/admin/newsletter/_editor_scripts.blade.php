<link rel="stylesheet" href="/vendor/summernote/summernote-lite.min.css">
<style>
    .note-editable table { border-collapse: collapse; width: 100%; }
    .note-editable td,
    .note-editable th  { border: 1px solid #ccc; padding: 6px 10px; }
</style>
<script>
(function () {
    var saveStatus    = document.getElementById('editor-save-status');
    var autoSaveTimer = null;

    function showSaved() {
        if (!saveStatus) return;
        var now = new Date();
        saveStatus.textContent = 'Entwurf gespeichert '
            + now.getHours().toString().padStart(2, '0') + ':'
            + now.getMinutes().toString().padStart(2, '0');
    }

    function syncTextarea() {
        if (window.$ && window.$('#body_html').data('summernote')) {
            var ta = document.getElementById('body_html');
            if (ta) ta.value = window.$('#body_html').summernote('code');
        }
    }

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
                    var token = document.querySelector('meta[name="csrf-token"]');
                    if (!token) return;
                    for (var i = 0; i < files.length; i++) {
                        (function (file) {
                            var data = new FormData();
                            data.append('image', file);
                            data.append('_token', token.getAttribute('content'));
                            fetch('{{ route('admin.newsletter.upload-image') }}', {
                                method: 'POST',
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                body: data,
                            }).then(function (r) { return r.json(); })
                              .then(function (d) {
                                  if (d.url) window.$('#body_html').summernote('insertImage', d.url);
                              })
                              .catch(function () {});
                        })(files[i]);
                    }
                },
            },
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('newsletter-form');
        if (form) form.addEventListener('submit', syncTextarea);

        var script = document.createElement('script');
        script.src = '/vendor/summernote/summernote-lite.min.js';
        script.onload = function () { initSummernote(); };
        document.head.appendChild(script);

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
