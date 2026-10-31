// Guide « Articles et stock » : liste et création d'un article, fiche (détail, tarifs, gamme,
// documents), puis organisation du stock (stock → emplacement → ligne), entrée, sortie,
// statut du stock, numéros de série, inventaires, lots (coulée, certificat).
// Données préfixées [FORMATION]. La réception d'achat qui crée le lot est préparée par
// tinker (commande et réception [FORMATION]) ; seule la mise en stock passe par l'écran.
// Aucun mouvement de stock ne se supprime : le scénario n'en crée que sur sa propre ligne.
// Ne clique jamais « Dupliqué produit », « Créer une commande interne », la carte des
// commandes internes du stock, « Mettre à jour » d'un mouvement ni la validation d'inventaire.
// Usage : node tools/training/scenarios/article.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import { readEnv, openSession, field, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const u = p => `${BASE}/fr${p}`;
const tinker = code => execFileSync(PHP, ['artisan', 'tinker', '--execute', code]).toString().trim().split('\n').pop();
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');

const { browser, page, shot: rawShot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/article' });
const shot = async (...a) => { await page.evaluate(() => document.activeElement?.blur()); return rawShot(...a); };
const ids = {};
const CODE = `FORM-EQ90-${stamp}`;

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'article', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}
const openTab = async hash => { await page.locator(`a.nav-link[href="${hash}"]`).first().click(); await page.waitForTimeout(400); };
const formOf = suffix => page.locator(`form[action$="${suffix}"]`).first();
const submitOf = form => form.locator('button[type=submit]').last();
async function submit(form) {
    await Promise.all([page.waitForNavigation(), submitOf(form).click()]);
    await settle(page);
}
const pickText = async (select, re) => {
    const v = await select.locator('option').evaluateAll((os, [src, f]) => os.find(o => new RegExp(src, f).test(o.text))?.value, [re.source, re.flags]);
    if (!v) throw new Error(`Option introuvable : ${re}`);
    await select.selectOption(v);
};

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('1. Liste des articles', async () => {
    await page.goto(u('/products'));
    await settle(page);
    await page.locator('#products-index-app .nav-link', { hasText: /Liste des produits/ }).first().click();
    await settle(page);
    await shot('liste', { title: 'Liste des articles', highlight: page.getByRole('button', { name: /Nouveau produit/ }) });
});

await step('2. Créer l’article', async () => {
    await page.getByRole('button', { name: /Nouveau produit/ }).click();
    const modal = page.locator('.modal-body').last();
    await modal.waitFor();
    await modal.locator('.fa-spinner').waitFor({ state: 'detached' }).catch(() => {});
    await settle(page);
    await field(modal, 'Code').fill(CODE);
    await field(modal, 'Libellé').fill('[FORMATION] Équerre support 90°');
    await field(modal, 'Ind').fill('A');
    const selects = modal.locator('select');
    await pickText(selects.nth(0), /COMPO|COMPONANT/i);
    await pickText(selects.nth(1), /Fitting|RAC/i);
    await pickText(selects.nth(2), /Unit/i);
    await selects.nth(3).selectOption('1');
    await selects.nth(4).selectOption('2');
    await selects.nth(5).selectOption('1');
    await field(modal, 'Prix U').fill('4.50');
    await field(modal, 'Matière').fill('Acier S235').catch(() => {});
    await field(modal, 'Epaisseur').fill('3').catch(() => {});
    await shot('creation', { title: 'Nouveau produit', highlight: [field(modal, 'Code'), field(modal, 'Libellé'), selects.nth(0), selects.nth(1), selects.nth(2)] });
    await Promise.all([page.waitForURL(/\/products\/\d+$/), modal.locator('button[type=submit]').click()]);
    await settle(page);
    ids.product = page.url().match(/products\/(\d+)/)?.[1];
    await shot('fiche', { title: 'Fiche article : onglets', highlight: page.locator('.nav-pills, .nav-tabs').first() });
});

await step('2b. Détail : plan de contrôle et criticité achat', async () => {
    const sel = ['#control_plan_mode', '#certificate_type', '#construction_code', '#nuclear_level'].map(s => page.locator(s));
    await sel[0].scrollIntoViewIfNeeded();
    await page.evaluate(() => window.scrollBy(0, -160));
    await shot('detail-criticite', { title: 'Détail produit : plan de contrôle et criticité achat', highlight: sel });
});

await step('2c. Tarifs', async () => {
    await page.evaluate(() => window.scrollTo(0, 0));
    await openTab('#Prices');
    await settle(page);
    const add = page.locator('#Prices [data-price-list-open]').first();
    await shot('tarifs-vide', { title: 'Onglet Tarifs : grille tarifaire client', highlight: add });
    await add.click();
    const modal = page.locator('#priceListModal');
    await modal.waitFor({ state: 'visible' });
    await page.waitForTimeout(400);
    await modal.locator('#price_list_min_qty').fill('50');
    await modal.locator('#price_list_price').fill('4');
    await modal.locator('#price_list_price').dispatchEvent('input');
    await shot('tarifs-palier', { title: 'Ajouter un palier', highlight: [modal.locator('.btn-group').first(), modal.locator('#price_list_min_qty'), modal.locator('#price_list_max_qty'), modal.locator('#price_list_price'), modal.locator('button[type=submit]')] });
    await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
    await settle(page);
    if (!(await page.locator('#Prices').isVisible())) await openTab('#Prices');
    await settle(page);
    await shot('tarifs', { title: 'Paliers de prix par quantité' });
});

await step('3. Gamme', async () => {
    await openTab('#TechnicalInfo');
    await settle(page);
    // Temps en minutes sur la base de démonstration (réglage « Unité des temps »).
    const ops = [
        { ordre: '10', service: /laser/i, label: 'Découpe laser 3 mm', setup: '6', unit: '1.2' },
        { ordre: '20', service: /pli|bend/i, label: 'Pliage 90°', setup: '15', unit: '1.8' },
    ];
    for (const [i, op] of ops.entries()) {
        await page.locator('button[title="Ajouter — Opération"]').click();
        const f = page.locator('form#techcut-form');
        await f.waitFor();
        const svc = field(f, 'Service', 'select');
        await pickText(svc, op.service);
        await field(f, 'N° ordre').fill(op.ordre);
        await f.getByPlaceholder("Libellé de l'opération").fill(op.label);
        await field(f, 'Temps de réglage').fill(op.setup);
        await field(f, 'Temps unitaire').fill(op.unit);
        const cost = field(f, 'Coût unitaire');
        const price = field(f, 'Prix unitaire');
        if (!(parseFloat(await cost.inputValue()) > 0)) await cost.fill('1');
        if (!(parseFloat(await price.inputValue()) > 0)) await price.fill('1.5');
        const btn = f.locator('button[type=submit]');
        if (i === 0) await shot('gamme-operation', { title: 'Nouvelle opération de la gamme', highlight: [svc, field(f, 'Temps de réglage'), field(f, 'Temps unitaire'), btn] });
        const [resp] = await Promise.all([page.waitForResponse(r => r.url().endsWith('/task') && r.request().method() === 'POST'), btn.click()]);
        if (!resp.ok()) throw new Error(`Opération refusée (${resp.status()}) : ${await resp.text()}`);
        await settle(page);
        await f.getByRole('button', { name: 'Annuler' }).click();
        await settle(page);
    }
    await page.reload();
    await settle(page);
    await openTab('#TechnicalInfo');
    await settle(page);
    await shot('gamme', { title: 'Gamme opératoire de l’article' });
});

await step('4. Documents', async () => {
    await openTab('#Documents');
    await page.waitForTimeout(600);
    await settle(page);
    await shot('documents', { title: 'Documents de l’article (plans, modèles 3D, photos)' });
});

await step('5. Stock et emplacement', async () => {
    await page.goto(u('/products/Stock'));
    await settle(page);
    let form = formOf('/products/Stock/create');
    await form.locator('#code').fill(`FORM-STOCK-${stamp}`);
    await form.locator('#label').fill('[FORMATION] Stock atelier');
    await shot('stock-creation', { title: 'Nouveau stock (entrepôt)', highlight: [form.locator('#code'), form.locator('#label'), submitOf(form)] });
    await submit(form);
    ids.stock = page.url().match(/Stock\/(\d+)/i)?.[1];

    form = formOf('/stock/location/create');
    await form.locator('#code').fill(`FORM-LOC-${stamp}`);
    await form.locator('#label').fill('[FORMATION] Étagère A1');
    await shot('emplacement-creation', { title: 'Nouvel emplacement dans le stock', highlight: [form.locator('#code'), form.locator('#label'), submitOf(form)] });
    await submit(form);
    ids.location = page.url().match(/location\/(\d+)/)?.[1];
});

await step('6. Ligne de stock', async () => {
    const form = formOf('/stock/location/product/create');
    await form.locator('#code').fill(`FORM-SLP-${stamp}`);
    await form.locator('#products_id_search').fill(CODE);
    await form.locator('[data-role=results] div').first().waitFor();
    await form.locator('[data-role=results] div').first().click();
    await form.locator('#mini_qty').fill('10');
    await form.locator('#addressing').fill('A1-01');
    await shot('ligne-creation', { title: 'Ranger l’article dans l’emplacement', highlight: [form.locator('#products_id_search'), form.locator('#mini_qty'), form.locator('#addressing'), submitOf(form)] });
    await submit(form);
    ids.line = tinker(`echo App\\Models\\Products\\StockLocationProducts::where('code', 'FORM-SLP-${stamp}')->value('id');`);
    await shot('emplacement', { title: 'Emplacement et ses lignes de stock' });
});

await step('7. Entrée et sortie', async () => {
    await page.goto(u(`/products/stock/location/product/${ids.line}`));
    await settle(page);
    let form = formOf('/stock/location/product/entry');
    await form.locator('[name="typ_move"]').selectOption('5');
    await form.locator('[name="qty"]').fill('20');
    await shot('entree', { title: 'Entrée de stock', highlight: [form.locator('[name="typ_move"]'), form.locator('[name="qty"]'), submitOf(form)] });
    await submit(form);

    form = formOf('/sorting');
    await form.locator('[name="typ_move"]').selectOption('6');
    await form.locator('[name="qty"]').fill('12');
    await shot('sortie', { title: 'Sortie de stock', highlight: [form.locator('[name="typ_move"]'), form.locator('[name="qty"]'), submitOf(form)] });
    await submit(form);
    await page.evaluate(() => window.scrollTo(0, 0));
    await shot('mouvements', { title: 'Ligne de stock : mouvements et stock courant' });
});

await step('8. Fiche article, onglet Stock', async () => {
    await page.goto(u(`/products/${ids.product}`));
    await settle(page);
    await openTab('#Stock');
    await shot('fiche-stock', { title: 'Le stock de l’article, emplacement par emplacement' });
});

await step('9. Statut du stock', async () => {
    await page.goto(u('/products/Stock/shortages'));
    await settle(page);
    const search = page.getByPlaceholder('Recherche...', { exact: true });
    await search.fill(CODE);
    await settle(page);
    await shot('statut', { title: 'Statut du stock : besoin, stock et minimum', highlight: search });
});

await step('10. Numéros de série et inventaires (lecture)', async () => {
    await page.goto(u('/products/serial-numbers'));
    await settle(page);
    await page.locator('.nav-link', { hasText: /Liste des num/ }).first().click().catch(() => {});
    await settle(page);
    await shot('series', { title: 'Numéros de série' });
    await page.goto(u('/products/inventories'));
    await settle(page);
    await shot('inventaires', { title: 'Inventaires' });
    const existing = tinker("echo App\\Models\\Products\\Inventory::orderByDesc('id')->value('id');");
    if (existing) {
        await page.goto(u(`/products/inventories/${existing}`));
        await settle(page);
        await shot('inventaire', { title: 'Un inventaire : liste à compter, import, validation' });
    }
});

await step('11. Réception avec coulée : création du lot', async () => {
    // Commande d'achat et réception [FORMATION] préparées en base : le parcours achat est
    // décrit dans le guide « Achats ». Fournisseur [FORMATION] le plus récent.
    const M = 'App\\Models\\';
    const php = [
        `$sup = ${M}Companies\\Companies::where('label', 'like', '[FORMATION]%')->where('statu_supplier', 2)->latest('id')->first();`,
        `$p = ${M}Purchases\\Purchases::create(['code' => 'ACH-FORM-${stamp}', 'label' => '[FORMATION] Équerres ${stamp}', 'companies_id' => $sup->id, 'companies_contacts_id' => $sup->Contacts()->value('id'), 'companies_addresses_id' => $sup->Addresses()->value('id'), 'statu' => 1, 'user_id' => 1]);`,
        `$l = ${M}Purchases\\PurchaseLines::create(['purchases_id' => $p->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => '${CODE}', 'product_id' => ${ids.product}, 'label' => '[FORMATION] Équerre support 90°', 'qty' => 5, 'selling_price' => 3.2, 'discount' => 0, 'unit_price_after_discount' => 3.2, 'total_selling_price' => 16, 'receipt_qty' => 5, 'invoiced_qty' => 0, 'methods_units_id' => ${M}Methods\\MethodsUnits::value('id'), 'accounting_vats_id' => ${M}Accounting\\AccountingVat::value('id'), 'certificate_type' => '3.1']);`,
        `$p->statu = 4; $p->save();`,
        `$r = ${M}Purchases\\PurchaseReceipt::create(['code' => 'REC-FORM-${stamp}', 'label' => '[FORMATION] Réception ${stamp}', 'companies_id' => $sup->id, 'statu' => 1, 'user_id' => 1]);`,
        `${M}Purchases\\PurchaseReceiptLines::create(['purchase_receipt_id' => $r->id, 'purchase_line_id' => $l->id, 'ordre' => 10, 'receipt_qty' => 5, 'received_certificate_type' => '3.1']);`,
        `echo $r->id;`,
    ].join(' ');
    ids.receipt = tinker(php);
    if (!/^\d+$/.test(ids.receipt)) throw new Error(`Réception non créée : ${ids.receipt}`);
    await page.goto(u(`/purchases/receipt/${ids.receipt}`));
    await settle(page);
    await openTab('#PurchaseLines');
    await settle(page);
    await page.locator('button[title="Mise en stock"]').first().click();
    await page.locator('#batch-heat-number').waitFor();
    await page.getByRole('link', { name: /Existant/ }).click();
    const sel = page.locator('#stock-existing-form select');
    await sel.selectOption(String(ids.line));
    await page.locator('#batch-heat-number').fill(`FORM-C${stamp}`);
    await page.locator('#batch-supplier-code').fill('AO-24117');
    await page.locator('#batch-certificate').selectOption('3.1');
    await page.waitForTimeout(400);
    const btn = page.getByRole('button', { name: /Mettre en stock/ });
    await shot('lot-reception', { title: 'Mise en stock d’une réception : coulée et certificat', highlight: [sel, page.locator('#batch-heat-number'), page.locator('#batch-supplier-code'), page.locator('#batch-certificate'), btn] });
    const [resp] = await Promise.all([page.waitForResponse(r => r.request().method() === 'POST' && /stock/i.test(r.url())), btn.click()]);
    if (!resp.ok()) throw new Error(`Mise en stock refusée (${resp.status()}) : ${await resp.text()}`);
    await settle(page);
});

await step('12. Lots : recherche par coulée et fiche lot', async () => {
    await page.goto(u('/products/batches'));
    await settle(page);
    const search = page.locator('input[name="search"]');
    await search.fill(`FORM-C${stamp}`);
    await Promise.all([page.waitForNavigation(), page.locator('form[action*="batches"] button[type=submit]').click()]);
    await settle(page);
    const eye = page.locator('a[href*="/products/batches/"]').first();
    await shot('lots', { title: 'Lots : recherche par coulée', highlight: [search, eye] });
    await Promise.all([page.waitForNavigation(), eye.click()]);
    await settle(page);
    ids.batch = page.url().match(/batches\/(\d+)/)?.[1];
    await page.waitForTimeout(1000);
    await settle(page);
    await shot('fiche-lot', { title: 'Fiche lot : identification, certificat, mouvements' });
    await page.goto(u(`/products/stock/location/product/${ids.line}`));
    await settle(page);
    await shot('mouvements-lot', { title: 'Mouvement de la ligne de stock avec sa coulée', highlight: page.locator('table a[href*="/products/batches/"]').first() });
});

save({ scenario: 'article', ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
