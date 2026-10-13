/**
 * Renomme les classes Bootstrap 4 en leurs équivalents Bootstrap 5 dans les
 * vues, les composants React, nos scripts et nos feuilles de style.
 *
 * Seules les classes entières sont remplacées (ml-2 oui, html-2 jamais). Les
 * classes construites dynamiquement (`badge-${couleur}`) et les mots ambigus
 * (close, media) sont laissés à une reprise manuelle.
 *
 * Usage : node tools/bs5-codemod.cjs            (simulation : volumes par règle)
 *         node tools/bs5-codemod.cjs --write    (applique)
 */
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const write = process.argv.includes('--write');

const BP = '(?:(sm|md|lg|xl)-)?';
const COLORS = '(primary|secondary|success|danger|warning|info|light|dark)';

// Couleurs propres à la palette AdminLTE 3 et leur remplaçant v4 / Bootstrap.
const V3_TO_V4 = {
    purple: 'violet', lightblue: 'sky', lime: 'success', maroon: 'danger',
    blue: 'primary', cyan: 'info', green: 'success', red: 'danger',
    yellow: 'warning', gray: 'secondary', 'gray-dark': 'dark',
};
const V3_COLORS = `(${Object.keys(V3_TO_V4).sort((a, b) => b.length - a.length).join('|')})`;

// [nom, motif (sans bornes), remplacement]. L'ordre compte : les classes les
// plus longues d'abord (custom-control-input avant custom-control).
const RULES = [
    ['marges ml/mr', new RegExp(`m([lr])-${BP}([0-5]|auto)`), (m, side, bp, n) => `m${side === 'l' ? 's' : 'e'}-${bp ? bp + '-' : ''}${n}`],
    ['marges pl/pr', new RegExp(`p([lr])-${BP}([0-5])`), (m, side, bp, n) => `p${side === 'l' ? 's' : 'e'}-${bp ? bp + '-' : ''}${n}`],
    ['float', new RegExp(`float-${BP}(left|right)`), (m, bp, dir) => `float-${bp ? bp + '-' : ''}${dir === 'left' ? 'start' : 'end'}`],
    ['text-align', new RegExp(`text-${BP}(left|right)`), (m, bp, dir) => `text-${bp ? bp + '-' : ''}${dir === 'left' ? 'start' : 'end'}`],
    // Pas la propriété CSS « border-left: … ».
    ['bordures', /border-(left|right)(-0)?(?!\s*:)/, (m, dir, zero) => `border-${dir === 'left' ? 'start' : 'end'}${zero || ''}`],
    ['arrondis', /rounded-(left|right)/, (m, dir) => `rounded-${dir === 'left' ? 'start' : 'end'}`],
    ['badges', new RegExp(`badge-${COLORS}`), (m, c) => `text-bg-${c}`],
    ['badge-pill', /badge-pill/, () => 'rounded-pill'],
    ['btn-block', /btn-block/, () => 'w-100'],
    ['font-weight', /font-weight-(bold|bolder|normal|light|lighter)/, (m, w) => `fw-${w}`],
    ['font-italic', /font-italic/, () => 'fst-italic'],
    ['text-monospace', /text-monospace/, () => 'font-monospace'],
    ['sr-only', /sr-only(-focusable)?/, (m, f) => `visually-hidden${f || ''}`],
    ['no-gutters', /no-gutters/, () => 'g-0'],
    ['dropdown-menu', /dropdown-menu-(left|right)/, (m, dir) => `dropdown-menu-${dir === 'left' ? 'start' : 'end'}`],
    ['thead', /thead-(light|dark)/, (m, c) => `table-${c}`],
    ['form-group', /form-group/, () => 'mb-3'],
    ['custom-select', /custom-select(-sm|-lg)?/, (m, s) => `form-select${s || ''}`],
    ['custom-range', /custom-range/, () => 'form-range'],
    ['custom-control-input', /custom-control-input/, () => 'form-check-input'],
    ['custom-control-label', /custom-control-label/, () => 'form-check-label'],
    ['custom-control-inline', /custom-control-inline/, () => 'form-check-inline'],
    ['custom-control', /custom-control/, () => 'form-check'],
    ['custom-switch', /custom-switch/, () => 'form-switch'],
    ['custom-checkbox / radio', /custom-(checkbox|radio)/, () => ''],
    ['img-circle', /img-circle/, () => 'rounded-circle'],
    // Seconde passe : classes sans équivalent direct, ramenées aux utilitaires BS5.
    ['bg-gradient', new RegExp(`bg-gradient-${COLORS}`), (m, c) => `text-bg-${c} bg-gradient`],
    ['btn-flat', /btn-flat/, () => 'rounded-0'],
    // form-row : gouttière horizontale de 10 px, sans gouttière verticale.
    ['form-row', /form-row/, () => 'row gx-2'],
    ['form-control-file', /form-control-file/, () => 'form-control'],
    ['elevation', /elevation-([0-5])/, (m, n) => ['shadow-none', 'shadow-sm', 'shadow', 'shadow', 'shadow-lg', 'shadow-lg'][n]],
    ['text-sm', /text-sm/, () => 'small'],
    // Palette AdminLTE 3 -> palette AdminLTE 4 / couleurs Bootstrap. En v3,
    // bg-<couleur> fixait aussi la couleur du texte : text-bg-* fait de même.
    ['couleurs v3 (bg)', new RegExp(`bg-${V3_COLORS}`), (m, c) => `text-bg-${V3_TO_V4[c]}`],
    ['couleurs v3', new RegExp(`(text|card|btn|btn-outline|border|callout|badge|alert)-${V3_COLORS}`), (m, p, c) => `${p}-${V3_TO_V4[c]}`],
    ['couleurs v3 (theme)', new RegExp(`theme="${V3_COLORS}"`), (m, c) => `theme="${V3_TO_V4[c]}"`],
    // Classes AdminLTE 3 sans équivalent : ramenées aux composants Bootstrap 5.
    ['btn-default', /btn-default/, () => 'btn-outline-secondary'],
    ['btn-xs', /btn-xs/, () => 'btn-sm'],
    ['list-group-unbordered', /list-group-unbordered/, () => 'list-group-flush'],
    ['profile-username', /profile-username/, () => 'fs-4 mt-1'],
    // Fonds clairs figés -> fonds du thème (identiques en clair, sombres en
    // mode sombre). bg-light / bg-white restent clairs même en mode sombre.
    ['bg-light', /bg-light/, () => 'bg-body-tertiary'],
    ['bg-white', /bg-white/, () => 'bg-body'],
];

// Une classe est délimitée par tout ce qui n'est ni lettre, chiffre, tiret ni
// soulignement — et pas un `$` / `{` (gabarit JS `badge-${x}`) juste après.
const wrap = (re) => new RegExp(`(?<![\\w$-])${re.source}(?![\\w$-]|\\{)`, 'g');
const compiled = RULES.map(([name, re, fn]) => [name, wrap(re), fn]);

// Les feuilles CSS/SCSS ne sont pas réécrites : un sélecteur .form-group devenu
// .mb-3 viserait toute marge basse. Elles sont reprises à la main.
const TARGETS = [
    ['resources/views', ['.blade.php']],
    ['resources/js', ['.js', '.jsx']],
    ['app', ['.php']],
    ['public/js', ['.js']],
];
// Fichiers générés, tiers ou hors Bootstrap, à ne jamais réécrire.
const EXCLUDE = [
    /resources[\\/]views[\\/]vendor[\\/]/,
    /public[\\/]js[\\/](spreadsheet|app|bootstrap|three\.min|three\.module|OrbitControls|STLLoader|node_modules_vue_dist_vue_esm-bundler_js)\.js$/,
];
// PDF (dompdf, CSS propre, HTML comparé à des références figées par
// SalesPdfLegacyRenderingTest) et gabarits d'e-mail : hors Bootstrap, sauf les
// pages de ces dossiers qui s'affichent dans le gabarit AdminLTE.
const OUTSIDE_BOOTSTRAP = /resources[\\/]views[\\/](print|emails)[\\/]/;
const isAdminltePage = (p) => /@extends\('adminlte::page'\)/.test(fs.readFileSync(p, 'utf8'));

const files = [];
const walk = (dir, exts) => {
    for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
        const p = path.join(dir, e.name);
        if (e.isDirectory()) walk(p, exts);
        else if (exts.some((x) => e.name.endsWith(x)) && !EXCLUDE.some((re) => re.test(p))
            && (!OUTSIDE_BOOTSTRAP.test(p) || isAdminltePage(p))) files.push(p);
    }
};
TARGETS.forEach(([dir, exts]) => walk(path.join(root, dir), exts));

const perRule = Object.fromEntries(RULES.map(([name]) => [name, 0]));
let changedFiles = 0;

for (const file of files) {
    const before = fs.readFileSync(file, 'utf8');
    // Les blocs <style> des vues sont laissés tels quels (voir TARGETS).
    let after = before.split(/(<style[\s\S]*?<\/style>)/i).map((part, i) => {
        if (i % 2 === 1) return part;
        for (const [name, re, fn] of compiled) {
            part = part.replace(re, (...args) => {
                perRule[name]++;
                return fn(...args);
            });
        }
        return part;
    }).join('');
    // Une classe supprimée (custom-checkbox) laisse un double espace.
    if (after !== before) {
        after = after.replace(/(class(?:Name)?=["'{`][^"'`]*?) {2,}/g, '$1 ');
        changedFiles++;
        if (write) fs.writeFileSync(file, after);
    }
}

for (const [name, n] of Object.entries(perRule)) if (n) console.log(`${String(n).padStart(5)}  ${name}`);
console.log(`${write ? 'Modifiés' : 'À modifier'} : ${changedFiles} fichiers sur ${files.length}`);
