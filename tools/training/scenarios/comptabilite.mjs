// Guide « Comptabilité » : paramétrage comptable et lien avec l'expert-comptable.
// LECTURE SEULE : aucun « Soumettre », « Ajouter », « + », « Mettre à jours », aucune
// corbeille, ni « Verrouiller », ni les boutons CSV / XLS / PDF des exports (définitifs).
// Les fenêtres « Modifier » sont ouvertes pour la capture puis fermées sans enregistrer.
// Usage : node tools/training/scenarios/comptabilite.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const u = p => `${BASE}/fr${p}`;
const tinker = code => execFileSync(PHP, ['artisan', 'tinker', '--execute', code]).toString().trim().split('\n').pop();

const { browser, page, shot: rawShot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/comptabilite' });
const shot = async (...a) => { await page.evaluate(() => document.activeElement?.blur()); return rawShot(...a); };
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'comptabilite', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

// Ouvre la fenêtre « Modifier » d'une ligne, capture, puis la ferme sans enregistrer.
async function peekModal(row, name, title, highlight) {
    await row.locator('button[data-bs-target]').first().click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await page.waitForTimeout(300);
    await shot(name, { title, highlight: highlight ? highlight(modal) : undefined });
    await modal.locator('[data-bs-dismiss="modal"]').first().click();
    await modal.waitFor({ state: 'hidden' });
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('1. TVA', async () => {
    await page.goto(u('/accounting/vats'));
    await settle(page);
    await shot('tva-liste', { title: 'Codes TVA', highlight: page.locator('#AccountingNav') });
    const row = page.locator('tr', { has: page.locator('td', { hasText: /^TVA0-UE$/ }) }).first();
    await peekModal(row, 'tva-modification', 'Code TVA : catégorie EN 16931 et motif d’exonération',
        m => [m.locator('[name="en16931_category"]'), m.locator('[name="exemption_reason_code"]'), m.locator('[name="exemption_reason_text"]'), m.locator('[name="legal_mention"]')]);
});

await step('2. Affectations comptables', async () => {
    await page.goto(u('/accounting/allocations'));
    await settle(page);
    await shot('affectations', { title: 'Allocation comptable : une ligne par type et par TVA' });
});

await step('3. Méthodes et conditions de paiement', async () => {
    await page.goto(u('/accounting/payment-methods'));
    await settle(page);
    await shot('methodes-paiement', { title: 'Méthodes de paiement et compte de trésorerie' });
    await page.goto(u('/accounting/payment-conditions'));
    await settle(page);
    await shot('conditions-paiement', { title: 'Conditions de paiement' });
});

await step('4. Régimes, natures et matrices', async () => {
    await page.goto(u('/accounting/vat-references'));
    await settle(page);
    await shot('regimes-natures', { title: 'Régimes et natures de TVA' });
    await page.goto(u('/accounting/vat-matrix-sales'));
    await settle(page);
    await shot('matrice-ventes', { title: 'Matrice TVA ventes' });
    await page.goto(u('/accounting/vat-matrix-purchase'));
    await settle(page);
    await shot('matrice-achats', { title: 'Matrice TVA achats' });
});

await step('5. Régime de TVA d’un client', async () => {
    ids.company = tinker("echo App\\Models\\Companies\\Companies::where('label','like','[FORMATION]%')->latest('id')->value('id');");
    await page.goto(u(`/companies/${ids.company}`));
    await settle(page);
    await page.locator('a.nav-link[href="#Company"]').click();
    await settle(page);
    // L'ancêtre le plus proche : la carte de l'onglet contient aussi ce titre.
    const card = page.locator('.card-title', { hasText: /R[ée]gime de TVA/ }).first().locator("xpath=ancestor::div[contains(concat(' ', normalize-space(@class), ' '), ' card ')][1]");
    await card.scrollIntoViewIfNeeded();
    await shot('societe-regime', { title: 'Fiche client : régime de TVA', highlight: card.locator('select').first() });
});

await step('6. Périodes', async () => {
    await page.goto(u('/accounting/periods-ui'));
    await settle(page);
    await shot('periodes', { title: 'Verrouillage des périodes' });
});

await step('7. Export FEC', async () => {
    await page.goto(u('/admin/imports-exports'));
    await settle(page);
    const app = page.locator('#fec-export-lines-app');
    await app.waitFor();
    const encais = app.locator('#journal_ENCAIS');
    if (!(await encais.isChecked())) await encais.check();
    const dates = app.locator('input[type=date]');
    await dates.nth(0).fill('2026-10-01');
    await dates.nth(1).fill('2026-10-31');
    // « Soumettre » ne fait que filtrer la liste (aucune écriture n'est exportée).
    await app.locator('button', { hasText: /Soumettre/ }).click();
    await settle(page);
    await shot('fec', { title: 'Export FEC : journaux et période', highlight: [app.locator('#journal_ENCAIS'), dates.nth(0), dates.nth(1)] });
});

save({ scenario: 'comptabilite', ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
