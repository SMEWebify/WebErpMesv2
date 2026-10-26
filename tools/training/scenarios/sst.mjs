// Guide « Risques et santé » : types d'habilitation, incident → risque → conformité →
// formations, matrice de polyvalence et écarts. Données préfixées [FORMATION].
// L'onglet « Écarts constatés » a besoin d'une tâche affectée à un opérateur : le scénario
// en affecte une le temps de la capture, puis la remet sans opérateur.
// Usage : node tools/training/scenarios/sst.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const u = p => `${BASE}/fr${p}`;
const tinker = code => execFileSync(PHP, ['artisan', 'tinker', '--execute', code]).toString().trim().split('\n').pop();

const { browser, page, shot: rawShot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/sst' });
const shot = async (...a) => { await page.evaluate(() => document.activeElement?.blur()); return rawShot(...a); };
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'sst', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        if (ids.task) tinker(`App\\Models\\Planning\\Task::whereKey(${ids.task})->update(['user_id' => null]);`);
        await browser.close();
        process.exit(1);
    }
}

// Choisit un utilisateur par son nom, que la liste soit une Tom Select ou un select natif.
async function pickUser(form, name) {
    await form.locator('select[name="user_id"]').evaluate((el, name) => {
        const opt = [...el.options].find(o => o.text.trim() === name);
        if (el.tomselect) el.tomselect.setValue(opt.value);
        else { el.value = opt.value; el.dispatchEvent(new Event('change', { bubbles: true })); }
    }, name);
}
const formOf = suffix => page.locator(`form[action$="${suffix}"]`);
const submitOf = form => form.locator('button[type=submit]');
async function submit(form) {
    await Promise.all([page.waitForNavigation(), submitOf(form).click()]);
    await settle(page);
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

const TYPES = [
    { code: 'F-PLI', label: '[FORMATION] Conduite plieuse', color: '#fd7e14', months: 60, resources: /^(BENDING_MACHINE|PRESS) / },
    { code: 'F-SST', label: '[FORMATION] Sauveteur secouriste du travail', color: '#dc3545', months: 24 },
    { code: 'F-CACES3', label: '[FORMATION] CACES R489 cat. 3', color: '#0d6efd', months: 60 },
    { code: 'F-MIG', label: '[FORMATION] Soudage MIG', color: '#6f42c1', months: 0, resources: /^MIG_WELDING / },
];

await step('1. Types d\'habilitation', async () => {
    await page.goto(u('/osh/trainings/skills-matrix'));
    await settle(page);
    await page.locator('a[href="#Types"]').click();
    let first = true;
    for (const t of TYPES) {
        if (await page.locator('#Types table td', { hasText: new RegExp(`^${t.code}$`) }).count()) continue;
        const form = formOf('/osh/trainings/types');
        await form.locator('#code').fill(t.code);
        await form.locator('#label').fill(t.label);
        await form.locator('#color').fill(t.color);
        await form.locator('#validity_months').fill(String(t.months));
        if (t.resources) {
            const values = await form.locator('#resources option').evaluateAll((os, src) =>
                os.filter(o => new RegExp(src).test(o.text)).map(o => o.value), t.resources.source);
            await form.locator('#resources').selectOption(values);
        }
        if (first) {
            await shot('type-creation', { title: 'Nouveau type d’habilitation, lié aux machines', highlight: [form.locator('#code'), form.locator('#validity_months'), form.locator('#resources'), submitOf(form)] });
            first = false;
        }
        await submit(form);
        await page.locator('a[href="#Types"]').click();
    }
    await shot('types-liste', { title: 'Types d’habilitation' });
});

await step('2. Incident', async () => {
    await page.goto(u('/osh/incidents'));
    await settle(page);
    const form = formOf('/osh/incidents/create');
    await form.locator('[name="incident_date"]').fill('2026-10-20');
    await form.locator('[name="description"]').fill('[FORMATION] Pincement de doigt à la plieuse lors d’un changement d’outillage');
    await form.locator('[name="severity"]').selectOption('2');
    await form.locator('[name="corrective_actions"]').fill('Consignation avant changement d’outil, rappel des consignes au poste');
    await pickUser(form, 'Test');
    await shot('incident-creation', { title: 'Déclarer un incident', highlight: [form.locator('[name="incident_date"]'), form.locator('[name="severity"]'), form.locator('[name="corrective_actions"]'), submitOf(form)] });
    await submit(form);

    const row = page.locator('tr', { hasText: '[FORMATION] Pincement' }).last();
    await row.locator('[data-bs-target^="#incident"]').click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('[name="statut"]').selectOption('2');
    await modal.locator('[name="resolution_date"]').fill('2026-10-24');
    await shot('incident-resolution', { title: 'Passer l’incident à « Résolue »', highlight: [modal.locator('[name="statut"]'), modal.locator('[name="resolution_date"]'), modal.locator('button[type=submit]')] });
    await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
    await settle(page);
    await shot('incidents-liste', { title: 'Registre des incidents' });
});

await step('3. Risque', async () => {
    await page.goto(u('/osh/risks'));
    await settle(page);
    const form = formOf('/osh/risks/create');
    const section = await form.locator('[name="section_id"] option').evaluateAll(os => (os.find(o => /bend/i.test(o.text)) || os[0]).value);
    await form.locator('[name="section_id"]').selectOption(section);
    await form.locator('[name="description"]').fill('[FORMATION] Écrasement des mains entre poinçon et matrice — presse plieuse');
    await form.locator('[name="severity"]').selectOption('3');
    await form.locator('[name="probability"]').selectOption('2');
    await form.locator('[name="preventive_measures"]').fill('Barrage immatériel, pédale 3 positions, habilitation F-PLI obligatoire');
    await pickUser(form, 'Admin');
    await shot('risque-creation', { title: 'Évaluer un risque : gravité et probabilité', highlight: [form.locator('[name="section_id"]'), form.locator('[name="severity"]'), form.locator('[name="probability"]'), form.locator('[name="preventive_measures"]')] });
    await submit(form);
    await shot('risques-liste', { title: 'Évaluation des risques' });
});

await step('4. Conformité', async () => {
    await page.goto(u('/osh/conformities'));
    await settle(page);
    const form = formOf('/osh/conformities/create');
    await form.locator('[name="document_type"]').fill('[FORMATION] VGP presse plieuse');
    await form.locator('[name="description"]').fill('Vérification générale périodique annuelle');
    await form.locator('[name="expiration_date"]').fill('2026-12-15');
    const section = await form.locator('[name="section_id"] option').evaluateAll(os => (os.find(o => /bend/i.test(o.text)) || os[0]).value);
    await form.locator('[name="section_id"]').selectOption(section);
    await pickUser(form, 'Admin');
    await shot('conformite-creation', { title: 'Suivre une vérification réglementaire', highlight: [form.locator('[name="document_type"]'), form.locator('[name="expiration_date"]'), submitOf(form)] });
    await submit(form);

    const row = page.locator('tr', { hasText: '[FORMATION] VGP' }).last();
    await row.locator('[data-bs-target^="#conformite"]').click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('[name="statut"]').selectOption('2');
    await shot('conformite-statut', { title: 'Statut de la conformité', highlight: modal.locator('[name="statut"]') });
    await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
    await settle(page);
    await shot('conformites-liste', { title: 'Registre des conformités' });
});

const TRAININGS = [
    { user: 'Test', type: 'F-PLI', label: '[FORMATION] Conduite plieuse', date: '2026-10-22', end: '2031-10-22' },
    { user: 'Admin', type: 'F-SST', label: '[FORMATION] SST initial', date: '2024-11-15', end: '2026-11-30' },
    { user: 'Test', type: 'F-CACES3', label: '[FORMATION] CACES 3', date: '2021-06-10', end: '2026-06-30' },
    { user: 'Admin', type: 'F-MIG', label: '[FORMATION] Soudage MIG', date: '2026-09-01', end: '', notObtained: true },
];

await step('5. Formations', async () => {
    await page.goto(u('/osh/trainings'));
    await settle(page);
    let first = true;
    for (const t of TRAININGS) {
        const form = formOf('/osh/trainings/create');
        await form.locator('[name="type_of_training"]').fill(t.label);
        const type = await form.locator('[name="training_type_id"] option').evaluateAll((os, code) => os.find(o => o.text.startsWith(code + ' '))?.value, t.type);
        await form.locator('[name="training_type_id"]').selectOption(type);
        await form.locator('[name="training_date"]').fill(t.date);
        if (t.end) await form.locator('[name="expiration_date"]').fill(t.end);
        await pickUser(form, t.user);
        if (first) {
            await shot('formation-creation', { title: 'Enregistrer une formation', highlight: [form.locator('[name="training_type_id"]'), form.locator('[name="training_date"]'), form.locator('[name="expiration_date"]'), submitOf(form)] });
            first = false;
        }
        await submit(form);
        if (t.notObtained) {
            // Par la cellule exacte : les fenêtres cachées des autres lignes listent aussi les types.
            const row = page.locator('tr', { has: page.locator(`td:text-is("${t.label}")`) });
            await row.locator('[data-bs-target^="#trainingModal"]').click();
            const modal = page.locator('.modal.show');
            await modal.waitFor();
            await modal.locator('[name="certification_obtained"]').selectOption('2');
            await shot('formation-non-obtenue', { title: 'Certification non obtenue', highlight: modal.locator('[name="certification_obtained"]') });
            await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
            await settle(page);
        }
    }
    await shot('formations-liste', { title: 'Liste des formations' });
});

await step('6. Matrice', async () => {
    await page.goto(u('/osh/trainings/skills-matrix'));
    await settle(page);
    await shot('matrice', { title: 'Matrice de polyvalence' });
});

await step('7. Écarts', async () => {
    ids.task = tinker("echo optional(App\\Models\\Planning\\Task::whereNull('user_id')->whereHas('resources', fn($q) => $q->whereIn('methods_ressources.code', ['PRESS','BENDING_MACHINE']))->first())->id;");
    if (!ids.task) throw new Error('Aucune tâche sur la presse ou la plieuse');
    tinker(`App\\Models\\Planning\\Task::whereKey(${ids.task})->update(['user_id' => 1]);`);
    try {
        await page.goto(u('/osh/trainings/skills-matrix'));
        await settle(page);
        await page.locator('a[href="#Alerts"]').click();
        await shot('ecarts', { title: 'Écarts constatés : opérateur non habilité' });
    } finally {
        tinker(`App\\Models\\Planning\\Task::whereKey(${ids.task})->update(['user_id' => null]);`);
    }
});

save({ scenario: 'sst', ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
