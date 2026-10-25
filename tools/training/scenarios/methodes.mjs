// Guide « Méthodes » : tour des écrans du menu Méthodes, en lecture seule (aucune donnée créée).
// Usage : node tools/training/scenarios/methodes.mjs   (HEADED=1 pour voir le navigateur)
import { readEnv, openSession, field, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const u = p => `${BASE}/fr${p}`;
const OUT = 'resources/guides/methodes';

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: OUT });

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'methodes', failedAt: title, error: String(e) });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

// Carte de création d'un écran Méthodes (celle qui porte le bouton « Soumettre »).
const createCard = () => page.locator('.card', { has: page.getByRole('button', { name: 'Soumettre' }) }).last();

async function screen(slug, path, title, labels = []) {
    await page.goto(u(path));
    await settle(page);
    const card = createCard();
    const targets = labels.map(l => field(card, l, 'input,select,textarea'));
    await shot(slug, { title, highlight: [...targets, card.getByRole('button', { name: 'Soumettre' })] });
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('Menu Méthodes', async () => {
    const menu = page.locator('.app-sidebar .nav-link:visible', { hasText: /^\s*Méthodes\s*$/ }).first();
    await menu.click();
    await page.waitForTimeout(400);
    await shot('menu', { title: 'Le menu Méthodes déplié', highlight: menu });
});

await step('Unités', () => screen('unites', '/methods/unit', 'Unités — nouvelle unité', ['ID externe', 'Libellé', 'Type']));
await step('Services', () => screen('services', '/methods/service', 'Services — nouveau service', ['Type', 'Taux horaire', 'Marge']));
await step('Familles', () => screen('familles', '/methods/family', 'Familles — nouvelle famille', ['Service', 'Moyen de débit']));
await step('Sections', () => screen('sections', '/methods/section', 'Sections — nouvelle section', ['Libellé', 'Utilisateur']));
await step('Ressources', () => screen('ressources', '/methods/ressources', 'Ressources — nouvelle ressource', ['Capacité', 'Section', 'Service']));
await step('Emplacements', () => screen('emplacements', '/methods/location', 'Emplacements atelier — nouvel emplacement', ['Ressource']));
await step('Outils', () => screen('outils', '/methods/tool', 'Outils — nouvel outil', ['Etat', 'Coût', 'Qt']));
await step('Délais inter-opérations', () => screen('delais', '/methods/operation-transition-delay', 'Délais inter-opérations — nouvelle paire', ['Opération de départ', "Opération d'arrivée", 'Délai']));

await step('Nomenclature standard', async () => {
    await page.goto(u('/methods/standard-nomenclature'));
    await settle(page);
    const view = page.locator('table a, table button', { hasText: /Voir/ }).first();
    await shot('nomenclatures', { title: 'Nomenclatures standard — « Voir » ouvre la gamme du modèle', highlight: [view, createCard().getByRole('button', { name: 'Soumettre' })] });
    await view.click();
    await page.waitForURL(/\/task\//);
    await settle(page);
    await shot('nomenclature-gamme', { title: 'Gamme d’un modèle standard : opérations et matières' });

    // Panneau d'opération ouvert pour montrer les services proposés, puis refermé sans rien enregistrer.
    await page.locator('button[title="Ajouter — Opération"]').click();
    const form = page.locator('form#techcut-form');
    await form.waitFor();
    const svc = field(form, 'Service', 'select');
    const options = await svc.locator('option').allTextContents();
    const laser = options.findIndex(t => /LASER/.test(t));
    await svc.selectOption({ index: laser > 0 ? laser : 1 });
    await field(form, 'Temps de réglage').fill('15');
    await field(form, 'Temps unitaire').fill('3');
    await settle(page);
    await shot('operation-service', {
        title: 'Le service choisi remplit le libellé et calcule coût et prix',
        highlight: [field(form, 'Service', 'select'), field(form, 'Temps de réglage'), field(form, 'Coût unitaire')],
    });
    await form.getByRole('button', { name: 'Annuler' }).click();
});

await step("Vue d'ensemble", async () => {
    await page.goto(u('/methods/overview'));
    await settle(page);
    await shot('vue-ensemble', { title: "Vue d'ensemble méthodes (menu Planification)" });
});

save({ scenario: 'methodes', finishedAt: new Date().toISOString() });
console.log('✔ Captures terminées');
await browser.close();
