// Parcours de vente complet : prospect → lead → opportunité → devis (ligne + gamme)
// → commande → déclaration de production → BL → facture.
// Usage : node tools/training/scenarios/vente-complete.mjs   (HEADED=1 pour voir le navigateur)
// Toutes les données créées portent le préfixe [FORMATION].
import { readEnv, openSession, field, settle, tab } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const EMAIL = process.env.TRAINING_EMAIL || env.TRAINING_EMAIL;
const PASSWORD = process.env.TRAINING_PASSWORD || env.TRAINING_PASSWORD;
if (!EMAIL || !PASSWORD) {
    console.error('TRAINING_EMAIL / TRAINING_PASSWORD manquants (.env)');
    process.exit(1);
}
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');
const COMPANY = `[FORMATION] Métallerie Durand ${stamp}`;
const u = p => `${BASE}/fr${p}`;
const idFrom = (url, seg) => url.match(new RegExp(`/${seg}/(\\d+)`))?.[1];

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/vente-complete' });
const ids = {};

// Fiche document ouverte sur l'onglet des lignes (un simple changement de #hash ne recharge pas la page).
async function openLines(path) {
    await page.goto(u(path));
    await settle(page);
    await page.locator('a[href="#Lines"]').first().click();
    await settle(page);
}

// Lien dont l'adresse est posée après un chargement (href="#" au départ) : attendre la vraie.
async function followHref(link) {
    await link.waitFor();
    await page.waitForFunction(el => el.getAttribute('href') && el.getAttribute('href') !== '#', await link.elementHandle());
    await page.goto(new URL(await link.getAttribute('href'), page.url()).href);
    await settle(page);
}

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'vente-complete', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await shot('connexion', { title: 'Écran de connexion', highlight: [page.locator('input[name="email"]'), page.locator('input[name="password"]'), page.locator('button[type=submit]')] });
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([page.waitForURL(/dashboard|setup|settings/), page.click('button[type=submit]')]);
    await settle(page);
    await shot('tableau-de-bord', { title: 'Tableau de bord après connexion' });
});

await step('1. Créer le prospect', async () => {
    await page.goto(u('/companies'));
    await settle(page);
    await tab(page, 'Liste des Tiers');
    const add = page.getByRole('button', { name: /Ajouter un tiers/i });
    await shot('tiers-liste', { title: 'Liste des tiers — bouton « Ajouter un tiers »', highlight: add });
    await add.click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await field(modal, 'Nom du tiers').fill(COMPANY);
    await field(modal, 'Utilisateur responsable', 'select').selectOption({ index: 1 });
    await shot('tiers-modale', { title: 'Fenêtre « Ajouter un tiers » remplie', highlight: [field(modal, 'Nom du tiers'), field(modal, 'Utilisateur responsable', 'select'), modal.getByRole('button', { name: 'Enregistrer' })] });
    await Promise.all([page.waitForURL(/\/companies\/\d+/), modal.getByRole('button', { name: 'Enregistrer' }).click()]);
    ids.company = idFrom(page.url(), 'companies');
    await settle(page);

    await page.locator('a[href="#Company"]').click();
    const statu = field(page.locator('#Company'), 'Etat client', 'select');
    await statu.selectOption('3');
    const update = page.locator('#Company button[type=submit].btn-info').first();
    await shot('tiers-statut-prospect', { title: 'Onglet « Détail » : « Etat client » = Prospect', highlight: statu });
    await shot('tiers-mettre-a-jour', { title: 'Enregistrer avec « Mettre à jour » en bas de la fiche', highlight: update });
    await update.click();
    await page.locator('.alert-success').first().waitFor();

    await page.locator('a[href="#Adresses"]').click();
    const adr = page.locator('#Adresses');
    await field(adr, 'Tri').fill('1');
    await field(adr, 'Libellé').fill('Siège');
    await field(adr, 'Adresse').fill('12 rue des Forges');
    await field(adr, 'Code postal').fill('35000');
    await field(adr, 'Ville').fill('Rennes');
    await field(adr, 'Pays').fill('France');
    await shot('tiers-adresse', { title: 'Ajouter l’adresse du prospect', highlight: adr.getByRole('button', { name: 'Enregistrer' }) });
    await adr.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);

    await page.locator('a[href="#Contact"]').click();
    const ct = page.locator('#Contact');
    await field(ct, 'Tri').fill('1');
    await field(ct, 'Civilité', 'select').selectOption({ index: 1 });
    await field(ct, 'Prénom').fill('Claire');
    await field(ct, /^Nom/).fill('Durand');
    await shot('tiers-contact', { title: 'Ajouter le contact du prospect', highlight: ct.getByRole('button', { name: 'Enregistrer' }) });
    await ct.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);
});

await step('2. Créer le lead', async () => {
    await page.goto(u('/leads'));
    await settle(page);
    await tab(page, 'Liste des Lead');
    const add = page.getByRole('button', { name: /Nouveau Lead/i });
    await shot('lead-liste', { title: 'Liste des leads — bouton « Nouveau Lead »', highlight: add });
    await add.click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await field(modal, 'Tier', 'select').selectOption({ label: COMPANY });
    await page.waitForTimeout(800);
    await field(modal, 'adresse', 'select').selectOption({ index: 1 });
    await field(modal, 'contact', 'select').selectOption({ index: 1 });
    await field(modal, 'Utilisateur', 'select').selectOption({ index: 1 });
    await field(modal, 'Priorité', 'select').selectOption('2');
    await field(modal, 'Source', 'input').fill('Salon Industrie');
    const submit = modal.getByRole('button', { name: 'Soumettre' });
    await shot('lead-modale', { title: 'Fenêtre « Nouveau Lead » remplie', highlight: submit });
    await Promise.all([page.waitForURL(/\/leads\/\d+/), submit.click()]);
    ids.lead = idFrom(page.url(), 'leads');
    await settle(page);
});

await step('3. Transformer le lead en opportunité', async () => {
    const btn = page.locator('a.btn-success[href*="/leads/store/opportunity/"]');
    await shot('lead-fiche', { title: 'Fiche du lead — « Nouvelle Oportunité »', highlight: btn });
    await Promise.all([page.waitForURL(/\/opportunities\/\d+/), btn.click()]);
    ids.opportunity = idFrom(page.url(), 'opportunities');
    await settle(page);
});

await step('4a. Créer le devis', async () => {
    await shot('opportunite-synthese', { title: 'Opportunité créée — onglet « Synthèse »' });
    await page.locator('a[href="#Opportunity"]').first().click();
    await settle(page);
    const link = page.locator('a.btn-success[href*="/opportunities/store/quote/"]');
    const card = page.locator('.card', { has: link }).last();
    if (!(await link.isVisible())) {
        await card.locator('[data-lte-toggle="card-collapse"]').first().click();
        await page.waitForTimeout(600);
    }
    await shot('opportunite-fiche', { title: 'Fiche opportunité — carte « Options », « Nouveau devis »', highlight: link });
    const href = await link.getAttribute('href');
    await page.goto(href.startsWith('http') ? href : BASE + href);
    await page.waitForURL(/\/quotes\/\d+/);
    ids.quote = idFrom(page.url(), 'quotes');
    await settle(page);
    await shot('devis-fiche', { title: 'Devis créé (statut Ouvert)' });
});

await step('4b. Ajouter une ligne au devis', async () => {
    await openLines(`/quotes/${ids.quote}`);
    const open = page.locator('button[title="Ajouter une ligne"]');
    await shot('devis-lignes-vide', { title: 'Onglet lignes — ouvrir le panneau « Ajouter »', highlight: open });
    await open.click();
    const form = page.locator('form#line-drawer-form');
    await form.waitFor();
    await form.getByPlaceholder('Code externe').fill('PLT-450');
    await form.getByPlaceholder('Description de la ligne').fill('Platine acier S235 ép. 8 mm, pliée');
    await field(form, 'Quantité').fill('20');
    await field(form, 'Prix unitaire').fill('42.50');
    const submit = page.locator('button[type=submit][form="line-drawer-form"]');
    await shot('devis-ligne-panneau', { title: 'Panneau « Nouvelle ligne » rempli', highlight: [form.getByPlaceholder('Description de la ligne'), field(form, 'Quantité'), field(form, 'Prix unitaire'), submit] });
    await submit.click();
    await settle(page);
    await page.getByRole('button', { name: 'Annuler', exact: true }).last().click();
    await settle(page);
    await shot('devis-ligne-ajoutee', { title: 'Ligne ajoutée au devis' });
});

await step('4c. Ajouter la gamme (tâches) à la ligne', async () => {
    const lineId = await page.evaluate(async (q) => {
        const r = await fetch(`/fr/quotes/${q}/lines/json`, { headers: { Accept: 'application/json' } }).catch(() => null);
        if (!r || !r.ok) return null;
        const j = await r.json();
        const rows = j.data || j.lines || j;
        return Array.isArray(rows) && rows.length ? rows[0].id : null;
    }, ids.quote);
    const split = page.locator('tbody tr', { hasText: 'PLT-450' }).locator('.dropdown-toggle, [data-bs-toggle="dropdown"]').last();
    await split.click();
    const item = page.getByText(/Tâches \(0\)/).first();
    await shot('devis-ligne-menu-taches', { title: 'Menu de la ligne — « Tâches »', highlight: item });
    await item.click();
    const manage = page.locator('a:visible', { hasText: 'Gérer les tâches' }).first();
    await manage.waitFor();
    await shot('devis-ligne-modale-taches', { title: 'Fenêtre des tâches — « Gérer les tâches »', highlight: manage });
    await followHref(manage);
    ids.quoteLine = page.url().match(/show\/(\d+)/)?.[1];
    await settle(page);

    const ops = [
        { ordre: '10', service: /laser|découpe/i, label: 'Découpe laser', setup: '15', unit: '3', cost: '12', price: '18' },
        { ordre: '20', service: /pli|bend/i, label: 'Pliage', setup: '20', unit: '2', cost: '9', price: '14' },
    ];
    for (const [i, op] of ops.entries()) {
        await page.locator('button[title="Ajouter — Opération"]').click();
        const f = page.locator('form#techcut-form');
        await f.waitFor();
        const svc = field(f, 'Service', 'select');
        const opts = await svc.locator('option').allTextContents();
        const pick = opts.findIndex(t => op.service.test(t));
        await svc.selectOption({ index: pick > 0 ? pick : 1 });
        await field(f, 'N° ordre').fill(op.ordre);
        await f.getByPlaceholder("Libellé de l'opération").fill(op.label);
        await field(f, 'Temps de réglage').fill(op.setup);
        await field(f, 'Temps unitaire').fill(op.unit);
        const cost = field(f, 'Coût unitaire');
        const price = field(f, 'Prix unitaire');
        // Service sans taux horaire : coût et prix restent à 0 et le serveur les refuse.
        if (!(parseFloat(await cost.inputValue()) > 0)) await cost.fill(op.cost);
        if (!(parseFloat(await price.inputValue()) > 0)) await price.fill(op.price);
        const submit = f.locator('button[type=submit]');
        if (i === 0) await shot('gamme-operation', { title: 'Panneau « Nouvelle opération » rempli', highlight: [svc, field(f, 'Temps de réglage'), field(f, 'Temps unitaire'), submit] });
        const [resp] = await Promise.all([page.waitForResponse(r => r.url().endsWith('/task') && r.request().method() === 'POST'), submit.click()]);
        if (!resp.ok()) throw new Error(`Opération « ${op.label} » refusée (${resp.status()}) : ${await resp.text()}`);
        await settle(page);
        await f.getByRole('button', { name: 'Annuler' }).click();
        await settle(page);
    }
    await page.reload();
    await settle(page);
    await shot('gamme-complete', { title: 'Gamme de la ligne : découpe puis pliage' });
});

await step('5. Convertir le devis en commande', async () => {
    await openLines(`/quotes/${ids.quote}`);
    await page.locator('tbody tr:visible', { hasText: 'PLT-450' }).locator('input[type=checkbox]').first().check();
    const btn = page.getByRole('button', { name: /Créer une commande/ });
    await shot('devis-vers-commande', { title: 'Cocher la ligne puis « Créer une commande »', highlight: [page.locator('tbody tr:visible', { hasText: 'PLT-450' }).locator('input[type=checkbox]').first(), btn] });
    await Promise.all([page.waitForURL(/\/orders\/\d+/, { timeout: 30000 }), btn.click()]);
    ids.order = idFrom(page.url(), 'orders');
    await settle(page);
    await shot('commande-fiche', { title: 'Commande créée depuis le devis' });
});

await step('6. Déclarer la production', async () => {
    await openLines(`/orders/${ids.order}`);
    const tasksBtn = page.locator('button.btn-warning[title^="Tâches ("]').first();
    await shot('commande-lignes', { title: 'Lignes de commande — bouton « Tâches »', highlight: tasksBtn });
    await tasksBtn.click();
    const manage = page.locator('a:visible', { hasText: 'Gérer les tâches' }).first();
    await followHref(manage);
    await settle(page);
    const taskLinks = page.locator('a[title$="— écran de suivi"]');
    await shot('commande-gamme', { title: 'Gamme de la commande — ouvrir l’écran de suivi d’une opération', highlight: taskLinks.first() });
    const hrefs = await taskLinks.evaluateAll(as => as.map(a => a.href));
    for (const [i, href] of hrefs.entries()) {
        await page.goto(href);
        await page.locator('#task-statu-app').waitFor();
        await settle(page);
        const play = page.locator('button.ts-run__btn--start');
        if (i === 0) await shot('suivi-play', { title: 'Écran de suivi — démarrer avec « Play »', highlight: play });
        await play.click();
        await settle(page);
        const set = page.getByRole('button', { name: 'Set' }).first();
        const qty = set.locator('xpath=preceding::input[1]');
        await qty.fill('20');
        if (i === 0) await shot('suivi-quantite', { title: 'Déclarer 20 pièces bonnes puis « Set »', highlight: [qty, set] });
        await set.click();
        await settle(page);
        const stop = page.locator('button.ts-run__btn--stop');
        await stop.click();
        await settle(page);
        if (i === 0) await shot('suivi-fin', { title: 'Opération terminée avec « Fin »', highlight: stop });
    }
});

await step('7. Créer le bon de livraison', async () => {
    await openLines(`/orders/${ids.order}`);
    const cb = page.locator('tbody tr:visible', { hasText: 'PLT-450' }).locator('input[type=checkbox]').first();
    await cb.check();
    const btn = page.getByRole('button', { name: /Nouveau BL/ });
    await shot('commande-vers-bl', { title: 'Cocher la ligne puis « Nouveau BL »', highlight: [cb, btn] });
    await Promise.all([page.waitForURL(/\/deliverys\/\d+/, { timeout: 30000 }), btn.click()]);
    ids.delivery = idFrom(page.url(), 'deliverys');
    await settle(page);
});

await step('8. Créer la facture', async () => {
    const link = page.locator('a.btn-primary[href*="/invoices/store/delevery/"]');
    await shot('bl-fiche', { title: 'Bon de livraison — « Nouvelle facture »', highlight: link });
    await Promise.all([page.waitForURL(/\/invoices\/\d+/, { timeout: 30000 }), link.click()]);
    ids.invoice = idFrom(page.url(), 'invoices');
    await settle(page);
    await shot('facture-fiche', { title: 'Facture créée (statut En cours)' });
});

save({ scenario: 'vente-complete', company: COMPANY, ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
