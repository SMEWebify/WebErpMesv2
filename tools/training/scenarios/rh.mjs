// Guide « Ressources humaines » : fiche salarié (informations RH, contrat, dossier
// confidentiel), note de frais avec justificatif, soumission puis validation RH.
// Données préfixées [FORMATION]. Le salarié de démonstration est « Test » (sans rôle) ; la
// note de frais est celle du compte de formation (seul compte à pouvoir se connecter).
// Ne clique jamais « Lire le justificatif » (envoie l'image au fournisseur d'IA) ni
// « Verrouiller » sur la fiche salarié.
// Usage : node tools/training/scenarios/rh.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const u = p => `${BASE}/fr${p}`;
const tinker = code => execFileSync(PHP, ['artisan', 'tinker', '--execute', code]).toString().trim().split('\n').pop();
const inDays = n => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);

const { browser, page, shot: rawShot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/rh' });
const shot = async (...a) => { await page.evaluate(() => document.activeElement?.blur()); return rawShot(...a); };
const ids = {};
const REPORT = '[FORMATION] Salon de la tôlerie, Lyon';

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'rh', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}
const openTab = async hash => { await page.locator(`a.nav-link[href="${hash}"]`).first().click(); await page.waitForTimeout(300); };
const submitOf = form => form.locator('button[type=submit]').last();
async function submit(form) {
    await Promise.all([page.waitForNavigation(), submitOf(form).click()]);
    await settle(page);
}

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
    ids.me = tinker("echo App\\Models\\User::where('email', '" + env.TRAINING_EMAIL + "')->value('id');");
    ids.employee = tinker("echo App\\Models\\User::where('name', 'Test')->value('id');");
});

await step('1. Liste des salariés', async () => {
    await page.goto(u('/human-resources/users'));
    await settle(page);
    await shot('salaries', { title: 'Liste des salariés' });
});

await step('2. Informations RH', async () => {
    await page.goto(u(`/human-resources/user/${ids.employee}`));
    await settle(page);
    const form = page.locator('form[action*="/update/user/"]');
    await form.locator('[name="job_title"]').fill('Opérateur presse plieuse');
    await form.locator('[name="payroll_number"]').fill('FORM-0003');
    await form.locator('[name="joined_date"]').fill('2026-09-01');
    await form.locator('[name="supervisor_id"]').selectOption({ label: 'Admin' }).catch(() => {});
    const section = await form.locator('[name="section_id"] option').evaluateAll(os => (os.find(o => /bend/i.test(o.text)) || os[1] || os[0]).value);
    await form.locator('[name="section_id"]').selectOption(section);
    await shot('fiche-rh', { title: 'Fiche salarié : informations RH', highlight: [form.locator('[name="job_title"]'), form.locator('[name="payroll_number"]'), form.locator('[name="role"]')] });
    await submit(form);
});

await step('3. Contrat', async () => {
    await page.goto(u(`/human-resources/user/${ids.employee}`));
    await settle(page);
    await openTab('#contract');
    const form = page.locator('form[action*="/contract/create"]');
    await form.locator('[name="type_of_contract"]').fill('CDI');
    await form.locator('[name="signature_date"]').fill('2026-08-20');
    await form.locator('[name="start_date"]').fill('2026-09-01');
    await form.locator('[name="duration_trial_period"]').fill('60');
    await form.locator('[name="weekly_duration"]').fill('35');
    await form.locator('[name="position"]').fill('Opérateur presse plieuse');
    await shot('contrat', { title: 'Nouveau contrat de travail', highlight: [form.locator('[name="type_of_contract"]'), form.locator('[name="start_date"]'), form.locator('[name="weekly_duration"]'), submitOf(form)] });
    await submit(form);
    await openTab('#contract');
    await shot('contrats', { title: 'Contrats du salarié' });
    await openTab('#documents');
    await page.waitForTimeout(800);
    // Le dossier de démonstration contient de vrais documents : l'aperçu et la liste sont masqués.
    await shot('documents', { title: 'Dossier du salarié (confidentiel)', mask: [page.locator('#documents .col-xl-7'), page.locator('#documents table'), page.locator('#documents .list-group')] });
});

await step('4. Note de frais', async () => {
    await page.goto(u(`/users/Profile/${ids.me}`));
    await settle(page);
    await openTab('#ExpenseReport');
    const form = page.locator('#ExpenseReport form[action$="/expense/create/report"]');
    await form.locator('[name="label"]').fill(REPORT);
    await form.locator('[name="date"]').fill(inDays(0));
    await shot('note-creation', { title: 'Le salarié ouvre une note de frais', highlight: [form.locator('[name="label"]'), form.locator('[name="date"]'), submitOf(form)] });
    await submit(form);
    ids.report = tinker("echo App\\Models\\UserExpenseReport::where('label', '" + REPORT.replace(/'/g, "\\'") + "')->latest('id')->value('id');");
});

await step('5. Justificatif et dépense', async () => {
    // Un ticket de caisse fictif, dessiné par le navigateur.
    const dir = join(tmpdir(), 'wem-training'); mkdirSync(dir, { recursive: true });
    const receipt = join(dir, 'ticket-restaurant.png');
    const tab = await page.context().newPage();
    await tab.setViewportSize({ width: 360, height: 480 });
    await tab.setContent(`<body style="font:15px monospace;padding:24px;background:#fff">
        <h3 style="text-align:center">BRASSERIE DU PARC</h3><p style="text-align:center">12 quai Rambaud, Lyon</p><hr>
        <p>Plat du jour .......... 16,90</p><p>Café ................... 2,60</p><p>Eau .................... 3,00</p><hr>
        <p><b>TOTAL TTC ........... 22,50 €</b></p><p>dont TVA 10 % .......... 2,05</p><p>${inDays(0)} 12:48 — CB</p></body>`);
    await tab.screenshot({ path: receipt });
    await tab.close();

    await page.goto(u(`/human-resources/expense/show/${ids.report}`));
    await settle(page);
    const card = page.locator('.card', { has: page.locator('.card-title', { hasText: /Justificatif/ }) }).first();
    await card.locator('input[type=file]').setInputFiles(receipt);
    await card.getByText('ticket-restaurant', { exact: false }).first().waitFor({ timeout: 20000 });
    await settle(page);
    await card.evaluate(el => { el.scrollIntoView({ block: 'start', behavior: 'instant' }); window.scrollBy(0, -70); });
    await shot('justificatif', { title: 'Déposer le justificatif', highlight: card.locator('.card-title') });

    // La liste des justificatifs du formulaire se recharge au chargement de la page.
    await page.reload();
    await settle(page);
    const form = page.locator('form[action*="/expense/create/expense/"]');
    await form.locator('#receipt_file_id option').nth(1).waitFor({ state: 'attached' });
    await form.locator('#receipt_file_id').selectOption({ index: 1 });
    await form.locator('[name="category_id"]').selectOption({ label: 'Repas midi' });
    await form.locator('[name="expense_date"]').fill(inDays(0));
    await form.locator('[name="location"]').fill('Lyon');
    await form.locator('[name="description"]').fill('[FORMATION] Déjeuner, salon de la tôlerie');
    await form.locator('[name="amount"]').fill('22.50');
    await form.locator('[name="tax"]').fill('10');
    await shot('depense', { title: 'Saisir la dépense à partir du justificatif', highlight: [form.locator('#receipt_file_id'), form.locator('#receipt-read'), form.locator('[name="category_id"]')] });
    await form.locator('[name="amount"]').scrollIntoViewIfNeeded();
    // Le champ « Taxe » est un taux (%), pas un montant.
    await shot('depense-montant', { title: 'Montant TTC et taux de TVA', highlight: [form.locator('[name="amount"]'), form.locator('[name="tax"]'), submitOf(form)] });
    await submit(form);
    await shot('note', { title: 'Note de frais : dépenses et justificatifs' });
});

await step('6. Soumettre', async () => {
    await page.goto(u(`/users/Profile/${ids.me}`));
    await settle(page);
    await openTab('#ExpenseReport');
    const row = page.locator('#ExpenseReport tr', { has: page.locator('td', { hasText: REPORT }) }).last();
    await row.locator('button[data-bs-target]').first().click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('[name="status"]').selectOption('3');
    await shot('note-soumission', { title: 'Soumettre la note aux RH', highlight: [modal.locator('[name="status"]'), modal.locator('button[type=submit]')] });
    await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
    await settle(page);
});

await step('7. Validation RH', async () => {
    await page.goto(u('/human-resources'));
    await settle(page);
    await shot('validation-liste', { title: 'Notes de frais à valider' });
    const row = page.locator('#ExpenseValidate tr', { has: page.locator('td', { hasText: REPORT }) }).last();
    await row.locator('button[data-bs-target]').first().click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('[name="status"]').selectOption('5');
    await shot('validation', { title: 'Approuver ou retourner la note', highlight: [modal.locator('[name="status"]'), modal.locator('button[type=submit]')] });
    await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
    await settle(page);
    await openTab('#ExpenseCat');
    await shot('categories', { title: 'Catégories de dépense' });
});

save({ scenario: 'rh', ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
