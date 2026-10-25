// Guide « Atelier et production » : mode atelier, liste et écran de suivi d'une opération
// (Play, quantités, rebuts, Pause, Fin), puis écrans du chef d'atelier en lecture seule
// (liste des tâches, Kanban, planning de charge, rapports, Andon).
// Pilote une opération « Open » d'une commande [FORMATION] (laissée par vente-complete) ;
// ne clique jamais « Simuler » / « Calculer » du planning (réécrit les dates de toute la base)
// ni ne déplace de carte Kanban.
// Usage : node tools/training/scenarios/atelier.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const u = p => `${BASE}/fr${p}`;

// Opération interne « Open » la plus récente d'une commande [FORMATION].
const task = JSON.parse(execFileSync(PHP, ['artisan', 'tinker', '--execute',
    "echo json_encode(App\\Models\\Planning\\Task::where('status_id', App\\Models\\Planning\\Status::where('title','Open')->value('id'))->whereNotNull('order_lines_id')->whereHas('service', fn($q) => $q->where('type',1))->whereHas('OrderLines.order.companie', fn($q) => $q->where('label','like','[FORMATION]%'))->latest('id')->first(['id','label','methods_services_id']));",
]).toString().trim().split('\n').pop());
if (!task) {
    console.error('Aucune opération « Open » sur une commande [FORMATION] : relancer vente-complete.mjs jusqu’à la commande.');
    process.exit(1);
}

const { browser, page, shot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/atelier' });

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'atelier', failedAt: title, error: String(e), task });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

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

await step('4. Écran de suivi', async () => {
    await page.goto(u(`/workshop/Task/Statu/Id/${task.id}`));
    await page.locator('#task-statu-app').waitFor();
    await settle(page);
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
    const sched = page.locator('.ts-card', { has: page.locator('.ts-card__title', { hasText: 'Scheduling' }) }).first();
    await shot('suivi-planification', { title: 'Carte de planification : date de fin, ressource', highlight: sched });

    await page.locator('.ts-tab', { hasText: 'Informations' }).first().click();
    await settle(page);
    await shot('suivi-informations', { title: 'Onglet « Informations » : temps, coût, TRS, avancement' });

    await page.locator('button.ts-run__btn--start').click();
    await settle(page);
    const qty = page.getByRole('button', { name: 'Set' }).first().locator('xpath=preceding::input[1]');
    await qty.fill('9');
    await page.getByRole('button', { name: 'Set' }).first().click();
    await settle(page);
    const stop = page.locator('button.ts-run__btn--stop');
    await stop.click();
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

await step('5. Liste des tâches', async () => {
    await page.goto(u('/production/Task'));
    await settle(page);
    await shot('liste-taches', { title: 'Planification › Liste des tâches' });
});

await step('6. Kanban', async () => {
    await page.goto(u(`/production/kanban?methods_services_id=${task.methods_services_id}`));
    await settle(page);
    await shot('kanban', { title: 'Flux de travail (Kanban) filtré sur un service' });
});

await step('7. Planning de charge', async () => {
    await page.goto(u('/production/load-planning'));
    await settle(page);
    await shot('planning-charge', { title: 'Planning de charge' });
});

await step('8. Rapports atelier', async () => {
    await page.goto(u('/workshop/Reports'));
    await settle(page);
    await page.getByRole('button', { name: /30 derniers jours/ }).first().click().catch(() => {});
    await settle(page);
    await shot('rapports', { title: 'Rapports atelier : réalisé, rebuts, charge machine, andon' });
});

await step('9. Andon', async () => {
    await page.goto(u('/workshop/andon'));
    await settle(page);
    await shot('andon', { title: 'Écran Andon : alertes à traiter' });
});

save({ scenario: 'atelier', task, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', task);
await browser.close();
