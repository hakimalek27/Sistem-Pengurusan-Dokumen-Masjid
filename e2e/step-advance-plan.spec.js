// F2a (PELAN-PEMBAIKAN §3.6) — jadual keputusan `stepAdvancePlan` diuji sebagai FUNGSI TULEN.
// Modul diimport terus sebagai Node ESM (package.json "type":"module"; modul tiada aset),
// jadi tiada bundler baharu dan TIADA hook ujian dalam bundle produksi.
import { expect, test } from '@playwright/test';
import { ACTION_KINDS, stepAdvancePlan } from '../resources/js/help/step-advance-plan.js';

const nampakSemua = { isVisible: () => true, samePath: () => true };
const tiadaNampak = { isVisible: () => false, samePath: () => true };

/** Bina deps di mana hanya sasaran tertentu kelihatan. */
function nampakHanya(targets, samePath = () => true) {
    return { isVisible: (step) => targets.includes(step.target), samePath };
}

test('kind: complete — langkah akhir biasa', () => {
    const steps = [{ target: 'page-content' }];
    expect(stepAdvancePlan(steps, 0, nampakSemua)).toEqual({ kind: 'complete', label: 'Selesai' });
});

test('kind: complete — langkah akhir wait_for_user tetapi sasaran generik', () => {
    const steps = [{ target: 'page-content', wait_for_user: true }];
    expect(stepAdvancePlan(steps, 0, nampakSemua))
        .toEqual({ kind: 'complete', label: 'Selesai' });
});

test('kind: final-action — langkah akhir menunggu tindakan pada sasaran spesifik', () => {
    const steps = [{ target: 'inbox-classify', wait_for_user: true }];
    expect(stepAdvancePlan(steps, 0, nampakSemua))
        .toEqual({ kind: 'final-action', label: 'Buat pada skrin' });
});

test('kind: navigate — langkah berikut pada halaman lain', () => {
    const steps = [{ target: 'page-content' }, { target: 'page-content', route: '/app/mam/records' }];
    expect(stepAdvancePlan(steps, 0, { isVisible: () => true, samePath: () => false }))
        .toEqual({ kind: 'navigate', label: 'Seterusnya' });
});

test('kind: action-then-navigate — perlu bertindak dahulu, kemudian halaman lain', () => {
    const steps = [
        { target: 'inbox-classify', wait_for_user: true },
        { target: 'page-content', route: '/app/mam/records' },
    ];
    expect(stepAdvancePlan(steps, 0, { isVisible: () => true, samePath: () => false }))
        .toEqual({ kind: 'action-then-navigate', label: 'Buat pada skrin' });
});

test('kind: advance — sasaran berikut sudah kelihatan (kes 94% langkah generik)', () => {
    const steps = [{ target: 'page-content' }, { target: 'nav-primary' }];
    expect(stepAdvancePlan(steps, 0, nampakSemua))
        .toEqual({ kind: 'advance', label: 'Seterusnya' });
});

test('kind: wait-for-action — sasaran berikut belum wujud, langkah ini menunggu tindakan', () => {
    const steps = [{ target: 'inbox-classify', wait_for_user: true }, { target: 'classification-source' }];
    expect(stepAdvancePlan(steps, 0, nampakHanya(['inbox-classify'])))
        .toEqual({ kind: 'wait-for-action', label: 'Buat pada skrin' });
});

test('kind: advance-blocked — sasaran berikut tiada dan bukan langkah tindakan', () => {
    const steps = [{ target: 'page-content' }, { target: 'page-content' }];
    expect(stepAdvancePlan(steps, 0, tiadaNampak))
        .toEqual({ kind: 'advance-blocked', label: 'Seterusnya' });
});

test('REGRESI RR-01-07: langkah generik berturut TIDAK PERNAH memaparkan "Buat pada skrin"', () => {
    // Sebelum F2, label dikira tanpa fallback generik manakala klik menggunakannya —
    // jadi langkah generik memaparkan CTA tindakan yang tidak menunggu apa-apa (20 kes).
    const generik = ['page-content', 'nav-primary', 'page-content', 'nav-primary'];
    const steps = generik.map((target) => ({ target }));
    for (let i = 0; i < steps.length; i += 1) {
        const plan = stepAdvancePlan(steps, i, nampakSemua);
        expect(ACTION_KINDS.has(plan.kind), `langkah ${i} kind=${plan.kind}`).toBe(false);
        expect(plan.label).toBe(i === steps.length - 1 ? 'Selesai' : 'Seterusnya');
    }
});

test('setiap label memetakan kepada TEPAT satu kelakuan (1:1 label↔kind)', () => {
    const kombinasi = [
        [[{ target: 'page-content' }], 0, nampakSemua],
        [[{ target: 'x', wait_for_user: true }], 0, nampakSemua],
        [[{ target: 'a' }, { target: 'b', route: '/lain' }], 0, { isVisible: () => true, samePath: () => false }],
        [[{ target: 'a', wait_for_user: true }, { target: 'b', route: '/lain' }], 0, { isVisible: () => true, samePath: () => false }],
        [[{ target: 'a' }, { target: 'b' }], 0, nampakSemua],
        [[{ target: 'a', wait_for_user: true }, { target: 'b' }], 0, nampakHanya(['a'])],
        [[{ target: 'a' }, { target: 'a' }], 0, tiadaNampak],
    ];
    const labelUntukKind = new Map();
    const kindUntukLabel = new Map();
    for (const [steps, index, deps] of kombinasi) {
        const { kind, label } = stepAdvancePlan(steps, index, deps);
        if (labelUntukKind.has(kind)) expect(labelUntukKind.get(kind)).toBe(label);
        labelUntukKind.set(kind, label);
        kindUntukLabel.set(label, (kindUntukLabel.get(label) ?? new Set()).add(kind));
    }
    // 7 kind diliputi; label "Buat pada skrin" sengaja dikongsi oleh 3 kind TINDAKAN
    // (semuanya minimise dahulu) — itu satu kelakuan dari sudut pengguna.
    expect([...labelUntukKind.keys()].sort()).toEqual([
        'action-then-navigate', 'advance', 'advance-blocked', 'complete', 'final-action',
        'navigate', 'wait-for-action',
    ]);
    for (const [label, kinds] of kindUntukLabel) {
        for (const kind of kinds) {
            expect(ACTION_KINDS.has(kind), `${label} → ${kind}`).toBe(label === 'Buat pada skrin');
        }
    }
});
