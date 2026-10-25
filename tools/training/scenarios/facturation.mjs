// Guide « Facturer et encaisser » : brouillon → émission → règlement partiel → relance tracée
// → solde, avoir sur une seconde facture, encours client, export comptable, intégrations.
// Part de deux factures brouillon [FORMATION] laissées par le scénario vente-complete
// (le relancer s'il n'en reste pas). Ne clique jamais : flèches de statut, e-mail, dépôt PDP,
// boutons CSV/XLS d'export (tous irréversibles ou sortants).
// Usage : node tools/training/scenarios/facturation.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import { readEnv, openSession, field, settle, tab } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const u = p => `${BASE}/fr${p}`;

// Deux brouillons [FORMATION] les plus récents : [principal, avoir].
const drafts = JSON.parse(execFileSync(PHP, ['artisan', 'tinker', '--execute',
    "echo json_encode(App\\Models\\Workflow\\Invoices::where('statu',1)->whereHas('companie', fn($q) => $q->where('label','like','[FORMATION]%'))->latest('id')->take(2)->get(['id','code','companies_id']));",
]).toString().trim().split('\n').pop());
if (drafts.length < 2) {
    console.error('Il faut deux factures brouillon [FORMATION] : relancer tools/training/scenarios/vente-complete.mjs');
    process.exit(1);
}
const [main, other] = drafts;
const daysAgo = n => new Date(Date.now() - n * 864e5).toISOString().slice(0, 10);

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/facturation' });

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'facturation', failedAt: title, error: String(e), main, other });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

async function openInvoice(id, hash) {
    await page.goto(u(`/invoices/${id}`));
    await settle(page);
    if (hash) {
        await page.locator(`.nav-link[href="${hash}"]`).first().click();
        await settle(page);
    }
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
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

    // Échéance passée pour la démonstration des relances (30 jours de retard).
    await openInvoice(main.id, '#Invoice');
    const due = page.locator('#due_date');
    await due.fill(daysAgo(30));
    const update = page.locator('#Invoice button[type=submit]').first();
    await shot('brouillon-echeance', { title: 'Onglet « Facture info » : date d’échéance', highlight: [due, update] });
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

save({ scenario: 'facturation', main, other, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', main, other);
await browser.close();
