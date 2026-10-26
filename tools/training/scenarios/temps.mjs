// Guide « Temps management » : nature de congé, régime horaire (2×8), jour férié, droits à
// congés, demande d'absence du salarié, validation RH, soldes, présence et export paie.
// Données préfixées [FORMATION] ; aucune suppression n'existe dans ces écrans.
// Ne coche jamais « régime par défaut » (changerait la capacité de toutes les ressources) et
// ne modifie pas l'unité des temps ni les événements machine existants.
// Usage : node tools/training/scenarios/temps.mjs   (HEADED=1 pour voir le navigateur)
import { execFileSync } from 'node:child_process';
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const PHP = process.env.TRAINING_PHP || 'C:/laragon/bin/php/php-8.4.26-nts-Win32-vs17-x64/php.exe';
const u = p => `${BASE}/fr${p}`;
const artisan = (...args) => execFileSync(PHP, ['artisan', ...args]).toString().trim();
const tinker = code => artisan('tinker', '--execute', code).split('\n').pop();

const { browser, page, shot: rawShot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/temps' });
const shot = async (...a) => { await page.evaluate(() => document.activeElement?.blur()); return rawShot(...a); };
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'temps', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

const formOf = suffix => page.locator(`form[action$="${suffix}"]`).first();
const submitOf = form => form.locator('button[type=submit]').last();
async function submit(form) {
    await Promise.all([page.waitForNavigation(), submitOf(form).click()]);
    await settle(page);
}
const openTab = async hash => { await page.locator(`a.nav-link[href="${hash}"]`).first().click(); await page.waitForTimeout(300); };

const LEAVE = { code: 'FCP', label: '[FORMATION] Congés payés' };
const SHIFT = { code: 'FORM-2X8', label: '[FORMATION] 2×8 atelier' };
const HOLIDAY = { label: '[FORMATION] Armistice', date: '2026-11-11' };
const ABSENCE = { start: '2026-11-09', end: '2026-11-13', comment: '[FORMATION] Congés semaine 46' };

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
    ids.user = tinker("echo App\\Models\\User::where('email', '" + env.TRAINING_EMAIL + "')->value('id');");
});

await step('1. Nature de congé', async () => {
    await page.goto(u('/human-resources'));
    await settle(page);
    await openTab('#LeaveTypes');
    // Le formulaire est capturé à chaque passage ; il n'est envoyé que si la nature n'existe pas.
    const exists = await page.locator('#LeaveTypes tr', { hasText: LEAVE.label }).count();
    const form = formOf('/human-resources/leave/types');
    await form.locator('#code').fill(LEAVE.code);
    await form.locator('#label').fill(LEAVE.label);
    await form.locator('#default_annual_quota').fill('25');
    await shot('nature-conge', { title: 'Nouvelle nature de congé', highlight: [form.locator('#code'), form.locator('#default_annual_quota'), form.locator('#counts_new'), submitOf(form)] });
    if (!exists) {
        await submit(form);
        await openTab('#LeaveTypes');
    } else {
        await page.reload();
        await settle(page);
        await openTab('#LeaveTypes');
    }
    await shot('natures-liste', { title: 'Natures de congé' });
});

await step('2. Régime horaire', async () => {
    await page.goto(u('/times/shift'));
    await settle(page);
    if (!(await page.locator('.card-title', { hasText: SHIFT.label }).count())) {
        const form = formOf('/times/shift/create');
        await form.locator('#code').fill(SHIFT.code);
        await form.locator('#label').fill(SHIFT.label);
        await form.locator('#comment').fill('[FORMATION] Deux équipes du lundi au vendredi');
        await shot('regime-creation', { title: 'Nouveau régime horaire', highlight: [form.locator('#code'), form.locator('#label'), form.locator('#is_default'), submitOf(form)] });
        await submit(form);
    }
    const card = page.locator('.card', { has: page.locator('.card-title', { hasText: SHIFT.label }) }).last();
    for (const [start, end, label] of [['06:00', '14:00', 'Matin'], ['14:00', '22:00', 'Après-midi']]) {
        if (await card.locator('.badge', { hasText: `${start} - ${end}` }).count()) continue;
        const slot = card.locator('form[action$="/slot"]');
        await slot.locator('[name="start_time"]').fill(start);
        await slot.locator('[name="end_time"]').fill(end);
        await slot.locator('[name="label"]').fill(label);
        if (label === 'Matin') await shot('regime-plage', { title: 'Ajouter une plage, du lundi au vendredi', highlight: [slot.locator('[name="start_time"]'), slot.locator('[name="end_time"]'), slot.locator('input[type=checkbox]'), slot.locator('button[type=submit]')] });
        await Promise.all([page.waitForNavigation(), slot.locator('button[type=submit]').click()]);
        await settle(page);
    }
    await page.locator('.card-title', { hasText: SHIFT.label }).first().scrollIntoViewIfNeeded();
    await shot('regime', { title: 'Régime 2×8 : deux plages par jour' });
});

await step('3. Jour férié', async () => {
    await page.goto(u('/times'));
    await settle(page);
    await openTab('#BanckHoliday');
    if (!(await page.locator('#BanckHoliday td', { hasText: HOLIDAY.label }).count())) {
        const form = formOf('/times/banckholiday/create');
        await form.locator('[name="label"]').fill(HOLIDAY.label);
        await form.locator('[name="date"]').fill(HOLIDAY.date);
        await shot('ferie-creation', { title: 'Nouveau jour férié', highlight: [form.locator('[name="label"]'), form.locator('[name="fixed"]'), form.locator('[name="date"]'), submitOf(form)] });
        await submit(form);
        await openTab('#BanckHoliday');
    }
    await shot('feries', { title: 'Jours fériés' });
    // La liste des fériés du décompte des congés est en cache une heure.
    artisan('cache:forget', 'hr_bank_holidays');
});

await step('4. Droits à congés', async () => {
    await page.goto(u(`/human-resources/user/${ids.user}`));
    await settle(page);
    await openTab('#leave');
    const row = page.locator('#leave tr', { hasText: LEAVE.label }).first();
    await row.locator('button[data-bs-target]').first().click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('[name="entitled_days"]').fill('25');
    await modal.locator('[name="carried_over_days"]').fill('2');
    await modal.locator('[name="comment"]').fill('[FORMATION] Droits 2026 / 2027');
    await shot('droits', { title: 'Droits à congés du salarié', highlight: [modal.locator('[name="entitled_days"]'), modal.locator('[name="carried_over_days"]'), modal.locator('[name="adjustment_days"]')] });
    await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
    await settle(page);
});

await step('5. Demande d’absence', async () => {
    await page.goto(u(`/users/Profile/${ids.user}`));
    await settle(page);
    await openTab('#LeaveRequest');
    const form = page.locator('#LeaveRequest form[action$="/times/absence/create"]');
    const type = await form.locator('[name="leave_type_id"] option').evaluateAll((os, l) => os.find(o => o.text.includes(l))?.value, LEAVE.label);
    await form.locator('[name="leave_type_id"]').selectOption(type);
    await form.locator('[name="absence_type"]').selectOption('1');
    await form.locator('[name="absence_type_day"]').selectOption('3');
    await form.locator('[name="start_date"]').fill(ABSENCE.start);
    await form.locator('[name="end_date"]').fill(ABSENCE.end);
    await form.locator('[name="comment"]').fill(ABSENCE.comment);
    await shot('absence-demande', { title: 'Le salarié demande une absence', highlight: [form.locator('[name="leave_type_id"]'), form.locator('[name="absence_type_day"]'), form.locator('[name="start_date"]'), form.locator('[name="end_date"]')] });
    await Promise.all([page.waitForNavigation(), submitOf(form).click()]);
    await settle(page);
    if (!/Profile/.test(page.url())) { await page.goto(u(`/users/Profile/${ids.user}`)); await settle(page); }
    await openTab('#LeaveRequest');
    await shot('absence-en-attente', { title: 'Demande en attente et solde du salarié' });
});

await step('6. Validation RH', async () => {
    await page.goto(u('/times'));
    await settle(page);
    await openTab('#Absence');
    const row = page.locator('#Absence tr', { has: page.locator('td', { hasText: LEAVE.label }) }).last();
    // Le tableau déborde sous la carte de droite : le bouton n'est pas cliquable à la souris.
    await row.locator('button[data-bs-target]').first().evaluate(b => b.click());
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('[name="statu"]').selectOption('2');
    await shot('absence-validation', { title: 'Valider la demande', highlight: [modal.locator('[name="statu"]'), modal.locator('button[type=submit]')] });
    await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
    await settle(page);
    // Sa propre demande renvoie vers le profil : on revient sur l'écran des temps.
    await page.goto(u('/times'));
    await settle(page);
    await shot('absences', { title: 'Temps et absences : demandes' });
});

await step('7. Soldes', async () => {
    await page.goto(u('/human-resources/leave/balances'));
    await settle(page);
    await shot('soldes', { title: 'Soldes de congés de l’atelier' });
});

await step('8. Présence et paie', async () => {
    await page.goto(u('/human-resources/attendance'));
    await settle(page);
    await shot('presence', { title: 'Rapport de présence' });
    await page.goto(u('/human-resources/payroll/export?month=2026-11'));
    await settle(page);
    await shot('export-paie', { title: 'Export paie du mois' });
});

await step('9. Référentiels (lecture)', async () => {
    await page.goto(u('/times'));
    await settle(page);
    await openTab('#MachineEvent');
    await shot('evenements-machine', { title: 'Événements machine' });
    await page.goto(u('/admin/time-unit-settings'));
    await settle(page);
    await shot('unite-temps', { title: 'Unité des temps' });
});

save({ scenario: 'temps', ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
