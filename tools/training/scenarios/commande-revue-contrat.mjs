// Revue de contrat d'une commande client : check-list, écarts, document contractuel, visa,
// prix figé / prix calculé, report de date avec motif, repères d'équipement, avenant.
// Usage : TRAINING_URL=http://127.0.0.1:8005 node tools/training/scenarios/commande-revue-contrat.mjs
// Toutes les données créées portent le préfixe [FORMATION]. Part d'un tiers [FORMATION] existant
// (créé par le scénario vente-complete).
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { readEnv, openSession, field, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const EMAIL = process.env.TRAINING_EMAIL || env.TRAINING_EMAIL;
const PASSWORD = process.env.TRAINING_PASSWORD || env.TRAINING_PASSWORD;
if (!EMAIL || !PASSWORD) {
    console.error('TRAINING_EMAIL / TRAINING_PASSWORD manquants (.env)');
    process.exit(1);
}
const SLUG = 'commande-revue-contrat';
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');
const ORDER_LABEL = `[FORMATION] Supports tuyauterie ${stamp}`;
const LINE_LABEL = 'Support de tuyauterie inox 316L';
const TAGS = '1RCP-SUP-001, 1RCP-SUP-002, 1RCP-SUP-003, 1RCP-SUP-004';
const u = p => `${BASE}/fr${p}`;
const idFrom = (url, seg) => url.match(new RegExp(`/${seg}/(\\d+)`))?.[1];
const isoIn = days => new Date(Date.now() + days * 86400000).toISOString().slice(0, 10);

// Pièce contractuelle factice (PDF minimal valide).
const PDF = path.join(os.tmpdir(), 'FORMATION-bon-de-commande-client.pdf');
fs.writeFileSync(PDF, '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n');

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: `resources/guides/${SLUG}` });
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: SLUG, failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

async function openOrder(hash) {
    await page.goto(u(`/orders/${ids.order}`));
    await settle(page);
    if (hash) {
        await page.locator(`a[href="#${hash}"][data-bs-toggle="tab"]`).first().click();
        await settle(page);
    }
}

const review = () => page.locator('#ContractReview');
const lineRow = () => page.locator('tbody tr:visible', { hasText: LINE_LABEL }).first();

async function openTaskModal() {
    await lineRow().locator('button.btn-warning[title^="Tâches ("]').click();
    await page.locator('strong', { hasText: `Tâches — ` }).waitFor();
    await settle(page);
}

async function openLineEdit() {
    await lineRow().locator('.dropdown-toggle-split').click();
    await page.locator('.dropdown-menu.show button', { hasText: 'Modifier' }).first().click();
    await page.locator('form#line-drawer-form').waitFor();
    await settle(page);
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([page.waitForURL(/dashboard|setup|settings/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('1. Créer la commande', async () => {
    await page.goto(u('/orders'));
    await settle(page);
    await page.locator('.card-header .nav-link', { hasText: /^Commandes$/ }).first().click();
    await settle(page);
    await page.getByRole('button', { name: /Nouvelle commande/ }).click();
    const modal = page.locator('.modal.show .modal-content').last();
    await modal.locator('.modal-title').waitFor();
    await settle(page);
    // Code propre au scénario : d'autres scénarios créent des commandes en parallèle sur la même base.
    await modal.locator('input.form-control').nth(0).fill(`CDE-FORM-${stamp}`);
    await modal.locator('input.form-control').nth(1).fill(ORDER_LABEL);
    await modal.locator('input[type=date]').fill(isoIn(30));
    const company = modal.locator('select').filter({ has: page.locator('option', { hasText: '[FORMATION] Métallerie Durand' }) }).first();
    const label = await company.locator('option', { hasText: '[FORMATION] Métallerie Durand' }).last().textContent();
    await company.selectOption({ label: label.trim() });
    await settle(page);
    await page.waitForTimeout(800);
    await Promise.all([page.waitForURL(/\/orders\/\d+/, { timeout: 30000 }), modal.getByRole('button', { name: /Enregistrer|Sauvegarder/ }).click()]);
    ids.order = idFrom(page.url(), 'orders');
    await settle(page);
});

await step('2. Ajouter la ligne avec ses repères', async () => {
    await openOrder('Lines');
    await page.locator('button[title="Ajouter une ligne"]').click();
    const form = page.locator('form#line-drawer-form');
    await form.waitFor();
    await form.getByPlaceholder('Code externe').fill('SUP-316-80');
    await form.getByPlaceholder('Description de la ligne').fill(LINE_LABEL);
    await field(form, 'Quantité').fill('4');
    await field(form, 'Prix unitaire').fill('185');
    const tags = form.locator('#line-equipment-tag');
    await tags.fill(TAGS);
    const submit = page.locator('button[type=submit][form="line-drawer-form"]');
    await shot('ligne-reperes', { title: 'Nouvelle ligne : repères d’équipement saisis', highlight: [tags, field(form, 'Quantité'), submit] });
    await submit.click();
    await settle(page);
    await page.getByRole('button', { name: 'Annuler', exact: true }).last().click();
    await settle(page);

    // Une opération sur la ligne, pour ouvrir la fenêtre des tâches (prix calculé).
    const line = await page.evaluate(async (o) => {
        const r = await fetch(`/fr/orders/${o}/lines/json`, { headers: { Accept: 'application/json' } });
        const j = await r.json();
        const rows = j.data || j.lines || j;
        return rows.find(l => l.line_type === 'article' || !l.line_type);
    }, ids.order);
    ids.line = line.id;
    await page.goto(new URL(line.task_url, BASE).href);
    await settle(page);
    await page.locator('button[title="Ajouter — Opération"]').click();
    const f = page.locator('form#techcut-form');
    await f.waitFor();
    const svc = field(f, 'Service', 'select');
    const opts = await svc.locator('option').allTextContents();
    const pick = opts.findIndex(t => /pli|laser|découpe/i.test(t));
    await svc.selectOption({ index: pick > 0 ? pick : 1 });
    await field(f, 'N° ordre').fill('10');
    await f.getByPlaceholder("Libellé de l'opération").fill('Découpe et pliage');
    await field(f, 'Temps de réglage').fill('15');
    await field(f, 'Temps unitaire').fill('6');
    const cost = field(f, 'Coût unitaire');
    const price = field(f, 'Prix unitaire');
    if (!(parseFloat(await cost.inputValue()) > 0)) await cost.fill('40');
    if (!(parseFloat(await price.inputValue()) > 0)) await price.fill('60');
    const [resp] = await Promise.all([page.waitForResponse(r => r.url().endsWith('/task') && r.request().method() === 'POST'), f.locator('button[type=submit]').click()]);
    if (!resp.ok()) throw new Error(`Opération refusée (${resp.status()}) : ${await resp.text()}`);
    await settle(page);
});

await step('3. Bandeau et colonne « Revue »', async () => {
    await openOrder('Order');
    const banner = page.locator('#Order .alert', { hasText: 'revue de contrat' }).first();
    await shot('commande-bandeau', { title: 'Fiche commande : bandeau « revue non visée » et onglet « Revue de contrat »', highlight: [banner, page.locator('a[href="#ContractReview"]').first()] });

    await page.goto(u('/orders'));
    await settle(page);
    await page.locator('.card-header .nav-link', { hasText: /^Commandes$/ }).first().click();
    await settle(page);
    const search = page.getByPlaceholder('Libellé ou repère…');
    await search.fill('1RCP-SUP-002');
    await settle(page);
    await page.waitForTimeout(800);
    await settle(page);
    const col = page.locator('tbody .badge', { hasText: 'Revue à faire' }).first();
    await shot('index-revue', { title: 'Liste des commandes : recherche par repère et colonne « Revue »', highlight: [search, col] });
});

await step('4. Préparer la revue', async () => {
    await openOrder('ContractReview');
    const r = review();
    await r.locator('h6', { hasText: 'Points revus' }).waitFor();
    await shot('revue-vide', { title: 'Onglet « Revue de contrat » : revue à faire' });
    for (const key of ['requirements_understood', 'lead_time_achievable', 'quality_requirements_identified']) {
        await r.locator(`#review-item-${key}`).check();
    }
    await r.locator('#review-gaps').fill('Bon de commande client reçu par e-mail, original papier attendu.\nQuantité 4 au lieu de 5 au devis : confirmée par le client.');
    const saveBtn = r.getByRole('button', { name: 'Enregistrer la préparation' });
    await shot('revue-preparation', { title: 'Cocher les points revus, décrire les écarts, « Enregistrer la préparation »', highlight: [r.locator('#review-item-requirements_understood'), r.locator('#review-gaps'), saveBtn] });
    await saveBtn.click();
    await settle(page);
    await r.locator('.alert-success', { hasText: 'Préparation enregistrée' }).waitFor();
});

await step('5. Déposer le document contractuel', async () => {
    await page.locator('#ContractReview a[href="#Documents"]').click();
    await settle(page);
    // L'onglet est rangé dans le menu « Plus » sur cet écran : le refermer avant la capture.
    await page.keyboard.press('Escape');
    await page.evaluate(() => document.querySelectorAll('#DocumentTabs .dropdown-menu.show, #DocumentTabs .dropdown-toggle.show')
        .forEach(el => { el.classList.remove('show'); el.setAttribute('aria-expanded', 'false'); }));
    const docs = page.locator('#Documents');
    const role = docs.locator('.wem-dropzone-form select').first();
    await role.selectOption('contractuel');
    await shot('ged-role', { title: 'Onglet « Documents » : type « Document contractuel », puis déposer le fichier', highlight: [role, docs.locator('.wem-dropzone')] });
    await docs.locator('input[type=file]').setInputFiles(PDF);
    await settle(page);
    await docs.getByText('FORMATION-bon-de-commande-client').first().waitFor({ timeout: 20000 });
    await page.locator('a[href="#ContractReview"][data-bs-toggle="tab"]').first().click();
    await settle(page);
    const card = review().locator('.card', { has: page.locator('h3', { hasText: 'Documents contractuels' }) }).first();
    await card.getByText('FORMATION-bon-de-commande-client').first().waitFor();
    await review().locator('#review-item-contract_documents_received').check();
    await shot('revue-documents', { title: 'Le document apparaît dans « Documents contractuels » ; cocher le point correspondant', highlight: [card, review().locator('#review-item-contract_documents_received')] });
});

await step('6. Prix calculé tant que la commande est en préparation', async () => {
    // Enregistrer la case cochée avant de quitter l'onglet.
    await review().getByRole('button', { name: 'Enregistrer la préparation' }).click();
    await settle(page);
    await page.locator('a[href="#Lines"][data-bs-toggle="tab"]').first().click();
    await settle(page);
    await openTaskModal();
    const btn = page.getByRole('button', { name: 'Activer prix calculé' });
    await shot('prix-preparation', { title: 'Fenêtre des tâches : « Activer prix calculé » libre en préparation', highlight: btn });
    await page.locator('button.btn-outline-dark:has(i.fa-times)').last().click();
    await settle(page);
});

await step('7. Viser la revue', async () => {
    await page.locator('a[href="#ContractReview"][data-bs-toggle="tab"]').first().click();
    await settle(page);
    const approve = review().getByRole('button', { name: 'Viser : revue validée' });
    await shot('revue-visa-boutons', { title: 'Visa : « Viser : revue validée » ou « Viser : revue rejetée »', highlight: [approve, review().getByRole('button', { name: 'Viser : revue rejetée' })] });
    await approve.click();
    await settle(page);
    await review().locator('.alert-success', { hasText: 'Revue visée' }).waitFor();
    await shot('revue-figee', { title: 'Revue visée : figée, auteur et date posés', highlight: review().locator('.alert-success', { hasText: 'Revue visée' }) });
});

await step('8. Prix figé après le visa', async () => {
    await openOrder('Lines');
    await openTaskModal();
    const btn = page.getByRole('button', { name: 'Activer prix calculé' });
    await btn.click();
    await page.locator('#calc-price-reason').waitFor();
    await page.locator('#calc-price-reason').fill('Exemple de motif');
    await shot('prix-motif', { title: 'Commande validée : motif exigé pour réactiver le prix calculé', highlight: [page.locator('#calc-price-reason'), page.getByRole('button', { name: 'Activer', exact: true })] });
    // On n'active pas : « Annuler » referme la demande de motif.
    await page.locator('#calc-price-reason').locator('xpath=following::button[normalize-space()="Annuler"][1]').click();
    await page.locator('button.btn-outline-dark:has(i.fa-times)').last().click();
    await settle(page);
});

await step('9. Reporter la date d’une ligne', async () => {
    await openLineEdit();
    const form = page.locator('form#line-drawer-form');
    const date = field(form, 'Date livraison');
    await date.fill(isoIn(45));
    const reason = form.locator('#line-delay-reason');
    await reason.waitFor();
    await reason.fill('[FORMATION] Retard matière inox chez le fournisseur');
    const submit = page.locator('button[type=submit][form="line-drawer-form"]');
    await shot('report-motif', { title: 'Modifier la date de livraison : le champ « Motif du report » apparaît', highlight: [date, reason, submit] });
    await submit.click();
    await settle(page);
    await page.waitForTimeout(500);
    const cell = lineRow().getByText('Promise le').first();
    await cell.waitFor();
    await shot('report-ligne', { title: 'La ligne affiche la nouvelle date et « Promise le » (date d’origine)', highlight: cell });
    await openLineEdit();
    const hist = page.locator('span', { hasText: 'Historique des reports' }).first();
    await page.locator('li', { hasText: 'Retard matière' }).first().waitFor();
    await shot('report-historique', { title: 'Panneau de la ligne : « Historique des reports »', highlight: hist.locator('xpath=ancestor::div[contains(@class,"border-top")][1]') });
    await page.getByRole('button', { name: 'Annuler', exact: true }).last().click();
    await settle(page);
});

await step('10. Créer un avenant', async () => {
    await page.locator('a[href="#ContractReview"][data-bs-toggle="tab"]').first().click();
    await settle(page);
    const r = review();
    await r.locator('#amendment-reason').fill('[FORMATION] Le client passe la quantité de 4 à 6 supports.');
    await r.locator('#amendment-requested-by').selectOption({ index: 1 });
    const create = r.getByRole('button', { name: "Créer l'avenant et rouvrir la revue" });
    await shot('avenant-formulaire', { title: 'Carte « Avenants » : motif, demandeur, date, puis créer', highlight: [r.locator('#amendment-reason'), r.locator('#amendment-requested-by'), create] });
    await create.click();
    await settle(page);
    await r.locator('.alert-warning', { hasText: 'Revue rouverte' }).waitFor();
    await shot('avenant-revue-rouverte', { title: 'Avenant n° 1 en attente : la revue est rouverte', highlight: [r.locator('.alert-warning', { hasText: 'Revue rouverte' }), r.locator('li.list-group-item', { hasText: 'Avenant n° 1' })] });
});

await step('11. Annuler l’avenant', async () => {
    const cancel = review().getByRole('button', { name: "Annuler l'avenant" });
    await shot('avenant-annuler', { title: '« Annuler l’avenant » rétablit la revue d’avant', highlight: cancel });
    await cancel.click();
    await settle(page);
    await review().locator('.alert-success', { hasText: 'Revue visée' }).waitFor();
});

await step('12. Avenant visé', async () => {
    const r = review();
    await r.locator('#amendment-reason').fill('[FORMATION] Le client passe la quantité de 4 à 6 supports.');
    await r.getByRole('button', { name: "Créer l'avenant et rouvrir la revue" }).click();
    await settle(page);
    await r.locator('.alert-warning', { hasText: 'Revue rouverte' }).waitFor();
    // Modifier la ligne (quantité 6, deux repères de plus) comme le veut l'avenant.
    await page.locator('a[href="#Lines"][data-bs-toggle="tab"]').first().click();
    await settle(page);
    await openLineEdit();
    const form = page.locator('form#line-drawer-form');
    await field(form, 'Quantité').fill('6');
    await form.locator('#line-equipment-tag').fill(`${TAGS}, 1RCP-SUP-005, 1RCP-SUP-006`);
    await page.locator('button[type=submit][form="line-drawer-form"]').click();
    await settle(page);
    await page.locator('a[href="#ContractReview"][data-bs-toggle="tab"]').first().click();
    await settle(page);
    const approve = r.getByRole('button', { name: 'Viser : revue validée' });
    await shot('avenant-visa', { title: 'Viser la revue rouverte = viser l’avenant', highlight: approve });
    await approve.click();
    await settle(page);
    await r.locator('li.list-group-item', { hasText: 'Avenant n° 2' }).locator('.badge', { hasText: 'Validé' }).waitFor();
    await shot('avenant-valide', { title: 'Avenant n° 2 validé : TTC avant / après', highlight: r.locator('li.list-group-item', { hasText: 'Avenant n° 2' }) });
});

save({ scenario: SLUG, order: ORDER_LABEL, ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
