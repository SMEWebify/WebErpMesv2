// Guide « Facturer et encaisser » : dossier de fin de fabrication (exigé, refus du BL, validé),
// BL et liste de colisage, demande de facture, brouillon → émission → règlement partiel →
// relance tracée → solde, avoir sur une seconde facture, encours client, export comptable,
// intégrations.
// Prépare sa propre commande [FORMATION] (3 lignes) sur une société [FORMATION] sans BL ni
// facture en attente. Ne clique jamais : flèches de statut, e-mail, dépôt PDP, boutons
// CSV/XLS d'export (tous irréversibles ou sortants).
// Usage : node tools/training/scenarios/facturation.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { readEnv, openSession, field, settle, tab } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const OUT = 'resources/guides/facturation';
const u = p => `${BASE}/fr${p}`;
const PRIVATE = (process.env.TRAINING_PRIVATE ?? env.TRAINING_PRIVATE ?? '').split('|').map(v => v.trim()).filter(Boolean);
const tinker = code => JSON.parse(execFileSync(PHP, ['artisan', 'tinker', '--execute', code]).toString().trim().split('\n').pop());

// Société [FORMATION] cliente, avec contact et adresse, sans ligne à livrer ni à facturer :
// les écrans « Attente de… » n'affichent alors que la commande du guide.
const company = tinker(`
$c = App\\Models\\Companies\\Companies::where('label','like','[FORMATION]%')->where('statu_customer',2)
  ->whereHas('Contacts')->whereHas('Addresses')
  ->whereDoesntHave('Orders', fn($q) => $q->whereHas('OrderLines', fn($l) => $l->where('delivered_remaining_qty','>',0)->orWhere('invoiced_remaining_qty','>',0)))
  ->orderBy('id')->first();
echo json_encode(['id' => $c?->id, 'label' => $c?->label, 'contact' => $c?->Contacts()->value('id'), 'address' => $c?->Addresses()->value('id')]);
`);
if (!company.id) {
    console.error('Aucune société [FORMATION] cliente libre : relancer tools/training/scenarios/vente-complete.mjs');
    process.exit(1);
}
const daysAgo = n => new Date(Date.now() - n * 864e5).toISOString().slice(0, 10);
const LINES = [
    { code: 'PLT-450', label: 'Platine acier S235 ép. 8 mm, pliée', qty: 20, selling_price: 42 },
    { code: 'EQR-120', label: 'Équerre de fixation galvanisée', qty: 50, selling_price: 6.5 },
    { code: 'CAP-900', label: 'Capot inox 304 brossé, soudé', qty: 4, selling_price: 260 },
];
let order = null;
let delivery = null;
let main = null;
let other = null;

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: OUT });

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'facturation', failedAt: title, error: String(e), company, order, delivery, main, other });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

// Appel JSON de l'application depuis la page connectée (cookie de session + jeton CSRF).
async function api(method, p, body) {
    return page.evaluate(async ([m, url, b]) => {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const r = await fetch(url, {
            method: m,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: b ? JSON.stringify(b) : undefined,
        });
        return { status: r.status, data: await r.json().catch(() => null) };
    }, [method, u(p), body]);
}

async function openInvoice(id, hash) {
    await page.goto(u(`/invoices/${id}`));
    await settle(page);
    if (hash) {
        await page.locator(`.nav-link[href="${hash}"]`).first().click();
        await settle(page);
    }
}

const lineRow = (scope, code) => page.locator(`${scope} tbody tr`, { hasText: code }).first();

async function openOrderLines() {
    await page.goto(u(`/orders/${order.id}`));
    await settle(page);
    await page.locator('a.nav-link[href="#Lines"]').first().click();
    await settle(page);
    await lineRow('#order-lines-page-app', 'CAP-900').waitFor();
}

async function selectLines(codes) {
    for (const code of codes) await lineRow('#order-lines-page-app', code).locator('input[type=checkbox]').first().check();
    await page.waitForTimeout(200);
}

const dossierToggle = () => page.locator('#order-lines-page-app button.dropdown-toggle', { hasText: 'Dossier' });
const dossierItem = label => page.locator('#order-lines-page-app .dropdown-menu.show button', { hasText: new RegExp(`^${label}`) });

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('Préparation : commande [FORMATION]', async () => {
    const sd = await page.evaluate(async url => (await fetch(url, { headers: { Accept: 'application/json' } })).json(), u('/orders/json/select-data'));
    // « 30 jours fin de mois » : l'échéance calculée se voit sur la facture.
    const cond = sd.payment_conditions.find(c => c.code === '30FDM') ?? sd.payment_conditions[0];
    // Le numéro proposé peut déjà être pris (base partagée) : numéro suivant.
    let code = sd.next_code_external;
    let res = null;
    for (let attempt = 0; attempt < 30; attempt += 1) {
        res = await api('POST', '/orders/json/store', {
            code, label: '[FORMATION] Facturation', user_id: 1, type: 1,
            companies_id: company.id, companies_contacts_id: company.contact, companies_addresses_id: company.address,
            accounting_payment_conditions_id: cond.id,
            accounting_payment_methods_id: (sd.payment_methods.find(m => m.default) ?? sd.payment_methods[0]).id,
            accounting_deliveries_id: (sd.deliveries.find(d => d.default) ?? sd.deliveries[0]).id,
            customer_reference: '[FORMATION] CDE-2026-1031',
        });
        if (res.status !== 422 || !res.data?.errors?.code) break;
        code = code.replace(/(\d+)(?!.*\d)/, n => String(Number(n) + 1).padStart(n.length, '0'));
    }
    if (res.status !== 201) throw new Error(`Commande non créée : ${res.status} ${JSON.stringify(res.data)}`);
    order = { id: Number(res.data.redirect.match(/\/(\d+)$/)[1]), code, condition: cond.label };
    for (const [i, l] of LINES.entries()) {
        const r = await api('POST', `/orders/${order.id}/lines/json/store`, { ordre: i + 1, discount: 0, ...l });
        if (r.status >= 300) throw new Error(`Ligne ${l.code} non créée : ${r.status} ${JSON.stringify(r.data)}`);
    }
    console.log('  commande', order);
});

await step('A. Exiger le dossier de fin de fabrication', async () => {
    await openOrderLines();
    await selectLines(['CAP-900']);
    await dossierToggle().click();
    await shot('dossier-exiger', { title: 'Lignes cochées : menu « Dossier », « Exiger le dossier »', highlight: [lineRow('#order-lines-page-app', 'CAP-900').locator('input[type=checkbox]'), dossierToggle(), dossierItem('Exiger le dossier')] });
    await dossierItem('Exiger le dossier').click();
    await settle(page);
    const badge = lineRow('#order-lines-page-app', 'CAP-900').locator('.badge', { hasText: 'Dossier à valider' });
    await badge.waitFor();
    await shot('dossier-a-valider', { title: 'Badge « Dossier à valider » sur la ligne', highlight: badge });
});

await step('B. BL refusé tant que le dossier n’est pas validé', async () => {
    await openOrderLines();
    await selectLines(LINES.map(l => l.code));
    await page.locator('#order-lines-page-app button', { hasText: 'Nouveau BL' }).click();
    const alert = page.locator('#order-lines-page-app .alert-danger').first();
    await alert.waitFor();
    await shot('dossier-bl-refuse', { title: 'Création du BL refusée : dossier non validé', highlight: alert });

    await page.goto(u('/deliverys/request'));
    await settle(page);
    await page.locator('#deliverys-request-app select').first().selectOption(String(company.id));
    await settle(page);
    const badge = lineRow('#deliverys-request-app', 'CAP-900').locator('.badge', { hasText: 'Dossier à valider' });
    await badge.waitFor();
    await shot('dossier-attente-bl', { title: 'Attente de bons de livraison : badge « Dossier à valider »', highlight: badge });
});

await step('C. Valider le dossier', async () => {
    await openOrderLines();
    await selectLines(['CAP-900']);
    await dossierToggle().click();
    await shot('dossier-valider', { title: 'Menu « Dossier » : « Valider le dossier »', highlight: dossierItem('Valider le dossier') });
    await dossierItem('Valider le dossier').click();
    await settle(page);
    const badge = lineRow('#order-lines-page-app', 'CAP-900').locator('.badge', { hasText: 'Dossier validé' });
    await badge.waitFor();
    await shot('dossier-valide', { title: 'Badge « Dossier validé »', highlight: badge });
});

await step('D. BL et colisage', async () => {
    await openOrderLines();
    await selectLines(LINES.map(l => l.code));
    await Promise.all([
        page.waitForURL(/\/deliverys\/\d+/, { timeout: 30000 }),
        page.locator('#order-lines-page-app button', { hasText: 'Nouveau BL' }).click(),
    ]);
    await settle(page);
    delivery = { id: Number(page.url().match(/\/deliverys\/(\d+)/)[1]) };

    const packages = [
        { code: 'COLIS 1', type: 'Palette', gross: 96, net: 82, l: 1200, w: 800, h: 450, content: { 'PLT-450': 20, 'EQR-120': 50 } },
        { code: 'COLIS 2', type: 'Caisse bois', gross: 41, net: 34, l: 1000, w: 600, h: 500, content: { 'CAP-900': 4 } },
    ];
    for (const [i, p] of packages.entries()) {
        await page.goto(u(`/deliverys/${delivery.id}`));
        await settle(page);
        await page.locator('a.nav-link[href="#Packaging"]').first().click();
        await settle(page);
        const form = page.locator('#Packaging form:has(input[name="deliverys_id"])');
        await form.locator('#code').fill(p.code);
        await form.locator('#type').fill(p.type);
        await form.locator('#gross_weight').fill(String(p.gross));
        await form.locator('#net_weight').fill(String(p.net));
        await form.locator('#length').fill(String(p.l));
        await form.locator('#width').fill(String(p.w));
        await form.locator('#height').fill(String(p.h));
        const inputs = [];
        for (const [code, qty] of Object.entries(p.content)) {
            const input = form.locator('tr', { hasText: code }).locator('input[type=number]');
            await input.fill(String(qty));
            inputs.push(input);
        }
        const submit = form.locator('button[type=submit]');
        if (i === 1) {
            await shot('colis-contenu', { title: 'Nouveau packaging : poids, dimensions et contenu du colis', highlight: [...inputs, submit] });
        }
        await Promise.all([page.waitForLoadState('load'), submit.click()]);
        await settle(page);
    }
    await page.goto(u(`/deliverys/${delivery.id}`));
    await settle(page);
    await page.locator('a.nav-link[href="#Packaging"]').first().click();
    await settle(page);
    await shot('colis-liste', { title: 'Onglet Packaging : les colis du BL', highlight: page.locator('#Packaging .card').first() });

    await page.locator('a.nav-link[href="#Delivery"]').first().click();
    await settle(page);
    const menu = page.locator('form:has(select[name="layout"])').first();
    await menu.locator('select[name="layout"]').selectOption('packing-list');
    await shot('colis-impression', { title: 'Menu d’impression du BL : modèle « Liste de colisage »', highlight: [menu.locator('select[name="layout"]'), menu.getByRole('button', { name: 'PDF' })] });
    delivery.pdf = `${await menu.getAttribute('action')}?layout=packing-list&lang=fr`;
});

await step('D bis. Aperçu de la liste de colisage', async () => {
    const pdf = await page.request.get(delivery.pdf);
    const raw = await pdf.body();
    // Téléchargement servi en text/html par l'application : on vérifie la signature du fichier.
    if (!pdf.ok() || raw.subarray(0, 4).toString() !== '%PDF') throw new Error(`PDF non reçu : ${pdf.status()} ${pdf.headers()['content-type']}`);
    const bytes = raw.toString('base64');
    // Rendu par pdf.js (celui du projet) dans une page servie par Playwright : le navigateur
    // sans interface n'affiche pas les PDF. Les textes privés de TRAINING_PRIVATE sont recouverts.
    const lib = path.resolve('node_modules/pdfjs-dist/build');
    const pattern = `${BASE}/__training/**`;
    await page.route(pattern, route => {
        const name = route.request().url().split('/__training/')[1];
        if (name === 'view.html') {
            return route.fulfill({ contentType: 'text/html', body: '<!doctype html><html><body style="margin:0;background:#525659"><canvas id="c" style="display:block;margin:0 auto"></canvas></body></html>' });
        }
        return route.fulfill({ contentType: 'text/javascript', body: fs.readFileSync(path.join(lib, name)) });
    });
    await page.goto(`${BASE}/__training/view.html`);
    const info = await page.evaluate(async ([b64, base, privates]) => {
        const pdfjs = await import(`${base}/__training/pdf.min.mjs`);
        pdfjs.GlobalWorkerOptions.workerSrc = `${base}/__training/pdf.worker.min.mjs`;
        const data = Uint8Array.from(atob(b64), c => c.charCodeAt(0));
        const doc = await pdfjs.getDocument({ data }).promise;
        const pg = await doc.getPage(1);
        const viewport = pg.getViewport({ scale: 1.6 });
        const canvas = document.getElementById('c');
        canvas.width = viewport.width; canvas.height = viewport.height;
        const ctx = canvas.getContext('2d');
        await pg.render({ canvasContext: ctx, viewport }).promise;
        const text = await pg.getTextContent();
        const all = text.items.map(i => i.str).join(' ');
        // Bloc expéditeur (colonne gauche, entre le nom de la société et la date) : adresse,
        // téléphone et e-mail de la société, toujours recouverts.
        const ys = it => it.transform[5];
        const dateItem = text.items.find(it => /^Date\s*:?/.test(it.str.trim()));
        const midX = pg.view[2] / 2;
        const leftItems = text.items.filter(it => it.str.trim() && it.transform[4] < midX && dateItem && ys(it) > ys(dateItem));
        const nameY = Math.max(...leftItems.filter(it => it.transform[4] < midX).map(ys).filter(y => y < pg.view[3] - 120));
        let masked = 0;
        for (const item of text.items) {
            const s = item.str.trim();
            if (!s) continue;
            const sender = leftItems.includes(item) && ys(item) < nameY - 1;
            const hit = sender || privates.some(v => s.includes(v) || (s.length >= 6 && v.includes(s)));
            if (!hit) continue;
            const [x, y] = viewport.convertToViewportPoint(item.transform[4], item.transform[5]);
            const h = Math.abs(item.transform[3]) * viewport.scale;
            ctx.fillStyle = '#adb5bd';
            ctx.fillRect(x - 2, y - h - 2, item.width * viewport.scale + 4, h + 6);
            masked += 1;
        }
        return { pages: doc.numPages, masked, height: canvas.height, width: canvas.width, leak: privates.filter(v => all.includes(v)).length };
    }, [bytes, BASE, PRIVATE]);
    console.log('  aperçu PDF', info);
    await page.setViewportSize({ width: 1440, height: Math.min(Math.ceil(info.height), 1900) });
    await shot('colis-pdf', { title: 'Liste de colisage imprimée (aperçu)' });
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.unroute(pattern);
});

await step('E. Demande de facture', async () => {
    for (const [i, codes] of [['PLT-450', 'EQR-120'], ['CAP-900']].entries()) {
        await page.goto(u('/invoices/request'));
        await settle(page);
        await page.locator('#invoices-request-app select').first().selectOption(String(company.id));
        await settle(page);
        const boxes = [];
        for (const code of codes) {
            const box = lineRow('#invoices-request-app', code).locator('input[type=checkbox]');
            await box.check();
            boxes.push(box);
        }
        const submit = page.locator('#invoices-request-app button[type=submit]');
        if (i === 0) await shot('demande-facture', { title: 'Attente de facture : cocher les lignes livrées, « Nouvelle facture »', highlight: [page.locator('#invoices-request-app select').first(), ...boxes, submit] });
        // Le numéro proposé peut déjà être pris (base partagée) : numéro suivant.
        const codeInput = field(page.locator('#invoices-request-app'), 'ID externe');
        for (let attempt = 0; attempt < 30; attempt += 1) {
            await submit.click();
            await Promise.race([
                page.waitForURL(/\/invoices\/\d+/, { timeout: 30000 }),
                page.locator('#invoices-request-app .text-danger').first().waitFor({ timeout: 30000 }),
            ]);
            if (/\/invoices\/\d+/.test(page.url())) break;
            const code = await codeInput.inputValue();
            await codeInput.fill(code.replace(/(\d+)(?!.*\d)/, n => String(Number(n) + 1).padStart(n.length, '0')));
        }
        await settle(page);
        const inv = { id: Number(page.url().match(/\/invoices\/(\d+)/)[1]), companies_id: company.id };
        if (i === 0) main = inv; else other = inv;
    }
    const codes = tinker(`echo json_encode(App\\Models\\Workflow\\Invoices::whereIn('id',[${main.id},${other.id}])->pluck('code','id'));`);
    main.code = codes[main.id];
    other.code = codes[other.id];
    console.log('  factures', main, other);
});

await step('1. Vérifier le brouillon', async () => {
    await openInvoice(main.id, '#InvoiceLines');
    const emit = page.getByRole('button', { name: 'Émettre la facture' });
    await shot('brouillon-lignes', { title: 'Brouillon : lignes encore modifiables', highlight: [page.locator('#InvoiceLines .alert-warning').first(), emit] });

    const form = page.locator('#InvoiceLines form', { has: page.getByRole('button', { name: 'Ajouter' }) }).last();
    await field(form, 'Désignation').fill('Frais de port');
    await field(form, 'Qté').fill('1');
    await field(form, 'P.U HT').fill('35');
    await shot('brouillon-ajout-ligne', { title: 'Ajouter une ligne au brouillon (frais de port)', highlight: [field(form, 'Désignation'), field(form, 'P.U HT'), form.getByRole('button', { name: 'Ajouter' })] });
    await form.getByRole('button', { name: 'Ajouter' }).click();
    await settle(page);

    await openInvoice(main.id, '#Invoice');
    const due = page.locator('#due_date');
    const update = page.locator('#Invoice button[type=submit]').first();
    main.computedDue = await due.inputValue();
    await shot('brouillon-echeance', { title: `Onglet « Facture info » : échéance calculée (${order.condition})`, highlight: [due, update] });
    // Échéance passée pour la démonstration des relances (30 jours de retard), sans capture.
    await due.fill(daysAgo(30));
    await update.click();
    await settle(page);
});

await step('2. Émettre', async () => {
    await openInvoice(main.id, '#InvoiceLines');
    await page.getByRole('button', { name: 'Émettre la facture' }).click();
    await page.waitForLoadState('load');
    await settle(page);
    await openInvoice(main.id);
    const pdf = page.locator('a.btn-info', { hasText: 'PDF' }).first();
    await shot('emise', { title: 'Facture émise : statut « Envoyé », PDF disponible', highlight: [page.locator('.doc-header__steps').getByText('Envoyé', { exact: true }).first(), pdf] });

    await page.goto(u('/invoices'));
    await settle(page);
    await tab(page, 'Factures liste');
    // La recherche générale porte sur le libellé : filtre de la colonne « Code ».
    await page.locator('#invoices-index-app thead input, thead input').first().fill(main.code);
    await settle(page);
    const fx = page.locator('[title="Factur-X"]').first();
    await shot('liste-facturx', { title: 'Liste des factures : bouton Factur-X', highlight: fx });
});

await step('3. Règlement partiel', async () => {
    await openInvoice(main.id, '#Payments');
    const pay = page.locator('#Payments');
    await pay.locator('input[name="amount"]').fill('500');
    await pay.locator('select[name="payment_method_id"]').selectOption({ index: 1 });
    await pay.locator('input[name="reference"]').fill('VIR 2026-118');
    const save1 = pay.getByRole('button', { name: 'Enregistrer' });
    await shot('reglement-saisie', { title: 'Saisir un règlement partiel', highlight: [pay.locator('input[name="amount"]'), pay.locator('select[name="payment_method_id"]'), pay.locator('input[name="reference"]'), save1] });
    await save1.click();
    await settle(page);
    await shot('reglement-partiel', { title: 'Règlement enregistré : reste dû affiché' });
});

await step('4. Relance tracée', async () => {
    await page.goto(u('/invoices/reminders'));
    await settle(page);
    await page.getByPlaceholder(/Rechercher un client ou une facture/).fill(main.code);
    await settle(page);
    const trace = page.getByRole('button', { name: /Tracer un appel/ }).first();
    await shot('relances-liste', { title: 'Factures à relancer : niveau proposé', highlight: trace });
    await trace.click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('#reminder-channel').selectOption('phone');
    await modal.locator('#reminder-note').fill('Appel comptabilité : paiement du solde promis sous 8 jours.');
    const record = modal.getByRole('button', { name: 'Enregistrer la relance' });
    await shot('relance-appel', { title: 'Tracer un appel (aucun e-mail envoyé)', highlight: [modal.locator('#reminder-channel'), modal.locator('#reminder-note'), record] });
    await record.click();
    await settle(page);
    await shot('relance-enregistree', { title: 'Relance enregistrée' });
});

await step('5. Solde', async () => {
    await openInvoice(main.id, '#Payments');
    const pay = page.locator('#Payments');
    const amount = pay.locator('input[name="amount"]');
    const rest = (await amount.getAttribute('placeholder') || '').replace(/[^\d,.-]/g, '').replace(',', '.');
    await amount.fill(rest);
    await pay.locator('select[name="payment_method_id"]').selectOption({ index: 1 });
    await pay.locator('input[name="reference"]').fill('VIR 2026-131');
    await pay.getByRole('button', { name: 'Enregistrer' }).click();
    await settle(page);
    // L'en-tête (statut) est rendu par le serveur : rechargement pour l'afficher à jour.
    await openInvoice(main.id, '#Payments');
    await shot('soldee', { title: 'Facture soldée : statut « Payé »' });
    await openInvoice(main.id);
    const card = page.locator('.card', { hasText: 'Relances' }).last();
    await shot('historique-relances', { title: 'Fiche facture : historique des relances', highlight: card });
});

await step('6. Avoir', async () => {
    await openInvoice(other.id, '#InvoiceLines');
    await page.getByRole('button', { name: 'Émettre la facture' }).click();
    await page.waitForLoadState('load');
    await settle(page);
    await openInvoice(other.id, '#InvoiceLines');
    const box = page.locator('input[name="selected_invoice_lines[]"]').first();
    await box.check();
    const create = page.getByRole('button', { name: 'Nouvel avoir' });
    await shot('avoir-creation', { title: 'Facture émise : cocher les lignes, « Nouvel avoir »', highlight: [box, create] });
    await Promise.all([page.waitForURL(/\/credit-notes\/\d+/, { timeout: 30000 }), create.click()]);
    await settle(page);
    await shot('avoir-fiche', { title: 'Avoir créé, statut « En attente »', highlight: page.locator('select[name="statu"]') });
});

await step('7. Encours client', async () => {
    await page.goto(u(`/companies/${main.companies_id}`));
    await settle(page);
    const card = page.locator('.card:has(> .card-header:has-text("Encours client"))').first();
    await shot('encours', { title: 'Fiche société, tableau de bord : encours client', highlight: card });
});

await step('8. Export comptable', async () => {
    await page.goto(u('/admin/imports-exports'));
    await settle(page);
    await page.locator('.nav-link', { hasText: 'Export FEC' }).first().click();
    await settle(page);
    const encais = page.locator('#journal_ENCAIS');
    await encais.check();
    const submit = page.locator('#fec-export-lines-app').getByRole('button', { name: 'Soumettre' });
    await submit.click();
    await settle(page);
    await shot('export-fec', { title: 'Export FEC : journaux, période, écritures à exporter', highlight: [encais, submit] });
});

await step('9. Intégrations', async () => {
    await page.goto(u('/admin/integrations'));
    await settle(page);
    const card = page.locator('.card', { hasText: 'Facturation électronique' }).first();
    // La carte e-mail voisine affiche l'adresse d'expédition réelle : masquée.
    const mail = page.locator(`.card:has(> .card-header:has-text("Envoi d'e-mail"))`);
    await shot('integrations-pdp', { title: 'Intégrations : facturation électronique (PDP)', highlight: card, mask: [mail] });
});

save({ scenario: 'facturation', company, order, delivery, main, other, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', order, main, other);
await browser.close();
