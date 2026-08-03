import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import '../css/help.css';
import { ACTION_KINDS, stepAdvancePlan } from './help/step-advance-plan.js';
import { NAV_PRIMARY, navPrimaryTarget } from './help/nav-target-plan.js';

const SELECTOR = (target) => `[data-help-target="${CSS.escape(target)}"]`;
const GENERIC_TARGETS = new Set(['page-content', 'page-primary']);
const ACTION_STOP_WORDS = new Set([
    'anda', 'atau', 'bagi', 'dan', 'dengan', 'di', 'ini', 'itu', 'jika', 'ke', 'kepada',
    'pada', 'sebagai', 'selepas', 'sebelum', 'serta', 'supaya', 'untuk', 'yang',
]);

let activeDriver = null;
let activeGuideId = null;
let completed = false;
let transitionObserver = null;
let transitionTimer = null;
let transitionPoller = null;
let waitingBanner = null;
let finalActionCleanup = null;
let automaticModalGuard = null;
let autoMinimiseTimer = null;
let autoMinimiseFrame = null;
let tourTrigger = null;

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = String(value ?? '');
    return element.innerHTML;
}

function normaliseText(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

function isVisible(element) {
    if (!(element instanceof HTMLElement)) return false;
    const style = window.getComputedStyle(element);
    return style.visibility !== 'hidden'
        && style.display !== 'none'
        && element.getClientRects().length > 0;
}

/**
 * F5c (§6.3) — calon konkrit bagi sasaran LOGIK `nav-primary`.
 *
 * Ruang nama BERASINGAN (`data-help-nav`, bukan `data-help-target`) dan itu bukan kosmetik:
 * satu elemen hanya boleh memegang SATU `data-help-target`. Percubaan pertama F5c menandakan
 * `.fi-sidebar` sebagai `sidebar` DAN `nav-sidebar`; kedua-duanya bertelagah pada atribut yang
 * sama, jadi `decorateTargets()` — yang dipanggil pada SETIAP `resolveStepElement()` —
 * menulis semula atribut pada setiap panggilan. Pemerhati mutasi tour (`transitionObserver`,
 * `automaticModalGuard`) memerhati `attributes: true` pada `documentElement`, jadi tulisan
 * berulang itu menjadi ribut mutasi berterusan dan koreografi tour klasifikasi tersangkut.
 * Disahkan: 3 ujian tour F2 tamat masa 180s dengan ruang nama bertindan, lulus 10–14s tanpanya.
 */
const NAV_SELECTORS = {
    'nav-sidebar': '.fi-sidebar',                    // desktop >=64rem
    'nav-menu-toggle': '.fi-topbar-open-sidebar-btn', // mobile (Filament sembunyikan >=64rem)
    'nav-bar': '.fi-topbar',                          // penambat terakhir; sentiasa dirender
};

function decorateTargets() {
    const targets = [
        ['main', 'page-content'],
        ['.fi-sidebar', 'sidebar'],
    ];

    for (const [selector, target] of targets) {
        const element = document.querySelector(selector);
        if (element && !document.querySelector(SELECTOR(target))) element.dataset.helpTarget = target;
    }

    for (const [nav, selector] of Object.entries(NAV_SELECTORS)) {
        const element = document.querySelector(selector);
        // Idempoten: hanya tulis jika belum bertanda — tiada dua kunci berkongsi elemen.
        if (element && element.dataset.helpNav !== nav) element.dataset.helpNav = nav;
    }
}

/**
 * Elemen bertindan dengan viewport?
 *
 * `isVisible()` sahaja TIDAK memadai untuk elemen off-canvas. Diukur pada iPhone 13
 * (390×664): `.fi-sidebar` mobile ialah `display:flex`, `visibility:visible`,
 * `getClientRects().length === 1` — tetapi `x = -320` dengan `width = 320`, iaitu tepat di
 * luar skrin. Menyorotnya bermakna pengguna melihat sorotan pada kawasan kosong.
 * (Andaian pertama F5c ialah Filament menyembunyikannya dengan `display:none`; ukuran
 * sebenar menolak andaian itu.)
 *
 * SENGAJA tidak dimasukkan ke dalam `isVisible()` global: itu akan mengubah keputusan label
 * bagi 473 langkah katalog sekali gus. Off-canvas sebagai isu am = kerja F6/F7.
 */
function intersectsViewport(element) {
    const rect = element.getBoundingClientRect();
    const vw = window.innerWidth || document.documentElement.clientWidth;
    const vh = window.innerHeight || document.documentElement.clientHeight;

    return rect.right > 0 && rect.bottom > 0 && rect.left < vw && rect.top < vh;
}

/** Calon navigasi (`data-help-nav`) yang kelihatan DAN berada di dalam skrin. */
function onScreenNavElement(nav) {
    return [...document.querySelectorAll(`[data-help-nav="${CSS.escape(nav)}"]`)]
        .find((el) => isVisible(el) && intersectsViewport(el)) || null;
}

/** Calon navigasi yang kelihatan, walaupun di luar skrin (penambat terakhir). */
function anyNavElement(nav) {
    return [...document.querySelectorAll(`[data-help-nav="${CSS.escape(nav)}"]`)].find(isVisible) || null;
}

function handleHelpImageError(event) {
    const image = event.target;
    if (!(image instanceof HTMLImageElement) || !image.matches('[data-help-image]')) return;

    markHelpImageMissing(image);
}

function markHelpImageMissing(image) {
    image.closest('[data-help-image-wrap]')?.classList.add('is-missing');
    image.removeAttribute('src');
}

function bindHelpImages() {
    document.querySelectorAll('[data-help-image]').forEach((image) => {
        if (image.dataset.helpImageBound !== '1') {
            image.dataset.helpImageBound = '1';
            image.addEventListener('error', () => markHelpImageMissing(image), { once: true });
        }
        if (image.complete && image.naturalWidth === 0) markHelpImageMissing(image);
    });
}

function semanticAction(step) {
    if (step.target !== 'page-primary') return null;

    const instruction = normaliseText(step.instruction);
    const instructionTokens = new Set(instruction.split(' ')
        .filter((token) => token.length >= 3 && !ACTION_STOP_WORDS.has(token)));
    let best = null;
    let bestScore = 0;

    const candidates = document.querySelectorAll([
        'main button:not([disabled])',
        'main a[href]',
        'main [role="button"]:not([aria-disabled="true"])',
        'main [role="tab"]',
    ].join(','));

    for (const candidate of candidates) {
        if (!isVisible(candidate) || candidate.closest('.driver-popover')) continue;
        const label = normaliseText(
            candidate.getAttribute('aria-label')
            || candidate.getAttribute('title')
            || candidate.textContent,
        );
        if (!label || label.length > 100) continue;

        const labelTokens = label.split(' ').filter((token) => token.length >= 3 && !ACTION_STOP_WORDS.has(token));
        let score = instruction.includes(label) ? 100 + Math.min(label.length, 50) : 0;
        for (const token of labelTokens) {
            if (instructionTokens.has(token)) score += token.length >= 7 ? 14 : 8;
        }

        if (score > bestScore) {
            best = candidate;
            bestScore = score;
        }
    }

    return bestScore >= 16 ? best : null;
}

function resolveStepElement(step, allowGenericFallback = true) {
    decorateTargets();

    if (step.target === 'page-primary') {
        const semantic = semanticAction(step);
        if (semantic) return semantic;
    }

    // F5c (§6.3): sasaran logik `nav-primary` → calon navigasi konkrit yang kelihatan.
    // Sengaja SEBELUM cabang `exact` supaya `nav-primary` tidak pernah dicari sebagai
    // `data-help-target` literal (ia tidak wujud dalam DOM — ia sasaran logik sahaja).
    if (step.target === NAV_PRIMARY) {
        // Predikat = DI DALAM SKRIN, bukan sekadar `isVisible`: sidebar mobile off-canvas
        // lulus `isVisible` (lihat intersectsViewport) dan akan disorot pada x = -320.
        const dipilih = navPrimaryTarget((candidate) => Boolean(onScreenNavElement(candidate)));

        return onScreenNavElement(dipilih) || anyNavElement(dipilih);
    }

    const exact = step.target
        ? [...document.querySelectorAll(SELECTOR(step.target))].find(isVisible)
        : null;
    if (exact && isVisible(exact)) {
        if (step.target.startsWith('classification-') && step.target !== 'classification-submit') {
            return exact.closest('.fi-modal-window') || exact;
        }
        return exact;
    }

    if (allowGenericFallback && GENERIC_TARGETS.has(step.target)) {
        return document.querySelector(SELECTOR('page-content')) || document.querySelector('main');
    }

    return null;
}

function samePath(route) {
    if (!route) return true;
    return new URL(route, window.location.origin).pathname === window.location.pathname;
}

function emit(event, guideId, stepIndex, target = null) {
    window.Livewire?.dispatch('guidanceProgress', { guideId, event, stepIndex, target });
    sessionStorage.setItem(`diwan-help:${guideId}`, JSON.stringify({ event, stepIndex }));
}

function clearTransitionWatch() {
    transitionObserver?.disconnect();
    transitionObserver = null;
    if (transitionTimer) window.clearTimeout(transitionTimer);
    transitionTimer = null;
    if (transitionPoller) window.clearInterval(transitionPoller);
    transitionPoller = null;
}

function clearWaitingBanner() {
    waitingBanner?.remove();
    waitingBanner = null;
}

function clearFinalActionWatch() {
    finalActionCleanup?.();
    finalActionCleanup = null;
}

function clearAutomaticModalGuard() {
    automaticModalGuard?.disconnect();
    automaticModalGuard = null;
}

/** F2c (§3.3) — batalkan tempoh-baca auto-minimize; dipanggil pada setiap peralihan. */
function clearAutoMinimise() {
    if (autoMinimiseTimer) window.clearTimeout(autoMinimiseTimer);
    autoMinimiseTimer = null;
    if (autoMinimiseFrame) window.cancelAnimationFrame(autoMinimiseFrame);
    autoMinimiseFrame = null;
}

/** F2d (§3.4) — fokus awal ke dalam popover; kitaran Tab kekal milik vendor Driver.js. */
function focusPopover() {
    const popover = document.querySelector('.driver-popover');
    if (!(popover instanceof HTMLElement)) return;
    const focusable = popover.querySelector(
        'button:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])',
    );
    (focusable instanceof HTMLElement ? focusable : popover).focus({ preventScroll: true });
    if (!(focusable instanceof HTMLElement) && !popover.hasAttribute('tabindex')) {
        popover.setAttribute('tabindex', '-1');
        popover.focus({ preventScroll: true });
    }
}

/**
 * F2d — pulangkan fokus kepada pencetus tour supaya pengguna papan kekunci tidak tersesat.
 *
 * Tour yang dimulakan melalui deep-link `?panduan=` tiada pencetus (fokus pada `<body>`),
 * jadi kita pulang ke butang Pembantu Diwan — pencetus semula jadi tour. Fokus ditangguh
 * satu frame kerana launcher disembunyikan (`body.driver-active`) sehingga Driver.js
 * membuang kelas itu semasa memusnahkan diri.
 */
function clearFocusManagement() {
    const trigger = tourTrigger;
    tourTrigger = null;
    window.setTimeout(() => {
        // Jangan rampas fokus jika pengguna sudah memindahkannya sendiri.
        if (document.activeElement && document.activeElement !== document.body) return;
        const destination = trigger instanceof HTMLElement && document.contains(trigger)
            ? trigger
            : document.querySelector(SELECTOR('help-launcher'));
        if (destination instanceof HTMLElement) destination.focus({ preventScroll: true });
    }, 50);
}

/**
 * F2c (§3.3) — auto-minimize HANYA apabila popover benar-benar BERTINDIH modal sasaran.
 *
 * Pada skrin kecil, overlay+popover Driver.js duduk di atas modal yang baru dibuka guide,
 * jadi butang modal tidak boleh ditekan sehingga pengguna menekan "Buat pada skrin"
 * (RR-08-03). Ukuran pertindihan sebenar dipilih berbanding timer buta: langkah yang
 * popovernya tidak menghalang apa-apa kekal terbuka untuk dibaca.
 */
function scheduleAutoMinimise(step, plan) {
    clearAutoMinimise();
    if (!ACTION_KINDS.has(plan.kind)) return;

    autoMinimiseFrame = window.requestAnimationFrame(() => {
        autoMinimiseFrame = null;
        const popover = document.getElementById('driver-popover-content');
        const target = resolveStepElement(step, false);
        const modal = target?.closest('.fi-modal-window');
        if (!popover || !modal || !isVisible(modal)) return;

        const a = popover.getBoundingClientRect();
        const b = modal.getBoundingClientRect();
        const bertindih = a.left < b.right && a.right > b.left && a.top < b.bottom && a.bottom > b.top;
        if (!bertindih) return;

        // Tempoh baca yang boleh dibatalkan (bukan 600ms — terlalu singkat untuk membaca
        // dan cukup panjang untuk berlumba dengan tindakan pengguna).
        autoMinimiseTimer = window.setTimeout(() => {
            autoMinimiseTimer = null;
            if (activeDriver?.isActive()) minimiseForAction(step);
        }, 1800);
    });
}

function guardAutomaticGuideFromDialogs(guideSteps, guide) {
    clearAutomaticModalGuard();
    automaticModalGuard = new MutationObserver(() => {
        if (!activeDriver?.isActive()) return;
        const modal = [...document.querySelectorAll('.fi-modal-window')].find(isVisible);
        const index = activeDriver.getActiveIndex() ?? 0;
        const current = guideSteps[index];
        if (!modal || !current || !GENERIC_TARGETS.has(current.target)) return;

        emit('dismissed', guide.id, current.sourceIndex, current.target);
        activeDriver.destroy();
    });
    automaticModalGuard.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
}

function focusActionTarget(step) {
    const direct = step.target
        ? [...document.querySelectorAll(SELECTOR(step.target))].find(isVisible)
        : null;
    const target = direct || resolveStepElement(step, false);
    if (!(target instanceof HTMLElement)) return false;

    const focusableSelector = [
        'button:not([disabled])',
        'a[href]',
        'input:not([disabled]):not([type="hidden"])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');
    const focusable = target.matches(focusableSelector) ? target : target.querySelector(focusableSelector);
    const destination = focusable instanceof HTMLElement ? focusable : target;
    if (destination === target && !target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
    destination.focus({ preventScroll: true });

    return document.activeElement === destination;
}

function minimiseForAction(step) {
    clearAutoMinimise();   // pengguna bertindak dahulu — jangan biar timer menembak kemudian
    const popover = document.getElementById('driver-popover-content');
    if (!popover) return;
    popover.style.display = 'none';
    clearWaitingBanner();

    waitingBanner = document.createElement('div');
    waitingBanner.className = 'diwan-tour-waiting';
    waitingBanner.dataset.diwanTourWaiting = '';
    waitingBanner.setAttribute('role', 'status');
    waitingBanner.setAttribute('aria-live', 'polite');

    const message = document.createElement('span');
    message.textContent = `Panduan menunggu: ${step.title}`;
    const show = document.createElement('button');
    show.type = 'button';
    show.textContent = 'Tunjuk arahan';
    show.addEventListener('click', () => {
        clearWaitingBanner();
        popover.style.display = 'block';
        activeDriver?.refresh();
        window.setTimeout(() => document.querySelector('.driver-popover-next-btn')?.focus(), 0);
    });
    waitingBanner.append(message, show);
    document.body.appendChild(waitingBanner);
    if (!focusActionTarget(step)) show.focus();
}

function completeGuide(driverApi, guide, step) {
    completed = true;
    emit('completed', guide.id, step.sourceIndex, step.target);
    driverApi.destroy();
    stripGuideQuery();
}

function watchForActionCompletion(step, onComplete) {
    clearFinalActionWatch();
    const action = resolveStepElement(step, false);
    if (!action) {
        onComplete();
        return;
    }

    let attempted = false;
    let checkTimer = null;
    const observer = new MutationObserver(() => {
        if (!attempted || checkTimer) return;
        checkTimer = window.setTimeout(() => {
            checkTimer = null;
            if (!resolveStepElement(step, false)) onComplete();
        }, 600);
    });
    const onAction = () => {
        attempted = true;
        const message = waitingBanner?.querySelector('span');
        if (message) message.textContent = 'Menunggu sistem mengesahkan tindakan...';
    };
    action.addEventListener('click', onAction, true);
    observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
    finalActionCleanup = () => {
        action.removeEventListener('click', onAction, true);
        observer.disconnect();
        if (checkTimer) window.clearTimeout(checkTimer);
    };
}

function waitForStep(step, timeout = 3000, allowGenericFallback = false) {
    const existing = resolveStepElement(step, allowGenericFallback);
    if (existing) return Promise.resolve(existing);

    return new Promise((resolve) => {
        const observer = new MutationObserver(() => {
            const element = resolveStepElement(step, allowGenericFallback);
            if (element) {
                observer.disconnect();
                window.clearTimeout(timer);
                resolve(element);
            }
        });
        observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
        const timer = window.setTimeout(() => {
            observer.disconnect();
            resolve(null);
        }, timeout);
    });
}

function stripGuideQuery() {
    const url = new URL(window.location.href);
    url.searchParams.delete('panduan');
    url.searchParams.delete('langkah');
    window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
}

function helpArticleUrl(runtime, guideId) {
    const url = new URL(runtime.dataset.helpUrl, window.location.origin);
    url.searchParams.set('artikel', guideId);
    return `${url.pathname}${url.search}`;
}

/**
 * F2a — SATU sumber keputusan untuk label DAN kelakuan (§3.1).
 *
 * `isVisible` menggunakan fallback generik yang SAMA seperti `onNextClick` dahulu; itulah
 * ketidakpadanan yang menyebabkan label "Buat pada skrin" pada langkah yang sebenarnya
 * hanya `moveNext()` (RR-01-07 / RR-10-06).
 */
function planFor(guideSteps, index) {
    return stepAdvancePlan(guideSteps, index, {
        isVisible: (step) => Boolean(resolveStepElement(step, GENERIC_TARGETS.has(step.target))),
        samePath,
    });
}

function nextButtonLabel(guideSteps, index) {
    return planFor(guideSteps, index).label;
}

function stepDescription(runtime, guide, step, plan) {
    // Hint mengikut KIND yang sama dengan label — tiada lagi teks "tindakan" pada langkah
    // yang sebenarnya hanya maju (RR-01-07).
    let hint = 'Baca penerangan ini, kemudian tekan <strong>Seterusnya</strong>.';
    if (ACTION_KINDS.has(plan.kind)) {
        hint = '<strong>Tindakan anda:</strong> tekan <strong>Buat pada skrin</strong> untuk mengecilkan arahan, kemudian gunakan kawalan halaman yang disorot. Panduan akan muncul semula apabila langkah seterusnya terbuka.';
    }

    return `
        <p class="diwan-tour-instruction">${escapeHtml(step.instruction)}</p>
        <p class="diwan-tour-hint">${hint}</p>
        <p class="diwan-tour-status" data-diwan-tour-status aria-live="polite"></p>
        <a class="diwan-tour-article" href="${escapeHtml(helpArticleUrl(runtime, guide.id))}">Buka panduan penuh</a>
    `;
}

function setTourStatus(message, tone = 'info') {
    const status = document.querySelector('[data-diwan-tour-status]');
    if (!status) return;
    status.textContent = message;
    status.dataset.tone = tone;
}

function watchForNextStep(guideSteps, index) {
    clearTransitionWatch();
    const current = guideSteps[index];
    const next = guideSteps[index + 1];
    if (!current || !next || (next.route && !samePath(next.route))) return;
    // Predikat kini datang dari plan yang sama seperti label (F2a). Mekanisme sync di bawah
    // (observer + poll 120ms) TIDAK diubah — ia terbukti berfungsi (§3 sempadan F2).
    if (!ACTION_KINDS.has(planFor(guideSteps, index).kind) || resolveStepElement(next, false)) return;

    const advanceWhenReady = () => {
        if (!activeDriver?.isActive()) return;
        const activeIndex = activeDriver.getActiveIndex();
        if (activeIndex !== undefined && activeIndex !== index) return;
        if (!resolveStepElement(next, false)) return;

        transitionObserver?.disconnect();
        transitionObserver = null;
        if (transitionPoller) window.clearInterval(transitionPoller);
        transitionPoller = null;
        transitionTimer = window.setTimeout(() => {
            const currentIndex = activeDriver?.getActiveIndex();
            if (activeDriver?.isActive() && (currentIndex === undefined || currentIndex === index)) {
                clearWaitingBanner();
                activeDriver.moveNext();
            }
        }, 120);
    };

    transitionObserver = new MutationObserver(advanceWhenReady);
    transitionObserver.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
    // Livewire may settle a replacement between observer delivery cycles while
    // Driver.js has hidden its popover. Poll briefly so a valid next target can
    // never leave the user stranded behind the waiting banner.
    transitionPoller = window.setInterval(advanceWhenReady, 120);
}

function showUnavailableGuide(runtime, guide, step) {
    const fallback = document.querySelector(SELECTOR('page-content')) || document.querySelector('main') || document.body;
    const article = escapeHtml(helpArticleUrl(runtime, guide.id));
    activeDriver = driver({
        animate: true,
        allowClose: true,
        overlayClickBehavior: 'close',
        // F2b (§3.2) — label vendor lalai ialah Inggeris ("Previous"/"Next"/"1 of 1").
        doneBtnText: 'Tutup',
        prevBtnText: 'Kembali',
        nextBtnText: 'Seterusnya',
        progressText: '{{current}} daripada {{total}}',
        showProgress: false,          // satu langkah — "1 daripada 1" tiada makna
        steps: [{
            element: fallback,
            popover: {
                popoverClass: 'diwan-tour-popover',
                title: 'Tindakan belum tersedia',
                description: `Kawalan untuk <strong>${escapeHtml(step.title)}</strong> tidak kelihatan pada halaman ini. Semak prasyarat atau data yang diperlukan dahulu.<br><a class="diwan-tour-article" href="${article}">Baca panduan dan penyelesaian</a>`,
            },
        }],
        onPopoverRender: (popover) => {
            popover.closeButton.setAttribute('aria-label', 'Tutup panduan');
            popover.closeButton.title = 'Tutup panduan';
            // F2d (§3.4): fallback ialah SATU langkah tanpa interaksi halaman, jadi
            // aria-modal jujur di sini (popover UTAMA sengaja TIADA aria-modal — halaman
            // di sana masih boleh diguna melalui minimize/focusActionTarget).
            popover.wrapper.setAttribute('aria-modal', 'true');
            focusPopover();
        },
        onDestroyed: () => {
            activeDriver = null;
            activeGuideId = null;
            clearTransitionWatch();
            clearWaitingBanner();
            clearFinalActionWatch();
            clearAutomaticModalGuard();
            clearAutoMinimise();
            clearFocusManagement();
        },
    });
    activeDriver.drive();
}

async function startGuide(runtime, guide, startIndex = 0, explicit = false) {
    if (!guide?.steps?.length || activeGuideId === guide.id) return;
    // F2d: rujukan pencetus disimpan supaya fokus boleh pulang selepas tour ditutup.
    // `document.body` juga HTMLElement — ia bermakna "tiada apa difokus" (cth deep-link
    // `?panduan=`), jadi ia BUKAN pencetus; sandaran launcher digunakan sebaliknya.
    const aktif = document.activeElement;
    tourTrigger = aktif instanceof HTMLElement && aktif !== document.body ? aktif : null;
    activeGuideId = guide.id;
    completed = false;
    decorateTargets();

    const sourceSteps = guide.steps.map((step, sourceIndex) => ({ ...step, sourceIndex }));
    const guideSteps = runtime.dataset.mode === 'ringkas' && sourceSteps.length > 2
        ? [sourceSteps[0], sourceSteps[sourceSteps.length - 1]]
        : sourceSteps;
    let driverStartIndex = guideSteps.findIndex((step) => step.sourceIndex >= startIndex);
    if (driverStartIndex < 0) driverStartIndex = guideSteps.length - 1;

    const steps = guideSteps.map((step, index) => {
        const plan = planFor(guideSteps, index);
        const actionStep = ACTION_KINDS.has(plan.kind);
        const finalAction = plan.kind === 'final-action';
        const buttonLabel = plan.label;
        return {
            element: () => resolveStepElement(step) || document.querySelector(SELECTOR('page-content')),
            popover: {
                popoverClass: 'diwan-tour-popover',
                title: escapeHtml(step.title),
                description: stepDescription(runtime, guide, step, plan),
                side: 'bottom',
                align: 'start',
                nextBtnText: buttonLabel,
            },
            diwan: { ...step, actionStep, finalAction, kind: plan.kind },
        };
    });

    const first = guideSteps[driverStartIndex];
    if (first.route && !samePath(first.route)) {
        window.location.assign(`${first.route}?panduan=${encodeURIComponent(guide.id)}&langkah=${first.sourceIndex}`);
        return;
    }

    const firstTarget = await waitForStep(first, 2500, GENERIC_TARGETS.has(first.target));
    if (!firstTarget) {
        emit('target_missing', guide.id, first.sourceIndex, first.target);
        showUnavailableGuide(runtime, guide, first);
        return;
    }

    activeDriver = driver({
        animate: true,
        allowClose: true,
        overlayClickBehavior: 'close',
        disableActiveInteraction: false,
        showProgress: true,
        progressText: '{{current}} daripada {{total}}',
        nextBtnText: 'Seterusnya',
        prevBtnText: 'Kembali',
        doneBtnText: 'Selesai',
        steps,
        onPopoverRender: (popover) => {
            popover.closeButton.setAttribute('aria-label', 'Tutup panduan');
            popover.closeButton.title = 'Tutup panduan';
        },
        onHighlighted: (_element, _step, options) => {
            const index = options.driver.getActiveIndex() ?? 0;
            const current = guideSteps[index];
            const next = guideSteps[index + 1];
            const plan = planFor(guideSteps, index);
            const nextButton = document.querySelector('.driver-popover-next-btn');
            if (nextButton) nextButton.textContent = plan.label;
            const description = document.querySelector('.driver-popover-description');
            if (description) description.innerHTML = stepDescription(runtime, guide, current, plan);
            setTourStatus('');
            watchForNextStep(guideSteps, index);
            clearAutoMinimise();          // batalkan baki tempoh-baca langkah sebelumnya
            focusPopover();               // F2d: fokus awal (vendor tidak melakukannya)
            scheduleAutoMinimise(current, plan);
            emit(index === driverStartIndex ? 'started' : 'progressed', guide.id, current.sourceIndex, current.target);
        },
        // F2a: cabang dipilih oleh plan YANG SAMA dengan label — tiada lagi label yang
        // menjanjikan tindakan sedangkan klik hanya maju (RR-01-07). Setiap cabang
        // mengekalkan tingkah laku sedia ada yang betul.
        onNextClick: (_element, _driverStep, options) => {
            const index = options.driver.getActiveIndex() ?? 0;
            const current = guideSteps[index];
            const next = guideSteps[index + 1];
            const gotoNextRoute = () => {
                emit('progressed', guide.id, next.sourceIndex, next.target);
                window.location.assign(`${next.route}?panduan=${encodeURIComponent(guide.id)}&langkah=${next.sourceIndex}`);
            };

            switch (planFor(guideSteps, index).kind) {
                case 'complete':
                    completeGuide(options.driver, guide, current);

                    return;
                case 'final-action':
                    if (resolveStepElement(current, false)) {
                        minimiseForAction(current);
                        watchForActionCompletion(current, () => completeGuide(options.driver, guide, current));

                        return;
                    }
                    completeGuide(options.driver, guide, current);

                    return;
                case 'action-then-navigate':
                    minimiseForAction(current);
                    watchForActionCompletion(current, gotoNextRoute);

                    return;
                case 'navigate':
                    gotoNextRoute();

                    return;
                case 'advance':
                    options.driver.moveNext();

                    return;
                case 'wait-for-action':
                    minimiseForAction(current);

                    return;
                default: // advance-blocked
                    setTourStatus(
                        'Sasaran langkah seterusnya tidak ditemui. Muat semula halaman atau buka panduan penuh.',
                        'error',
                    );
                    emit('target_missing', guide.id, next.sourceIndex, next.target);
            }
        },
        onDestroyStarted: (_element, _step, options) => {
            if (!completed) {
                const index = options.driver.getActiveIndex() ?? 0;
                const current = guideSteps[index];
                emit('dismissed', guide.id, current.sourceIndex, current.target);
            }
            clearTransitionWatch();
            clearWaitingBanner();
            clearFinalActionWatch();
            clearAutomaticModalGuard();
            clearAutoMinimise();
            options.driver.destroy();
        },
        onDestroyed: () => {
            activeDriver = null;
            activeGuideId = null;
            clearTransitionWatch();
            clearWaitingBanner();
            clearFinalActionWatch();
            clearAutomaticModalGuard();
            clearAutoMinimise();
            clearFocusManagement();       // F2d: fokus pulang ke pencetus/launcher
            if (completed) stripGuideQuery();
        },
    });

    activeDriver.drive(driverStartIndex);
    if (!explicit) guardAutomaticGuideFromDialogs(guideSteps, guide);
}

function bootRuntime() {
    bindHelpImages();
    decorateTargets();
    document.querySelectorAll('[data-diwan-help-runtime]').forEach((runtime) => {
        if (runtime.dataset.helpBooted === '1') return;
        runtime.dataset.helpBooted = '1';
        const payload = runtime.querySelector('[data-diwan-guide-payload]');
        if (!payload) return;

        try {
            const guide = JSON.parse(payload.textContent);
            const explicit = new URL(window.location.href).searchParams.get('panduan') === guide.id;
            const isPublic = runtime.dataset.panel === 'public';
            const publicSeen = isPublic && localStorage.getItem(`diwan-help-seen:${guide.id}`);
            const shouldStart = explicit || (runtime.dataset.autoStart === '1' && !publicSeen);
            if (shouldStart) {
                if (isPublic) localStorage.setItem(`diwan-help-seen:${guide.id}`, '1');
                window.setTimeout(() => startGuide(runtime, guide, Number(runtime.dataset.resumeStep || 0), explicit), 450);
            }
        } catch {
            // Katalog tidak sah tidak boleh memecahkan halaman utama.
        }
    });
}

document.addEventListener('DOMContentLoaded', bootRuntime);
document.addEventListener('livewire:navigated', bootRuntime);
document.addEventListener('error', handleHelpImageError, true);
