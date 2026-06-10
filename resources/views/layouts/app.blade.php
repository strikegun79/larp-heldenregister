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
            <i class="close icon"></i>
            <div class="header" id="app-modal-header"></div>
            <div class="scrolling content" id="app-modal-content"></div>
            <div class="actions" id="app-modal-actions"></div>
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

        <!-- jQuery + Fomantic JS -->
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fomantic-ui@2.9.3/dist/semantic.min.js"></script>
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
                if (!form.closest('#app-modal')) return;
                e.preventDefault();

                const submitBtn = form.querySelector('[type=submit]');
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

        @stack('scripts')
    </body>
</html>
