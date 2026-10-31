// Le devis au-delà de la base : repères d'équipement, copie de lignes, lignes de présentation,
// marge interne, revue, modèles d'impression, trame, indice, variante, devis gagné, motif de perte.
// Usage : TRAINING_URL=http://127.0.0.1:8004 node tools/training/scenarios/devis-avance.mjs   (HEADED=1 pour voir)
// Toutes les données créées portent le préfixe [FORMATION]. Les flèches de statut ne sont
// cliquées que sur les devis [FORMATION] créés par ce scénario.
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
const LABEL = `[FORMATION] Garde-corps atelier ${stamp}`;
const TEMPLATE = `[FORMATION] Trame garde-corps ${stamp}`;
const u = p => `${BASE}/fr${p}`;
const idFrom = (url, seg) => url.match(new RegExp(`/${seg}/(\\d+)`))?.[1];
const OUT = 'resources/guides/devis-avance';

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: OUT });
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR-devis-avance.png', fullPage: true }).catch(() => {});
        save({ scenario: 'devis-avance', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

async function openQuote(id, hash) {
    await page.goto(u(`/quotes/${id}`));
    await settle(page);
    if (hash) {
        await page.locator(`#DocumentTabs a[href="${hash}"]`).first().click();
        await settle(page);
    }
}

// Carte AdminLTE d'après son titre (la carte la plus proche, pas la carte des onglets qui l'englobe).
const cardByTitle = title => page.locator('.card-title:visible', { hasText: title }).first()
    .locator("xpath=ancestor::div[contains(concat(' ', normalize-space(@class), ' '), ' card ')][1]");

// Carte AdminLTE repliée : la déplier.
async function expandCard(card) {
    const body = card.locator('.card-body').first();
    if (!(await body.evaluate(el => el.offsetHeight > 0 && getComputedStyle(el).display !== 'none'))) {
        await card.locator('[data-lte-toggle="card-collapse"]').first().click();
        await page.waitForTimeout(600);
    }
}

// Choix d'un client [FORMATION] (avec adresse et contact) dans la fenêtre « Nouveau devis ».
async function pickTrainingCompany(modal) {
    const sel = field(modal, 'Nom du tiers', 'select');
    const opts = await sel.locator('option').evaluateAll(os => os.map(o => ({ v: o.value, t: o.textContent })));
    const pick = opts.reverse().find(o => /\[FORMATION\] Métallerie Durand/.test(o.t)) || opts.find(o => /\[FORMATION\]/.test(o.t));
    if (!pick) throw new Error('Aucun client [FORMATION] : lancer d’abord vente-complete.mjs');
    await sel.selectOption(pick.v);
    await settle(page);
    return sel;
}

const lineRow = text => page.locator('tbody tr:visible', { hasText: text }).first();
async function openLineMenu(text) {
    await lineRow(text).locator('.dropdown-toggle-split').click();
    await page.waitForTimeout(300);
    return page.locator('.dropdown-menu.show').last();
}

// Ligne de présentation tout juste créée : saisir son libellé dans le champ ouvert.
async function typePresentationLabel(text) {
    const input = page.locator('tr.line-section input[type=text], tr.line-text textarea, tr.line-subtotal input[type=text]').last();
    await input.waitFor();
    await input.fill(text);
    await input.locator('xpath=following::button[@title="Enregistrer"][1]').click();
    await settle(page);
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([page.waitForURL(/dashboard|setup|settings/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('1. Créer le devis', async () => {
    await page.goto(u('/quotes?tab=list'));
    await settle(page);
    const add = page.getByRole('button', { name: /Nouveau devis/ }).first();
    await add.click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('select').first().waitFor();
    await settle(page);
    const tpl = field(modal, "Partir d'une trame", 'select');
    await pickTrainingCompany(modal);
    await field(modal, 'Libellé').fill(LABEL);
    const submit = modal.locator('button[type=submit]').first();
    await shot('nouveau-devis-trame', { title: 'Fenêtre « Nouveau devis » : « Partir d’une trame »', highlight: [tpl, field(modal, 'Libellé'), submit] });
    await Promise.all([page.waitForURL(/\/quotes\/\d+/, { timeout: 30000 }), submit.click()]);
    ids.quote = idFrom(page.url(), 'quotes');
    await settle(page);
});

await step('2. Lignes avec repères d’équipement', async () => {
    await openQuote(ids.quote, '#Lines');
    const lines = [
        { code: 'GC-1000', label: 'Garde-corps acier 1 000 mm, thermolaqué', qty: '4', price: '180', tags: 'GC-A1, GC-A2, GC-A3, GC-A4' },
        { code: 'MC-1000', label: 'Main courante inox Ø 42', qty: '4', price: '65' },
    ];
    for (const [i, l] of lines.entries()) {
        await page.locator('button[title="Ajouter une ligne"]').click();
        const form = page.locator('form#line-drawer-form');
        await form.waitFor();
        await form.getByPlaceholder('Code externe').fill(l.code);
        await form.getByPlaceholder('Description de la ligne').fill(l.label);
        await field(form, 'Quantité').fill(l.qty);
        await field(form, 'Prix unitaire').fill(l.price);
        const tags = form.locator('#line-equipment-tag');
        if (l.tags) await tags.fill(l.tags);
        const submit = page.locator('button[type=submit][form="line-drawer-form"]');
        if (i === 0) await shot('ligne-reperes', { title: 'Panneau de ligne : champ « Repères équipement »', highlight: [tags, submit] });
        await submit.click();
        await settle(page);
        await page.getByRole('button', { name: 'Annuler', exact: true }).last().click();
        await settle(page);
    }
});

await step('3. Importer une ligne d’un autre devis', async () => {
    const btn = page.getByRole('button', { name: /Importer d'un autre devis/ });
    await btn.click();
    const modal = page.locator('.modal.show', { hasText: "Importer des lignes d'un autre devis" });
    await modal.waitFor();
    await modal.getByPlaceholder('Code, libellé, client ou ligne…').fill('Platine');
    await page.waitForTimeout(600);
    await settle(page);
    const card = modal.locator('.card', { hasText: '[FORMATION]' }).first();
    await card.locator('.card-header').click();
    const row = card.locator('tbody tr', { hasText: 'PLT-450' }).first();
    await row.click();
    const go = modal.locator('.modal-footer .btn-primary');
    await shot('import-lignes', { title: 'Fenêtre « Importer des lignes d’un autre devis »', highlight: [modal.getByPlaceholder('Code, libellé, client ou ligne…'), row, go] });
    await go.click();
    await settle(page);
    await lineRow('PLT-450').waitFor();
});

await step('4. Sections, sous-total, texte, forfait', async () => {
    let menu = await openLineMenu('GC-1000');
    await shot('ligne-menu-presentation', { title: 'Menu de la ligne : « Insérer une section au-dessus »…', highlight: menu.getByRole('button', { name: 'Insérer une section au-dessus' }) });
    await menu.getByRole('button', { name: 'Insérer une section au-dessus' }).click();
    await settle(page);
    await typePresentationLabel('Garde-corps');

    menu = await openLineMenu('MC-1000');
    await menu.getByRole('button', { name: 'Insérer un sous-total en dessous' }).click();
    await settle(page);
    // Sous-total sans libellé : « Sous-total » suivi du nom de la section, automatiquement.

    menu = await openLineMenu('PLT-450');
    await menu.getByRole('button', { name: 'Insérer une section au-dessus' }).click();
    await settle(page);
    await typePresentationLabel('Fixations');

    const layoutBtn = page.getByRole('button', { name: /Mise en page/ });
    await layoutBtn.click();
    const addText = page.getByRole('button', { name: 'Ajouter un texte' });
    await shot('mise-en-page-menu', { title: 'Bouton « Mise en page » : section, sous-total, texte', highlight: [layoutBtn, addText] });
    await addText.click();
    await settle(page);
    await typePresentationLabel('Pose et scellement non compris.');

    // Rechargement : les lignes tout juste créées affichent leur statut réel.
    await openQuote(ids.quote, '#Lines');
    const fixations = page.locator('tr.line-section', { hasText: 'Fixations' }).locator('select').first();
    await fixations.selectOption({ label: 'Forfait : montant seul' });
    await settle(page);
    const eye = lineRow('MC-1000').locator('button[title^="Masquer sur le PDF"]');
    await shot('lignes-presentation', {
        title: 'Lignes avec sections, sous-total, texte, forfait et repères',
        highlight: [page.locator('tr.line-section', { hasText: 'Garde-corps' }), page.locator('tr.line-subtotal').first(), fixations, page.locator('tr.line-text').first(), eye],
    });
});

await step('5. Colonne marge', async () => {
    const toggle = page.locator('button[aria-pressed]', { hasText: 'Marge' });
    if ((await toggle.getAttribute('aria-pressed')) !== 'true') await toggle.click();
    await settle(page);
    await shot('lignes-marge', { title: 'Bouton « Marge » : colonne de marge par ligne (interne)', highlight: [toggle, lineRow('PLT-450').locator('td[title]').filter({ hasText: '%' }).first()] });
});

await step('6. Carte Marge (interne) et marge globale', async () => {
    await openQuote(ids.quote, '#Quote');
    const card = cardByTitle('Marge (interne)');
    await expandCard(card);
    const rate = card.locator('#margin_rate');
    await rate.fill('30');
    const apply = card.getByRole('button', { name: /Recalculer les prix/ });
    await shot('marge-carte', { title: 'Carte « Marge (interne) » : appliquer une marge globale', highlight: [rate, apply] });
    await Promise.all([page.waitForURL(/\/quotes\/\d+/), apply.click()]);
    await settle(page);
    await page.locator('#DocumentTabs a[href="#Quote"]').first().click();
    const card2 = cardByTitle('Marge (interne)');
    await expandCard(card2);
    await shot('marge-appliquee', { title: 'Marge appliquée : message et nouvelle marge', highlight: [page.locator('.alert-success, .alert-info, .alert-warning').first(), card2] });
});

await step('7. Onglet Revue', async () => {
    await openQuote(ids.quote, '#Review');
    const pane = page.locator('#Review');
    await pane.locator('#reviewed_by').selectOption({ index: 1 });
    await pane.locator('#review_decision').selectOption('approved');
    const submit = pane.locator('button[type=submit]').first();
    await shot('revue', { title: 'Onglet « Revue » : relecteur, date, décision', highlight: [page.locator('#DocumentTabs a[href="#Review"]'), pane.locator('#reviewed_by'), pane.locator('#review_decision'), submit] });
    await submit.click();
    await settle(page);
});

await step('8. Modèle d’impression et copies', async () => {
    await openQuote(ids.quote, '#Quote');
    const options = cardByTitle('Options');
    await expandCard(options);
    const pdf = options.locator('form[action*="/print/"], form').filter({ has: page.locator('button', { hasText: 'PDF' }) }).first();
    const highlights = [];
    if (await pdf.locator('select[name=layout]').count()) highlights.push(pdf.locator('select[name=layout]'));
    if (await pdf.locator('select[name=lang]').count()) highlights.push(pdf.locator('select[name=lang]'));
    highlights.push(pdf.getByRole('button', { name: 'PDF' }));
    await shot('impression', { title: 'Carte « Options » : modèle, langue puis « PDF »', highlight: highlights });
    ids.layouts = await pdf.locator('select[name=layout] option').allTextContents();

    const copies = options.getByRole('button', { name: /Copier…/ });
    await copies.click();
    await page.waitForTimeout(400);
    const menu = options.locator('.dropdown-menu.show');
    await shot('copies-menu', { title: 'Menu « Copier… » : dupliquer, nouvel indice, variante, trame', highlight: [copies, menu] });
    await page.keyboard.press('Escape');
});

await step('9. Enregistrer comme trame', async () => {
    const options = cardByTitle('Options');
    await options.getByRole('button', { name: /Copier…/ }).click();
    await options.locator('.dropdown-menu.show').getByRole('button', { name: /Enregistrer comme trame/ }).click();
    const modal = page.locator('#quoteTemplateModal');
    await modal.waitFor({ state: 'visible' });
    await modal.locator('#template_label').fill(TEMPLATE);
    const submit = modal.locator('button[type=submit]');
    await shot('trame-modale', { title: 'Fenêtre « Enregistrer comme trame »', highlight: [modal.locator('#template_label'), submit] });
    await Promise.all([page.waitForURL(url => idFrom(url.toString(), 'quotes') !== ids.quote), submit.click()]);
    ids.template = idFrom(page.url(), 'quotes');
    await settle(page);
    const newFrom = page.getByRole('link', { name: /Nouveau devis depuis cette trame/ });
    await shot('trame-fiche', { title: 'Fiche de la trame : bandeau et « Nouveau devis depuis cette trame »', highlight: newFrom });

    await page.goto(u('/quotes?tab=templates'));
    await settle(page);
    await shot('trames-onglet', { title: 'Liste des devis, onglet « Trames de devis »', highlight: page.locator('.nav-link', { hasText: 'Trames de devis' }) });

    await page.goto(u(`/quotes?tab=list&template=${ids.template}`));
    await settle(page);
    let modal2 = page.locator('.modal.show');
    if (!(await modal2.count())) {
        await page.getByRole('button', { name: /Nouveau devis/ }).first().click();
        modal2 = page.locator('.modal.show');
    }
    await modal2.waitFor();
    await modal2.locator('select').first().waitFor();
    await settle(page);
    const tpl = field(modal2, "Partir d'une trame", 'select');
    if ((await tpl.inputValue()) !== ids.template) await tpl.selectOption(ids.template);
    await pickTrainingCompany(modal2);
    // Le libellé n'est pas toujours repris de la trame (il reste « - ») : le saisir.
    await field(modal2, 'Libellé').fill(`[FORMATION] Garde-corps depuis trame ${stamp}`);
    const submit2 = modal2.locator('button[type=submit]').first();
    await shot('devis-depuis-trame', { title: 'Nouveau devis : la trame est déjà choisie, reste le client', highlight: [tpl, field(modal2, 'Libellé'), field(modal2, 'Nom du tiers', 'select'), submit2] });
    await Promise.all([page.waitForURL(/\/quotes\/\d+/, { timeout: 30000 }), submit2.click()]);
    ids.fromTemplate = idFrom(page.url(), 'quotes');
    await settle(page);
});

await step('10. Nouvel indice puis variante', async () => {
    await openQuote(ids.quote, '#Quote');
    const options = cardByTitle('Options');
    await options.getByRole('button', { name: /Copier…/ }).click();
    await Promise.all([
        page.waitForURL(url => idFrom(url.toString(), 'quotes') !== ids.quote, { timeout: 30000 }),
        options.locator('.dropdown-menu.show').getByRole('button', { name: /Nouvel indice/ }).click(),
    ]);
    ids.revision = idFrom(page.url(), 'quotes');
    await settle(page);
    await page.locator('#DocumentTabs a[href="#Quote"]').first().click();
    await shot('indice-cree', { title: 'Indice B créé : message, code et carte « Historique »', highlight: [page.locator('.alert-success').first(), cardByTitle('Historique')] });

    const opt2 = cardByTitle('Options');
    await opt2.getByRole('button', { name: /Copier…/ }).click();
    await opt2.locator('.dropdown-menu.show').getByRole('button', { name: /Variante/ }).click();
    const modal = page.locator('#quoteVariantModal');
    await modal.waitFor({ state: 'visible' });
    await modal.locator('#variant_label').fill('Version inox');
    const submit = modal.locator('button[type=submit]');
    await shot('variante-modale', { title: 'Fenêtre « Variante » : libellé obligatoire', highlight: [modal.locator('#variant_label'), submit] });
    await Promise.all([page.waitForURL(url => idFrom(url.toString(), 'quotes') !== ids.revision, { timeout: 30000 }), submit.click()]);
    ids.variant = idFrom(page.url(), 'quotes');
    await settle(page);
});

await step('11. Variante gagnée, clôture des autres', async () => {
    const won = page.locator('.as-btn[title="Gagné"]');
    await shot('variante-gagnee', { title: 'Variante : passer au statut « Gagné »', highlight: won });
    await Promise.all([page.waitForURL(/\/quotes\/\d+/), won.click()]);
    await page.waitForTimeout(1500);
    await openQuote(ids.variant, '#Quote');
    const close = page.getByRole('button', { name: /Clore les autres/ });
    await shot('clore-famille', { title: 'Devis gagné : bandeau « Clore les autres »', highlight: close });
    await close.click();
    await settle(page);
    await page.locator('#DocumentTabs a[href="#Quote"]').first().click();
    await shot('historique', { title: 'Carte « Historique » après clôture', highlight: cardByTitle('Historique') });
});

await step('12. Devis perdu : motif', async () => {
    await openQuote(ids.fromTemplate);
    const lost = page.locator('.as-btn[title="Perdu"]');
    await lost.click();
    const dialog = page.locator('.modal.show', { has: page.locator('#loss-reason-select') });
    await dialog.waitFor();
    await dialog.locator('#loss-reason-select').selectOption({ index: 1 });
    await dialog.locator('#loss-reason-comment').fill('[FORMATION] Écart de prix de 12 % avec le concurrent retenu.');
    const ok = dialog.locator('.btn-danger');
    await shot('motif-perte', { title: 'Fenêtre « Devis perdu : pourquoi ? »', highlight: [dialog.locator('#loss-reason-select'), dialog.locator('#loss-reason-comment'), ok] });
    await Promise.all([page.waitForURL(/\/quotes\/\d+/), ok.click()]);
    await settle(page);
    await page.waitForTimeout(1000);
    await shot('motif-perte-affiche', { title: 'Motif affiché sous les flèches de statut', highlight: page.locator('.as-track .text-body-secondary', { hasText: 'Motif de perte' }) });
});

await step('13. Indicateur pertes commerciales', async () => {
    await page.goto(u('/quotes'));
    await settle(page);
    await page.locator('.card-header .nav-link', { hasText: 'Tableau de bord' }).first().click();
    await settle(page);
    const card = page.locator('.card', { hasText: 'Pertes commerciales' }).last();
    await card.waitFor();
    await settle(page);
    await shot('pertes-commerciales', { title: 'Tableau de bord des devis : carte « Pertes commerciales »', highlight: card });
});

save({ scenario: 'devis-avance', label: LABEL, template: TEMPLATE, ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
