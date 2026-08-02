import { expect } from '@playwright/test';

/**
 * Masukkan fail ke medan FileUpload Filament dengan selamat.
 *
 * Filament memuat JS komponennya secara LAZY (`x-load`). `setInputFiles` pada input yang
 * belum dipasang FilePond hanya menetapkan fail pada DOM — tiada handler, jadi
 * `/livewire/upload-file` TIDAK PERNAH dihantar dan "Upload complete" tidak muncul
 * (menumbangkan `ci-domain` pada CI run 30769364093: 0 permintaan upload dalam serve-ci.log).
 * Runner CI lebih perlahan daripada mesin dev, jadi tetingkap race itu jauh lebih lebar.
 *
 * Menunggu `.filepond--root` membuktikan skrip komponen sudah dimuat DAN diinisialisasi.
 */
export async function attachFile(scope, file, { timeout = 60_000 } = {}) {
    await expect(scope.locator('.filepond--root').first()).toBeVisible({ timeout });
    await scope.locator('input[type="file"]').first().setInputFiles(file);
    await expect(scope.getByText('Upload complete', { exact: true }).first()).toBeVisible({ timeout });
}
