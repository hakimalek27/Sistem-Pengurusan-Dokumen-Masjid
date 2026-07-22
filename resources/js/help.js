import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import '../css/help.css';

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
let waitingBanner = null;
let finalActionCleanup = null;
let automaticModalGuard = null;

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

function decorateTargets() {
    const targets = [
        ['main', 'page-content'],
        ['.fi-sidebar', 'sidebar'],
    ];

    for (const [selector, target] of targets) {
        const element = document.querySelector(selector);
        if (element && !document.querySelector(SELECTOR(target))) element.dataset.helpTarget = target;
    }
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

function isActionStep(step, nextStep) {
    if (step.wait_for_user) return true;
    if (!nextStep || (nextStep.route && !samePath(nextStep.route))) return false;
    return nextStep.target !== step.target && !resolveStepElement(nextStep, false);
}

function nextButtonLabel(guideSteps, index) {
    const step = guideSteps[index];
    const next = guideSteps[index + 1];
    if (!next) return step.wait_for_user && step.target !== 'page-content' ? 'Buat pada skrin' : 'Selesai';
    if (!isActionStep(step, next)) return 'Seterusnya';
    const waitsForScreen = (!next.route || samePath(next.route))
        && next.target !== step.target
        && !resolveStepElement(next, false);

    return waitsForScreen ? 'Buat pada skrin' : 'Saya sudah buat';
}

function stepDescription(runtime, guide, step, actionStep, buttonLabel) {
    let hint = 'Baca penerangan ini, kemudian tekan <strong>Seterusnya</strong>.';
    if (actionStep && buttonLabel === 'Buat pada skrin') {
        hint = '<strong>Tindakan anda:</strong> tekan <strong>Buat pada skrin</strong> untuk mengecilkan arahan, kemudian gunakan kawalan halaman yang disorot. Panduan akan muncul semula apabila langkah seterusnya terbuka.';
    } else if (actionStep) {
        hint = '<strong>Tindakan anda:</strong> lakukan arahan pada skrin. Tekan <strong>Saya sudah buat</strong> hanya selepas selesai; panduan tidak mengklik, menyimpan atau menghantar bagi pihak anda.';
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
    if (!isActionStep(current, next) || resolveStepElement(next, false)) return;

    transitionObserver = new MutationObserver(() => {
        if (!activeDriver?.isActive() || activeDriver.getActiveIndex() !== index) return;
        if (!resolveStepElement(next, false)) return;

        transitionObserver?.disconnect();
        transitionObserver = null;
        transitionTimer = window.setTimeout(() => {
            if (activeDriver?.isActive() && activeDriver.getActiveIndex() === index) {
                clearWaitingBanner();
                activeDriver.moveNext();
            }
        }, 120);
    });
    transitionObserver.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
}

function showUnavailableGuide(runtime, guide, step) {
    const fallback = document.querySelector(SELECTOR('page-content')) || document.querySelector('main') || document.body;
    const article = escapeHtml(helpArticleUrl(runtime, guide.id));
    activeDriver = driver({
        animate: true,
        allowClose: true,
        overlayClickBehavior: 'close',
        doneBtnText: 'Tutup',
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
        },
        onDestroyed: () => {
            activeDriver = null;
            activeGuideId = null;
            clearTransitionWatch();
            clearWaitingBanner();
            clearFinalActionWatch();
            clearAutomaticModalGuard();
        },
    });
    activeDriver.drive();
}

async function startGuide(runtime, guide, startIndex = 0, explicit = false) {
    if (!guide?.steps?.length || activeGuideId === guide.id) return;
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
        const next = guideSteps[index + 1];
        const actionStep = isActionStep(step, next);
        const finalAction = !next && step.wait_for_user && step.target !== 'page-content';
        const buttonLabel = nextButtonLabel(guideSteps, index);
        return {
            element: () => resolveStepElement(step) || document.querySelector(SELECTOR('page-content')),
            popover: {
                popoverClass: 'diwan-tour-popover',
                title: escapeHtml(step.title),
                description: stepDescription(runtime, guide, step, actionStep, buttonLabel),
                side: 'bottom',
                align: 'start',
                nextBtnText: buttonLabel,
            },
            diwan: { ...step, actionStep, finalAction },
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
            const buttonLabel = nextButtonLabel(guideSteps, index);
            const nextButton = document.querySelector('.driver-popover-next-btn');
            if (nextButton) nextButton.textContent = buttonLabel;
            const description = document.querySelector('.driver-popover-description');
            if (description) description.innerHTML = stepDescription(runtime, guide, current, isActionStep(current, next), buttonLabel);
            setTourStatus('');
            watchForNextStep(guideSteps, index);
            emit(index === driverStartIndex ? 'started' : 'progressed', guide.id, current.sourceIndex, current.target);
        },
        onNextClick: (_element, driverStep, options) => {
            const index = options.driver.getActiveIndex() ?? 0;
            const current = guideSteps[index];
            if (index >= guideSteps.length - 1) {
                if (driverStep.diwan?.finalAction && resolveStepElement(current, false)) {
                    minimiseForAction(current);
                    watchForActionCompletion(current, () => completeGuide(options.driver, guide, current));
                    return;
                }
                completeGuide(options.driver, guide, current);
                return;
            }

            const next = guideSteps[index + 1];
            if (next.route && !samePath(next.route)) {
                if (current.wait_for_user && !GENERIC_TARGETS.has(current.target) && resolveStepElement(current, false)) {
                    minimiseForAction(current);
                    watchForActionCompletion(current, () => {
                        emit('progressed', guide.id, next.sourceIndex, next.target);
                        window.location.assign(`${next.route}?panduan=${encodeURIComponent(guide.id)}&langkah=${next.sourceIndex}`);
                    });
                    return;
                }
                emit('progressed', guide.id, next.sourceIndex, next.target);
                window.location.assign(`${next.route}?panduan=${encodeURIComponent(guide.id)}&langkah=${next.sourceIndex}`);
                return;
            }

            if (resolveStepElement(next, GENERIC_TARGETS.has(next.target))) {
                options.driver.moveNext();
                return;
            }

            const expectedAction = driverStep.diwan?.actionStep;
            if (expectedAction) {
                minimiseForAction(current);
                return;
            }
            setTourStatus(
                'Sasaran langkah seterusnya tidak ditemui. Muat semula halaman atau buka panduan penuh.',
                'error',
            );
            emit('target_missing', guide.id, next.sourceIndex, next.target);
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
            options.driver.destroy();
        },
        onDestroyed: () => {
            activeDriver = null;
            activeGuideId = null;
            clearTransitionWatch();
            clearWaitingBanner();
            clearFinalActionWatch();
            clearAutomaticModalGuard();
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
