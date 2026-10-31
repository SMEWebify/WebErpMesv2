// Fournisseur qualifié obligatoire : fournisseur [FORMATION] neuf (non qualifié), commande refusée,
// passe-droit motivé, carte de la fiche commande, évaluation approuvée sur cette commande, fournisseur
// qualifié, export CSV (montré, jamais cliqué).
// Option PURCHASES_REQUIRE_QUALIFIED_SUPPLIER=true requise sur l'instance locale.
// Usage : node tools/training/scenarios/fournisseurs-qualifies.mjs   (HEADED=1 pour voir le navigateur)
import { readEnv, openSession, field, settle, tab } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const EMAIL = process.env.TRAINING_EMAIL || env.TRAINING_EMAIL;
const PASSWORD = process.env.TRAINING_PASSWORD || env.TRAINING_PASSWORD;
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');
const SUPPLIER = `[FORMATION] Laquage Armor ${stamp}`;
const u = p => `${BASE}/fr${p}`;
const idFrom = (url, seg) => url.match(new RegExp(`/${seg}/(\\d+)`))?.[1];

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/fournisseurs-qualifies' });
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR-fq.png', fullPage: true }).catch(() => {});
        save({ scenario: 'fournisseurs-qualifies', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

async function openEvaluation() {
    await page.goto(u(`/companies/${ids.supplier}`));
    await settle(page);
    await page.locator('a[href="#evaluation"]').click();
    await settle(page);
}

async function openCreateModal() {
    await page.goto(u('/purchases'));
    await settle(page);
    await page.locator('.app-main .nav-link', { hasText: "Liste commande d'achat" }).first().click();
    await settle(page);
    await page.getByRole('button', { name: /Nouvelle commande d'achat/ }).first().click();
    const modal = page.locator('.modal.show').last();
    await field(modal, 'Nom du tiers', 'select').waitFor();
    return modal;
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('Fournisseur [FORMATION]', async () => {
    await page.goto(u('/companies'));
    await settle(page);
    await tab(page, 'Liste des Tiers');
    await page.getByRole('button', { name: /Ajouter un tiers/i }).click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await field(modal, 'Nom du tiers').fill(SUPPLIER);
    await field(modal, 'Utilisateur responsable', 'select').selectOption({ index: 1 });
    await Promise.all([page.waitForURL(/\/companies\/\d+/), modal.getByRole('button', { name: 'Enregistrer' }).click()]);
    ids.supplier = idFrom(page.url(), 'companies');
    await settle(page);

    await page.locator('a[href="#Company"]').click();
    await field(page.locator('#Company'), 'Etat fournisseur', 'select').selectOption('2');
    await page.locator('#Company button[type=submit].btn-info').first().click();
    await page.locator('.alert-success').first().waitFor();

    await page.locator('a[href="#Adresses"]').click();
    const adr = page.locator('#Adresses');
    await field(adr, 'Tri').fill('1');
    await field(adr, 'Libellé').fill('Usine');
    await field(adr, 'Adresse').fill('5 rue des Peintres');
    await field(adr, 'Code postal').fill('22000');
    await field(adr, 'Ville').fill('Saint-Brieuc');
    await field(adr, 'Pays').fill('France');
    await page.locator('#addr-default-create').check().catch(() => {});
    await adr.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);

    await page.locator('a[href="#Contact"]').click();
    const ct = page.locator('#Contact');
    await field(ct, 'Tri').fill('1');
    await field(ct, 'Civilité', 'select').selectOption({ index: 1 });
    await field(ct, 'Prénom').fill('Anne');
    await field(ct, /^Nom/).fill('Le Bihan');
    await page.locator('#contact-default-create').check().catch(() => {});
    await ct.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);
});

await step('1. Fournisseur non qualifié', async () => {
    await openEvaluation();
    const card = page.locator('#evaluation .card', { hasText: 'Qualification' }).first();
    const empty = page.locator('#evaluation').getByText("Aucune commande d'achat disponible").first();
    await shot('fiche-non-qualifie', { title: 'Onglet « Évaluations fournisseurs » : « Non qualifié », aucune commande à évaluer', highlight: [card, empty] });
});

await step('2. Commande refusée', async () => {
    const modal = await openCreateModal();
    await field(modal, 'Libellé').fill(`[FORMATION] Laquage RAL 7016 ${stamp}`);
    const supplier = field(modal, 'Nom du tiers', 'select');
    const label = (await supplier.locator('option').allTextContents()).find(t => t.includes(SUPPLIER));
    await supplier.selectOption({ label });
    await page.waitForTimeout(800);
    await settle(page);
    const badge = modal.locator('.badge', { hasText: 'Non qualifié' }).first();
    const save = modal.getByRole('button', { name: 'Enregistrer' });
    await shot('creation-non-qualifie', { title: 'Nouvelle commande d’achat : le fournisseur porte « Non qualifié »', highlight: [supplier, badge, save] });
    await save.click();
    const dialog = page.locator('.modal.show', { hasText: 'Fournisseur non qualifié' }).last();
    await dialog.waitFor();
    await settle(page);
    const reason = dialog.locator('#supplier-override-reason');
    await shot('fenetre-refus', { title: 'Fenêtre « Fournisseur non qualifié » : motif obligatoire', highlight: reason });
    await reason.fill('Premier lot de qualification : 20 pièces témoins, contrôle à réception renforcé.');
    const force = dialog.getByRole('button', { name: 'Commander quand même' });
    await shot('passe-droit', { title: 'Motif saisi, puis « Commander quand même »', highlight: [reason, force] });
    await Promise.all([page.waitForURL(/\/purchases\/\d+/, { timeout: 30000 }), force.click()]);
    ids.purchase = idFrom(page.url(), 'purchases');
    await settle(page);
});

await step('3. Carte de la commande', async () => {
    const card = page.locator('.card', { hasText: 'Passe-droits' }).last();
    await shot('commande-carte', { title: 'Fiche commande d’achat : carte « Qualification » et passe-droit tracé', highlight: card });
});

await step('4. Évaluer et qualifier', async () => {
    await openEvaluation();
    const form = page.locator('#evaluation form[action*="ratings"]');
    await form.locator('#purchases_id').selectOption({ index: 0 });
    await form.locator('#rating').selectOption('4');
    await form.locator('#evaluation_status').selectOption('approved');
    const review = new Date(Date.now() + 365 * 86400000).toISOString().slice(0, 10);
    await form.locator('#next_review_at').fill(review);
    await form.locator('#evaluation_score_quality').fill('85');
    await form.locator('#evaluation_score_logistics').fill('80');
    await form.locator('#evaluation_score_service').fill('90');
    await form.locator('#comment').fill('[FORMATION] Audit sur site et pièces témoins conformes.');
    const submit = form.locator('button[type=submit]');
    await shot('evaluation-approuvee', {
        title: 'Nouvelle évaluation : statut « Approuvé » et date de prochaine revue',
        highlight: [form.locator('#purchases_id'), form.locator('#evaluation_status'), form.locator('#next_review_at'), submit],
    });
    await Promise.all([page.waitForLoadState('load'), submit.click()]);
    await settle(page);
    await page.locator('a[href="#evaluation"]').click();
    await settle(page);
    const badge = page.locator('#evaluation .badge', { hasText: 'Qualifié' }).first();
    await shot('fiche-qualifie', { title: 'Le fournisseur est « Qualifié »', highlight: badge });
});

await step('5. Commande acceptée', async () => {
    const modal = await openCreateModal();
    const supplier = field(modal, 'Nom du tiers', 'select');
    const label = (await supplier.locator('option').allTextContents()).find(t => t.includes(SUPPLIER));
    await supplier.selectOption({ label });
    await page.waitForTimeout(800);
    await settle(page);
    const badge = modal.locator('.badge', { hasText: 'Qualifié' }).first();
    await shot('creation-qualifie', { title: 'Fournisseur qualifié : badge vert, la commande se crée sans fenêtre', highlight: [supplier, badge] });
    await modal.getByRole('button', { name: 'Annuler' }).click();
});

await step('6. Export CSV', async () => {
    await page.goto(u('/purchases'));
    await settle(page);
    const exp = page.locator('a[href*="suppliers/qualified/export"]').first();
    await shot('export-csv', { title: 'Liste des fournisseurs qualifiés : « Fournisseurs qualifiés (CSV) »', highlight: exp });
});

save({ scenario: 'fournisseurs-qualifies', supplier: SUPPLIER, ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
