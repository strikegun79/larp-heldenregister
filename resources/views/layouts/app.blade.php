<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Heldenregister') }}</title>

        <link rel="icon" href="/favicon.ico">

        <!-- Fonts: Body + mittelalterliche Überschriften (wie im Legacy) -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cinzel+Decorative:wght@700&family=Uncial+Antiqua&family=MedievalSharp&family=EB+Garamond:wght@400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('css/heldenregister.css') }}">
        <!-- Fomantic UI (wie im Legacy) -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.css">
        <!-- Cropper.js für Avatar-Editor (PLAY-11) -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">

        <!-- Scripts (Tailwind/Breeze danach, damit das Theme gewinnt) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>body { font-family: 'EB Garamond', serif; }</style>
    </head>
    <body class="antialiased text-stone-800">
        <div class="min-h-screen flex flex-col bg-parchment">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white/50 border-b-2 border-[#5a3a22]/40 shadow-sm">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <!-- Footer (Vereinskontakt, wie im Legacy) -->
            <footer class="mt-8 border-t-2 border-[#5a3a22]/40 bg-black/10 text-waldritter">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid gap-6 sm:grid-cols-3 text-sm">
                    <div>
                        <strong>Waldritter-Gießen e.V. – Heldenregister</strong><br>
                        Entwicklung &amp; Support<br>
                        <a class="hover:underline" href="mailto:richy.mueller@waldritter-giessen.de">richy.mueller@waldritter-giessen.de</a>
                    </div>
                    <div>
                        <strong>Vereinskontakt</strong><br>
                        <a class="hover:underline" href="mailto:info@waldritter-giessen.de">info@waldritter-giessen.de</a>
                    </div>
                    <div class="sm:text-right">
                        &copy; {{ date('Y') }} Waldritter-Gießen e.V.
                    </div>
                </div>
            </footer>
        </div>

        <!-- Gemeinsames Fomantic-Modal: Header + scrollender Inhalt + Footer.
             Inhalt wird per AJAX aus [data-modal-url] geladen; das Partial liefert
             [data-modal-title] (Header) und optional [data-modal-actions] (Footer). -->
        <div class="ui modal" id="app-modal">
            {{-- Kein Schließ-Icon (PLAY-11): Schließen über den „Schließen"-Button im Footer. --}}
            <div class="header" id="app-modal-header"></div>
            <div class="scrolling content" id="app-modal-content"></div>
            <div class="actions" id="app-modal-actions"></div>
        </div>

        <!-- Gestapeltes Modal (ADV-22): öffnet über #app-modal (z. B. Anmeldung,
             Gast-Anmeldung, Anmeldung bearbeiten). Kein Schließ-Icon; nur über
             Speichern oder „Schließen" zu schließen. -->
        <div class="ui modal" id="app-modal-2">
            <div class="header" id="app-modal-2-header"></div>
            <div class="scrolling content" id="app-modal-2-content"></div>
            <div class="actions" id="app-modal-2-actions"></div>
        </div>

        <!-- Foto-Crop-Modal: Helden-Foto zuschneiden (HERO-22) -->
        <div class="ui modal" id="photo-crop-modal">
            <div class="header">Foto zuschneiden</div>
            <div class="scrolling content">
                <div style="max-height:400px; overflow:hidden; background:#111; border-radius:.4rem;">
                    <img id="photo-crop-img" src="" alt="Zuschnitt" style="display:block; max-width:100%;">
                </div>
                <p class="text-sm text-stone-700 mt-2">Rahmen verschieben und anpassen, dann „Übernehmen" klicken.</p>
            </div>
            <div class="actions">
                <div class="ui deny button">Abbrechen</div>
                <button type="button" id="photo-crop-save-btn" class="ui primary button">
                    <i class="check icon"></i> Übernehmen
                </button>
            </div>
        </div>

        <!-- Bestätigungs-Modal: Fertigkeit erlernen/aberkennen (HERO-14/16) -->
        <div class="ui small modal" id="skill-modal">
            <div class="header" id="skill-modal-title"></div>
            <div class="content">
                <p id="skill-modal-desc" class="text-stone-700"></p>
                <p id="skill-modal-meta"></p>
                <p id="skill-modal-warn" class="text-red-600" style="display:none">Nicht genug EP für diese Fertigkeit.</p>
            </div>
            <div class="actions">
                <div class="ui deny button">Schließen</div>
                <button type="button" class="ui positive button" id="skill-modal-accept">Fertigkeit errungen</button>
                <button type="button" class="ui negative button" id="skill-modal-revoke">Fertigkeit aberkennen</button>
            </div>
        </div>

        <!-- Unterschrift + Check-in als Multimodal (ADV-19) -->
        <div class="ui modal" id="signature-modal">
            <div class="header">Unterschrift &amp; Check-in</div>
            <div class="content">
                <p id="signature-modal-name" class="font-semibold"></p>
                <p class="text-sm text-stone-700">Mit Tablet &amp; Stift im Feld unterschreiben.</p>
                <canvas id="signature-pad" width="600" height="240"
                        style="border:2px solid #5a3a22; border-radius:.4rem; touch-action:none; background:#fff; max-width:100%; width:600px; height:240px;"></canvas>
            </div>
            <div class="actions">
                <div class="ui deny button">Abbrechen</div>
                <button type="button" class="ui button" onclick="clearSignaturePad()">Löschen</button>
                <button type="button" class="ui primary button" id="signature-modal-save">Check-in bestätigen</button>
            </div>
        </div>

        <!-- Abmeldung mit Grund als Multimodal (ADV-19) -->
        <div class="ui small modal" id="deregister-modal">
            <div class="header">Teilnehmer abmelden</div>
            <div class="content">
                <p id="deregister-modal-name" class="font-semibold"></p>
                <div class="ui form">
                    <div class="field">
                        <label>Grund der Abmeldung</label>
                        <select id="deregister-reason">
                            <option value="">— wählen —</option>
                            @foreach (\App\Models\Booking::ABSENCE_REASONS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="actions">
                <div class="ui deny button">Abbrechen</div>
                <button type="button" class="ui primary button" id="deregister-modal-save">Abmelden</button>
            </div>
        </div>

        <!-- jQuery + Fomantic JS -->
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
        <!-- Cropper.js für Avatar-Editor (PLAY-11) -->
        <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
        <script>
            let appModalUrl = null;

            function loadModalContent(url, preserveTab) {
                const $content = $('#app-modal-content');
                const $header = $('#app-modal-header');
                const $actions = $('#app-modal-actions');
                // Aktiven Tab vor dem Neuladen merken (z. B. nach EP-/Skill-Aktion).
                const prevTab = preserveTab ? $content.find('.menu .item.active[data-tab]').attr('data-tab') : null;
                $header.empty();
                $actions.empty();
                $content.html('<div class="ui active centered inline loader" style="display:block;margin:2rem auto"></div>');
                return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        $content.html(html);
                        // Titel ins Header, Aktionen in den Footer hochziehen (Konvention).
                        const $title = $content.find('[data-modal-title]').first();
                        $header.html($title.length ? $title.html() : '');
                        $title.remove();
                        const $partActions = $content.find('[data-modal-actions]').first();
                        $actions.html($partActions.length ? $partActions.html() : '');
                        $partActions.remove();
                        // Standard-Schließen-Button immer anbieten.
                        $actions.append('<div class="ui deny button">Schließen</div>');
                        // Fomantic-Tabs im Modal aktivieren (z. B. Detail-Tabs / Fertigkeitsbaum).
                        $content.find('.menu .item[data-tab]').tab();
                        // Zuvor aktiven Tab wiederherstellen, falls vorhanden.
                        if (prevTab && $content.find('.menu .item[data-tab="' + prevTab + '"]').length) {
                            $content.find('.menu .item[data-tab], .tab[data-tab]').removeClass('active');
                            $content.find('[data-tab="' + prevTab + '"]').addClass('active');
                        }
                        // HERO-19: Helden-Detail bekommt eine feste Modal-Größe.
                        $('#app-modal').toggleClass('modal-hero', $content.find('#skilltree').length > 0);
                        // ADV-19: Verwaltungs-Modal (3 Tabs) auf feste Größe.
                        $('#app-modal').toggleClass('modal-event', $content.find('[data-tab="checkin"]').length > 0);
                        // ADV-17: Unterschriften-Pad aktivieren, falls vorhanden.
                        if ($content.find('#signature-pad').length) initSignaturePad('signature-pad');
                        $('#app-modal').modal('refresh');
                    })
                    .catch(() => $content.html('<div class="ui error message">Konnte nicht geladen werden.</div>'));
            }

            // Klick auf ein Element mit data-modal-url -> Inhalt per AJAX ins Modal laden.
            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-modal-url]');
                if (!trigger) return;
                e.preventDefault();
                appModalUrl = trigger.getAttribute('data-modal-url');
                $('#app-modal').modal({ autofocus: false, observeChanges: true }).modal('show');
                loadModalContent(appModalUrl);
            });

            // Unteransicht im Modal laden, OHNE appModalUrl zu überschreiben
            // (z. B. Buchung bearbeiten -> nach dem Speichern zurück aufs Detail).
            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-modal-subview]');
                if (!trigger) return;
                e.preventDefault();
                loadModalContent(trigger.getAttribute('data-modal-subview'));
            });

            // ADV-22/PLAY-11: Inhalt in das gestapelte Modal (#app-modal-2) über dem
            // Haupt-Modal laden. Kein Schließ-Icon, nur Speichern/Schließen.
            let appModal2Url = null;

            function loadStackContent(url, preserveTab) {
                const $content = $('#app-modal-2-content');
                const $header = $('#app-modal-2-header');
                const $actions = $('#app-modal-2-actions');
                appModal2Url = url;
                const prevTab = preserveTab ? $content.find('.menu .item.active[data-tab]').attr('data-tab') : null;
                $header.empty();
                $actions.empty();
                $content.html('<div class="ui active centered inline loader" style="display:block;margin:2rem auto"></div>');
                return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text())
                    .then(html => {
                        $content.html(html);
                        const $title = $content.find('[data-modal-title]').first();
                        $header.html($title.length ? $title.html() : '');
                        $title.remove();
                        const $partActions = $content.find('[data-modal-actions]').first();
                        $actions.html($partActions.length ? $partActions.html() : '');
                        $partActions.remove();
                        $actions.append('<div class="ui deny button">Schließen</div>');
                        // Tabs (z. B. Helden-Detail) im gestapelten Modal aktivieren.
                        $content.find('.menu .item[data-tab]').tab();
                        if (prevTab && $content.find('.menu .item[data-tab="' + prevTab + '"]').length) {
                            $content.find('.menu .item[data-tab], .tab[data-tab]').removeClass('active');
                            $content.find('[data-tab="' + prevTab + '"]').addClass('active');
                        }
                        $('#app-modal-2').toggleClass('modal-hero', $content.find('#skilltree').length > 0);
                        $('#app-modal-2').modal('refresh');
                    })
                    .catch(() => $content.html('<div class="ui error message">Konnte nicht geladen werden.</div>'));
            }

            document.addEventListener('click', function (e) {
                const trigger = e.target.closest('[data-modal-stack]');
                if (!trigger) return;
                e.preventDefault();
                loadStackContent(trigger.getAttribute('data-modal-stack'));
                $('#app-modal-2').modal({ allowMultiple: true, closable: false, autofocus: false }).modal('show');
            });

            // ADV-17/19: einfaches Unterschriften-Pad (Tablet/Stift/Maus via Pointer Events).
            // Mehrfach-Init ist idempotent (Listener nur einmal registrieren).
            function initSignaturePad(id) {
                const c = document.getElementById(id || 'signature-pad');
                if (!c) return;
                const ctx = c.getContext('2d');
                ctx.lineWidth = 2.5;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = '#1a1a1a';
                c.__clear = () => ctx.clearRect(0, 0, c.width, c.height);
                if (c.__init) return;
                c.__init = true;
                let drawing = false, last = null;
                const pos = (e) => {
                    const r = c.getBoundingClientRect();
                    return { x: (e.clientX - r.left) * (c.width / r.width), y: (e.clientY - r.top) * (c.height / r.height) };
                };
                c.addEventListener('pointerdown', (e) => { drawing = true; last = pos(e); c.setPointerCapture(e.pointerId); });
                c.addEventListener('pointermove', (e) => {
                    if (!drawing) return;
                    const p = pos(e);
                    ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
                    last = p;
                });
                c.addEventListener('pointerup', () => { drawing = false; });
                c.addEventListener('pointerleave', () => { drawing = false; });
            }
            function clearSignaturePad(id) {
                const c = document.getElementById(id || 'signature-pad');
                if (c && c.__clear) c.__clear();
            }

            // ADV-19: Check-in (Unterschrift) und Abmelden (Grund) als Multimodale.
            // Gemeinsamer Sender: schickt FormData und lädt das Verwaltungs-Modal neu.
            function sendModalAction(url, method, fields, $modal, btn) {
                btn.classList.add('loading', 'disabled');
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                fd.append('_method', method);
                Object.entries(fields).forEach(([k, v]) => fd.append(k, v));
                fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd })
                    .then(async (r) => {
                        const d = await r.json().catch(() => ({}));
                        if (r.ok) {
                            showToast(d.message || 'Gespeichert.', 'success');
                            $modal.modal('hide');
                            if (appModalUrl) loadModalContent(appModalUrl, true);
                        } else {
                            const errs = d.errors ? Object.values(d.errors).flat().join('<br>') : '';
                            showToast(errs || d.message || 'Aktion fehlgeschlagen.', 'error');
                        }
                    })
                    .catch(() => showToast('Netzwerkfehler.', 'error'))
                    .finally(() => btn.classList.remove('loading', 'disabled'));
            }

            let checkinUrl = null;
            document.addEventListener('click', function (e) {
                const t = e.target.closest('.checkin-trigger');
                if (!t) return;
                e.preventDefault();
                checkinUrl = t.getAttribute('data-url');
                document.getElementById('signature-modal-name').textContent = t.getAttribute('data-name') || '';
                $('#signature-modal').modal({
                    allowMultiple: true, autofocus: false,
                    onVisible: () => { initSignaturePad('signature-pad'); clearSignaturePad('signature-pad'); },
                }).modal('show');
            });
            document.getElementById('signature-modal-save').addEventListener('click', function () {
                if (!checkinUrl) return;
                const data = document.getElementById('signature-pad').toDataURL('image/png');
                sendModalAction(checkinUrl, 'PUT', { signature: data }, $('#signature-modal'), this);
            });

            let deregisterUrl = null;
            document.addEventListener('click', function (e) {
                const t = e.target.closest('.deregister-trigger');
                if (!t) return;
                e.preventDefault();
                deregisterUrl = t.getAttribute('data-url');
                document.getElementById('deregister-modal-name').textContent = t.getAttribute('data-name') || '';
                document.getElementById('deregister-reason').value = '';
                $('#deregister-modal').modal({ allowMultiple: true, autofocus: false }).modal('show');
            });
            document.getElementById('deregister-modal-save').addEventListener('click', function () {
                const reason = document.getElementById('deregister-reason').value;
                if (!deregisterUrl || !reason) { showToast('Bitte einen Grund wählen.', 'error'); return; }
                sendModalAction(deregisterUrl, 'PATCH', { absence_reason: reason }, $('#deregister-modal'), this);
            });

            function showToast(message, type) {
                $('body').toast({
                    class: type === 'error' ? 'error' : 'success',
                    showIcon: type === 'error' ? 'exclamation circle' : 'check circle',
                    message: message,
                    position: 'top right',
                    displayTime: type === 'error' ? 7000 : 3000,
                });
            }

            // Formulare innerhalb des Modals per AJAX absenden; Rückmeldung als Toast.
            document.addEventListener('submit', function (e) {
                const form = e.target;
                const inStack = !! form.closest('#app-modal-2');
                if (! inStack && ! form.closest('#app-modal')) return;
                // Inline-onsubmit (z. B. confirm() == false) respektieren.
                if (e.defaultPrevented) return;
                e.preventDefault();

                const submitBtn = e.submitter || form.querySelector('[type=submit]');
                submitBtn && submitBtn.classList.add('loading', 'disabled');

                fetch(form.action, {
                    method: 'POST', // PUT/DELETE laufen via _method-Spoofing im FormData
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: new FormData(form),
                })
                    .then(async (resp) => {
                        const data = await resp.json().catch(() => ({}));
                        if (resp.ok) {
                            showToast(data.message || 'Gespeichert.', 'success');
                            if (data.reload) {
                                setTimeout(() => window.location.reload(), 700);
                            } else if (inStack) {
                                // Einmalige Formulare (z. B. Anmeldung) schließen den Stack
                                // und aktualisieren das Haupt-Modal; sonst Stack selbst neu laden.
                                if (form.hasAttribute('data-stack-close')) {
                                    $('#app-modal-2').modal('hide');
                                    if (appModalUrl) loadModalContent(appModalUrl, true);
                                } else if (data.refresh_modal && appModal2Url) {
                                    loadStackContent(appModal2Url, true);
                                } else {
                                    $('#app-modal-2').modal('hide');
                                }
                            } else if (data.refresh_modal && appModalUrl) {
                                loadModalContent(appModalUrl, true); // Modal neu laden, aktiven Tab erhalten
                            } else {
                                $('#app-modal').modal('hide');
                            }
                        } else if (resp.status === 422) {
                            const errors = data.errors ? Object.values(data.errors).flat() : [];
                            showToast(errors.join('<br>') || data.message || 'Bitte Eingaben prüfen.', 'error');
                        } else {
                            showToast(data.message || 'Fehler beim Speichern.', 'error');
                        }
                    })
                    .catch(() => showToast('Netzwerkfehler.', 'error'))
                    .finally(() => submitBtn && submitBtn.classList.remove('loading', 'disabled'));
            });

            // Skilltree: Klick auf eine Fertigkeit (Marker oder Liste) -> Modal (HERO-14/16).
            let skillBaseUrl = null, skillCurrentId = null, skillCanEdit = false;

            document.addEventListener('click', function (e) {
                const node = e.target.closest('.skill-trigger');
                if (!node) return;
                e.preventDefault();
                const tree = node.closest('#skilltree');
                skillBaseUrl = tree ? tree.getAttribute('data-learn-url') : null;
                skillCanEdit = tree ? tree.getAttribute('data-can-edit') === '1' : false;
                skillCurrentId = node.getAttribute('data-skill-id');
                const balance = parseFloat(tree ? tree.getAttribute('data-balance') : '0') || 0;
                const cost = parseFloat(node.getAttribute('data-skill-cost')) || 0;
                const learned = node.getAttribute('data-skill-learned') === '1';

                $('#skill-modal-title').text(node.getAttribute('data-skill-name') || 'Fertigkeit');
                $('#skill-modal-desc').text(node.getAttribute('data-skill-desc') || '');

                const $accept = $('#skill-modal-accept');
                const $revoke = $('#skill-modal-revoke');
                $('#skill-modal-warn').hide();

                if (learned) {
                    // Bereits erlernt -> aberkennen (EP-Rückerstattung).
                    $('#skill-modal-meta').text('Bereits erlernt · Rückerstattung bei Aberkennung: ' + cost + ' EP');
                    $accept.hide();
                    $revoke.toggle(skillCanEdit);
                } else {
                    const enough = balance >= cost;
                    $('#skill-modal-meta').text('Kosten: ' + cost + ' EP · Verfügbar: ' + balance + ' EP');
                    $('#skill-modal-warn').toggle(!enough);
                    $revoke.hide();
                    $accept.toggle(skillCanEdit).toggleClass('disabled', !enough);
                }

                $('#skill-modal').modal({ allowMultiple: true, autofocus: false }).modal('show');
            });

            // Gemeinsamer Helfer fürs Lernen/Aberkennen.
            function submitSkill(btn, url, method) {
                if (!url || !skillCurrentId || btn.classList.contains('disabled')) return;
                btn.classList.add('loading', 'disabled');
                const fd = new FormData();
                fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                if (method) fd.append('_method', method);
                if (!method) fd.append('skill_id', skillCurrentId);
                fetch(url, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: fd,
                })
                    .then(async (resp) => {
                        const data = await resp.json().catch(() => ({}));
                        if (resp.ok) {
                            showToast(data.message || 'Gespeichert.', 'success');
                            $('#skill-modal').modal('hide');
                            if (appModalUrl) loadModalContent(appModalUrl, true);
                        } else {
                            showToast(data.message || 'Aktion fehlgeschlagen.', 'error');
                        }
                    })
                    .catch(() => showToast('Netzwerkfehler.', 'error'))
                    .finally(() => btn.classList.remove('loading', 'disabled'));
            }

            document.getElementById('skill-modal-accept').addEventListener('click', function () {
                submitSkill(this, skillBaseUrl, null); // POST .../skills  -> erlernen
            });
            document.getElementById('skill-modal-revoke').addEventListener('click', function () {
                submitSkill(this, skillBaseUrl + '/' + skillCurrentId, 'DELETE'); // DELETE .../skills/{id}
            });
        </script>

        <script>
            // Gemeinsamer Foto-Crop-Editor im gestapelten Modal (HERO-22 / PLAY-11).
            var photoCropper      = null;
            var photoCropUrl      = null;
            var photoCropCallback = null;

            /**
             * Öffnet #photo-crop-modal mit Cropper.js.
             * Wartet auf FileReader, öffnet Modal erst dann; Cropper wird in
             * onVisible initialisiert (erst dann hat das Element Dimensionen).
             */
            function openPhotoCropper(file, uploadUrl, onSuccess) {
                if (file.size > 20 * 1024 * 1024) {
                    showToast('Bild zu groß (max. 20 MB).', 'error');
                    return;
                }
                photoCropUrl      = uploadUrl;
                photoCropCallback = onSuccess || null;

                var reader = new FileReader();
                reader.onload = function (e) {
                    var img = document.getElementById('photo-crop-img');
                    img.src = e.target.result;
                    // Modal erst öffnen wenn src gesetzt → onVisible kann Cropper korrekt messen
                    $('#photo-crop-modal').modal({
                        allowMultiple: true,
                        closable:      false,
                        autofocus:     false,
                        onVisible: function () {
                            if (photoCropper) { photoCropper.destroy(); }
                            photoCropper = new Cropper(img, {
                                aspectRatio:  1,
                                viewMode:     1,
                                autoCropArea: 1,
                                background:   false,
                                responsive:   true,
                            });
                        },
                        onHidden: function () {
                            if (photoCropper) { photoCropper.destroy(); photoCropper = null; }
                            img.src = '';
                        },
                    }).modal('show');
                };
                reader.readAsDataURL(file);
            }

            document.getElementById('photo-crop-save-btn').addEventListener('click', function () {
                if (!photoCropper || !photoCropUrl) return;
                var btn = this;
                btn.classList.add('loading', 'disabled');
                photoCropper.getCroppedCanvas({ width: 400, height: 400 }).toBlob(function (blob) {
                    var fd = new FormData();
                    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
                    fd.append('image', blob, 'photo.jpg');
                    fetch(photoCropUrl, {
                        method:  'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body:    fd,
                    })
                    .then(function (r) { return r.json().catch(function () { return {}; }); })
                    .then(function (data) {
                        showToast(data.message || 'Foto gespeichert.', 'success');
                        $('#photo-crop-modal').modal('hide');
                        if (photoCropCallback) photoCropCallback();
                    })
                    .catch(function () { showToast('Netzwerkfehler.', 'error'); })
                    .finally(function () { btn.classList.remove('loading', 'disabled'); });
                }, 'image/jpeg', 0.85);
            });
        </script>

        @stack('scripts')
    </body>
</html>
