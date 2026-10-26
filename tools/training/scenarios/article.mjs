// Guide « Articles et stock » : liste et création d'un article, fiche (détail, gamme,
// documents), puis organisation du stock (stock → emplacement → ligne), entrée, sortie,
// statut du stock, numéros de série, inventaires. Données préfixées [FORMATION].
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
    await search.fill('FORMATION');
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
    const existing = tinker("echo App\\Models\\Products\\Inventory::where('status', 4)->value('id') ?? App\\Models\\Products\\Inventory::value('id');");
    if (existing) {
        await page.goto(u(`/products/inventories/${existing}`));
        await settle(page);
        await shot('inventaire', { title: 'Un inventaire : liste à compter, import, validation' });
    }
});

save({ scenario: 'article', ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
