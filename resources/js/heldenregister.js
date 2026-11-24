import Cropper from 'cropperjs';
import QRCode from 'qrcode';

/* ================================================================
   Heldenregister – Frontend-Logik (ARCH-001)
   Aus layouts/app.blade.php ausgelagert und als Vite-Entrypoint
   eingebunden. Globale Exports für Blade-Partials (Inline-Skripte):
   window.showToast, window.loadModalContent, window.loadStackContent,
   window.openPhotoCropper, window.clearSignaturePad,
   window.appModalUrl, window.appModal2Url.
   ================================================================ */

// ------------------------------------------------------------------
// Toast (Fomantic UI)
// ------------------------------------------------------------------
function escapeHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}

let _sessionExpired = false;

function showToast(message, type) {
    if (_sessionExpired) return;
    $('body').toast({
        class: type === 'error' ? 'error' : 'success',
        showIcon: type === 'error' ? 'exclamation circle' : 'check circle',
        message: escapeHtml(message),
        position: 'top right',
        displayTime: type === 'error' ? 7000 : 3000,
    });
}
window.showToast = showToast;

// ------------------------------------------------------------------
// Sitzung abgelaufen (SEC-01): globaler 419-Handler
// ------------------------------------------------------------------
function showSessionExpiredModal() {
    _sessionExpired = true;
    $('#session-expired-modal').modal({ closable: false, allowMultiple: true }).modal('show');
}

// 419 global abfangen: Modal zeigen, leere JSON-Antwort zurückgeben damit
// alle bestehenden .then()-Handler still durchlaufen (Toast ist durch _sessionExpired unterdrückt).
const _nativeFetch = window.fetch;
window.fetch = function () {
    return _nativeFetch.apply(this, arguments).then(function (resp) {
        if (resp.status === 419) {
            showSessionExpiredModal();
            return new Response('{}', { status: 419, headers: { 'Content-Type': 'application/json' } });
        }
        return resp;
    });
};

// ------------------------------------------------------------------
// Modal-Zustandsvariablen (auf window: Blade-Partials lesen direkt)
// ------------------------------------------------------------------
window.appModalUrl  = null;
window.appModal2Url = null;

// ------------------------------------------------------------------
// Verwaltungsbereich erkennen: Modals mit Verwaltungsinhalt bekommen die
// Klasse wr-admin-area (Rahmen in Signalfarbe, siehe app.css).
// ------------------------------------------------------------------
function isAdminAreaUrl(url) {
    let path;
    try { path = new URL(url, window.location.href).pathname; } catch (e) { return false; }
    return /^\/admin(\/|$)/.test(path)
        || /^\/adventures-manage(\/|$)/.test(path)
        || /^\/adventures\/create$/.test(path)
        || /^\/adventures\/[^/]+\/(manage|edit)$/.test(path);
}

// ------------------------------------------------------------------
// Haupt-Modal laden (AJAX-Partial -> #app-modal)
// ------------------------------------------------------------------
function loadModalContent(url, preserveTab) {
    const $content = $('#app-modal-content');
    const $header  = $('#app-modal-header');
    const $actions = $('#app-modal-actions');
    // Aktiven Tab vor dem Neuladen merken (z. B. nach EP-/Skill-Aktion).
    const prevTab = preserveTab
        ? $content.find('.menu .item.active[data-tab]').attr('data-tab')
        : null;
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
            // Verwaltungsinhalt kennzeichnen (Signalrahmen).
            $('#app-modal').toggleClass('wr-admin-area', isAdminAreaUrl(url));
            // Alle Modale mit Tabs: fixe Höhe, damit kein Springen beim Tab-Wechsel.
            $('#app-modal').toggleClass('modal-has-tabs', $content.find('.menu .item[data-tab]').length > 0);
            // ADV-17: Unterschriften-Pad aktivieren, falls vorhanden.
            if ($content.find('#signature-pad').length) initSignaturePad('signature-pad');
            // UI-05: Fomantic-Datepicker im geladenen Inhalt initialisieren.
            initFomanticCalendars($content);
            // PUB-05: QR-Code nach Modal-Load rendern.
            initQrCodes($content);
            // UX-901: Toggle-Checkboxen (z. B. Sichtbarkeit/Suchbarkeit im Helden-Detail).
            $content.find('.ui.checkbox').checkbox();
            // Durchsuchbare und Floating-Dropdowns im geladenen Modal-Inhalt initialisieren.
            $content.find('.ui.search.selection.dropdown, .ui.floating.dropdown').dropdown();
            // BOOK-12: Consent-Pflichtfeld + PLAY-15: Spieler-Prefill (nach .checkbox()!).
            updateHealthConsent($content);
            initBookingPlayerPrefill($content);
            $('#app-modal').modal('refresh');
            initBookingsTableSort($content);
            // UI-11: Fokus nach AJAX-Load ins Modal verschieben.
            requestAnimationFrame(function () {
                const first = $content[0].querySelector(
                    'button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'
                );
                if (first) first.focus();
            });
        })
        .catch(() => $content.html('<div class="ui error message">Konnte nicht geladen werden.</div>'));
}
window.loadModalContent = loadModalContent;

// UI-20: Tastatursteuerung für interaktive Zeilen/Karten (tabindex="0").
// Enter oder Space auf fokussierten [data-modal-url]/[data-modal-stack]/[data-navigate]-Elementen
// löst denselben Pfad wie ein Mausklick aus.
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const trigger = e.target.closest('[data-modal-url], [data-modal-stack], [data-navigate]');
    if (!trigger || trigger.tagName === 'A' || trigger.tagName === 'BUTTON') return;
    e.preventDefault();
    trigger.click();
});

// Direktnavigation für [data-navigate]-Elemente (z. B. klickbare Tabellenzeilen).
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-navigate]');
    if (!trigger) return;
    if (e.target.closest('a, button, form')) return;
    location.href = trigger.getAttribute('data-navigate');
});

// Klick auf ein Element mit data-modal-url -> Inhalt per AJAX ins Modal laden.
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-modal-url]');
    if (!trigger) return;
    e.preventDefault();
    window.appModalUrl = trigger.getAttribute('data-modal-url');
    $('#app-modal').modal({ autofocus: false, observeChanges: true }).modal('show');
    loadModalContent(window.appModalUrl);
});

// Unteransicht im Modal laden, OHNE appModalUrl zu überschreiben
// (z. B. Buchung bearbeiten -> nach dem Speichern zurück aufs Detail).
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-modal-subview]');
    if (!trigger) return;
    e.preventDefault();
    loadModalContent(trigger.getAttribute('data-modal-subview'));
});

// ------------------------------------------------------------------
// Gestapeltes Modal laden (ADV-22/PLAY-11: #app-modal-2 über #app-modal)
// ------------------------------------------------------------------
function loadStackContent(url, preserveTab) {
    const $content = $('#app-modal-2-content');
    const $header  = $('#app-modal-2-header');
    const $actions = $('#app-modal-2-actions');
    window.appModal2Url = url;
    const prevTab = preserveTab
        ? $content.find('.menu .item.active[data-tab]').attr('data-tab')
        : null;
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
            $actions.append('<button type="button" class="ui deny button">&#8592; Zurück</button>');
            // Tabs (z. B. Helden-Detail) im gestapelten Modal aktivieren.
            $content.find('.menu .item[data-tab]').tab();
            if (prevTab && $content.find('.menu .item[data-tab="' + prevTab + '"]').length) {
                $content.find('.menu .item[data-tab], .tab[data-tab]').removeClass('active');
                $content.find('[data-tab="' + prevTab + '"]').addClass('active');
            }
            $('#app-modal-2').toggleClass('modal-hero', $content.find('#skilltree').length > 0);
            // Verwaltung: gestapeltes Modal erbt die Kennzeichnung vom Verwaltungs-Modal darunter.
            $('#app-modal-2').toggleClass('wr-admin-area',
                isAdminAreaUrl(url) || $('#app-modal').hasClass('wr-admin-area'));
            // Alle Modale mit Tabs: fixe Höhe (gestapeltes Modal).
            $('#app-modal-2').toggleClass('modal-has-tabs', $content.find('.menu .item[data-tab]').length > 0);
            // UI-05: Fomantic-Datepicker im gestapelten Modal initialisieren.
            initFomanticCalendars($content);
            // PUB-05: QR-Code nach Stack-Modal-Load rendern.
            initQrCodes($content);
            // UX-901: Toggle-Checkboxen im gestapelten Modal initialisieren.
            $content.find('.ui.checkbox').checkbox();
            // Durchsuchbare Dropdowns im gestapelten Modal.
            $content.find('.ui.search.selection.dropdown').dropdown();
            // BOOK-12: Einwilligungs-Checkbox + PLAY-15: Spieler-Prefill (gestapeltes Modal).
            updateHealthConsent($content);
            initBookingPlayerPrefill($content);
            $('#app-modal-2').modal('refresh');
            initBookingsTableSort($content);
            // UI-11: Fokus nach AJAX-Load ins gestapelte Modal verschieben.
            requestAnimationFrame(function () {
                const first = $content[0].querySelector(
                    'button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])'
                );
                if (first) first.focus();
            });
        })
        .catch(() => $content.html('<div class="ui error message">Konnte nicht geladen werden.</div>'));
}
window.loadStackContent = loadStackContent;

document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-modal-stack]');
    if (!trigger) return;
    e.preventDefault();
    loadStackContent(trigger.getAttribute('data-modal-stack'));
    $('#app-modal-2').modal({ allowMultiple: true, autofocus: false }).modal('show');
});

// ------------------------------------------------------------------
// Unterschriften-Pad (ADV-17/19: Tablet/Stift/Maus via Pointer Events)
// Mehrfach-Init ist idempotent (Listener nur einmal registrieren).
// ------------------------------------------------------------------
function initSignaturePad(id) {
    const c = document.getElementById(id || 'signature-pad');
    if (!c) return;
    const ctx = c.getContext('2d');
    ctx.lineWidth = 2.5;
    ctx.lineCap   = 'round';
    ctx.lineJoin  = 'round';
    ctx.strokeStyle = '#1a1a1a';
    c.__clear = () => ctx.clearRect(0, 0, c.width, c.height);
    if (c.__init) return;
    c.__init = true;
    let drawing = false, last = null;
    const pos = (e) => {
        const r = c.getBoundingClientRect();
        return { x: (e.clientX - r.left) * (c.width / r.width), y: (e.clientY - r.top) * (c.height / r.height) };
    };
    c.addEventListener('pointerdown',  (e) => { drawing = true; last = pos(e); c.setPointerCapture(e.pointerId); });
    c.addEventListener('pointermove',  (e) => {
        if (!drawing) return;
        const p = pos(e);
        ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
        last = p;
    });
    c.addEventListener('pointerup',    () => { drawing = false; });
    c.addEventListener('pointerleave', () => { drawing = false; });
}

function clearSignaturePad(id) {
    const c = document.getElementById(id || 'signature-pad');
    if (c && c.__clear) c.__clear();
}
window.clearSignaturePad = clearSignaturePad;

// ------------------------------------------------------------------
// Gemeinsamer Modal-Aktions-Sender (ADV-19: Check-in, Abmelden)
// ------------------------------------------------------------------
function sendModalAction(url, method, fields, $modal, btn) {
    btn.classList.add('loading', 'disabled');
    const fd = new FormData();
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
    fd.append('_method', method);
    Object.entries(fields).forEach(([k, v]) => {
        if (Array.isArray(v)) v.forEach(x => fd.append(k + '[]', x));
        else fd.append(k, v);
    });
    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: fd,
    })
        .then(async (r) => {
            const d = await r.json().catch(() => ({}));
            if (r.ok) {
                showToast(d.message || 'Gespeichert.', 'success');
                $modal.modal('hide');
                // Modal-Kontext: Inhalt per AJAX neu laden. Verwaltungsseite: nur aktiven
                // Tab/offene Abschnitte aktualisieren (Tab + Scroll bleiben). Sonst: Reload.
                if (window.appModalUrl) loadModalContent(window.appModalUrl, true);
                else if (document.querySelector('.ui.top.attached.tabular.menu .item[data-tab]')) refreshManageTab();
                else window.location.reload();
            } else {
                const errs = d.errors ? Object.values(d.errors).flat().join('<br>') : '';
                showToast(errs || d.message || 'Aktion fehlgeschlagen.', 'error');
            }
        })
        .catch(() => showToast('Netzwerkfehler.', 'error'))
        .finally(() => btn.classList.remove('loading', 'disabled'));
}

// --- Check-in (Unterschrift) -------------------------------------
let checkinUrl = null;
let checkinIds = null; // Mehrfach-Check-in (Issue #11): Booking-IDs, sonst null

function openSignatureModal(url, label, ids) {
    checkinUrl = url;
    checkinIds = ids;
    document.getElementById('signature-modal-name').textContent = label;
    $('#signature-modal').modal({
        allowMultiple: true, autofocus: false,
        onVisible: () => { initSignaturePad('signature-pad'); clearSignaturePad('signature-pad'); },
    }).modal('show');
}

document.addEventListener('click', function (e) {
    const t = e.target.closest('.checkin-trigger');
    if (!t) return;
    e.preventDefault();
    openSignatureModal(t.getAttribute('data-url'), t.getAttribute('data-name') || '', null);
});
document.getElementById('signature-modal-save').addEventListener('click', function () {
    if (!checkinUrl) return;
    const fields = { signature: document.getElementById('signature-pad').toDataURL('image/png') };
    if (checkinIds) fields.booking_ids = checkinIds;
    sendModalAction(checkinUrl, 'PUT', fields, $('#signature-modal'), this);
});

// --- Abmeldung mit Grund ----------------------------------------
let deregisterUrl = null;
let deregisterIds = null; // Mehrfach-Abmeldung (Issue #11): Booking-IDs, sonst null

function openDeregisterModal(url, label, ids) {
    deregisterUrl = url;
    deregisterIds = ids;
    document.getElementById('deregister-modal-name').textContent = label;
    document.getElementById('deregister-reason').value = '';
    $('#deregister-modal').modal({ allowMultiple: true, autofocus: false }).modal('show');
}

document.addEventListener('click', function (e) {
    const t = e.target.closest('.deregister-trigger');
    if (!t) return;
    e.preventDefault();
    openDeregisterModal(t.getAttribute('data-url'), t.getAttribute('data-name') || '', null);
});
document.getElementById('deregister-modal-save').addEventListener('click', function () {
    const reason = document.getElementById('deregister-reason').value;
    if (!deregisterUrl || !reason) { showToast('Bitte einen Grund wählen.', 'error'); return; }
    const fields = { absence_reason: reason };
    if (deregisterIds) fields.booking_ids = deregisterIds;
    sendModalAction(deregisterUrl, 'PATCH', fields, $('#deregister-modal'), this);
});

// --- Mehrfachaktionen im Check-in (Issue #11) --------------------
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-checkin-bulk-action]');
    if (!btn) return;
    e.preventDefault();
    const wrap = btn.closest('[data-checkin-root]');
    const selected = Array.from(wrap.querySelectorAll('input[data-checkin-select]:checked'));
    if (selected.length === 0) return;

    const ids   = selected.map(cb => cb.value);
    const label = selected.length + ' Teilnehmer: ' + selected.map(cb => cb.dataset.name).join(', ');
    if (btn.dataset.checkinBulkAction === 'checkin') {
        openSignatureModal(btn.dataset.url, label, ids);
    } else {
        openDeregisterModal(btn.dataset.url, label, ids);
    }
});

// --- Storno-Rücknahme anfragen (BOOK-09) -------------------------
let reinstateRequestUrl = null;
document.addEventListener('click', function (e) {
    const t = e.target.closest('.reinstate-request-trigger');
    if (!t) return;
    e.preventDefault();
    reinstateRequestUrl = t.getAttribute('data-url');
    document.getElementById('reinstate-request-message').value = '';
    $('#reinstate-request-modal').modal({ allowMultiple: true, autofocus: false }).modal('show');
});
document.getElementById('reinstate-request-send').addEventListener('click', function () {
    const msg = document.getElementById('reinstate-request-message').value.trim();
    if (!reinstateRequestUrl || !msg) { showToast('Bitte eine Nachricht eingeben.', 'error'); return; }
    sendModalAction(reinstateRequestUrl, 'POST', { message: msg }, $('#reinstate-request-modal'), this);
});

// ------------------------------------------------------------------
// Anforderung des Auftraggebers (Issue #14): Popup mit Beschreibung und
// optionalem PDF-Download über dem Anmeldeformular.
// ------------------------------------------------------------------
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('[data-requirement-modal]');
    if (!trigger) return;
    e.preventDefault();
    document.getElementById('requirement-modal-header').textContent = trigger.dataset.title || '';
    // Bereinigtes HTML aus dem <template> der Anforderung übernehmen.
    const tpl = document.getElementById(trigger.dataset.requirementModal);
    const desc = document.getElementById('requirement-modal-description');
    desc.replaceChildren(tpl ? tpl.content.cloneNode(true) : document.createTextNode(''));
    const download = document.getElementById('requirement-modal-download');
    download.href = trigger.dataset.download || '#';
    download.style.display = trigger.dataset.download ? '' : 'none';
    $('#requirement-modal').modal({ allowMultiple: true, autofocus: false }).modal('show');
});

// ------------------------------------------------------------------
// Bestätigungs-Modal (UI-17): Capture-Phase Submit-Handler für
// data-confirm. Läuft vor dem AJAX-Bubble-Handler.
// ------------------------------------------------------------------
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.dataset.confirm || form.dataset.confirmReady) return;

    // data-confirm-unless-id / -val: Bestätigung überspringen wenn Bedingung erfüllt.
    const skipId  = form.dataset.confirmUnlessId;
    const skipVal = form.dataset.confirmUnlessVal;
    if (skipId && skipVal) {
        const el = document.getElementById(skipId);
        if (el && el.value === skipVal) return;
    }

    e.preventDefault();
    e.stopImmediatePropagation();
    const submitter = e.submitter || null;
    document.getElementById('confirm-modal-message').textContent = form.dataset.confirm;
    const okBtn = document.getElementById('confirm-modal-ok');
    function onOk() {
        okBtn.removeEventListener('click', onOk);
        $('#confirm-modal').modal('hide');
        form.dataset.confirmReady = '1';
        form.requestSubmit(submitter);
        delete form.dataset.confirmReady;
    }
    okBtn.addEventListener('click', onOk);
    $('#confirm-modal').off('hide.modal.confirm').on('hide.modal.confirm', function () {
        okBtn.removeEventListener('click', onOk);
    });
    $('#confirm-modal').modal({ allowMultiple: true, autofocus: false }).modal('show');
}, true);

// ------------------------------------------------------------------
// Seiteninhalt (nur <main>) per AJAX neu laden (kein Seiten-Reload)
// ------------------------------------------------------------------
function refreshPageContent() {
    return fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            const doc    = new DOMParser().parseFromString(html, 'text/html');
            const newMain = doc.getElementById('page-main');
            const curMain = document.getElementById('page-main');
            if (newMain && curMain) curMain.innerHTML = newMain.innerHTML;
        })
        .catch(() => window.location.reload());
}

// ------------------------------------------------------------------
// Modal-Formular-Submit per AJAX; Rückmeldung als Toast
// ------------------------------------------------------------------
document.addEventListener('submit', function (e) {
    const form    = e.target;
    const inStack = !! form.closest('#app-modal-2');
    if (! inStack && ! form.closest('#app-modal')) return;
    // Capture-Handler (data-confirm, UI-17) hat ggf. bereits verhindert.
    if (e.defaultPrevented) return;
    e.preventDefault();

    const submitBtn = e.submitter || form.querySelector('[type=submit]');
    submitBtn && submitBtn.classList.add('loading', 'disabled');

    // BOOK-12: Einwilligung Gesundheitsdaten – Fallback falls nativer required-Check umgangen wird.
    const _hcBox = form.querySelector('input[name="health_data_consent"]');
    if (_hcBox && !_hcBox.checkValidity()) {
        updateHealthConsent(form);
        _hcBox.reportValidity();
        submitBtn && submitBtn.classList.remove('loading', 'disabled');
        return;
    }

    fetch(form.action, {
        method:  'POST', // PUT/DELETE laufen via _method-Spoofing im FormData
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body:    new FormData(form),
    })
        .then(async (resp) => {
            const data = await resp.json().catch(() => ({}));
            if (resp.ok) {
                showToast(data.message || 'Gespeichert.', 'success');
                if (data.reload) {
                    // Modals schließen, dann nur <main> per AJAX aktualisieren.
                    $('#app-modal-2').modal('hide');
                    $('#app-modal').modal('hide');
                    refreshPageContent();
                } else if (inStack) {
                    if (form.hasAttribute('data-stack-close')) {
                        $('#app-modal-2').modal('hide');
                        if (window.appModalUrl) loadModalContent(window.appModalUrl, true);
                        else window.location.reload();
                    } else if (data.refresh_modal && window.appModal2Url) {
                        loadStackContent(window.appModal2Url, true);
                    } else {
                        $('#app-modal-2').modal('hide');
                    }
                } else if (data.refresh_modal && window.appModalUrl) {
                    loadModalContent(window.appModalUrl, true);
                } else {
                    $('#app-modal').modal('hide');
                }
            } else if (resp.status === 422) {
                const errors = data.errors ? Object.values(data.errors).flat() : [];
                showToast(errors.join(' · ') || data.message || 'Bitte Eingaben prüfen.', 'error');
            } else {
                showToast(data.message || 'Fehler beim Speichern.', 'error');
            }
        })
        .catch(() => showToast('Netzwerkfehler.', 'error'))
        .finally(() => submitBtn && submitBtn.classList.remove('loading', 'disabled'));
});

// ------------------------------------------------------------------
// BOOK-11: Vollseiten-Formulare (außerhalb Modalen) mit data-refresh-modal
// per AJAX abschicken und danach den aktiven Tab aktualisieren.
// ------------------------------------------------------------------
function refreshManageTab() {
    const activeItem = document.querySelector('.ui.top.attached.tabular.menu .item.active[data-tab]');
    const activeTabName = activeItem ? activeItem.getAttribute('data-tab') : null;

    return fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');

            // Tab-Labels einzeln aktualisieren (z. B. Anmeldungszähler), ohne die DOM-Knoten zu
            // ersetzen – so bleiben Fomantic-Tab-Handler aktiv und der aktive Tab bleibt stabil.
            doc.querySelectorAll('.ui.top.attached.tabular.menu .item[data-tab]').forEach(newItem => {
                const tabName = newItem.getAttribute('data-tab');
                const curItem = document.querySelector(
                    '.ui.top.attached.tabular.menu .item[data-tab="' + tabName + '"]'
                );
                if (curItem) curItem.innerHTML = newItem.innerHTML;
            });

            // Nur den aktiven Tab-Inhalt ersetzen
            if (activeTabName) {
                const sel = '.ui.bottom.attached.tab.segment[data-tab="' + activeTabName + '"]';
                const newSeg = doc.querySelector(sel);
                const curSeg = document.querySelector(sel);
                if (newSeg && curSeg) curSeg.innerHTML = newSeg.innerHTML;
            }

            // Dropdowns und Checkboxen im aktualisierten Segment neu initialisieren
            const activeSeg = document.querySelector('.ui.bottom.attached.tab.segment.active');
            if (activeSeg) {
                $(activeSeg).find('.ui.dropdown').dropdown();
                $(activeSeg).find('.ui.checkbox').checkbox();
                initBookingsTableSort(activeSeg);
            }

            // Mobile (Accordion): geöffnete Abschnitte aktualisieren, Zuordnung per Reihenfolge.
            // Abschnitte mit dem Event-Daten-Formular bleiben unangetastet (ungespeicherte Eingaben).
            const newSections = doc.querySelectorAll('details.mobile-accordion-section');
            document.querySelectorAll('details.mobile-accordion-section').forEach((curSec, i) => {
                if (!curSec.open || !newSections[i]) return;
                if (curSec.querySelector('#manage-adventure-form-mobile')) return;
                const curBody = curSec.querySelector(':scope > div');
                const newBody = newSections[i].querySelector(':scope > div');
                if (!curBody || !newBody) return;
                curBody.innerHTML = newBody.innerHTML;
                $(curBody).find('.ui.dropdown').dropdown();
                $(curBody).find('.ui.checkbox').checkbox();
                initBookingsTableSort(curBody);
            });
        })
        .catch(() => window.location.reload());
}

// ------------------------------------------------------------------
// Helden-Detail per AJAX neu laden (#skilltree ersetzen, Tabs erhalten)
// Wird nach EP-Buchungen und Skill-Aktionen auf /heroes/{id} genutzt.
// ------------------------------------------------------------------
function refreshHeroDetail() {
    const tree = document.getElementById('skilltree');
    if (!tree) { window.location.reload(); return; }

    const activeItem = tree.querySelector('.ui.top.attached.tabular.menu .item.active[data-tab]');
    const activeTab  = activeItem ? activeItem.getAttribute('data-tab') : null;

    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
            const doc     = new DOMParser().parseFromString(html, 'text/html');
            const newTree = doc.getElementById('skilltree');
            if (!newTree) { window.location.reload(); return; }

            // data-Attribute am Root-Element aktualisieren (z. B. data-balance)
            Array.from(newTree.attributes).forEach(attr => tree.setAttribute(attr.name, attr.value));
            tree.innerHTML = newTree.innerHTML;

            // Fomantic-Tabs neu initialisieren
            $(tree).find('.menu .item[data-tab]').tab();

            // Aktiven Tab wiederherstellen
            if (activeTab) {
                $(tree).find('[data-tab]').removeClass('active');
                $(tree).find(`.menu .item[data-tab="${activeTab}"]`).addClass('active');
                $(tree).find(`.ui.bottom.attached.tab.segment[data-tab="${activeTab}"]`).addClass('active');
            }

            // Weitere Fomantic-Komponenten neu initialisieren
            $(tree).find('.ui.dropdown').dropdown();
            $(tree).find('.ui.checkbox').checkbox();
            $(tree).find('.ui.search.selection.dropdown').dropdown();
        })
        .catch(() => window.location.reload());
}

document.addEventListener('submit', function (e) {
    const form = e.target;
    // Nur außerhalb von Modalen — dort greift der eigene AJAX-Handler (s. o.)
    if (form.closest('#app-modal') || form.closest('#app-modal-2')) return;
    if (!form.hasAttribute('data-refresh-modal')) return;
    if (e.defaultPrevented) return;
    e.preventDefault();

    const submitBtn = e.submitter || form.querySelector('[type=submit]');
    submitBtn?.classList.add('loading', 'disabled');

    fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(form),
    })
        .then(async (resp) => {
            const data = await resp.json().catch(() => ({}));
            if (resp.ok) {
                showToast(data.message || 'Gespeichert.', 'success');
                // Helden-Detailseite hat #skilltree → alles neu laden
                if (document.getElementById('skilltree')) refreshHeroDetail();
                // Seiten mit Reitern/Akkordeon (Abenteuer-Verwaltung): nur den aktiven Bereich
                else if (document.querySelector('.ui.top.attached.tabular.menu, details.mobile-accordion-section')) refreshManageTab();
                // Sonst (z. B. Fertigkeit bearbeiten als Vollseite): Seite neu laden
                else window.location.reload();
            } else if (resp.status === 422) {
                const errors = data.errors ? Object.values(data.errors).flat() : [];
                showToast(errors.join('<br>') || data.message || 'Bitte prüfen.', 'error');
            } else {
                showToast(data.message || 'Fehler.', 'error');
            }
        })
        .catch(() => showToast('Netzwerkfehler.', 'error'))
        .finally(() => submitBtn?.classList.remove('loading', 'disabled'));
});

// ------------------------------------------------------------------
// Inspektor (Issue #9): Slide-In mit Anmeldedetails in der Event-Verwaltung.
// Zeilenklick auf tr[data-inspector-url] öffnet das Panel; Formulare mit
// data-inspector-form werden per AJAX abgeschickt, danach werden Inspektor
// und Teilnehmerliste neu geladen.
// ------------------------------------------------------------------
let inspectorUrl = null;
let inspectorCloseTimer = null;
// „Neu …“ im Inspektor: nach dem Anlegen schließen, Fokus zurück zum Auslöser.
let inspectorCreateMode = false;
let inspectorOpener = null;

function inspectorEl() {
    return document.getElementById('booking-inspector');
}

function inspectorIsOpen() {
    const el = inspectorEl();
    return !!el && !el.hidden;
}

// Ist ein Modal offen, muss das Panel im Fomantic-Dimmer liegen, sonst verdeckt
// der Dimmer es. Auf der Vollseite bleibt es direkt im <body>.
function placeInspector() {
    const el = inspectorEl();
    const dimmer = document.querySelector('.ui.dimmer.modals.active');
    const target = dimmer || document.body;
    if (el.parentElement !== target) target.appendChild(el);
}

function findInspectorRow(url) {
    if (!url) return null;
    return Array.from(document.querySelectorAll('tr[data-inspector-url]'))
        .find(tr => tr.getAttribute('data-inspector-url') === url) || null;
}

function markInspectedRow(url) {
    document.querySelectorAll('tr.is-inspected').forEach(tr => tr.classList.remove('is-inspected'));
    findInspectorRow(url)?.classList.add('is-inspected');
}

function loadInspector(focusPanel) {
    const content = document.getElementById('booking-inspector-content');
    const title   = document.getElementById('booking-inspector-title');
    const url     = inspectorUrl;
    content.innerHTML = '<div class="ui active centered inline loader" style="display:block;margin:2rem auto"></div>';

    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => { if (!r.ok) throw new Error(r.status); return r.text(); })
        .then(html => {
            if (url !== inspectorUrl) return; // inzwischen andere Zeile geöffnet
            // jQuery .html() führt Inline-Skripte der geladenen Ansicht aus (wie im Modal).
            $(content).html(html);
            // Titel: eigener Inspektor-Titel oder – bei Modal-Ansichten – der Modal-Titel.
            const t = content.querySelector('[data-inspector-title]') || content.querySelector('[data-modal-title]');
            title.textContent = t ? t.textContent.trim() : 'Details';
            t?.remove();
            adaptModalViewForInspector(content);
            if (focusPanel) inspectorEl().querySelector('[data-inspector-close]')?.focus();
        })
        .catch(() => {
            content.innerHTML = '<div class="ui error message">Konnte nicht geladen werden.</div>';
        });
}

// Für das Modal gebaute Ansichten (z. B. Admin-Bearbeiten-Formulare) im Inspektor nutzbar machen:
// Modal-Aktionen sichtbar unten anzeigen, Formulare per AJAX im Inspektor speichern,
// Fomantic-Elemente initialisieren. Eigene Inspektor-Ansichten bleiben unverändert.
function adaptModalViewForInspector(content) {
    const actions = content.querySelector('[data-modal-actions]');
    if (actions) {
        const bar = document.createElement('div');
        bar.className = 'wr-inspector-actions';
        bar.innerHTML = actions.innerHTML;
        actions.remove();
        content.appendChild(bar);
    }
    content.querySelectorAll('form').forEach(form => {
        // Neu anlegen: nach Erfolg schließen (statt das leere Formular neu zu laden).
        if (inspectorCreateMode) form.setAttribute('data-inspector-close-on-success', '');
        if (form.hasAttribute('data-inspector-form')) return;
        form.removeAttribute('data-refresh-modal');
        form.removeAttribute('data-reload');
        form.setAttribute('data-inspector-form', '');
    });
    const $c = $(content);
    $c.find('.ui.dropdown').dropdown();
    $c.find('.ui.checkbox').checkbox();
    initFomanticCalendars($c);
}

function openInspector(url, opener) {
    const el = inspectorEl();
    if (!el) return;
    clearTimeout(inspectorCloseTimer);
    inspectorCreateMode = !!opener;
    inspectorOpener = opener || null;
    inspectorUrl = url;
    markInspectedRow(url);
    placeInspector();
    el.hidden = false;
    requestAnimationFrame(() => el.classList.add('is-open'));
    loadInspector(true);
}

function closeInspector(restoreFocus) {
    const el = inspectorEl();
    if (!inspectorIsOpen()) return;
    const row = findInspectorRow(inspectorUrl) || inspectorOpener;
    inspectorUrl = null;
    inspectorCreateMode = false;
    inspectorOpener = null;
    markInspectedRow(null);
    el.classList.remove('is-open');
    inspectorCloseTimer = setTimeout(() => { el.hidden = true; }, 200);
    // Fokus zurück auf die Zeile, von der aus der Inspektor geöffnet wurde.
    if (restoreFocus !== false) row?.focus();
}

// Teilnehmerliste dahinter aktualisieren (Modal oder Vollseite).
function refreshInspectorBackground() {
    const inModal = window.appModalUrl && $('#app-modal').hasClass('active');
    // Vollseite mit Reitern (Abenteuer-Verwaltung): nur den aktiven Reiter; sonst (Admin-Listen) den Seiteninhalt.
    const hasTabs = !! document.querySelector('.ui.top.attached.tabular.menu .item[data-tab]');
    const done = inModal ? loadModalContent(window.appModalUrl, true) : (hasTabs ? refreshManageTab() : refreshPageContent());
    return Promise.resolve(done).then(() => {
        markInspectedRow(inspectorUrl);
        // loadModalContent setzt den Fokus ins Modal – zurück in den Inspektor holen.
        requestAnimationFrame(() => {
            if (inspectorIsOpen()) inspectorEl().querySelector('[data-inspector-close]')?.focus();
        });
    });
}

document.addEventListener('click', function (e) {
    // „Neu …“-Button (data-inspector-new): leeres Formular im Inspektor statt im Modal.
    const newBtn = e.target.closest('[data-inspector-new]');
    if (newBtn && inspectorEl()) {
        e.preventDefault();
        openInspector(newBtn.getAttribute('data-inspector-new'), newBtn);
        return;
    }

    // Zeilenklick öffnet den Inspektor (Buttons/Links in der Zeile behalten ihre Funktion).
    const row = e.target.closest('tr[data-inspector-url]');
    if (row) {
        if (e.target.closest('a, button, form, input, select, textarea, label')) return;
        openInspector(row.getAttribute('data-inspector-url'));
        return;
    }

    if (e.target.closest('[data-inspector-close]')) {
        // „Bearbeiten“ öffnet zusätzlich das gestapelte Modal (eigener Handler).
        closeInspector(!e.target.closest('[data-modal-stack]'));
        return;
    }

    // Klick daneben schließt – außer in Dialogen/Toasts über dem Inspektor.
    if (inspectorIsOpen()
        && !inspectorEl().contains(e.target)
        && !e.target.closest('#confirm-modal, #app-modal-2, #session-expired-modal, .ui.toast-container')) {
        closeInspector(false);
    }
});

// Enter/Leertaste auf einer fokussierten Zeile öffnet den Inspektor.
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const row = e.target.closest('tr[data-inspector-url]');
    if (!row || e.target !== row) return;
    e.preventDefault();
    openInspector(row.getAttribute('data-inspector-url'));
});

// Esc schließt zuerst den Inspektor, nicht das Modal dahinter.
window.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape' || !inspectorIsOpen()) return;
    if ($('#confirm-modal').hasClass('active') || $('#app-modal-2').hasClass('active')) return;
    e.preventDefault();
    e.stopPropagation();
    closeInspector();
}, true);

// Maus-Events im Inspektor nicht an den Fomantic-Dimmer weitergeben,
// sonst schließt ein Klick ins Panel das Modal dahinter.
['mousedown', 'mouseup'].forEach(type => {
    inspectorEl()?.addEventListener(type, e => e.stopPropagation());
});

// Wird das Verwaltungs-Modal geschlossen, schließt auch der Inspektor.
const appModalEl = document.getElementById('app-modal');
if (appModalEl) {
    new MutationObserver(() => {
        if (!appModalEl.classList.contains('active') && inspectorIsOpen()
            && inspectorEl().parentElement !== document.body) {
            closeInspector(false);
        }
    }).observe(appModalEl, { attributes: true, attributeFilter: ['class'] });
}

// Aktionen im Inspektor per AJAX (data-confirm läuft vorher im Capture-Handler).
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.hasAttribute('data-inspector-form')) return;
    if (e.defaultPrevented) return;
    e.preventDefault();

    const submitBtn = e.submitter || form.querySelector('[type=submit]');
    submitBtn?.classList.add('loading', 'disabled');

    fetch(form.action, {
        method: 'POST', // PATCH/DELETE via _method-Spoofing im FormData
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: new FormData(form),
    })
        .then(async (resp) => {
            const data = await resp.json().catch(() => ({}));
            if (resp.ok) {
                showToast(data.message || 'Gespeichert.', 'success');
                // Gelöschte Einträge (z. B. stornierte Teamer-Anmeldung): Inspektor schließen statt neu laden.
                if (form.hasAttribute('data-inspector-close-on-success')) {
                    // Nach „Neu …“ Fokus zurück zum Button (auch wenn die Liste ihn neu rendert);
                    // nach Löschen gibt es die Zeile nicht mehr.
                    const createdFrom = inspectorCreateMode ? inspectorUrl : null;
                    closeInspector(!!createdFrom);
                    refreshInspectorBackground().then(() => {
                        if (!createdFrom) return;
                        Array.from(document.querySelectorAll('[data-inspector-new]'))
                            .find(b => b.getAttribute('data-inspector-new') === createdFrom)?.focus();
                    });
                    return;
                }
                refreshInspectorBackground();
                if (inspectorIsOpen()) loadInspector(false);
            } else if (resp.status === 422) {
                const errors = data.errors ? Object.values(data.errors).flat() : [];
                showToast(errors.join(' · ') || data.message || 'Bitte prüfen.', 'error');
            } else if (resp.status !== 419) {
                showToast(data.message || 'Fehler.', 'error');
            }
        })
        .catch(() => showToast('Netzwerkfehler.', 'error'))
        .finally(() => submitBtn?.classList.remove('loading', 'disabled'));
});

// ------------------------------------------------------------------
// Skilltree: Klick auf eine Fertigkeit -> Skill-Modal (HERO-14/16)
// ------------------------------------------------------------------
let skillBaseUrl = null, skillCurrentId = null, skillCanEdit = false, skillCanBypassLock = false;

document.addEventListener('click', function (e) {
    const node = e.target.closest('.skill-trigger');
    if (!node) return;
    e.preventDefault();
    const tree         = node.closest('#skilltree');
    skillBaseUrl       = tree ? tree.getAttribute('data-learn-url') : null;
    skillCanEdit       = tree ? tree.getAttribute('data-can-edit') === '1' : false;
    skillCanBypassLock = tree ? tree.getAttribute('data-can-bypass-lock') === '1' : false;
    skillCurrentId     = node.getAttribute('data-skill-id');
    const balance  = parseFloat(tree ? tree.getAttribute('data-balance') : '0') || 0;
    const cost     = parseFloat(node.getAttribute('data-skill-cost')) || 0;
    const learned  = node.getAttribute('data-skill-learned') === '1';

    $('#skill-modal-title').text(node.getAttribute('data-skill-name') || 'Fertigkeit');
    $('#skill-modal-desc').text(node.getAttribute('data-skill-desc') || '');

    // Issue #25: Perlenfarbe anzeigen (Name als Text, Farbe nur als gültiger Hex-Code).
    const perlName = node.getAttribute('data-skill-perl-name') || '';
    const perlCode = node.getAttribute('data-skill-perl-code') || '';
    $('#skill-modal-perl-name').text(perlName);
    document.getElementById('skill-modal-perl-dot').style.background =
        /^#[0-9a-fA-F]{3,8}$/.test(perlCode) ? perlCode : 'transparent';
    $('#skill-modal-perl').toggle(perlName !== '');

    const locked  = node.getAttribute('data-skill-locked') === '1';
    const prereqs = node.getAttribute('data-skill-prereqs') || '';

    const $accept = $('#skill-modal-accept');
    const $revoke = $('#skill-modal-revoke');
    const $warn   = $('#skill-modal-warn');
    $warn.hide();

    if (learned) {
        $('#skill-modal-meta').html('Bereits erlernt &nbsp;·&nbsp; Rückerstattung: <strong>' + cost + ' EP</strong>');
        $accept.hide();
        $revoke.toggle(skillCanEdit);
    } else if (locked) {
        // SKILL-06: Voraussetzungen nicht erfüllt.
        // Bürokrat darf trotzdem zuteilen (Sonderfall via data-can-bypass-lock).
        $('#skill-modal-meta').html('Kosten: <strong>' + cost + ' EP</strong>');
        $warn.text('Voraussetzungen fehlen: ' + prereqs + '.').show();
        $revoke.hide();
        if (skillCanBypassLock) {
            $accept.show().removeClass('disabled').text('Trotzdem zuteilen (Sonderfall)');
        } else {
            $accept.hide();
        }
    } else {
        const enough   = balance >= cost;
        const epColor  = enough ? 'text-green-700' : 'text-red-700';
        $('#skill-modal-meta').html('Kosten: <strong>' + cost + ' EP</strong> &nbsp;·&nbsp; Verfügbar: <span class="' + epColor + ' font-medium">' + balance + ' EP</span>');
        $warn.text('Nicht genug EP. EP werden durch Abenteuer-Teilnahme gutgeschrieben.').toggle(!enough);
        $revoke.hide();
        $accept.text('Fertigkeit erlernen').toggle(skillCanEdit).toggleClass('disabled', !enough);
    }

    $('#skill-modal').modal({ allowMultiple: true, autofocus: false }).modal('show');
});

function submitSkill(btn, url, method) {
    if (!url || !skillCurrentId || btn.classList.contains('disabled')) return;
    btn.classList.add('loading', 'disabled');
    const fd = new FormData();
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
    if (method)  fd.append('_method', method);
    if (!method) fd.append('skill_id', skillCurrentId);
    fetch(url, {
        method:  'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body:    fd,
    })
        .then(async (resp) => {
            const data = await resp.json().catch(() => ({}));
            if (resp.ok) {
                showToast(data.message || 'Gespeichert.', 'success');
                $('#skill-modal').modal('hide');
                if (window.appModalUrl) loadModalContent(window.appModalUrl, true);
                else refreshHeroDetail();
            } else {
                showToast(data.message || 'Aktion fehlgeschlagen.', 'error');
            }
        })
        .catch(() => showToast('Netzwerkfehler.', 'error'))
        .finally(() => btn.classList.remove('loading', 'disabled'));
}

document.getElementById('skill-modal-accept').addEventListener('click', function () {
    submitSkill(this, skillBaseUrl, null); // POST .../skills -> erlernen
});
document.getElementById('skill-modal-revoke').addEventListener('click', function () {
    submitSkill(this, skillBaseUrl + '/' + skillCurrentId, 'DELETE'); // DELETE .../skills/{id}
});

// ------------------------------------------------------------------
// Foto-Crop-Editor (HERO-22 / PLAY-11)
// Fomantic-UI registriert onVisible/onHidden bei erneutem .modal({...})
// auf einer bereits initialisierten Instanz NICHT neu. Daher: Modal
// einmalig mit persistenten Callbacks initialisieren; openPhotoCropper
// setzt nur Daten und ruft .modal('show') auf.
// ------------------------------------------------------------------
let photoCropper      = null;
let photoCropUrl      = null;
let photoCropCallback = null;
let photoCropAspect   = 1;

$('#photo-crop-modal').modal({
    allowMultiple: true,
    closable:      false,
    autofocus:     false,
    onVisible: function () {
        const img = document.getElementById('photo-crop-img');
        if (photoCropper) { photoCropper.destroy(); }
        photoCropper = new Cropper(img, {
            aspectRatio:  photoCropAspect,
            viewMode:     1,
            autoCropArea: 1,
            background:   false,
            responsive:   true,
        });
    },
    onHidden: function () {
        if (photoCropper) { photoCropper.destroy(); photoCropper = null; }
        document.getElementById('photo-crop-img').src = '';
    },
});

// opts: { aspectRatio: 1 } für Avatar-Quadrat, { aspectRatio: NaN } für freies Format (Galerie).
function openPhotoCropper(file, uploadUrl, onSuccess, opts) {
    if (file.size > 20 * 1024 * 1024) {
        showToast('Bild zu groß (max. 20 MB).', 'error');
        return;
    }
    photoCropUrl      = uploadUrl;
    photoCropCallback = onSuccess || null;
    photoCropAspect   = (opts && opts.aspectRatio !== undefined) ? opts.aspectRatio : 1;

    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('photo-crop-img').src = e.target.result;
        $('#photo-crop-modal').modal('show');
    };
    reader.readAsDataURL(file);
}
window.openPhotoCropper = openPhotoCropper;

document.getElementById('photo-crop-save-btn').addEventListener('click', function () {
    if (!photoCropper || !photoCropUrl) return;
    const btn = this;
    btn.classList.add('loading', 'disabled');
    // Für freies Seitenverhältnis (Galerie) max 1200×900; für Avatar 400×400.
    const canvasOpts = isNaN(photoCropAspect) ? { maxWidth: 1200, maxHeight: 900 } : { width: 400, height: 400 };
    photoCropper.getCroppedCanvas(canvasOpts).toBlob(function (blob) {
        const fd = new FormData();
        fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
        fd.append('image', blob, 'photo.jpg');
        fetch(photoCropUrl, {
            method:  'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body:    fd,
        })
            .then(r => r.json().catch(() => ({})))
            .then(data => {
                showToast(data.message || 'Foto gespeichert.', 'success');
                $('#photo-crop-modal').modal('hide');
                if (photoCropCallback) {
                    photoCropCallback();
                } else if (data.refresh_modal) {
                    if (window.appModal2Url) loadStackContent(window.appModal2Url, true);
                    else if (window.appModalUrl) loadModalContent(window.appModalUrl, true);
                }
            })
            .catch(() => showToast('Netzwerkfehler.', 'error'))
            .finally(() => btn.classList.remove('loading', 'disabled'));
    }, 'image/jpeg', 0.85);
});

// ------------------------------------------------------------------
// Getipptes Datum im deutschen Format in ein Date-Objekt umwandeln (Issue #13).
// Erlaubt: 15.03.2012 · 15.3.2012 · 15/03/2012 · 15-03-2012 · 15032012
// Mit Uhrzeit (withTime): zusätzlich „15.03.2012 18:30“ (ohne Uhrzeit → 00:00).
// Das Jahr muss vierstellig sein; unmögliche Daten (31.02.) ergeben null.
// ------------------------------------------------------------------
function parseGermanDate(text, withTime) {
    if (text instanceof Date) return text;

    const s = String(text ?? '').trim();
    if (!s) return null;

    const m = s.match(/^(\d{1,2})[./-](\d{1,2})[./-](\d{4})(?:[\s,T]+(\d{1,2})[:.](\d{2}))?$/)
           || s.match(/^(\d{2})(\d{2})(\d{4})(?:\s+(\d{1,2})[:.]?(\d{2}))?$/);
    if (!m) return null;

    const day = +m[1], month = +m[2], year = +m[3];
    const hasTime = m[4] !== undefined;
    if (hasTime && !withTime) return null;

    const hour = hasTime ? +m[4] : 0;
    const minute = hasTime ? +m[5] : 0;
    if (hour > 23 || minute > 59) return null;

    const d = new Date(year, month - 1, day, hour, minute);
    // Überlauf erkennen (z. B. 31.02. → 03.03.) – dann ist das Datum ungültig.
    if (d.getFullYear() !== year || d.getMonth() !== month - 1 || d.getDate() !== day) return null;

    return d;
}

// ------------------------------------------------------------------
// Fomantic Calendar (UI-05): x-date-picker-Komponenten initialisieren
// ------------------------------------------------------------------
function initFomanticCalendars($container) {
    $container.find('.ui.calendar[data-cal-type]').each(function () {
        const $cal = $(this);
        if ($cal.data('cal-ready')) return;
        $cal.data('cal-ready', true);

        const type       = $cal.data('cal-type');   // 'date' | 'datetime'
        const initialIso = $cal.data('initial') || '';
        const $hidden    = $cal.find('input[type=hidden]');
        const pad        = (n) => String(n).padStart(2, '0');

        const fmtDate     = (d) => pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '.' + d.getFullYear();
        const fmtDatetime = (d) => fmtDate(d) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());

        const deText = {
            days:         ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
            dayNamesShort:['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
            dayNames:     ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
            months:       ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
                           'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
            monthsShort:  ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun',
                           'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
            today: 'Heute', now: 'Jetzt', am: 'AM', pm: 'PM', weekNo: 'KW',
        };

        const opts = {
            type:         type === 'datetime' ? 'datetime' : 'date',
            monthFirst:   false,
            today:        true,
            closable:     true,
            firstDayOfWeek: 1,
            text:         deText,
            formatter:    type === 'datetime'
                ? { datetime: (d) => d ? fmtDatetime(d) : '', cellTime: 'HH:mm' }
                : { date:     (d) => d ? fmtDate(d)     : '' },
            // Tastatureingabe im deutschen Format (Issue #13)
            parser: {
                date: (text) => parseGermanDate(text, type === 'datetime'),
            },
            onChange: function (date) {
                if (!date) { $hidden.val(''); return; }
                const iso = type === 'datetime'
                    ? date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate())
                      + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes())
                    : date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
                $hidden.val(iso);
            },
        };

        $cal.calendar(opts);

        if (initialIso) {
            const parts = initialIso.split(/[-T:]/);
            const d = type === 'datetime'
                ? new Date(+parts[0], +parts[1] - 1, +parts[2], +(parts[3] || 0), +(parts[4] || 0))
                : new Date(+parts[0], +parts[1] - 1, +parts[2]);
            if (!isNaN(d)) $cal.calendar('set date', d, true, false);
        }
    });
}

// ------------------------------------------------------------------
// PUB-05: QR-Codes für [data-qr-url]-Canvas-Elemente rendern.
// ------------------------------------------------------------------
function initQrCodes(root) {
    (root instanceof $ ? root[0] : root).querySelectorAll('canvas[data-qr-url]').forEach(function (canvas) {
        QRCode.toCanvas(canvas, canvas.dataset.qrUrl, { width: 160, margin: 2, color: { dark: '#5a3a22' } });
    });
}

// ------------------------------------------------------------------
// BOOK-12: Einwilligungs-Checkbox visuell aktualisieren.
// Plain-HTML-Checkbox (kein Fomantic-Wrapper) → native Browser-Validierung.
// Wird nach Modal-Load, Spieler-Prefill und Texteingabe aufgerufen.
// ------------------------------------------------------------------
function updateHealthConsent(scope) {
    const root = scope instanceof $ ? scope[0] : scope;
    if (!root) return;

    const consentBox    = root.querySelector('input[name="health_data_consent"]');
    if (!consentBox) return;

    const allergienTA   = root.querySelector('textarea[name="allergien"]');
    const medikamenteTA = root.querySelector('textarea[name="medikamente"]');
    const consentField  = consentBox.closest('.field');
    const badge         = consentField ? consentField.querySelector('[data-hcr-badge]') : null;

    const hasData      = (allergienTA   && allergienTA.value.trim()   !== '')
                      || (medikamenteTA && medikamenteTA.value.trim() !== '');
    const needsConsent = hasData && !consentBox.checked;

    consentBox.required = hasData;

    // Roten Ring wenn Pflichtfeld noch nicht bestätigt.
    consentBox.classList.toggle('ring-2',        needsConsent);
    consentBox.classList.toggle('ring-red-400',  needsConsent);
    consentBox.classList.toggle('ring-offset-1', needsConsent);

    // Nativer Browser-Tooltip mit deutschem Hinweis.
    consentBox.setCustomValidity(
        needsConsent
            ? 'Bitte bestätige die Einwilligung zur Speicherung der Gesundheitsdaten.'
            : ''
    );

    if (badge) badge.hidden = !hasData;
}

// Document-level event delegation: greift unabhängig vom Modal-Load-Timing.
document.addEventListener('input', function (e) {
    const name = e.target.name;
    if (name !== 'allergien' && name !== 'medikamente') return;
    const scope = e.target.closest('#app-modal-2-content, #app-modal-content')
               || e.target.closest('form')
               || document.body;
    updateHealthConsent(scope);
});

// Roten Ring sofort entfernen wenn Checkbox angehakt wird.
document.addEventListener('change', function (e) {
    if (e.target.name !== 'health_data_consent') return;
    const scope = e.target.closest('#app-modal-2-content, #app-modal-content')
               || e.target.closest('form')
               || document.body;
    updateHealthConsent(scope);
});

// ------------------------------------------------------------------
// PLAY-15: Spieler-Auswahl → Gesundheitsdaten vorausfüllen.
// Wird von loadModalContent/loadStackContent aufgerufen.
// ------------------------------------------------------------------
function initBookingPlayerPrefill(container) {
    const root = container instanceof $ ? container[0] : container;
    if (!root) return;

    const sel = root.querySelector('#booking-player-select');
    if (!sel) return;

    // Issue #8: Admin-Anmeldung lädt zusätzlich die Betreuer des Spielers.
    if (sel.dataset.contextUrl) {
        sel.addEventListener('change', function () { loadBookingPlayerContext(root, sel); });
        return;
    }

    sel.addEventListener('change', function () {
        const playerId    = this.value;
        const urlTemplate = this.dataset.prefillUrl;
        if (!playerId || !urlTemplate) return;

        fetch(urlTemplate.replace('__ID__', playerId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
        })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;
                const allergienTA   = root.querySelector('textarea[name="allergien"]');
                const medikamenteTA = root.querySelector('textarea[name="medikamente"]');
                if (allergienTA   && allergienTA.value.trim()   === '') allergienTA.value   = data.allergien   ?? '';
                if (medikamenteTA && medikamenteTA.value.trim() === '') medikamenteTA.value = data.medikamente ?? '';
                updateHealthConsent(root);
            })
            .catch(() => {});
    });
}

// ------------------------------------------------------------------
// Admin-Anmeldung (Issue #8): Betreuer des gewählten Spielers ins Auswahlfeld
// laden; Notfallnummer vom Betreuer übernehmen; Allergien/Medikamente vorbefüllen.
// ------------------------------------------------------------------
function loadBookingPlayerContext(root, sel) {
    const ctSelect = root.querySelector('#booking-caretaker-select');
    const ctHint   = root.querySelector('#booking-caretaker-hint');
    const phone    = root.querySelector('input[name="kontakt_telefon"]');
    const defaultHint = 'Erhält die Anmeldebestätigung und erscheint als Kontakt in der Teilnehmerliste.';
    if (!ctSelect) return;

    // Optionen per DOM aufbauen (Namen/Telefon stammen aus Nutzerprofilen → kein innerHTML).
    const makeOption = (value, text, phoneVal) => {
        const o = document.createElement('option');
        o.value = value;
        o.textContent = text;
        if (phoneVal !== undefined) o.dataset.phone = phoneVal;
        return o;
    };
    const setOptions = (options, disabled) => {
        ctSelect.replaceChildren(...options);
        ctSelect.disabled = disabled;
    };

    // Telefonnummer nur überschreiben, wenn das Feld leer ist oder noch die
    // automatisch übernommene Nummer enthält (manuelle Eingaben bleiben erhalten).
    const applyPhone = (value) => {
        if (!phone) return;
        if (phone.value.trim() === '' || phone.dataset.autofilled === phone.value) {
            phone.value = value || '';
            phone.dataset.autofilled = phone.value;
        }
    };

    // Spielerwechsel: automatisch übernommene Nummer des vorherigen Betreuers entfernen.
    applyPhone('');

    if (!sel.value) {
        setOptions([makeOption('', '— zuerst Spieler wählen —')], true);
        return;
    }
    setOptions([makeOption('', 'Lädt …')], true);

    fetch(sel.dataset.contextUrl.replace('__ID__', sel.value), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin',
    })
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(data => {
            const caretakers = data.caretakers || [];
            if (caretakers.length === 0) {
                setOptions([makeOption('', '— kein Betreuer hinterlegt —')], true);
                if (ctHint) {
                    ctHint.textContent = data.player_email
                        ? 'Kein Betreuer hinterlegt – die Bestätigung geht an die Spieler-E-Mail ' + data.player_email + '.'
                        : 'Kein Betreuer und keine Spieler-E-Mail hinterlegt – es wird keine Bestätigung versendet.';
                }
                applyPhone('');
            } else {
                const opts = caretakers.map(c =>
                    makeOption(String(c.id), c.name + (c.email ? ' · ' + c.email : ''), c.phone || ''));
                if (caretakers.length > 1) opts.unshift(makeOption('', '— wählen —'));
                setOptions(opts, false);
                ctSelect.required = true;
                if (ctHint) ctHint.textContent = defaultHint;
                if (caretakers.length === 1) applyPhone(caretakers[0].phone);
            }

            const allergienTA   = root.querySelector('textarea[name="allergien"]');
            const medikamenteTA = root.querySelector('textarea[name="medikamente"]');
            if (allergienTA   && allergienTA.value.trim()   === '') allergienTA.value   = data.allergien   ?? '';
            if (medikamenteTA && medikamenteTA.value.trim() === '') medikamenteTA.value = data.medikamente ?? '';
            updateHealthConsent(root);
        })
        .catch(() => {
            setOptions([makeOption('', 'Betreuer konnten nicht geladen werden')], true);
        });

    // Betreuerwechsel → dessen Telefonnummer übernehmen (nur einmal registrieren).
    if (!ctSelect.__phoneInit) {
        ctSelect.__phoneInit = true;
        ctSelect.addEventListener('change', function () {
            const opt = ctSelect.selectedOptions[0];
            applyPhone(opt ? opt.dataset.phone : '');
        });
    }
}

// ------------------------------------------------------------------
// UX-120: Anmeldungs-Tabelle sortierbar machen.
// Wird nach jedem AJAX-Tab-Refresh und Modal-Load neu aufgerufen,
// weil innerHTML alle Event-Listener und table.__sortInit verwirft.
// ------------------------------------------------------------------
function initBookingsTableSort(root) {
    const el     = (root instanceof $) ? root[0] : (root || document);
    // Issue #10: Check-in-Liste wird an denselben Stellen initialisiert.
    initCheckinTable(el);
    const tables = el.querySelectorAll ? el.querySelectorAll('[data-bookings-table]') : [];
    tables.forEach(function (table) {
        if (table.__sortInit) return;
        table.__sortInit = true;

        let sortCol = 'created';
        let sortAsc  = false;
        let filterVal = '';
        const numeric = new Set(['age', 'paid', 'created', 'status']);

        function applyFilter() {
            const tbody = table.querySelector('tbody');
            Array.from(tbody.querySelectorAll('tr')).forEach(function (r) {
                const nameCell = r.querySelector('[data-col="name"]');
                if (!nameCell) return;
                const name = (nameCell.dataset.sortVal || '').toLowerCase();
                r.style.display = name.includes(filterVal) ? '' : 'none';
            });
        }

        function applySort(col, asc) {
            const tbody = table.querySelector('tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr')).filter(function (r) {
                return r.querySelector('[data-col]');
            });

            rows.sort(function (a, b) {
                const av = (a.querySelector('[data-col="' + col + '"]') || {}).dataset?.sortVal ?? '';
                const bv = (b.querySelector('[data-col="' + col + '"]') || {}).dataset?.sortVal ?? '';
                const cmp = numeric.has(col)
                    ? parseFloat(av || 0) - parseFloat(bv || 0)
                    : av.localeCompare(bv, 'de', { sensitivity: 'base' });
                return asc ? cmp : -cmp;
            });

            rows.forEach(function (r) { tbody.appendChild(r); });
            applyFilter();

            table.querySelectorAll('[data-sort-col]').forEach(function (th) {
                const icon = th.querySelector('[data-sort-icon]');
                if (!icon) return;
                if (th.dataset.sortCol === col) {
                    icon.className = 'sort ' + (asc ? 'up' : 'down') + ' icon text-xs ml-0.5';
                } else {
                    icon.className = 'sort icon text-stone-300 text-xs ml-0.5';
                }
            });

            sortCol = col;
            sortAsc  = asc;
        }

        table.querySelectorAll('[data-sort-col]').forEach(function (th) {
            th.addEventListener('click', function () {
                const col = th.dataset.sortCol;
                applySort(col, col === sortCol ? !sortAsc : col !== 'created');
            });
        });

        const searchInput = table.querySelector('[data-bookings-search]');
        if (searchInput) {
            searchInput.addEventListener('input', function (e) {
                filterVal = (e.target.value || '').trim().toLowerCase();
                applyFilter();
            });
        }

        applySort('created', false);
    });
}

// ------------------------------------------------------------------
// Check-in-Liste (Issue #10): Suche ab dem 3. Zeichen + sortierbare Spalten.
// Suchbegriff und Sortierung werden pro Abenteuer gemerkt, damit sie nach
// Check-in/Auschecken (Reiter wird neu geladen) erhalten bleiben.
// ------------------------------------------------------------------
const checkinTableState = new Map();
const CHECKIN_MIN_CHARS = 3;

function initCheckinTable(root) {
    const scope = root && root.querySelectorAll ? root : document;
    const roots = Array.from(scope.querySelectorAll('[data-checkin-root]'));
    if (scope.matches && scope.matches('[data-checkin-root]')) roots.push(scope);

    roots.forEach(function (wrap) {
        if (wrap.__checkinInit) return;
        wrap.__checkinInit = true;

        const table = wrap.querySelector('[data-checkin-table]');
        const input = wrap.querySelector('[data-checkin-search]');
        const count = wrap.querySelector('[data-checkin-count]');
        if (!table) return;

        const tbody    = table.querySelector('tbody');
        const emptyRow = tbody.querySelector('[data-checkin-empty]');
        const rows     = Array.from(tbody.querySelectorAll('tr[data-search]'));
        const key      = wrap.dataset.stateKey || '';
        const state    = Object.assign({ col: 'lastname', asc: true, query: '' }, checkinTableState.get(key));

        const cellVal = (row, col) => (row.querySelector('[data-col="' + col + '"]') || {}).dataset?.sortVal ?? '';

        function applyFilter() {
            const q = state.query.trim().toLowerCase();
            const active = q.length >= CHECKIN_MIN_CHARS;
            let visible = 0;
            rows.forEach(function (r) {
                const hit = !active || r.dataset.search.includes(q);
                // style.display statt hidden: Mobile-Karten setzen display per CSS
                r.style.display = hit ? '' : 'none';
                if (hit) visible++;
            });
            if (emptyRow) emptyRow.style.display = visible > 0 ? 'none' : '';
            // Ausgeblendete Zeilen abwählen – Mehrfachaktionen nie auf unsichtbare Einträge.
            rows.forEach(function (r) {
                const cb = r.querySelector('input[data-checkin-select]');
                if (cb && r.style.display === 'none') cb.checked = false;
            });
            updateSelection();
            if (count) {
                count.textContent = active
                    ? visible + ' von ' + rows.length + ' Teilnehmern'
                    : (q.length > 0 ? 'Noch ' + (CHECKIN_MIN_CHARS - q.length) + ' Zeichen …' : '');
            }
        }

        function applySort() {
            const th = table.querySelector('[data-sort-col="' + state.col + '"]');
            const numeric = th?.dataset.sortType === 'num';
            const sorted = rows.slice().sort(function (a, b) {
                const av = cellVal(a, state.col), bv = cellVal(b, state.col);
                let cmp = numeric
                    ? (parseFloat(av) || 0) - (parseFloat(bv) || 0)
                    : av.localeCompare(bv, 'de', { sensitivity: 'base' });
                // Gleichstand: nach Nachname, dann Vorname
                if (cmp === 0) {
                    cmp = cellVal(a, 'lastname').localeCompare(cellVal(b, 'lastname'), 'de', { sensitivity: 'base' })
                       || cellVal(a, 'firstname').localeCompare(cellVal(b, 'firstname'), 'de', { sensitivity: 'base' });
                    return cmp;
                }
                return state.asc ? cmp : -cmp;
            });
            sorted.forEach(r => tbody.appendChild(r));
            if (emptyRow) tbody.appendChild(emptyRow);

            table.querySelectorAll('[data-sort-col]').forEach(function (h) {
                const icon = h.querySelector('[data-sort-icon]');
                const isActive = h.dataset.sortCol === state.col;
                if (icon) {
                    icon.className = isActive
                        ? 'sort ' + (state.asc ? 'up' : 'down') + ' icon text-xs ml-0.5'
                        : 'sort icon text-stone-300 text-xs ml-0.5';
                }
                if (isActive) h.setAttribute('aria-sort', state.asc ? 'ascending' : 'descending');
                else h.removeAttribute('aria-sort');
            });
        }

        function save() {
            if (key) checkinTableState.set(key, { col: state.col, asc: state.asc, query: state.query });
        }

        function sortBy(col) {
            state.asc = col === state.col ? !state.asc : true;
            state.col = col;
            applySort();
            save();
        }

        table.querySelectorAll('[data-sort-col]').forEach(function (th) {
            th.addEventListener('click', () => sortBy(th.dataset.sortCol));
            th.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.preventDefault();
                sortBy(th.dataset.sortCol);
            });
        });

        if (input) {
            input.value = state.query;
            input.addEventListener('input', function () {
                state.query = input.value;
                applyFilter();
                save();
            });
        }

        // --- Mehrfachauswahl (Issue #11) ---
        function updateSelection() {
            const checked = tbody.querySelectorAll('input[data-checkin-select]:checked').length;
            if (bulkBar) bulkBar.hidden = checked === 0;
            if (bulkCount) bulkCount.textContent = checked === 1 ? '1 Teilnehmer ausgewählt' : checked + ' Teilnehmer ausgewählt';
        }

        const bulkBar   = wrap.querySelector('[data-checkin-bulkbar]');
        const bulkCount = wrap.querySelector('[data-checkin-bulk-count]');

        tbody.addEventListener('change', function (e) {
            if (e.target.matches('input[data-checkin-select]')) updateSelection();
        });
        wrap.querySelector('[data-checkin-bulk-clear]')?.addEventListener('click', function () {
            rows.forEach(r => { const cb = r.querySelector('input[data-checkin-select]'); if (cb) cb.checked = false; });
            updateSelection();
        });

        applySort();
        applyFilter();
    });
}

// ------------------------------------------------------------------
// Siegel-Input: Kleinbuchstaben automatisch in Großbuchstaben umwandeln
// ------------------------------------------------------------------
document.addEventListener('input', function (e) {
    if (! e.target.classList.contains('siegel-input')) return;
    const input = e.target;
    const pos = input.selectionStart;
    input.value = input.value.toUpperCase();
    input.setSelectionRange(pos, pos);
});

// ------------------------------------------------------------------
// DOMContentLoaded: Kalender + Flash-Toast (UI-09)
// Module laufen nach DOM-Parse, aber vor DOMContentLoaded.
// ------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    // UI-05: Standalone-Seiten (Held/Spieler anlegen, Formular direkt im DOM).
    initFomanticCalendars($(document));
    // PUB-05: QR-Codes auf der Vollseite (z. B. heroes.show ohne Modal).
    initQrCodes(document);
    // UX-120: Sortierbare Anmeldungs-Tabelle auf der Verwaltungs-Vollseite.
    initBookingsTableSort(document);

    // UI-09: Session-Flash als Toast (Vollseiten-Redirects).
    // Daten kommen aus data-Attributen von #app-flash (gesetzt via Blade in app.blade.php).
    const flash = document.getElementById('app-flash');
    if (flash) {
        if (flash.dataset.status) showToast(flash.dataset.status, 'success');
        if (flash.dataset.error)  showToast(flash.dataset.error,  'error');
    }

    // ARCH-006: Service-Worker registrieren (PWA).
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {
            // SW-Registrierung ist optional – kein Hard-Error bei Fehlschlag.
        });
    }
});

// ------------------------------------------------------------------
// Issue #28: EP buchen – Standard-EP der gewählten Buchungsart vorbelegen.
// Delegiert, da das Heldenbuch auch per AJAX in Modals geladen wird.
// Ohne Standardwert wird nur ein zuvor automatisch gesetzter Wert geleert;
// selbst eingetippte Werte bleiben dann stehen.
// ------------------------------------------------------------------
document.addEventListener('change', function (e) {
    const select = e.target;
    if (!(select instanceof HTMLSelectElement) || !select.matches('[data-ep-type-select]')) return;
    const input = document.getElementById(select.dataset.epCountTarget);
    if (!input) return;

    const def = select.selectedOptions[0]?.dataset.defaultEp || '';
    if (def !== '') {
        input.value = def;
        input.dataset.epAuto = def;
    } else {
        if (input.dataset.epAuto !== undefined && input.value === input.dataset.epAuto) input.value = '';
        delete input.dataset.epAuto;
    }
});
