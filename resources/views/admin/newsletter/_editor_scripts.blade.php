<link rel="stylesheet" href="/vendor/summernote/summernote-lite.min.css">
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

    function scheduleAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function () {
            var form = document.getElementById('newsletter-form');
            if (!form) return;
            var data = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data,
            }).then(function (r) { if (r.ok || r.redirected) showSaved(); })
              .catch(function () {});
        }, 3000);
    }

    function initEditor() {
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
            callbacks: {
                onChange: function () { scheduleAutoSave(); },
            },
        });

        @if ($subscriberCount === 0)
        fetch('/admin/newsletter/abonnenten-anzahl', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); })
          .then(function (d) {
              var el = document.getElementById('subscriber-count');
              if (el) el.textContent = d.count;
          }).catch(function () {});
        @endif
    }

    // vendor.js (type="module") setzt window.$ erst nach DOMContentLoaded.
    // Summernote braucht window.jQuery beim Laden → dynamisch laden, nachdem window.$ gesetzt ist.
    document.addEventListener('DOMContentLoaded', function () {
        var script = document.createElement('script');
        script.src = '/vendor/summernote/summernote-lite.min.js';
        script.onload = function () { initEditor(); };
        document.head.appendChild(script);
    });
})();
</script>
