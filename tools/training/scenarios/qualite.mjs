// Guide « Qualité » : réglage (défaillances, causes, corrections), appareils de mesure,
// non-conformité → action corrective → dérogation → clôture, puis plans de contrôle, rapport
// premier article, audits et retours en lecture seule. Données préfixées [FORMATION].
// Ne clique jamais : « Nouveau plan de contrôle », « Ouvrir le bulleur », « Nouvelle version »,
// « Contrôler », « Signer et dater », « Générer le PDF », ni les boutons de NC des pages publiques.
// Usage : node tools/training/scenarios/qualite.mjs   (HEADED=1 pour voir le navigateur)
import { readEnv, openSession, settle } from '../lib.mjs';

const env = readEnv();
const BASE = (process.env.TRAINING_URL || env.TRAINING_URL || env.APP_URL || 'http://127.0.0.1:8001').replace(/\/$/, '');
const u = p => `${BASE}/fr${p}`;
const stamp = new Date().toISOString().slice(5, 16).replace(/[-T:]/g, '');
const inDays = n => new Date(Date.now() + n * 864e5).toISOString().slice(0, 10);

const { browser, page, shot: rawShot, save } = await openSession({ baseUrl: BASE, outDir: 'resources/guides/qualite' });
const shot = async (...a) => { await page.evaluate(() => document.activeElement?.blur()); return rawShot(...a); };
const ids = {};

async function step(title, fn) {
    console.log(`▶ ${title}`);
    try {
        await fn();
    } catch (e) {
        await page.screenshot({ path: 'storage/logs/training-ERREUR.png', fullPage: true }).catch(() => {});
        save({ scenario: 'qualite', failedAt: title, error: String(e), ids });
        console.error(`✖ ${title}\n${e}`);
        await browser.close();
        process.exit(1);
    }
}

// Champ d'un formulaire React sans name : le bloc .mb-3 dont le libellé correspond.
const byLabel = (scope, label, what = 'select, input, textarea') =>
    scope.locator('.mb-3', { has: page.locator('label', { hasText: label }) }).locator(what).first();
const pickText = async (select, re) => {
    const v = await select.locator('option').evaluateAll((os, [src, flags]) => os.find(o => new RegExp(src, flags).test(o.text))?.value, [re.source, re.flags]);
    if (!v) throw new Error(`Option introuvable : ${re}`);
    // Une Tom Select doit être réglée par son API, sinon son affichage reste sur l'ancien choix.
    const ts = await select.evaluate((el, v) => { if (!el.tomselect) return false; el.tomselect.setValue(v); return true; }, v);
    if (!ts) await select.selectOption(v);
};
async function pickUser(form, name) {
    await form.locator('select[name="user_id"]').evaluate((el, name) => {
        const opt = [...el.options].find(o => o.text.trim() === name);
        if (el.tomselect) el.tomselect.setValue(opt.value);
        else { el.value = opt.value; el.dispatchEvent(new Event('change', { bubbles: true })); }
    }, name);
}
const app = () => page.locator('#quality-index-app');
const tabQ = async name => { await app().locator('.nav-link', { hasText: name }).first().click(); await settle(page); };

await step('Connexion', async () => {
    await page.goto(u('/login'));
    await page.fill('input[name="email"]', env.TRAINING_EMAIL);
    await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
    await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
    await settle(page);
});

await step('1. Tableau de bord', async () => {
    await page.goto(u('/quality'));
    await settle(page);
    await shot('tableau-de-bord', { title: 'Tableau de bord qualité' });
});

const REFS = [
    { card: /D[ée]faillance/, code: `F-DEF-${stamp}`, label: '[FORMATION] Angle de pliage hors tolérance' },
    { card: /Cause/, code: `F-CAU-${stamp}`, label: '[FORMATION] Butée de presse mal réglée' },
    { card: /Correcti/, code: `F-COR-${stamp}`, label: '[FORMATION] Reprise au pliage et contrôle à 100 %' },
];

await step('2. Réglage', async () => {
    await tabQ(/R[ée]glage/);
    for (const [i, r] of REFS.entries()) {
        // L'ancêtre le plus proche : la carte des onglets contient aussi ces titres.
        const card = app().locator('h3.card-title', { hasText: r.card }).first().locator('xpath=ancestor::div[contains(@class,"card-outline")][1]');
        await card.locator('[data-lte-toggle="card-collapse"]').first().click();
        await card.locator('.card-body').first().waitFor({ state: 'visible' });
        const form = card.locator('form').last();
        await byLabel(form, /Code|ID externe/, 'input').fill(r.code);
        await byLabel(form, /Libell/, 'input').fill(r.label);
        if (i === 0) await shot('reglage', { title: 'Réglage : défaillances, causes et corrections types', highlight: [byLabel(form, /Code|ID externe/, 'input'), byLabel(form, /Libell/, 'input'), form.locator('button[type=submit]')] });
        await form.locator('button[type=submit]').click();
        await card.locator('td', { hasText: r.label }).first().waitFor();
    }
});

await step('3. Appareils de mesure', async () => {
    await page.reload();
    await settle(page);
    await tabQ(/Appareil/);
    const devices = [
        { code: `F-PAC-${stamp}`, label: '[FORMATION] Pied à coulisse 150 mm', serial: `SN-PAC-${stamp}`, done: inDays(-360), due: inDays(5) },
        { code: `F-RAP-${stamp}`, label: '[FORMATION] Rapporteur d’angle', serial: `SN-RAP-${stamp}`, done: inDays(-400), due: inDays(-10) },
    ];
    for (const [i, d] of devices.entries()) {
        const form = app().locator('form').last();
        await byLabel(form, /ID externe|Code/, 'input').fill(d.code);
        await byLabel(form, /Libell/, 'input').fill(d.label);
        await pickText(byLabel(form, /Service/, 'select'), /BEND|Pli/i);
        await byLabel(form, /Utilisateur/, 'select').selectOption({ label: 'Admin' });
        await byLabel(form, /s[ée]rie/i, 'input').fill(d.serial);
        await byLabel(form, /Calibr|[ée]talonn[ée] le/i, 'input').fill(d.done);
        await byLabel(form, /[ÉE]ch[ée]ance/i, 'input').fill(d.due);
        if (i === 0) await shot('appareil-creation', { title: 'Nouvel appareil de mesure', highlight: [byLabel(form, /ID externe|Code/, 'input'), byLabel(form, /s[ée]rie/i, 'input'), byLabel(form, /[ÉE]ch[ée]ance/i, 'input'), form.locator('button[type=submit]')] });
        await form.locator('button[type=submit]').click();
        await app().locator('td', { hasText: d.code }).first().waitFor();
    }
    // Les bandeaux d'étalonnage sont calculés au chargement de la page.
    await page.reload();
    await settle(page);
    await tabQ(/Appareil/);
    await shot('appareils-liste', { title: 'Appareils de mesure et alertes d’étalonnage' });
});

await step('4. Non-conformité', async () => {
    await page.goto(u('/quality/nonConformitie'));
    await settle(page);
    const card = page.locator('.card', { has: page.locator('.card-title', { hasText: /Nouvelle non conformit/i }) }).first();
    await card.locator('.card-header').click();
    const form = card.locator('form');
    await form.waitFor();
    ids.nc = await byLabel(form, /ID externe/, 'input').inputValue();
    await byLabel(form, /Libell/, 'input').fill('[FORMATION] Équerres support : angle de pliage 92° au lieu de 90°');
    await form.locator('.mb-3', { has: page.locator('label:text-is("Type")') }).locator('select').selectOption('1');
    await byLabel(form, /Utilisateur/, 'select').selectOption({ label: 'Admin' });
    await pickText(byLabel(form, /Service/, 'select'), /BEND|Pli|PLI/i);
    await pickText(byLabel(form, /Client|Soci/, 'select'), /\[FORMATION\]/);
    await pickText(byLabel(form, /D[ée]faillance/, 'select'), /\[FORMATION\] Angle/);
    await byLabel(form, /D[ée]faillance/, 'textarea').fill('Mesuré 92° au rapporteur sur 12 pièces');
    await pickText(byLabel(form, /Cause/, 'select'), /\[FORMATION\] Butée/);
    await byLabel(form, /Cause/, 'textarea').fill('Butée arrière déplacée au changement d’outil');
    await pickText(byLabel(form, /Correcti/, 'select'), /\[FORMATION\] Reprise/);
    await byLabel(form, /Correcti/, 'textarea').fill('Reprise des 12 pièces, contrôle du lot');
    await byLabel(form, /^Qt/, 'input').fill('12');
    await byLabel(form, /Ind\. plan/, 'input').fill('B');
    await shot('nc-creation', { title: 'Déclarer une non-conformité', highlight: [byLabel(form, /Libell/, 'input'), byLabel(form, /Utilisateur/, 'select'), byLabel(form, /D[ée]faillance/, 'select'), byLabel(form, /Cause/, 'select'), byLabel(form, /Correcti/, 'select')] });
    await form.locator('button[type=submit]').click();
    await page.locator('td', { hasText: ids.nc }).first().waitFor();
    await settle(page);
    await shot('nc-liste', { title: 'Liste des non-conformités' });
});

await step('5. Action corrective', async () => {
    await page.goto(u('/quality/action'));
    await settle(page);
    const form = page.locator('form[action$="/quality/action/create"]');
    ids.action = await form.locator('[name="code"]').inputValue();
    await form.locator('[name="label"]').fill('[FORMATION] Fiche de réglage de la butée et autocontrôle 1re pièce');
    await pickUser(form, 'Admin');
    await pickText(form.locator('[name="quality_non_conformitie_id"]'), new RegExp(ids.nc.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
    await form.locator('[name="pb_descp"]').fill('Angle de pliage à 92° au lieu de 90° sur un lot de 12 équerres');
    await form.locator('[name="cause"]').fill('Butée arrière non contrôlée après le changement d’outil');
    await form.locator('[name="action"]').fill('Fiche de réglage au poste, contrôle de la 1re pièce au rapporteur avant la série');
    await shot('action-creation', { title: 'Action corrective liée à la non-conformité', highlight: [form.locator('[name="label"]'), form.locator('[name="quality_non_conformitie_id"]'), form.locator('[name="cause"]'), form.locator('[name="action"]')] });
    await Promise.all([page.waitForNavigation(), form.locator('button[type=submit]').click()]);
    await settle(page);
    // Les actions sont triées par id croissant : la nouvelle est en dernière page.
    const last = page.locator('.pagination .page-link').filter({ hasText: /^\d+$/ }).last();
    if (await last.count()) { await last.click(); await settle(page); }
    await shot('actions-liste', { title: 'Actions qualité' });
});

await step('6. Dérogation', async () => {
    await page.goto(u('/quality/derogation'));
    await settle(page);
    const form = page.locator('form[action$="/quality/derogation/create"]');
    await form.locator('[name="label"]').fill('[FORMATION] Acceptation du lot d’équerres à 91,5°');
    await pickUser(form, 'Admin');
    await pickText(form.locator('[name="quality_non_conformitie_id"]'), new RegExp(ids.nc.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')));
    await form.locator('[name="pb_descp"]').fill('Lot de 12 équerres : angle mesuré entre 91,5° et 92°');
    await form.locator('[name="proposal"]').fill('Livrer en l’état les pièces à 91,5°, reprendre les autres');
    await form.locator('[name="reply"]').fill('En attente de la réponse du client');
    await form.locator('[name="decision"]').fill('À confirmer après réponse du client');
    await shot('derogation-creation', { title: 'Demande de dérogation', highlight: [form.locator('[name="proposal"]'), form.locator('[name="reply"]'), form.locator('[name="decision"]')] });
    await Promise.all([page.waitForNavigation(), form.locator('button[type=submit]').click()]);
    await settle(page);
});

await step('7. Clôture de la NC', async () => {
    await page.goto(u(`/quality/nonConformitie?search=${encodeURIComponent(ids.nc)}`));
    await settle(page);
    const row = page.locator('tr', { has: page.locator('td', { hasText: ids.nc }) }).first();
    await row.locator('button.btn-teal').click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    const solved = modal.locator('button', { hasText: /R[ée]solue/ });
    await shot('nc-cloture', { title: 'Clôturer avec le bouton « Résolue »', highlight: solved });
    await solved.click();
    await modal.waitFor({ state: 'detached' });
    await settle(page);
    await shot('nc-resolue', { title: 'Non-conformité résolue' });
});

await step('8. Plans de contrôle (lecture)', async () => {
    await page.goto(u('/quality/plans'));
    await settle(page);
    await page.locator('.nav-link', { hasText: /Valid/ }).first().click();
    await settle(page);
    await shot('plans-valides', { title: 'Plans qualité : plans validés' });
    await page.goto(u('/quality/plans/1/bubbler'));
    await settle(page);
    await page.waitForTimeout(1500);
    await shot('bulleur', { title: 'Plan bullé (validé, en lecture seule)' });
    await page.goto(u('/quality/inspections/2/report'));
    await settle(page);
    await shot('rapport-fai', { title: 'Rapport premier article' });
});

await step('9. Audits et retours (lecture)', async () => {
    // Les audits sont hors du groupe de langue : pas de /fr.
    await page.goto(`${BASE}/audit`);
    await settle(page);
    await page.locator('#audit-planner-app .nav-link', { hasText: /Checklist/ }).first().click();
    await settle(page);
    await shot('audits', { title: 'Audits internes : checklists ISO 9001' });
    await page.goto(u('/returns'));
    await settle(page);
    await shot('retours', { title: 'Retours clients' });
});

save({ scenario: 'qualite', ids, finishedAt: new Date().toISOString() });
console.log('✔ Parcours terminé', ids);
await browser.close();
