<script src="/vendor/tinymce/tinymce.min.js"></script>
<script>
(function () {
    var saveStatus = document.getElementById('editor-save-status');
    var autoSaveTimer = null;

    function showSaved() {
        if (!saveStatus) return;
        var now = new Date();
        saveStatus.textContent = 'Entwurf gespeichert ' +
            now.getHours().toString().padStart(2, '0') + ':' +
            now.getMinutes().toString().padStart(2, '0');
    }

    function scheduleAutoSave(editor) {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function () {
            editor.save(); // Sync TinyMCE-Inhalt zurück in das Textarea
            var form = document.getElementById('newsletter-form');
            if (!form) return;

            var data = new FormData(form);
            var action = form.getAttribute('action');
            var method = form.querySelector('input[name="_method"]');

            // Auto-Save via Fetch (PATCH/POST)
            fetch(action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data,
            }).then(function (resp) {
                if (resp.ok || resp.redirected) showSaved();
            }).catch(function () {});
        }, 3000);
    }

    tinymce.init({
        selector: '#body_html',
        base_url: '/vendor/tinymce',
        suffix: '.min',
        // language: 'de', // Sprachdatei nicht gebündelt – englische UI ist akzeptabel
        height: 480,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: 'lists link image autoresize',
        toolbar: 'undo redo | bold italic underline | h2 h3 | bullist numlist | link image | removeformat',
        block_formats: 'Absatz=p; Überschrift 2=h2; Überschrift 3=h3',
        // Nur sichere Tags zulassen – server-seitig wird zusätzlich mews/purifier eingesetzt
        valid_elements: 'h2,h3,p[style],br,strong,em,u,s,ul,ol,li,a[href|title|target],img[src|alt|width|height|style],blockquote,span[style]',
        valid_styles: {
            '*': 'color,background-color,text-align,font-size,font-weight,font-style,text-decoration',
        },
        image_uploadtab: false,
        // Bilder nur als URL einfügen, kein Base64-Upload
        automatic_uploads: false,
        file_picker_types: '',
        setup: function (editor) {
            editor.on('input change', function () {
                scheduleAutoSave(editor);
            });
        },
        init_instance_callback: function (editor) {
            // Subscriber-Zähler setzen (nur create.blade.php braucht JS-Fetch)
            @if ($subscriberCount === 0)
            fetch('/admin/newsletter/abonnenten-anzahl', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var el = document.getElementById('subscriber-count');
                    if (el) el.textContent = d.count;
                }).catch(function () {});
            @endif
        },
    });
})();
</script>
