import { access, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';

const projectRoot = process.cwd();
const manualRoot = path.resolve(projectRoot, 'Manual Penguna');
const manifestPath = path.join(manualRoot, 'manifest-tangkapan.json');
const catalogPath = path.resolve(projectRoot, 'resources/help/guides.json');
const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
const catalog = JSON.parse(await readFile(catalogPath, 'utf8'));

function imagePath(role, relative) {
    return `Manual Penguna/${role.folder}/${relative}`.replaceAll('\\', '/');
}

function routeTemplate(value) {
    return value.replace(/^\/app\/mam(?=\/|$)/, '/app/{tenant}');
}

function normalise(value) {
    return value.toLocaleLowerCase('ms-MY').replace(/[^a-z0-9]+/g, ' ').trim();
}

function roleImages(guide, roleKey) {
    const role = manifest.roles[roleKey];
    if (!role) return [];

    const exactExtras = role.extras.filter((extra) => {
        const guideTitle = normalise(guide.title);
        const extraTitle = normalise(extra.title);

        return extraTitle === guideTitle || extraTitle.startsWith(`${guideTitle} `);
    });
    if (exactExtras.length) return exactExtras.map((extra) => imagePath(role, extra.image));

    const task = (catalog.task_blueprints?.[roleKey] ?? []).find((item) => item.title === guide.title);
    if (task) {
        return task.screens.flatMap((screen) => {
            if (screen.source === 'login') return [imagePath(role, role.login.image)];
            if (screen.source === 'page') {
                const page = role.pages.find((item) => {
                    const key = item.path === '/app/mam' ? 'dashboard' : item.path.split('/').filter(Boolean).at(-1);

                    return key === screen.key;
                });

                return page ? [imagePath(role, page.image)] : [];
            }
            const exact = role.extras.filter((item) => item.title === screen.title);
            const extras = exact.length ? exact : role.extras.filter((item) => item.title.startsWith(`${screen.title} `));

            return extras.map((extra) => imagePath(role, extra.image));
        });
    }

    const page = role.pages.find((item) => routeTemplate(item.path) === guide.route);

    return page ? [imagePath(role, page.image)] : [];
}

function publicImages(guide) {
    const captures = manifest.public?.captures ?? [];
    const names = new Set(matchPublicCaptureNames(guide));

    return captures
        .filter((capture) => names.has(path.basename(capture.image)))
        .map((capture) => `Manual Penguna/${manifest.public.folder}/${capture.image}`.replaceAll('\\', '/'));
}

function matchPublicCaptureNames(guide) {
    if (guide.route === '/') return ['01-laman-utama.png'];
    if (guide.route === '/daftar') return ['02-borang-daftar.png', '02b-pentadbir.png', '02c-persetujuan.png', '03-permohonan-diterima.png'];
    if (guide.route === '/log-masuk') return ['06-log-masuk-pautan.png'];
    if (guide.route === '/bantuan') return ['07-pusat-bantuan.png'];

    return [];
}

let updated = 0;
let referenced = 0;
for (const guide of catalog.guides) {
    let images = [];
    if (guide.panel === 'app') {
        images = [...new Set((guide.roles ?? []).flatMap((role) => roleImages(guide, role)))];
    } else if (guide.panel === 'public') {
        images = publicImages(guide);
    }

    if (images.length === 0) continue;
    for (const image of images) await access(path.resolve(projectRoot, image));
    if (JSON.stringify(images) !== JSON.stringify(guide.images ?? [])) updated += 1;
    guide.images = images;
    referenced += images.length;
}

await writeFile(catalogPath, `${JSON.stringify(catalog, null, 2)}\n`, 'utf8');
console.log(JSON.stringify({ guides: catalog.guides.length, updated, referenced }, null, 2));
