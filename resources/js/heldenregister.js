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
function showToast(message, type) {
    $('body').toast({
        class: type === 'error' ? 'error' : 'success',
        showIcon: type === 'error' ? 'exclamation circle' : 'check circle',
        message: message,
        position: 'top right',
        displayTime: type === 'error' ? 7000 : 3000,
    });
}
window.showToast = showToast;

// ------------------------------------------------------------------
// Modal-Zustandsvariablen (auf window: Blade-Partials lesen direkt)
// ------------------------------------------------------------------
window.appModalUrl  = null;
window.appModal2Url = null;

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
            // Standard-Schließen-Button immer anbieten.
            $actions.append('<button type="button" class="ui deny button">Schließen</button>');
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
            // UI-05: Fomantic-Datepicker im geladenen Inhalt initialisieren.
            initFomanticCalendars($content);
            $('#app-modal').modal('refresh');
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
            // UI-05: Fomantic-Datepicker im gestapelten Modal initialisieren.
            initFomanticCalendars($content);
            $('#app-modal-2').modal('refresh');
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
    Object.entries(fields).forEach(([k, v]) => fd.append(k, v));
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
                // Modal-Kontext: Inhalt per AJAX neu laden. Vollseite: Standard-Reload.
                if (window.appModalUrl) loadModalContent(window.appModalUrl, true);
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

// --- Abmeldung mit Grund ----------------------------------------
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
    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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
                showToast(errors.join('<br>') || data.message || 'Bitte Eingaben prüfen.', 'error');
            } else {
                showToast(data.message || 'Fehler beim Speichern.', 'error');
            }
        })
        .catch(() => showToast('Netzwerkfehler.', 'error'))
        .finally(() => submitBtn && submitBtn.classList.remove('loading', 'disabled'));
});

// ------------------------------------------------------------------
// Skilltree: Klick auf eine Fertigkeit -> Skill-Modal (HERO-14/16)
// ------------------------------------------------------------------
let skillBaseUrl = null, skillCurrentId = null, skillCanEdit = false;

document.addEventListener('click', function (e) {
    const node = e.target.closest('.skill-trigger');
    if (!node) return;
    e.preventDefault();
    const tree    = node.closest('#skilltree');
    skillBaseUrl  = tree ? tree.getAttribute('data-learn-url') : null;
    skillCanEdit  = tree ? tree.getAttribute('data-can-edit') === '1' : false;
    skillCurrentId = node.getAttribute('data-skill-id');
    const balance  = parseFloat(tree ? tree.getAttribute('data-balance') : '0') || 0;
    const cost     = parseFloat(node.getAttribute('data-skill-cost')) || 0;
    const learned  = node.getAttribute('data-skill-learned') === '1';

    $('#skill-modal-title').text(node.getAttribute('data-skill-name') || 'Fertigkeit');
    $('#skill-modal-desc').text(node.getAttribute('data-skill-desc') || '');

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
        $('#skill-modal-meta').html('Kosten: <strong>' + cost + ' EP</strong>');
        $warn.text('Voraussetzungen fehlen: ' + prereqs + '.').show();
        $accept.hide();
        $revoke.hide();
    } else {
        const enough   = balance >= cost;
        const epColor  = enough ? 'text-green-700' : 'text-red-700';
        $('#skill-modal-meta').html('Kosten: <strong>' + cost + ' EP</strong> &nbsp;·&nbsp; Verfügbar: <span class="' + epColor + ' font-medium">' + balance + ' EP</span>');
        $warn.text('Nicht genug EP. EP werden durch Abenteuer-Teilnahme gutgeschrieben.').toggle(!enough);
        $revoke.hide();
        $accept.toggle(skillCanEdit).toggleClass('disabled', !enough);
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
// ------------------------------------------------------------------
let photoCropper      = null;
let photoCropUrl      = null;
let photoCropCallback = null;

function openPhotoCropper(file, uploadUrl, onSuccess) {
    if (file.size > 20 * 1024 * 1024) {
        showToast('Bild zu groß (max. 20 MB).', 'error');
        return;
    }
    photoCropUrl      = uploadUrl;
    photoCropCallback = onSuccess || null;

    const reader = new FileReader();
    reader.onload = function (e) {
        const img = document.getElementById('photo-crop-img');
        img.src = e.target.result;
        // Modal erst öffnen wenn src gesetzt -> onVisible kann Cropper korrekt messen.
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
window.openPhotoCropper = openPhotoCropper;

document.getElementById('photo-crop-save-btn').addEventListener('click', function () {
    if (!photoCropper || !photoCropUrl) return;
    const btn = this;
    btn.classList.add('loading', 'disabled');
    photoCropper.getCroppedCanvas({ width: 400, height: 400 }).toBlob(function (blob) {
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
                if (photoCropCallback) photoCropCallback();
            })
            .catch(() => showToast('Netzwerkfehler.', 'error'))
            .finally(() => btn.classList.remove('loading', 'disabled'));
    }, 'image/jpeg', 0.85);
});

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

        const opts = {
            type:       type === 'datetime' ? 'datetime' : 'date',
            monthFirst: false,
            today:      true,
            closable:   true,
            formatter:  type === 'datetime'
                ? { datetime: (d) => d ? fmtDatetime(d) : '' }
                : { date:     (d) => d ? fmtDate(d)     : '' },
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
// DOMContentLoaded: Kalender + Flash-Toast (UI-09)
// Module laufen nach DOM-Parse, aber vor DOMContentLoaded.
// ------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {
    // UI-05: Standalone-Seiten (Held/Spieler anlegen, Formular direkt im DOM).
    initFomanticCalendars($(document));

    // UI-09: Session-Flash als Toast (Vollseiten-Redirects).
    // Daten kommen aus data-Attributen von #app-flash (gesetzt via Blade in app.blade.php).
    const flash = document.getElementById('app-flash');
    if (flash) {
        if (flash.dataset.status) showToast(flash.dataset.status, 'success');
        if (flash.dataset.error)  showToast(flash.dataset.error,  'error');
    }
});
