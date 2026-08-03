// F2a (PELAN-PEMBAIKAN §3.1) — SATU sumber keputusan untuk label butang DAN kelakuan klik.
//
// Punca RR-01-07: `nextButtonLabel()` memutuskan label dengan `resolveStepElement(next, false)`
// (TANPA fallback generik) sementara `onNextClick` memutuskan kelakuan dengan
// `resolveStepElement(next, GENERIC_TARGETS.has(...))` (DENGAN fallback). Untuk langkah
// generik — 94% katalog — label berkata "Buat pada skrin" tetapi klik hanya `moveNext()`.
// Itulah "dah tekan ke belum?" yang dilaporkan pemilik, dan 20× CTA generik (RR-10-06).
//
// Modul ini TULEN: tiada import CSS, tiada sentuhan DOM, satu export. Itu membolehkan ujian
// mengimportnya terus sebagai Node ESM tanpa bundler dan TANPA meninggalkan hook ujian dalam
// bundle produksi (kontrak C11).
//
// JADUAL LABEL ↔ KIND (setiap label 1:1 dengan satu kelakuan — tiada label berkongsi dua):
//
//   kind                  | label            | kelakuan onNextClick
//   ----------------------|------------------|------------------------------------------------
//   complete              | Selesai          | completeGuide()
//   final-action          | Buat pada skrin  | minimise + watchForActionCompletion → complete
//   navigate              | Seterusnya       | pergi ke route langkah berikut
//   action-then-navigate  | Buat pada skrin  | minimise + watch → kemudian pergi ke route
//   advance               | Seterusnya       | moveNext()
//   wait-for-action       | Buat pada skrin  | minimise (tunggu sasaran berikut muncul)
//   advance-blocked       | Seterusnya       | setTourStatus(ralat) + emit target_missing

/**
 * @param {Array<object>} guideSteps langkah guide (sudah ditapis mengikut mod)
 * @param {number} index kedudukan langkah semasa
 * @param {object} deps
 * @param {(step: object) => boolean} deps.isVisible sasaran langkah kelihatan SEKARANG?
 *        (pemanggil menentukan sama ada fallback generik dibenarkan — resolver menyentuh DOM,
 *        jadi ia kekal di luar modul tulen ini)
 * @param {(route: string) => boolean} deps.samePath route itu halaman semasa?
 * @returns {{kind: string, label: string}}
 */
export function stepAdvancePlan(guideSteps, index, deps) {
    const { isVisible, samePath } = deps;
    const step = guideSteps[index];
    const next = guideSteps[index + 1];

    // 1. Langkah AKHIR
    if (!next) {
        return step.wait_for_user && step.target !== 'page-content'
            ? { kind: 'final-action', label: 'Buat pada skrin' }
            : { kind: 'complete', label: 'Selesai' };
    }

    // 2. Langkah berikut pada HALAMAN LAIN
    if (next.route && !samePath(next.route)) {
        return step.wait_for_user && step.target !== 'page-content' && isVisible(step)
            ? { kind: 'action-then-navigate', label: 'Buat pada skrin' }
            : { kind: 'navigate', label: 'Seterusnya' };
    }

    // 3. Sasaran berikut sudah kelihatan (fallback generik dibenarkan oleh pemanggil)
    //    → maju terus. Inilah yang membetulkan 20× CTA generik palsu.
    if (isVisible(next)) {
        return { kind: 'advance', label: 'Seterusnya' };
    }

    // 4. Sasaran berikut belum wujud DAN langkah ini memang menunggu tindakan pengguna
    const menungguTindakan = step.wait_for_user || next.target !== step.target;
    if (menungguTindakan) {
        return { kind: 'wait-for-action', label: 'Buat pada skrin' };
    }

    // 5. Sasaran berikut tiada dan bukan langkah tindakan — klik memaparkan ralat sedia ada.
    return { kind: 'advance-blocked', label: 'Seterusnya' };
}

/** Kind yang bermaksud "pengguna perlu bertindak pada skrin dahulu". */
export const ACTION_KINDS = new Set(['final-action', 'action-then-navigate', 'wait-for-action']);
