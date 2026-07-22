import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import '../css/help.css';

const SELECTOR = (target) => `[data-help-target="${CSS.escape(target)}"]`;
let activeDriver = null;
let activeGuideId = null;
let completed = false;

function decorateTargets() {
    const targets = [
        ['main', 'page-content'],
        ['.fi-sidebar', 'sidebar'],
        ['.fi-header-actions', 'page-primary'],
        ['.fi-ta', 'page-primary'],
    ];

    for (const [selector, target] of targets) {
        const element = document.querySelector(selector);
        if (element && !document.querySelector(SELECTOR(target))) element.dataset.helpTarget = target;
    }

    if (!document.querySelector(SELECTOR('page-primary'))) {
        const primary = document.querySelector('main button:not([disabled]), main a[href]');
        if (primary) primary.dataset.helpTarget = 'page-primary';
    }
}

function samePath(route) {
    if (!route) return true;
    return new URL(route, window.location.origin).pathname === window.location.pathname;
}

function emit(event, guideId, stepIndex, target = null) {
    window.Livewire?.dispatch('guidanceProgress', { guideId, event, stepIndex, target });
    sessionStorage.setItem(`diwan-help:${guideId}`, JSON.stringify({ event, stepIndex }));
}

function waitForTarget(target, timeout = 10000) {
    if (!target) return Promise.resolve(null);
    decorateTargets();
    const existing = document.querySelector(SELECTOR(target));
    if (existing) return Promise.resolve(existing);

    return new Promise((resolve) => {
        const observer = new MutationObserver(() => {
            decorateTargets();
            const element = document.querySelector(SELECTOR(target));
            if (element) {
                observer.disconnect();
                clearTimeout(timer);
                resolve(element);
            }
        });
        observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true });
        const timer = setTimeout(() => {
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

async function startGuide(runtime, guide, startIndex = 0) {
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

    const steps = guideSteps.map((step) => ({
        element: SELECTOR(step.target || 'page-content'),
        popover: {
            title: step.title,
            description: step.instruction,
            side: 'bottom',
            align: 'start',
        },
        diwan: step,
    }));

    const first = guideSteps[driverStartIndex];
    if (first.route && !samePath(first.route)) {
        window.location.assign(`${first.route}?panduan=${encodeURIComponent(guide.id)}&langkah=${first.sourceIndex}`);
        return;
    }

    const firstTarget = await waitForTarget(first.target, 4000);
    if (!firstTarget) {
        emit('target_missing', guide.id, first.sourceIndex, first.target);
        activeGuideId = null;
        window.location.assign(`${runtime.dataset.helpUrl}&artikel=${encodeURIComponent(guide.id)}`);
        return;
    }

    activeDriver = driver({
        animate: true,
        allowClose: true,
        overlayClickBehavior: 'close',
        showProgress: true,
        progressText: '{{current}} daripada {{total}}',
        nextBtnText: 'Seterusnya',
        prevBtnText: 'Kembali',
        doneBtnText: 'Selesai',
        steps,
        onHighlighted: (_element, _step, options) => {
            const index = options.driver.getActiveIndex() ?? 0;
            const current = guideSteps[index];
            emit(index === driverStartIndex ? 'started' : 'progressed', guide.id, current.sourceIndex, current.target);
        },
        onNextClick: async (_element, _step, options) => {
            const index = options.driver.getActiveIndex() ?? 0;
            const current = guideSteps[index];
            if (index >= guideSteps.length - 1) {
                completed = true;
                emit('completed', guide.id, current.sourceIndex, current.target);
                options.driver.destroy();
                stripGuideQuery();
                return;
            }

            const nextIndex = index + 1;
            const next = guideSteps[nextIndex];
            if (next.route && !samePath(next.route)) {
                emit('progressed', guide.id, next.sourceIndex, next.target);
                window.location.assign(`${next.route}?panduan=${encodeURIComponent(guide.id)}&langkah=${next.sourceIndex}`);
                return;
            }

            const target = await waitForTarget(next.target);
            if (!target) {
                emit('target_missing', guide.id, next.sourceIndex, next.target);
                options.driver.destroy();
                window.location.assign(`${runtime.dataset.helpUrl}&artikel=${encodeURIComponent(guide.id)}`);
                return;
            }
            options.driver.moveNext();
        },
        onDestroyStarted: (_element, _step, options) => {
            if (!completed) {
                const index = options.driver.getActiveIndex() ?? 0;
                const current = guideSteps[index];
                emit('dismissed', guide.id, current.sourceIndex, current.target);
            }
            options.driver.destroy();
            activeGuideId = null;
            if (completed) stripGuideQuery();
        },
    });

    activeDriver.drive(driverStartIndex);
}

function bootRuntime() {
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
                window.setTimeout(() => startGuide(runtime, guide, Number(runtime.dataset.resumeStep || 0)), 450);
            }
        } catch {
            // Katalog tidak sah tidak boleh memecahkan halaman utama.
        }
    });
}

document.addEventListener('DOMContentLoaded', bootRuntime);
document.addEventListener('livewire:navigated', bootRuntime);
new MutationObserver(decorateTargets).observe(document.documentElement, { childList: true, subtree: true });
