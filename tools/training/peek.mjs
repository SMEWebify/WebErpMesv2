// Captures de contrôle d'une liste d'URL (usage interne : vérifier un écran).
import { readEnv, openSession, settle } from './lib.mjs';
const env = readEnv();
const base = env.APP_URL.replace(/\/$/, '');
const [outDir, theme, ...paths] = process.argv.slice(2);
const { browser, page } = await openSession({ baseUrl: base, outDir, theme });
await page.goto(`${base}/fr/login`);
await page.fill('input[name="email"]', env.TRAINING_EMAIL);
await page.fill('input[name="password"]', env.TRAINING_PASSWORD);
await Promise.all([page.waitForURL(/dashboard/), page.click('button[type=submit]')]);
for (const [i, p] of paths.entries()) {
    await page.goto(`${base}${p}`);
    await settle(page);
    const anchor = new URL(page.url()).hash;
    if (anchor) await page.locator(anchor).evaluate(el => el.scrollIntoView({ block: 'start', behavior: 'instant' }));
    await page.waitForTimeout(300);
    await page.screenshot({ path: `${outDir}/peek-${i}.png`, fullPage: p.includes('full') });
    console.log(i, p, page.url());
}
await browser.close();
