// Guide « Mise en route d'une instance » : tour des écrans de réglage, en lecture seule.
// N'enregistre rien : aucun « Soumettre », « Mettre à jour », « Ajout », aucune corbeille.
// Les données réelles de la société (adresse, e-mail, téléphone, IBAN) sont masquées par
// TRAINING_PRIVATE (.env).
// Usage : node tools/training/scenarios/mise-en-route.mjs   (HEADED=1 pour voir le navigateur)
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const u = p => `${BASE}/fr${p}`;

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/mise-en-route' });

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'mise-en-route', failedAt: title, error: String(e) });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

async function open(path, hash) {
    await page.goto(u(path));
    await settle(page);
    if (hash) {
        await page.locator(`a[href="${hash}"]`).first().click();
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

await step('1. Réglage société', async () => {
    await open('/admin/factory', '#Settings');
    await shot('societe-identite', {
        title: 'Réglage société : identité',
        highlight: [page.locator('#name'), page.locator('#address'), page.locator('#mail')],
    });
    await shot('societe-administratif', {
        title: 'Réglage société : SIREN, n° de TVA, IBAN',
        highlight: [page.locator('#siren'), page.locator('#vat_num'), page.locator('#iban')],
    });
    await shot('societe-defauts', {
        title: 'Réglage société : valeurs par défaut',
        highlight: [page.locator('#accounting_vats_id'), page.locator('#curency'), page.locator('#fiscal_year_start_month')],
    });
    await open('/admin/factory', '#DocumentCodeTemplates');
    await shot('numerotation', { title: 'Numérotation des documents' });
});

await step('2. Comptabilité', async () => {
    await open('/accounting/vats');
    const edit = page.locator('button', { hasText: /Modifier/ }).first();
    await shot('tva', { title: 'TVA : liste et création', highlight: edit });
    await edit.click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await page.waitForTimeout(400);
    const def = modal.locator('select[name="default"]').first();
    await shot('tva-defaut', { title: 'Fenêtre « Modifier » : réglage « Par défaut »', highlight: def });
    await page.keyboard.press('Escape');
    await page.waitForTimeout(400);
    await open('/accounting/payment-conditions');
    await shot('conditions-paiement', { title: 'Conditions de paiement' });
    await open('/accounting/payment-methods');
    await shot('modes-paiement', { title: 'Modes de paiement' });
});

await step('3. Flux des tâches', async () => {
    await open('/admin/kanban-settings');
    await shot('flux-taches', { title: 'Flux des tâches : statuts des opérations' });
});

await step('4. Objectif de chiffre', async () => {
    await open('/admin/estimated-budgets-settings');
    await shot('objectif-ca', { title: 'Objectif de chiffre d’affaires' });
});

await step('5. Rôles et permissions', async () => {
    await open('/admin/roles-permissions', '#Role');
    await shot('roles', { title: 'Rôles et permissions' });
});

await step('6. Utilisateurs', async () => {
    await open('/human-resources/users');
    await shot('utilisateurs', { title: 'Liste des utilisateurs' });
    await page.locator('tr[data-row-href]').last().click();
    await page.waitForURL(/\/human-resources\/user\/\d+/);
    await settle(page);
    await page.locator('a[href="#activity"]').first().click();
    await settle(page);
    const role = page.locator('select#role');
    await shot('utilisateur-role', { title: 'Fiche salarié, onglet « RH information » : le rôle', highlight: role });
});

await step('7. E-mail', async () => {
    await open('/admin/integrations/mail');
    // Serveur, identifiant et expéditeur sont ceux de l'instance : masqués.
    await shot('email', { title: 'Configuration e-mail (SMTP)', mask: [page.locator('.app-main form input:not([type=hidden]):not([type=checkbox]), .app-main form select')] });
});

await step('8. Imports', async () => {
    await open('/admin/imports-exports', '#CustomerImport');
    await shot('import-clients', { title: 'Imports/Exports : import des clients' });
});

await step('9. Apparence', async () => {
    await page.goto(u('/users/Profile/1#Appearance'));
    await settle(page);
    await page.locator('a[href="#Appearance"]').first().click().catch(() => {});
    await settle(page);
    await shot('apparence', { title: 'Profil › Apparence : habillage et couleur' });
});

save({ scenario: 'mise-en-route', finishedAt: new Date().toISOString() });
console.log('✔ Captures terminées');
await browser.close();
