// Parcours achats : fournisseur → demande de prix → commande d'achat → réception → facture fournisseur.
// Usage : node tools/training/scenarios/achats.mjs   (HEADED=1 pour voir le navigateur)
// Données créées préfixées [FORMATION]. Lancer `php artisan queue:work` en parallèle :
// les passages de statut automatiques (demande de prix, commande) passent par la file.
import { readEnv, openSession, field, settle, tab } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');
const SUPPLIER = `[FORMATION] Aciers de l'Ouest ${stamp}`;
const u = p => `${BASE}/fr${p}`;
const idFrom = (url, seg) => url.match(new RegExp(`/${seg}/(\\d+)`))?.[1];

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
    await shot('demande-prix-conversion', { title: 'Saisir le prix négocié, cocher les lignes, « Nouvelle commande »', highlight: [price, boxes.first(), order] });
    await Promise.all([page.waitForURL(/\/purchases\/\d+$/, { timeout: 30000 }), order.click()]);
    ids.order = idFrom(page.url(), 'purchases');
    await settle(page);
    await shot('commande-achat', { title: 'Commande d’achat créée' });
});

await step("5. Commande d'achat", async () => {
    await page.locator('a[href="#PurchaseLines"]').click();
    await settle(page);
    await shot('commande-achat-lignes', { title: 'Lignes de la commande d’achat' });

    await page.locator('a[href="#Purchase"]').click();
    await settle(page);
    const ordered = page.locator('.doc-header__steps').getByText('En commande', { exact: true }).first();
    await shot('commande-achat-statut', { title: 'Une fois envoyée au fournisseur : passer à « En commande »', highlight: ordered });
    await ordered.click();
    await page.waitForLoadState('load');
    await settle(page);
    await shot('commande-achat-en-commande', { title: 'Commande d’achat « En commande » : lignes verrouillées' });
});

await step('6. Réception', async () => {
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
    await page.addStyleTag({ content: '.btn-teal.position-fixed, [title="Fermer"].position-fixed { display: none !important; }' });
    await page.locator('a[href="#PurchaseLines"]').click();
    await settle(page);
    const inspect = page.locator('button[title="Détails d\'inspection"]').first();
    await shot('reception-lignes', { title: 'Bon de réception : contrôle de chaque ligne', highlight: inspect });
});

await step('7. Facture fournisseur', async () => {
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

await step('8. Factures reçues', async () => {
    await page.goto(u('/purchases/incoming-invoices'));
    await settle(page);
    await shot('factures-recues', { title: 'Factures reçues (Factur-X) : dépôt et rapprochement' });
});

save({ scenario: 'achats', supplier: SUPPLIER, ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
