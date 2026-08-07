import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            // F7 §8.2 — `a11y-landmarks.js` ialah entri KEENAM. Tanpa baris ini ia tidak
            // dibina dan tidak muncul dalam `manifest.json`, jadi `@vite(...)` melempar dan
            // panel gagal dirender. Diassert oleh `A11yLandmarksTest`.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/help.js', 'resources/js/a11y-landmarks.js', 'resources/js/document-viewer.js', 'resources/css/filament/theme.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
