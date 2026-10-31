// Parcours achats : fournisseur → demande de prix → commande d'achat (criticité, AR) → réception
// (notation, visa documentaire) → facture fournisseur → suivi du fournisseur.
// Usage : node tools/training/scenarios/achats.mjs   (HEADED=1 pour voir le navigateur)
// Données créées préfixées [FORMATION]. Lancer `php artisan queue:work` en parallèle :
// les passages de statut automatiques (demande de prix, commande) passent par la file.
// Captures prises option « fournisseur qualifié obligatoire » active
// (PURCHASES_REQUIRE_QUALIFIED_SUPPLIER) : le fournisseur neuf n'a pas d'évaluation,
// sa première commande passe donc par un passe-droit motivé.
import { readEnv, openSession, field, settle, tab } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');
const SUPPLIER = `[FORMATION] Aciers de l'Ouest ${stamp}`;
const u = p => `${BASE}/fr${p}`;
const idFrom = (url, seg) => url.match(new RegExp(`/${seg}/(\\d+)`))?.[1];
const inDays = d => new Date(Date.now() + d * 86400000).toISOString().slice(0, 10);

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/achats' });
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'achats', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('1. Fournisseur', async () => {
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
    const state = field(page.locator('#Company'), 'Etat fournisseur', 'select');
    await state.selectOption('2');
    await shot('fournisseur-etat', { title: 'Fiche du tiers : « Etat fournisseur » = Actif', highlight: state });
    await page.locator('#Company button[type=submit].btn-info').first().click();
    await page.locator('.alert-success').first().waitFor();

    await page.locator('a[href="#Adresses"]').click();
    const adr = page.locator('#Adresses');
    await field(adr, 'Tri').fill('1');
    await field(adr, 'Libellé').fill('Dépôt');
    await field(adr, 'Adresse').fill('3 rue du Laminoir');
    await field(adr, 'Code postal').fill('44000');
    await field(adr, 'Ville').fill('Nantes');
    await field(adr, 'Pays').fill('France');
    await page.locator('#addr-default-create').check();
    await shot('fournisseur-adresse', { title: 'Adresse du fournisseur, cochée « Par défaut »', highlight: [page.locator('#addr-default-create'), adr.getByRole('button', { name: 'Enregistrer' })] });
    await adr.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);

    await page.locator('a[href="#Contact"]').click();
    const ct = page.locator('#Contact');
    await field(ct, 'Tri').fill('1');
    await field(ct, 'Civilité', 'select').selectOption({ index: 1 });
    await field(ct, 'Prénom').fill('Marc');
    await field(ct, /^Nom/).fill('Le Goff');
    await field(ct, 'E-mail').fill('achats@aciers-ouest.example');
    await page.locator('#contact-default-create').check();
    await shot('fournisseur-contact', { title: 'Contact du fournisseur, coché « Par défaut »', highlight: [page.locator('#contact-default-create'), ct.getByRole('button', { name: 'Enregistrer' })] });
    await ct.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);
});

await step('2. Demande de prix', async () => {
    await page.goto(u('/purchases/request'));
    await settle(page);
    await shot('demande-achat', { title: 'Écran « Demande d’achat » : besoins issus des commandes clients' });
    const type = field(page, 'Type de document', 'select');
    await type.selectOption('PQ');
    await settle(page);
    const suppliers = field(page, 'Sélectionner des fournisseurs', 'select');
    const label = (await suppliers.locator('option').allTextContents()).find(t => t.includes(SUPPLIER));
    await suppliers.selectOption({ label });
    await field(page, 'Libellé', 'input').fill('Tôles S235 — affaire Durand');
    const submit = page.locator('button[type=submit].btn-success').first();
    await shot('demande-prix-creation', { title: 'Type « Demande d’achat », fournisseur(s) et libellé', highlight: [type, suppliers, field(page, 'Libellé', 'input'), submit] });
    await Promise.all([page.waitForURL(/\/purchases\/quotation\/\d+/, { timeout: 30000 }), submit.click()]);
    ids.rfq = idFrom(page.url(), 'quotation');
    await settle(page);
});

await step('3. Lignes de la demande de prix', async () => {
    await page.getByRole('link', { name: /Ligne devis d'achat/ }).or(page.locator('a', { hasText: "Ligne devis d'achat" })).first().click();
    await settle(page);
    const lines = [
        { label: 'Tôle S235 3000×1500 ép. 8 mm', qty: '4', price: '310' },
        { label: 'Tôle S235 3000×1500 ép. 3 mm', qty: '6', price: '125' },
    ];
    for (const [i, line] of lines.entries()) {
        const form = page.locator('form', { has: page.getByRole('button', { name: 'Soumettre' }) }).last();
        await field(form, 'Tri').fill(String((i + 1) * 10));
        await field(form, 'Description').fill(line.label);
        await field(form, 'Qt').fill(line.qty);
        await field(form, 'Prix U').fill(line.price);
        if (i === 0) await shot('demande-prix-ligne', { title: 'Ajouter une ligne à la demande de prix', highlight: [field(form, 'Description'), field(form, 'Qt'), field(form, 'Prix U'), form.getByRole('button', { name: 'Soumettre' })] });
        await form.getByRole('button', { name: 'Soumettre' }).click();
        await settle(page);
    }
    await shot('demande-prix-lignes', { title: 'Demande de prix avec ses lignes' });

    await page.locator('a', { hasText: "Info Devis d'achat" }).first().click();
    await settle(page);
    const pdf = page.locator('a.btn-outline-danger', { hasText: 'PDF' }).first();
    await shot('demande-prix-envoi', { title: 'Envoyer la demande : PDF ou e-mail depuis la carte Options', highlight: pdf });

    const sent = page.locator('[title="Envoyé"]').first();
    await sent.click();
    await settle(page);
    await shot('demande-prix-envoyee', { title: 'Demande de prix passée à « Envoyé »', highlight: sent });
});

await step('4. Commande depuis la demande de prix', async () => {
    // L'onglet lignes oublie les lignes ajoutées dans la session après un changement d'onglet :
    // rechargement pour repartir des données serveur.
    await page.reload();
    await settle(page);
    await page.locator('a', { hasText: "Ligne devis d'achat" }).first().click();
    await settle(page);
    const boxes = page.locator('input[name="PurchaseQuotationLine[]"]');
    const count = await boxes.count();
    for (let i = 0; i < count; i++) await boxes.nth(i).check();
    const price = page.locator('input[name^="PurchaseQuotationLinePrice"]').first();
    await price.fill('295');
    const order = page.getByRole('button', { name: 'Nouvelle commande' });

    // Option « fournisseur qualifié obligatoire » : fournisseur neuf, donc sans évaluation.
    const reason = page.locator('#supplier-override-reason');
    const warning = page.locator('.alert-warning', { has: reason });
    if (await reason.count()) {
        await reason.fill('Premier achat chez ce fournisseur : qualification après la première livraison.');
        await shot('demande-prix-passe-droit', { title: 'Fournisseur non qualifié : motif du passe-droit', highlight: reason });
        // Capture générique, valable option inactive : avertissement masqué le temps de la capture.
        await warning.evaluate(el => { el.style.display = 'none'; });
    }
    await shot('demande-prix-conversion', { title: 'Saisir le prix négocié, cocher les lignes, « Nouvelle commande »', highlight: [price, boxes.first(), order] });
    if (await reason.count()) await warning.evaluate(el => { el.style.display = ''; });
    await Promise.all([page.waitForURL(/\/purchases\/\d+$/, { timeout: 30000 }), order.click()]);
    ids.order = idFrom(page.url(), 'purchases');
    await settle(page);
    await shot('commande-achat', { title: 'Commande d’achat créée' });
});

await step("5. Commande d'achat", async () => {
    await page.locator('a[href="#PurchaseLines"]').click();
    await settle(page);
    await shot('commande-achat-lignes', { title: 'Lignes de la commande d’achat' });

    // Date demandée et certificat EN 10204 exigé sur la tôle de 8 mm
    await page.locator('#PurchaseLines button[title="Modifier"]').first().click();
    const form = page.locator('#purchase-line-drawer-form');
    await form.waitFor();
    await page.waitForTimeout(400);
    const drawer = form.locator('xpath=../..');
    const date = field(form, 'Date livraison prévue');
    await date.fill(inDays(7));
    const cert = field(form, 'Certificat', 'select');
    await cert.selectOption('3.1');
    const saveBtn = drawer.getByRole('button', { name: 'Enregistrer' });
    await shot('commande-achat-ligne-criticite', { title: 'Ligne d’achat : date demandée et certificat exigé', highlight: [date, cert, saveBtn] });
    await saveBtn.click();
    await settle(page);

    await page.reload();
    await settle(page);
    const clauses = page.locator('#Purchase .card', { hasText: 'Clauses qualité' }).first();
    await shot('commande-achat-clauses', { title: 'Carte « Clauses qualité » : clauses dues au certificat exigé', highlight: clauses });

    const ordered = page.locator('.doc-header__steps').getByText('En commande', { exact: true }).first();
    await shot('commande-achat-statut', { title: 'Une fois envoyée au fournisseur : passer à « En commande »', highlight: ordered });
    await ordered.click();
    await page.waitForLoadState('load');
    await settle(page);
    await shot('commande-achat-en-commande', { title: 'Commande d’achat « En commande » : lignes verrouillées' });
});

await step('6. Accusé de réception du fournisseur', async () => {
    await page.reload();
    await settle(page);
    await page.locator('a[href="#PurchaseLines"]').click();
    await settle(page);
    const ackBtn = page.locator('#PurchaseLines').getByRole('button', { name: 'AR reçu' }).first();
    await ackBtn.click();
    const modal = page.locator('.modal.show', { has: page.locator('#supplier-ack-title') });
    await modal.waitFor();
    await modal.locator('#supplier-ack-reference').fill(`AR-${stamp}`);
    const dates = modal.locator('tbody input[type=date]');
    const n = await dates.count();
    for (let i = 0; i < n; i++) await dates.nth(i).fill(inDays(10));
    const submit = modal.getByRole('button', { name: /Enregistrer l'AR/ });
    await shot('commande-achat-ar', { title: 'Accusé de réception : référence et dates confirmées', highlight: [modal.locator('#supplier-ack-reference'), dates.first(), submit] });
    await submit.click();
    await settle(page);
    await page.waitForTimeout(500);
    const cell = page.locator('#PurchaseLines td', { has: page.locator('.fa-handshake') }).first();
    await shot('commande-achat-ar-lignes', { title: 'Date confirmée, date demandée et écart sur la ligne', highlight: cell });

    await page.goto(u('/purchases/acknowledgements/missing'));
    await settle(page);
    await shot('commandes-sans-ar', { title: 'Menu Acheter › Commandes sans AR' });

    await page.goto(u('/purchases/quality-clauses'));
    await settle(page);
    await shot('clauses-qualite', { title: 'Acheter › Clauses qualité : textes et règles d’application' });
});

await step('7. Réception', async () => {
    await page.goto(u('/purchases/waiting/receipt'));
    await settle(page);
    const supplier = field(page, 'Trie Tiers', 'select');
    const label = (await supplier.locator('option').allTextContents()).find(t => t.includes(SUPPLIER));
    await supplier.selectOption({ label });
    await settle(page);
    await field(page, 'Nom du bon de livraison', 'input').fill('BL 88412');
    await field(page, 'Utilisateur responsable', 'select').selectOption({ index: 1 });
    const boxes = page.locator('input[id^="line-"]');
    const count = await boxes.count();
    for (let i = 0; i < count; i++) await boxes.nth(i).check();
    const create = page.getByRole('button', { name: 'Nouveau reçu', exact: true });
    await shot('reception-attente', { title: '« En attente de réception » : fournisseur, n° de BL, lignes reçues', highlight: [supplier, field(page, 'Nom du bon de livraison', 'input'), boxes.first(), create] });
    await Promise.all([page.waitForURL(/\/purchases\/receipt\/\d+/, { timeout: 30000 }), create.click()]);
    ids.receipt = idFrom(page.url(), 'receipt');
    await settle(page);

    // Notation rapide : la fenêtre s'ouvre seule après la création
    const rating = page.locator('.modal.show', { has: page.locator('#receipt-rating-title') });
    await rating.waitFor();
    for (const [label, score] of [['Conformité', 5], ['Délai', 4], ['Documents', 3]]) {
        await rating.getByRole('radio', { name: `${label} ${score}/5`, exact: true }).click();
    }
    await rating.locator('#receipt-rating-comment').fill('Certificat matière arrivé deux jours après les tôles.');
    const rate = rating.locator('.modal-footer .btn-primary');
    await shot('reception-notation', { title: 'Noter le fournisseur à la réception (facultatif)', highlight: [rating.getByRole('radiogroup').first(), rate] });
    await rate.click();
    await settle(page);

    await page.addStyleTag({ content: '.btn-teal.position-fixed, [title="Fermer"].position-fixed { display: none !important; }' });
    await page.locator('a[href="#PurchaseLines"]').click();
    await settle(page);
    const inspect = page.locator('button[title="Détails d\'inspection"]').first();
    const visa = page.locator('button[title="Visa documentaire"]').first();
    await shot('reception-lignes', { title: 'Bon de réception : contrôle physique et visa documentaire', highlight: [inspect, visa] });

    await page.goto(u('/purchases/receipt/document-check'));
    await settle(page);
    await shot('visa-documentaire', { title: 'Qualité › Visa documentaire réception : certificats à viser' });
});

await step('8. Facture fournisseur', async () => {
    await page.goto(u('/purchases/waiting/invoice'));
    await settle(page);
    const supplier = field(page, 'Trie Tiers', 'select');
    const label = (await supplier.locator('option').allTextContents()).find(t => t.includes(SUPPLIER));
    await supplier.selectOption({ label });
    await settle(page);
    const ref = page.getByPlaceholder('N° de facture du fournisseur');
    await ref.fill(`FA-${stamp}`);
    await field(page, 'Utilisateur responsable', 'select').selectOption({ index: 1 });
    const boxes = page.locator('input[id^="line-"]');
    const count = await boxes.count();
    for (let i = 0; i < count; i++) await boxes.nth(i).check();
    const create = page.getByRole('button', { name: 'Nouvelle facture' });
    await shot('facture-attente', { title: '« En attente de facturation » : référence fournisseur et réceptions facturées', highlight: [supplier, ref, boxes.first(), create] });
    await Promise.all([page.waitForURL(/\/purchases\/invoice\/\d+/, { timeout: 30000 }), create.click()]);
    ids.invoice = idFrom(page.url(), 'invoice');
    await settle(page);
    await shot('facture-fiche', { title: 'Facture fournisseur créée' });
});

await step('9. Factures reçues', async () => {
    await page.goto(u('/purchases/incoming-invoices'));
    await settle(page);
    await shot('factures-recues', { title: 'Factures reçues (Factur-X) : dépôt et rapprochement' });
});

await step('10. Suivi du fournisseur', async () => {
    await page.goto(u(`/companies/${ids.supplier}`));
    await settle(page);
    await page.locator('a[href="#evaluation"]').click();
    await settle(page);
    await shot('fournisseur-suivi', { title: 'Fiche fournisseur : note glissante et livraisons à l’heure', highlight: [page.locator('#supplier-rolling-rating'), page.locator('#supplier-on-time-delivery')] });

    await page.goto(u('/purchases'));
    await settle(page);
    await page.locator('.card-orange .nav-pills .nav-link').first().click();
    await settle(page);
    const card = page.locator('.card', { has: page.locator('.fa-truck-fast') }).last();
    await shot('achats-livraisons-heure', { title: 'Tableau de bord Achats : livraisons à l’heure', highlight: card });
});

save({ scenario: 'achats', supplier: SUPPLIER, ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
