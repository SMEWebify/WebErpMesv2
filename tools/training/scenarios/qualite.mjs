// Guide « Qualité » : réglage (défaillances, causes, corrections), appareils de mesure,
// non-conformité → action corrective (validée, historique) → dérogation externe (fiche, accord
// du client dans la GED, validation) → clôture et historique de la NC, liste du visa documentaire,
// puis plans de contrôle, rapport premier article, audits et retours en lecture seule.
// Données préfixées [FORMATION].
// Ne clique jamais : « Envoyer pour signature » (DocuSign), « Relire l'état », « Nouveau plan de
// contrôle », « Ouvrir le bulleur », « Nouvelle version », « Contrôler », « Signer et dater »,
// « Générer le PDF », « Viser », ni les boutons de NC des pages publiques.
// La dérogation n'est reliée à AUCUNE ligne de commande : une dérogation externe ouverte
// bloquerait le BL de cette ligne pour les autres scénarios.
// Usage : node tools/training/scenarios/qualite.mjs   (HEADED=1 pour voir le navigateur)
import { readEnv, openSession, settle } from '../lib.mjs';
import { mkdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

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
        if (i === 0) await shot('appareil-creation', { title: 'Nouvel appareil de mesure', highlight: [byLabel(form, /ID externe|Code/, 'input'), byLabel(form, /s[ée]rie/i, 'input'), byLabel(form, /[ÉE]ch[ée]ance/i, 'input')] });
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
    ids.actionUrl = page.url();
});

await step('5b. Action validée et historique', async () => {
    const row = () => page.locator('tr', { has: page.locator('td', { hasText: ids.action }) }).first();
    await row().locator('button[data-bs-target^="#QualityAction"]:not([data-bs-target^="#QualityActionView"])').click();
    const modal = page.locator('.modal.show');
    await modal.waitFor();
    await modal.locator('select[name="statu"]').selectOption('3');
    await Promise.all([page.waitForNavigation(), modal.locator('button[type=submit]').click()]);
    await settle(page);
    await page.goto(ids.actionUrl);
    await settle(page);
    await row().locator('button[data-bs-target^="#QualityActionView"]').click();
    const view = page.locator('.modal.show');
    await view.waitFor();
    await page.waitForTimeout(400);
    const hist = view.locator('h6', { hasText: /Historique/ });
    await shot('action-historique', { title: 'Historique des modifications de l’action', highlight: hist });
    await view.locator('[data-bs-dismiss="modal"]').first().click().catch(() => {});
});

const ncRe = () => new RegExp(ids.nc.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
const cardOf = title => page.locator('.card', { has: page.locator('.card-title', { hasText: title }) }).first();
const toTop = loc => loc.evaluate(el => { el.scrollIntoView({ block: 'start', behavior: 'instant' }); window.scrollBy(0, -70); });

await step('6. Dérogation : demande', async () => {
    await page.goto(u('/quality/derogation'));
    await settle(page);
    const form = page.locator('form[action$="/quality/derogation/create"]');
    await form.locator('[name="label"]').fill('[FORMATION] Acceptation du lot d’équerres à 91,5°');
    // Interrupteur coché = interne ; décoché, la dérogation est externe (elle engage le client).
    await form.locator('input[name="type"]').uncheck({ force: true });
    await pickUser(form, 'Admin');
    await pickText(form.locator('[name="quality_non_conformitie_id"]'), ncRe());
    await form.locator('[name="pb_descp"]').fill('Lot de 12 équerres : angle mesuré entre 91,5° et 92°');
    await form.locator('[name="proposal"]').fill('Livrer en l’état les pièces à 91,5°, reprendre les autres');
    const sw = form.locator('input[name="type"]').locator('xpath=ancestor::div[1]');
    const submit = form.locator('button[type=submit]');
    await shot('derogation-creation', { title: 'Demande de dérogation', highlight: [sw, form.locator('[name="quality_non_conformitie_id"]'), form.locator('[name="proposal"]'), submit] });
    await Promise.all([page.waitForURL(/\/quality\/derogation\/\d+/), submit.click()]);
    await settle(page);
    ids.derogation = page.url().match(/derogation\/(\d+)/)[1];
});

await step('7. Dérogation : client et réponse', async () => {
    const contact = page.locator('#derogation_contact');
    if (await contact.locator('option').count() > 1) await contact.selectOption({ index: 1 });
    await page.locator('#customer_reply_date').fill(inDays(0));
    await page.locator('#customer_signatory').fill('M. Martin, responsable qualité du client');
    await page.locator('textarea[name="reply"]').fill('Accord du client pour les 7 pièces à 91,5° ; les 5 autres sont reprises');
    await page.locator('textarea[name="decision"]').fill('Livraison des 7 pièces sous dérogation, reprise des 5 autres');
    const update = page.locator('form[action*="/quality/derogation/edit/"] button[type=submit]');
    await toTop(cardOf(/Client concern/));
    await shot('derogation-fiche', { title: 'Fiche dérogation : client et réponse', highlight: [page.locator('#derogation_companies_id'), contact, page.locator('#derogation_order_line'), page.locator('#customer_reply_date'), page.locator('#customer_signatory'), update] });
    await Promise.all([page.waitForNavigation(), update.click()]);
    await settle(page);
    await page.evaluate(() => window.scrollTo(0, 0));
    await shot('derogation-manque', { title: 'Ce qui manque pour valider, et l’accord DocuSign', highlight: [cardOf(/^Validation$/), cardOf(/DocuSign/)] });
});

await step('8. Dérogation : accord du client et validation', async () => {
    // Un courrier d'accord fictif, dessiné par le navigateur.
    const dir = join(tmpdir(), 'wem-training'); mkdirSync(dir, { recursive: true });
    const letter = join(dir, 'accord-client-derogation.png');
    const tab = await page.context().newPage();
    await tab.setViewportSize({ width: 600, height: 420 });
    await tab.setContent(`<body style="font:15px Georgia,serif;padding:32px;background:#fff">
        <p><b>[FORMATION] Client exemple</b></p><p style="text-align:right">Le ${inDays(0)}</p>
        <p>Objet : demande de dérogation — équerres support</p>
        <p>Nous acceptons la livraison en l'état des 7 pièces mesurées à 91,5°, aux conditions proposées.
        Les 5 autres pièces seront reprises avant livraison.</p>
        <p style="margin-top:40px">M. Martin, responsable qualité</p></body>`);
    await tab.screenshot({ path: letter });
    await tab.close();

    const docs = cardOf(/^Documents$/);
    const role = docs.locator('.wem-dropzone-form select').first();
    await role.waitFor();
    await role.selectOption('accord_client');
    await toTop(docs);
    await shot('derogation-documents', { title: 'Joindre l’accord écrit du client', highlight: [role, docs.locator('[role=button]').first()] });
    await docs.locator('input[type=file]').setInputFiles(letter);
    await docs.getByText('accord-client-derogation', { exact: false }).first().waitFor({ timeout: 20000 });
    await settle(page);

    await page.reload();
    await settle(page);
    await page.locator('#statu').selectOption('3');
    await page.evaluate(() => window.scrollTo(0, 0));
    await shot('derogation-valider', { title: 'Passer la dérogation en « Validé »', highlight: [cardOf(/^Validation$/), page.locator('#statu')] });
    await Promise.all([page.waitForNavigation(), page.locator('form[action*="/quality/derogation/edit/"] button[type=submit]').click()]);
    await settle(page);
    await page.evaluate(() => window.scrollTo(0, 0));
    await cardOf(/^Validation$/).locator('.text-success').first().waitFor();
    await shot('derogation-validee', { title: 'Dérogation validée', highlight: cardOf(/^Validation$/) });
});

await step('9. Clôture de la NC', async () => {
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
    // Un clic sur la ligne ouvre le détail, avec l'historique des modifications.
    await page.locator('tr', { has: page.locator('td', { hasText: ids.nc }) }).first().locator('td').nth(1).click();
    const view = page.locator('.modal.show');
    await view.waitFor();
    await view.locator('li.list-group-item').first().waitFor();
    await page.waitForTimeout(300);
    const hist = view.locator('h6', { hasText: /Historique/ }).locator('xpath=..');
    await shot('nc-historique', { title: 'Historique des modifications de la non-conformité', highlight: hist });
    await view.locator('.btn-close').first().click();
});

await step('10. Visa documentaire des réceptions (lecture)', async () => {
    await page.goto(u('/purchases/receipt/document-check'));
    await settle(page);
    const filters = page.locator('.btn-group', { has: page.getByRole('button', { name: /En attente de visa/ }) }).first();
    // Rien en attente : « Tous les visas » montre au moins des lignes déjà visées.
    if (await page.getByText('Aucune ligne.').isVisible().catch(() => false)) {
        await filters.getByRole('button', { name: /Tous les visas/ }).click();
        await settle(page);
    }
    await shot('visa-documentaire', { title: 'Visa documentaire des réceptions', highlight: filters });
});

await step('11. Plans de contrôle (lecture)', async () => {
    await page.goto(u('/quality/plans'));
    await settle(page);
    await page.locator('.nav-link', { hasText: /Valid/ }).first().click();
    await settle(page);
    await shot('plans-valides', { title: 'Plans qualité : plans validés' });
    await page.goto(u('/quality/plans/1/bubbler'));
    await settle(page);
    await page.waitForTimeout(1500);
    // Le plan de démonstration porte un cartouche (dessinateur, référence) : il est couvert
    // d'un aplat, comme les données privées, avant la capture.
    await page.evaluate(() => {
        const c = [...document.querySelectorAll('canvas')].sort((a, b) => b.clientWidth * b.clientHeight - a.clientWidth * a.clientHeight)[0];
        if (!c) return;
        const r = c.getBoundingClientRect();
        const cover = document.createElement('div');
        cover.className = 'training-badge';
        Object.assign(cover.style, {
            position: 'fixed', left: `${r.left + r.width * 0.32}px`, top: `${r.top + r.height * 0.72}px`,
            width: `${r.width * 0.68}px`, height: `${r.height * 0.28}px`, background: '#adb5bd', zIndex: 9999,
        });
        document.body.appendChild(cover);
    });
    await shot('bulleur', { title: 'Plan bullé (validé, en lecture seule)' });
    await page.evaluate(() => document.querySelectorAll('.training-badge').forEach(b => b.remove()));
    await page.goto(u('/quality/inspections/2/report'));
    await settle(page);
    const drawingNo = page.getByText('N° de plan', { exact: true }).first().locator('xpath=following::input[1]');
    await shot('rapport-fai', { title: 'Rapport premier article', mask: [drawingNo] });
});

await step('12. Audits et retours (lecture)', async () => {
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
