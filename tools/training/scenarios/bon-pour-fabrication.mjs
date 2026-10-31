// Bon pour fabrication (BPF) : une commande [FORMATION] à deux lignes, chacune avec une opération
// de production ; tâche bloquée sans BPF, BPF donné sur une ligne cochée depuis la fiche commande,
// tâche débloquée, colonne BPF de l'index des lignes (annulation refusée après démarrage),
// cadenas de la liste des tâches.
// Option PRODUCTION_REQUIRE_RELEASE=true requise sur l'instance locale.
// Usage : node tools/training/scenarios/bon-pour-fabrication.mjs   (HEADED=1 pour voir le navigateur)
import { readEnv, openSession, field, settle, tab } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const EMAIL = process.env.TRAINING_EMAIL || env.TRAINING_EMAIL;
const PASSWORD = process.env.TRAINING_PASSWORD || env.TRAINING_PASSWORD;
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');
const CUSTOMER = `[FORMATION] Chaudronnerie Morel ${stamp}`;
const u = p => `${BASE}/fr${p}`;
const idFrom = (url, seg) => url.match(new RegExp(`/${seg}/(\\d+)`))?.[1];

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/bon-pour-fabrication' });
const ids = {};
const LINES = [
    { code: 'SUP-120', label: '[FORMATION] Support moteur S235 ép. 6', qty: '10', price: '38' },
    { code: 'CAP-300', label: '[FORMATION] Capot moteur inox 304 ép. 1,5', qty: '4', price: '96' },
];

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR-bpf.png', fullPage: true }).catch(() => {});
        save({ scenario: 'bon-pour-fabrication', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

async function openLines() {
    await page.goto(u(`/orders/${ids.order}`));
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

const lineRow = code => page.locator('tbody tr:visible', { hasText: code }).first();

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('Client [FORMATION]', async () => {
    await page.goto(u('/companies'));
    await settle(page);
    await tab(page, 'Liste des Tiers');
    await page.getByRole('button', { name: /Ajouter un tiers/i }).click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await field(modal, 'Nom du tiers').fill(CUSTOMER);
    await field(modal, 'Utilisateur responsable', 'select').selectOption({ index: 1 });
    await Promise.all([page.waitForURL(/\/companies\/\d+/), modal.getByRole('button', { name: 'Enregistrer' }).click()]);
    ids.customer = idFrom(page.url(), 'companies');
    await settle(page);

    await page.locator('a[href="#Company"]').click();
    await field(page.locator('#Company'), 'Etat client', 'select').selectOption('2');
    await page.locator('#Company button[type=submit].btn-info').first().click();
    await page.locator('.alert-success').first().waitFor();

    await page.locator('a[href="#Adresses"]').click();
    const adr = page.locator('#Adresses');
    await field(adr, 'Tri').fill('1');
    await field(adr, 'Libellé').fill('Atelier');
    await field(adr, 'Adresse').fill('8 rue des Chaudronniers');
    await field(adr, 'Code postal').fill('49000');
    await field(adr, 'Ville').fill('Angers');
    await field(adr, 'Pays').fill('France');
    await page.locator('#addr-default-create').check().catch(() => {});
    await adr.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);

    await page.locator('a[href="#Contact"]').click();
    const ct = page.locator('#Contact');
    await field(ct, 'Tri').fill('1');
    await field(ct, 'Civilité', 'select').selectOption({ index: 1 });
    await field(ct, 'Prénom').fill('Paul');
    await field(ct, /^Nom/).fill('Morel');
    await page.locator('#contact-default-create').check().catch(() => {});
    await ct.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);
});

await step('Commande [FORMATION]', async () => {
    await page.goto(u('/orders'));
    await settle(page);
    await page.locator('.app-main .nav-link', { hasText: /^\s*Commandes\s*$/ }).first().click();
    await settle(page);
    await page.getByRole('button', { name: /Nouvelle commande/ }).first().click();
    const modal = page.locator('.modal.show').last();
    await field(modal, 'Nom du tiers', 'select').waitFor();
    // Numéro proposé parfois déjà pris (autres scénarios en parallèle) : code propre au parcours.
    await field(modal, 'Code').fill(`CMD-BPF-${stamp}`);
    await field(modal, 'Libellé').fill(`[FORMATION] Supports et capots ${stamp}`);
    const delivery = new Date(Date.now() + 21 * 86400000).toISOString().slice(0, 10);
    await field(modal, 'Date de livraison').fill(delivery);
    await field(modal, 'Utilisateur responsable', 'select').selectOption({ index: 1 });
    const company = field(modal, 'Nom du tiers', 'select');
    const label = (await company.locator('option').allTextContents()).find(t => t.includes(CUSTOMER));
    await company.selectOption({ label });
    await page.waitForTimeout(1200);
    await settle(page);
    await Promise.all([page.waitForURL(/\/orders\/\d+/, { timeout: 30000 }), modal.getByRole('button', { name: 'Enregistrer' }).click()]);
    ids.order = idFrom(page.url(), 'orders');
    ids.orderCode = `CMD-BPF-${stamp}`;
    await settle(page);
});

await step('Lignes et gammes', async () => {
    await openLines();
    for (const line of LINES) {
        await page.locator('button[title="Ajouter une ligne"]').click();
        const form = page.locator('form#line-drawer-form');
        await form.waitFor();
        await form.getByPlaceholder('Code externe').fill(line.code);
        await form.getByPlaceholder('Description de la ligne').fill(line.label);
        await field(form, 'Quantité').fill(line.qty);
        await field(form, 'Prix unitaire').fill(line.price);
        await page.locator('button[type=submit][form="line-drawer-form"]').click();
        await settle(page);
        await page.getByRole('button', { name: 'Annuler', exact: true }).last().click().catch(() => {});
        await settle(page);
    }

    // Une opération de découpe laser (service productif) par ligne.
    for (const line of LINES) {
        await openLines();
        await lineRow(line.code).locator('.dropdown-toggle').last().click();
        await page.locator('.dropdown-menu.show .dropdown-item', { hasText: /Tâches \(/ }).first().click();
        const manage = page.locator('a:visible', { hasText: 'Gérer les tâches' }).first();
        await followHref(manage);
        await page.locator('button[title="Ajouter — Opération"]').click();
        const f = page.locator('form#techcut-form');
        await f.waitFor();
        const svc = field(f, 'Service', 'select');
        const opts = await svc.locator('option').allTextContents();
        const pick = opts.findIndex(t => /laser/i.test(t));
        await svc.selectOption({ index: pick > 0 ? pick : 1 });
        await field(f, 'N° ordre').fill('10');
        await f.getByPlaceholder("Libellé de l'opération").fill('Découpe laser');
        await field(f, 'Temps de réglage').fill('15');
        await field(f, 'Temps unitaire').fill('3');
        const cost = field(f, 'Coût unitaire');
        const price = field(f, 'Prix unitaire');
        if (!(parseFloat(await cost.inputValue()) > 0)) await cost.fill('12');
        if (!(parseFloat(await price.inputValue()) > 0)) await price.fill('18');
        const [resp] = await Promise.all([page.waitForResponse(r => r.url().endsWith('/task') && r.request().method() === 'POST'), f.locator('button[type=submit]').click()]);
        if (!resp.ok()) throw new Error(`Opération refusée (${resp.status()}) : ${await resp.text()}`);
        await settle(page);
        await page.reload();
        await settle(page);
        if (line === LINES[0]) {
            ids.taskUrl = await page.locator('a[title$="— écran de suivi"]').first().evaluate(a => a.href);
        }
    }
});

await step('1. Lignes sans BPF', async () => {
    await openLines();
    await shot('commande-lignes-sans-bpf', {
        title: 'Fiche commande, onglet lignes : badge « Sans BPF » sur chaque ligne',
        highlight: [lineRow(LINES[0].code).locator('.badge', { hasText: 'Sans BPF' }), lineRow(LINES[1].code).locator('.badge', { hasText: 'Sans BPF' })],
    });
});

await step('2. Tâche bloquée', async () => {
    await page.goto(ids.taskUrl);
    await page.locator('#task-statu-app').waitFor();
    await settle(page);
    const alert = page.locator('.ts-alert--warn').first();
    const play = page.locator('button.ts-run__btn--start');
    await shot('tache-bloquee', { title: 'Écran de suivi : bandeau et « Play » grisé tant que la ligne n’a pas de BPF', highlight: [alert, play] });
});

await step('3. BPF groupé', async () => {
    await openLines();
    const cb = lineRow(LINES[0].code).locator('input[type=checkbox]').first();
    await cb.check();
    const btn = page.getByRole('button', { name: /Bon pour fabrication \(/ });
    await shot('commande-bpf-groupe', { title: 'Cocher la ligne, puis « Bon pour fabrication (1) »', highlight: [cb, btn] });
    await btn.click();
    await settle(page);
    await shot('commande-bpf-donne', {
        title: 'La ligne libérée porte le badge « BPF », l’autre reste « Sans BPF »',
        highlight: [lineRow(LINES[0].code).locator('.badge', { hasText: /^BPF$/ }), lineRow(LINES[1].code).locator('.badge', { hasText: 'Sans BPF' })],
    });
});

await step('4. Tâche débloquée', async () => {
    await page.goto(ids.taskUrl);
    await page.locator('#task-statu-app').waitFor();
    await settle(page);
    const play = page.locator('button.ts-run__btn--start');
    await shot('tache-debloquee', { title: 'BPF donné : plus de bandeau, « Play » disponible', highlight: play });
    await play.click();
    await settle(page);
});

await step('5. Index des lignes de commande', async () => {
    await page.goto(u('/orders/lines'));
    await settle(page);
    // La recherche porte sur le libellé ; le filtre de colonne « Commande » isole la commande du parcours.
    await page.getByPlaceholder('Rechercher', { exact: true }).fill('moteur');
    await page.waitForTimeout(800);
    await settle(page);
    await page.locator('thead input').first().fill(ids.orderCode);
    await settle(page);
    const mine = page.locator('tbody tr:visible', { hasText: ids.orderCode });
    const row1 = mine.filter({ hasText: LINES[0].code }).first();
    const row2 = mine.filter({ hasText: LINES[1].code }).first();
    await shot('index-lignes-bpf', {
        title: 'Lignes de commande : colonne « BPF », bouton « Bon pour fabrication » ou annulation',
        highlight: [row2.getByRole('button', { name: 'Bon pour fabrication' }), row1.locator('button[title="Annuler le BPF"]')],
    });
    // Annulation refusée : une tâche de la ligne a démarré (message affiché dans une alerte, relevé dans le journal).
    await row1.locator('button[title="Annuler le BPF"]').click();
    await settle(page);
});

await step('6. Liste des tâches', async () => {
    await page.goto(u('/production/Task'));
    await settle(page);
    // Filtre de colonne « Commande Libellé » : la ligne restée sans BPF.
    const filter = page.locator('thead tr').nth(1).locator('input').first();
    await filter.fill('Capot moteur');
    await settle(page);
    const lock = page.locator('tbody tr:visible', { hasText: 'Capot moteur' }).locator('i.fa-lock').first();
    await shot('taches-cadenas', { title: 'Liste des tâches : cadenas sur les tâches de production sans BPF', highlight: lock });
});

save({ scenario: 'bon-pour-fabrication', customer: CUSTOMER, ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
