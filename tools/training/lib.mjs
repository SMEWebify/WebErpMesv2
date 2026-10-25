// Outils communs aux scénarios de formation : lecture du .env, navigateur,
// captures annotées et journal des étapes (manifest.json) relu par l'agent rédacteur.
import { chromium } from 'playwright-core';
import fs from 'node:fs';
import path from 'node:path';

export function readEnv(file = '.env') {
    const env = {};
    if (!fs.existsSync(file)) return env;
    for (const line of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
        const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/);
        if (m) env[m[1]] = m[2].replace(/^["']|["']$/g, '');
    }
    return env;
}

export async function openSession({ baseUrl, outDir, theme = 'light', viewport = { width: 1440, height: 900 } }) {
    fs.mkdirSync(path.join(outDir, 'img'), { recursive: true });
    const browser = await chromium.launch({ channel: 'msedge', headless: !process.env.HEADED });
    const context = await browser.newContext({ viewport, locale: 'fr-FR', colorScheme: theme, deviceScaleFactor: 1 });
    const page = await context.newPage();
    page.setDefaultTimeout(15000);
    page.on('dialog', d => { console.log(`  💬 ${d.message()}`); d.accept(); });
    // Requêtes en cours, pour que settle() attende vraiment la fin des appels AJAX.
    page.__inflight = 0;
    page.on('request', () => { page.__inflight += 1; });
    page.on('requestfailed', () => { page.__inflight = Math.max(0, page.__inflight - 1); });
    page.on('requestfinished', () => { page.__inflight = Math.max(0, page.__inflight - 1); });
    page.on('requestfinished', req => {
        const ms = req.timing().responseEnd;
        if (ms > 1500 && req.method() !== 'GET') console.log(`  🐢 ${Math.round(ms)} ms ${req.method()} ${req.url()}`);
    });
    page.on('response', async r => {
        if (r.request().method() !== 'GET' && r.status() >= 400) {
            const body = await r.text().catch(() => '');
            console.log(`  ⚠ ${r.status()} ${r.request().method()} ${r.url()}\n    ${body.slice(0, 400)}`);
        }
    });

    const steps = [];
    let n = 0;

    // Capture la page, l'élément `highlight` (locator ou liste) entouré en rouge et numéroté.
    async function shot(slug, { title, highlight = [], fullPage = false, note } = {}) {
        n += 1;
        const file = `${String(n).padStart(2, '0')}-${slug}.png`;
        const targets = Array.isArray(highlight) ? highlight : [highlight];
        const handles = [];
        await page.addStyleTag({ content: '#ai-chat-widget{display:none!important}' }).catch(() => {});
        for (const [i, loc] of targets.entries()) {
            let h = await loc.first().elementHandle({ timeout: 5000 }).catch(() => null);
            if (!h) throw new Error(`Capture « ${title} » : élément à encadrer n° ${i + 1} introuvable`);
            // <select> remplacé par Tom Select : encadrer l'habillage visible.
            const wrapper = await h.evaluateHandle(el => el.tomselect?.wrapper || el);
            h = wrapper.asElement() || h;
            if (!(await h.isVisible())) throw new Error(`Capture « ${title} » : élément à encadrer n° ${i + 1} invisible`);
            handles.push(h);
        }
        // Défilement instantané puis pose des badges : un défilement animé décalerait leur position.
        if (handles[0]) {
            await handles[0].evaluate(el => el.scrollIntoView({ block: 'center', behavior: 'instant' }));
            await page.waitForTimeout(250);
        }
        for (const [i, h] of handles.entries()) {
            await h.evaluate((el, idx) => {
                el.dataset.trainingOutline = el.style.outline;
                el.style.outline = '3px solid #e11d48';
                el.style.outlineOffset = '2px';
                const r = el.getBoundingClientRect();
                const b = document.createElement('div');
                b.className = 'training-badge';
                b.textContent = String(idx + 1);
                Object.assign(b.style, {
                    position: 'fixed', left: `${Math.max(r.left - 14, 2)}px`, top: `${Math.max(r.top - 14, 2)}px`,
                    width: '26px', height: '26px', borderRadius: '50%', background: '#e11d48', color: '#fff',
                    font: 'bold 14px/26px sans-serif', textAlign: 'center', zIndex: 99999,
                });
                document.body.appendChild(b);
            }, i);
        }
        await page.waitForTimeout(300);
        await page.screenshot({ path: path.join(outDir, 'img', file), fullPage });
        for (const h of handles) {
            await h.evaluate(el => { el.style.outline = el.dataset.trainingOutline || ''; }).catch(() => {});
        }
        await page.evaluate(() => document.querySelectorAll('.training-badge').forEach(b => b.remove()));
        steps.push({ n, file: `img/${file}`, title, note, url: page.url() });
        console.log(`  📸 ${file} — ${title}`);
    }

    function save(meta) {
        fs.writeFileSync(path.join(outDir, 'manifest.json'), JSON.stringify({ ...meta, steps }, null, 2));
    }

    return { browser, page, shot, save, steps };
}

// Champ d'un formulaire React repéré par son libellé (les formulaires n'ont ni name ni id).
export function field(scope, label, tags = 'input,select,textarea') {
    const only = tags.split(',').map(t => `self::${t.trim()}`).join(' or ');
    return scope.locator('label:visible', { hasText: label }).first()
        .locator(`xpath=following::*[(${only}) and not(@type="hidden")][1]`);
}

// Attend 500 ms sans aucune requête en cours (waitForLoadState('networkidle') rend la main
// tout de suite une fois la page chargée et ne voit donc pas les appels AJAX d'un clic).
export async function settle(page, { quiet = 500, timeout = 20000 } = {}) {
    await page.waitForLoadState('load').catch(() => {});
    await page.waitForTimeout(150);
    const start = Date.now();
    let idleSince = null;
    while (Date.now() - start < timeout) {
        if ((page.__inflight ?? 0) === 0) {
            idleSince ??= Date.now();
            if (Date.now() - idleSince >= quiet) return;
        } else {
            idleSince = null;
        }
        await page.waitForTimeout(50);
    }
}

// Onglet d'un écran (nav-link / bouton), sans confondre avec un titre de même texte.
export async function tab(page, text) {
    await page.locator('.nav-link, .nav button, [role=tab]', { hasText: text }).first().click();
    await settle(page);
}
