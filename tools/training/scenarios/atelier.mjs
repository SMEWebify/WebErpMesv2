// Guide « Atelier et production » : mode atelier, liste et écran de suivi d'une opération
// (Play, quantités, rebuts, Pause, Fin), visa de libération d'une phase, sortie de matière
// par lot (coulée), puis écrans du chef d'atelier en lecture seule (liste des tâches,
// Kanban, planning de charge, rapports, Andon) et fiche suiveuse.
// Données : atelier-setup.php crée à chaque passage une ligne neuve sur la commande
// « [FORMATION] Atelier » (gamme Découpe laser → poste [FORMATION] à visa → Emballage,
// matière [FORMATION] en stock sous un lot avec coulée). Le BPF de la ligne est donné en
// cours de parcours quand l'option PRODUCTION_REQUIRE_RELEASE est active.
// Ne clique jamais « Simuler » / « Calculer » du planning (réécrit les dates de toute la base)
// ni ne déplace de carte Kanban.
// Usage : node tools/training/scenarios/atelier.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const u = p => `${BASE}/fr${p}`;
const tinker = code => execFileSync(PHP, ['artisan', 'tinker', '--execute', code]).toString().trim().split('\n').pop();

const data = JSON.parse(tinker("require base_path('tools/training/scenarios/atelier-setup.php');"));
const releaseEnabled = tinker("echo config('production.require_release') ? 1 : 0;") === '1';
console.log('Données', data, 'BPF exigé :', releaseEnabled);

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/atelier' });

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'atelier', failedAt: title, error: String(e), data });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

async function openTask(id) {
    await page.goto(u(`/workshop/Task/Statu/Id/${id}`));
    await page.locator('#task-statu-app').waitFor();
    await settle(page);
}

const cardOf = title => page.locator('.ts-card', { has: page.locator('.ts-card__title', { hasText: title }) }).first();

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('1. Mode atelier', async () => {
    await page.goto(u('/workshop'));
    await settle(page);
    await shot('accueil-atelier', { title: 'Accueil atelier : quatre tuiles', highlight: [page.locator('.ws-tile--tasks'), page.locator('.ws-tile--production')] });
});

await step('2. Liste atelier', async () => {
    await page.goto(u('/workshop/Task/Lines'));
    await settle(page);
    await shot('liste-atelier', { title: 'Liste atelier : opérations à faire, filtres et échéances' });
});

await step('3. Recherche et indicateurs', async () => {
    await page.goto(u('/workshop/Task/Statu'));
    await settle(page);
    const search = page.locator('form.ts-search input').first();
    await shot('recherche-tache', { title: 'Déclaration de production : recherche par n° d’opération', highlight: search });
});

if (releaseEnabled) {
    await step('4a. Démarrage refusé sans BPF', async () => {
        await openTask(data.cut);
        const banner = page.locator('.ts-alert--warn', { has: page.locator('.fa-lock') }).first();
        await shot('suivi-sans-bpf', {
            title: 'Sans bon pour fabrication : Play grisé, bandeau d’explication',
            highlight: [banner, page.locator('button.ts-run__btn--start')],
        });
        // BPF donné par le bureau d'études (voir le guide « Bon pour fabrication »).
        const email = JSON.stringify(env.TRAINING_EMAIL);
        tinker(`app(App\\Services\\Production\\ProductionRelease::class)->release(App\\Models\\Workflow\\OrderLines::find(${data.line}), App\\Models\\User::where('email', ${email})->firstOrFail()); echo 'ok';`);
    });
}

await step('4. Écran de suivi', async () => {
    await openTask(data.cut);
    const play = page.locator('button.ts-run__btn--start');
    await shot('suivi-avant', { title: 'Écran de suivi d’une opération', highlight: play });

    await play.click();
    await settle(page);
    const plus10 = page.locator('.ts-quick__btn--ok', { hasText: '+10' }).first();
    await plus10.click();
    await settle(page);
    const minus1 = page.locator('.ts-quick__btn--danger').first();
    await minus1.click();
    await settle(page);
    await shot('suivi-en-cours', {
        title: 'En cours : pièces bonnes et rebuts déclarés',
        highlight: [page.locator('button.ts-run__btn--pause'), plus10, minus1],
    });

    await page.locator('button.ts-run__btn--pause').click();
    await settle(page);
    await shot('suivi-planification', { title: 'Carte de planification : date de fin, ressource', highlight: cardOf('Scheduling') });

    await page.locator('.ts-tab', { hasText: 'Informations' }).first().click();
    await settle(page);
    await shot('suivi-informations', { title: 'Onglet « Informations » : temps, coût, TRS, avancement' });

    await page.locator('button.ts-run__btn--start').click();
    await settle(page);
    const qty = page.getByRole('button', { name: 'Set' }).first().locator('xpath=preceding::input[1]');
    await qty.fill('9');
    await page.getByRole('button', { name: 'Set' }).first().click();
    await settle(page);
    await page.locator('button.ts-run__btn--stop').click();
    await settle(page);
    await page.locator('.ts-tab', { hasText: 'Journal' }).first().click();
    await settle(page);
    await shot('suivi-termine', { title: 'Opération terminée : journal d’activité' });

    // Formulaire d'alerte ouvert pour la capture, jamais envoyé.
    const andon = page.getByRole('button', { name: /Alerte Andon/ }).first();
    await andon.click();
    await page.waitForTimeout(400);
    await shot('alerte-andon', { title: 'Signaler un problème : alerte Andon ou non-conformité', highlight: andon });
});

await step('5. Sortie de matière par lot', async () => {
    await openTask(data.material);
    const card = cardOf('Retirer du stock');
    await card.locator('input[type=number]').fill('10');
    await card.getByRole('button', { name: 'Set' }).click();
    await settle(page);
    await card.getByText('Lots consommés').waitFor();
    await shot('lots-consommes', { title: 'Matière sortie du stock : lot et coulée consommés', highlight: card });
});

await step('6. Visa de libération', async () => {
    await page.goto(u('/methods/service'));
    await settle(page);
    await page.locator(`button[data-bs-target="#MethodsService${data.visa_service}"]`).click();
    const modal = page.locator(`#MethodsService${data.visa_service}`);
    await modal.locator('.modal-content').waitFor();
    await page.waitForTimeout(400);
    await shot('poste-visa', {
        title: 'Poste de charge : « Visa de libération requis »',
        highlight: modal.locator(`label[for="requires_release_${data.visa_service}"]`).locator('xpath=..'),
    });
    await page.keyboard.press('Escape');

    // Contrôle final (poste à visa) : démarré puis terminé.
    await openTask(data.check);
    await page.locator('button.ts-run__btn--start').click();
    await settle(page);
    await page.locator('button.ts-run__btn--stop').click();
    await settle(page);
    const visaCard = cardOf('Visa de libération');
    await shot('visa-attente', {
        title: 'Opération terminée sur un poste à visa : en attente du visa',
        highlight: [visaCard.locator('textarea'), visaCard.getByRole('button', { name: /Viser et libérer/ })],
    });

    // Opération suivante : avertissement. Play jamais cliqué ici (la confirmation serait acceptée).
    await openTask(data.pack);
    const warn = page.locator('.ts-alert--warn[role=status]').first();
    await shot('visa-avertissement', { title: 'Opération suivante : avertissement, sans blocage', highlight: warn });

    await page.goto(u(`/production/kanban?methods_services_id=${data.visa_service}`));
    await settle(page);
    const badge = page.locator('.badge', { hasText: 'À viser' }).first();
    await shot('kanban-visa', { title: 'Kanban : pastille « À viser »', highlight: badge });

    await openTask(data.check);
    const card = cardOf('Visa de libération');
    await card.locator('textarea').fill('[FORMATION] Aspect et cotes conformes');
    await card.getByRole('button', { name: /Viser et libérer/ }).click();
    await settle(page);
    await card.getByText('Libérée le').waitFor();
    await shot('visa-libere', { title: 'Phase libérée : date, auteur, commentaire', highlight: card });
});

await step('7. Liste des tâches', async () => {
    await page.goto(u('/production/Task'));
    await settle(page);
    // Cadenas : tâche de production dont la ligne n'a pas de BPF (option active seulement).
    await shot('liste-taches', {
        title: 'Planification › Liste des tâches',
        highlight: releaseEnabled ? page.locator('td .fa-lock').first() : [],
    });
});

await step('8. Kanban', async () => {
    await page.goto(u(`/production/kanban?methods_services_id=${data.cut_service}`));
    await settle(page);
    await shot('kanban', { title: 'Flux de travail (Kanban) filtré sur un service' });
});

await step('9. Planning de charge', async () => {
    await page.goto(u('/production/load-planning'));
    await settle(page);
    await shot('planning-charge', {
        title: 'Planning de charge',
        highlight: releaseEnabled ? page.locator('.lp-tile__unreleased').first() : [],
    });
});

await step('10. Rapports atelier', async () => {
    await page.goto(u('/workshop/Reports'));
    await settle(page);
    await page.getByRole('button', { name: /30 derniers jours/ }).first().click().catch(() => {});
    await settle(page);
    await shot('rapports', { title: 'Rapports atelier : réalisé, rebuts, charge machine, andon' });
});

await step('11. Andon', async () => {
    await page.goto(u('/workshop/andon'));
    await settle(page);
    await shot('andon', { title: 'Écran Andon : alertes à traiter' });
});

await step('12. Fiche suiveuse', async () => {
    await page.goto(u(`/orders/${data.order}`));
    await settle(page);
    await page.locator('a[href="#Order"]').first().click();
    await settle(page);
    const print = page.locator('a[href*="/order/manufacturing/"]').first();
    await shot('fiche-suiveuse', { title: 'Fiche commande : impression de la fiche suiveuse', highlight: print });
});

save({ scenario: 'atelier', data, releaseEnabled, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', data);
await browser.close();
