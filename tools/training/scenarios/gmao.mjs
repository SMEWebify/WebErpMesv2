// Guide « GMAO » : équipement → plan de maintenance → ordre de travail généré → exécution
// et clôture, plus un ordre correctif. Données préfixées [FORMATION].
// Ne clique jamais « Supprimer » (sans confirmation, définitif pour plans et ordres) et ne
// clique qu'une fois « Générer un ordre de travail » (chaque clic crée un ordre).
// Usage : node tools/training/scenarios/gmao.mjs   (HEADED=1 pour voir le navigateur)
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const u = p => `${BASE}/fr${p}`;
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');
const ASSET = `[FORMATION] Presse plieuse PP-${stamp}`;
const inDays = n => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);
const now = (h) => `${inDays(0)}T${h}`;

const { browser, page, shot: rawShot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/gmao' });
// Un champ date garde le focus et sa partie sélectionnée apparaît surlignée sur la capture.
const shot = async (...a) => { await page.evaluate(() => document.activeElement?.blur()); return rawShot(...a); };
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'gmao', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

// Le bouton du formulaire de saisie (et non celui de déconnexion ou de suppression).
const submit = () => page.locator('form', { has: page.locator('#title, #name') }).locator('button[type=submit]').last();

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('1. Tableau de bord', async () => {
    await page.goto(u('/gmao/dashboard'));
    await page.locator('#gmao-dashboard-app *').first().waitFor();
    await settle(page);
    await shot('tableau-de-bord', { title: 'GMAO : tableau de bord' });
});

await step('2. Équipement', async () => {
    await page.goto(u('/assets/create'));
    await settle(page);
    await page.fill('#name', ASSET);
    await page.fill('#category', 'Pliage');
    const res = page.locator('#methods_ressource_id');
    const opts = await res.locator('option').allTextContents();
    const pick = opts.findIndex(t => /pli|bend/i.test(t));
    await res.selectOption({ index: pick > 0 ? pick : 1 });
    await page.fill('#acquisition_value', '85000');
    await page.fill('#acquisition_date', '2024-01-15');
    await page.fill('#depreciation_duration', '60');
    await shot('equipement-creation', { title: 'Nouvel équipement, rattaché à sa ressource', highlight: [page.locator('#name'), res, page.locator('#acquisition_value'), submit()] });
    await Promise.all([page.waitForURL(/\/assets\/\d+$/), submit().click()]);
    await settle(page);
    ids.asset = page.url().match(/assets\/(\d+)/)?.[1];
    await shot('equipement-fiche', { title: 'Fiche équipement : ordres de travail et plans', highlight: [page.getByRole('link', { name: /Nouvel ordre de travail/ }).first(), page.getByRole('link', { name: /Nouveau plan de maintenance/ }).first()] });
});

await step('3. Plan de maintenance', async () => {
    await page.goto(u(`/gmao/maintenance-plans/create?asset_id=${ids.asset}`));
    await settle(page);
    if (!(await page.locator('#asset_id').inputValue())) await page.locator('#asset_id').selectOption(ids.asset);
    await page.fill('#title', '[FORMATION] Vidange hydraulique et graissage');
    await page.locator('#trigger_type').selectOption('time');
    await page.fill('#trigger_value', 'mensuel');
    await page.fill('#fixed_date', inDays(7));
    await page.fill('#estimated_duration_minutes', '90');
    await page.fill('#required_skill', 'Mécanicien hydraulique');
    await page.fill('#actions', 'Vidange, contrôle du niveau, graissage des axes');
    await page.fill('#required_parts', 'Huile HLP46 20 L, filtre retour');
    await shot('plan-creation', { title: 'Nouveau plan de maintenance préventive', highlight: [page.locator('#trigger_type'), page.locator('#trigger_value'), page.locator('#fixed_date'), submit()] });
    await Promise.all([page.waitForURL(/maintenance-plans\/\d+$/), submit().click()]);
    await settle(page);
    ids.plan = page.url().match(/maintenance-plans\/(\d+)/)?.[1];
    const generate = page.locator('form[action$="/generate-work-order"] button');
    await shot('plan-fiche', { title: 'Fiche du plan : « Générer un ordre de travail »', highlight: generate });
});

await step('4. Ordre de travail généré', async () => {
    const generate = page.locator('form[action$="/generate-work-order"] button');
    await Promise.all([page.waitForURL(/work-orders\/\d+/), generate.click()]);
    await settle(page);
    ids.workOrder = page.url().match(/work-orders\/(\d+)/)?.[1];
    await shot('ot-genere', { title: 'Ordre de travail préventif créé depuis le plan' });
});

await step('5. Exécuter et clôturer', async () => {
    await page.goto(u(`/gmao/work-orders/${ids.workOrder}/edit`));
    await settle(page);
    await page.locator('#assigned_to').selectOption({ index: 1 });
    await page.locator('#status').selectOption('completed');
    await page.fill('#started_at', now('08:00'));
    await page.fill('#finished_at', now('09:40'));
    await page.fill('#actual_duration_minutes', '100');
    await page.locator('#machine_stopped').selectOption('0');
    await page.fill('#actions_performed', 'Vidange effectuée, niveau contrôlé, axes graissés. RAS.');
    await page.fill('#parts_consumed', 'Huile HLP46 20 L, filtre retour');
    await shot('ot-execution', { title: 'Intervention : statut, technicien, horaires', highlight: [page.locator('#status'), page.locator('#assigned_to'), page.locator('#started_at')] });
    await shot('ot-compte-rendu', { title: 'Compte rendu d’intervention', highlight: [page.locator('#actual_duration_minutes'), page.locator('#actions_performed'), page.locator('#parts_consumed')] });
    await Promise.all([page.waitForURL(/work-orders\/\d+$/), submit().click()]);
    await settle(page);

    await page.goto(u(`/gmao/work-orders/${ids.workOrder}/edit`));
    await settle(page);
    await page.locator('#status').selectOption('closed');
    await page.fill('#completed_at', inDays(0));
    await shot('ot-cloture', { title: 'Clôture : statut « Closed » et date de clôture', highlight: [page.locator('#completed_at'), submit()] });
    await Promise.all([page.waitForURL(/work-orders\/\d+$/), submit().click()]);
    await settle(page);
    await page.goto(u(`/gmao/work-orders/${ids.workOrder}`));
    await settle(page);
    await shot('ot-fiche', { title: 'Ordre de travail clôturé' });
});

await step('6. Ordre correctif', async () => {
    await page.goto(u(`/gmao/work-orders/create?asset_id=${ids.asset}`));
    await settle(page);
    if (!(await page.locator('#asset_id').inputValue())) await page.locator('#asset_id').selectOption(ids.asset);
    await page.fill('#title', '[FORMATION] Fuite vérin gauche');
    await page.locator('#priority').selectOption('high');
    await page.locator('#work_type').selectOption('corrective');
    await page.locator('#status').selectOption('planned');
    await page.fill('#failure_type', 'Fuite hydraulique');
    await page.fill('#severity', 'Majeure');
    await page.locator('#machine_stopped').selectOption('1');
    await page.fill('#failure_started_at', now('14:30'));
    await shot('ot-correctif', { title: 'Déclarer une panne : ordre de travail curatif', highlight: [page.locator('#work_type'), page.locator('#priority')] });
    await shot('ot-panne', { title: 'Description de la panne et arrêt machine', highlight: [page.locator('#failure_type'), page.locator('#severity'), page.locator('#machine_stopped'), page.locator('#failure_started_at')] });
    await Promise.all([page.waitForURL(/work-orders\/\d+$/), submit().click()]);
    await settle(page);
});

await step('7. Liste', async () => {
    await page.goto(u('/gmao/work-orders'));
    await settle(page);
    await shot('ot-liste', { title: 'Liste des ordres de travail' });
    await page.goto(u(`/assets/${ids.asset}`));
    await settle(page);
    await shot('equipement-historique', { title: 'Fiche équipement : historique des interventions' });
});

save({ scenario: 'gmao', ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
