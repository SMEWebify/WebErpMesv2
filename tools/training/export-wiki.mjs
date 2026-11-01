// Exporte les guides (resources/guides/*/guide.md et guide.en.md) vers un clone du wiki GitHub.
// Usage : node tools/training/export-wiki.mjs <dossier du clone .wiki>
//
// - une page par guide et par langue, nommée d'après son titre (+ « (FR) » pour le français) ;
// - captures copiées dans images/<slug>/ ;
// - en-tête YAML → ligne d'infos + résumé ; ```steps → étapes fléchées ;
//   citations ⚠️ / 💡 → encadrés GitHub > [!WARNING] / > [!TIP] ;
// - options du .env (`features:` de l'en-tête, blocs <!-- feature: x --> … <!-- /feature -->) :
//   le wiki sert toutes les instances, les deux variantes sont donc gardées et signalées ;
// - _Sidebar.md régénéré, et la liste des guides de Home.md réécrite entre
//   <!-- guides:start --> et <!-- guides:end -->.
// Ne supprime rien dans le wiki : le ménage des anciennes pages se fait à la main.
import fs from 'node:fs';
import path from 'node:path';

const wiki = process.argv[2];
if (!wiki || !fs.existsSync(path.join(wiki, '.git'))) {
    console.error('Usage : node tools/training/export-wiki.mjs <clone du wiki>');
    process.exit(1);
}
const root = 'resources/guides';

function parse(file) {
    const raw = fs.readFileSync(file, 'utf8').replace(/\r\n/g, '\n');
    const m = raw.match(/^---\n([\s\S]*?)\n---\n/);
    const meta = {};
    for (const line of (m?.[1] ?? '').split('\n')) {
        const kv = line.match(/^(\w+):\s*(.*)$/);
        if (kv) meta[kv[1]] = kv[2].replace(/^["']|["']$/g, '');
    }
    return { meta, body: m ? raw.slice(m[0].length) : raw };
}

// Virgules et apostrophes retirées : elles fragilisent les liens du wiki.
const pageName = (title, lang) =>
    `${title}${lang === 'fr' ? ' (FR)' : ''}`.replace(/[\\/:*?"<>|#%,'’]/g, '').replace(/\s+/g, '-');

// Ordre des catégories dans les listes : celui d'une mise en route, pas l'alphabet.
const CATEGORY_ORDER = ['Paramétrage', 'Setup', 'Ventes', 'Sales', 'Achats', 'Purchasing', 'Production'];
const categoryRank = c => (CATEGORY_ORDER.indexOf(c) + 1) || 99;

// Groupes de lignes « > … » → un encadré GitHub par paragraphe marqué ⚠️ / 💡.
function callouts(body) {
    const out = [];
    const lines = body.split('\n');
    for (let i = 0; i < lines.length; i++) {
        if (!lines[i].startsWith('>')) { out.push(lines[i]); continue; }
        const block = [];
        while (i < lines.length && lines[i].startsWith('>')) block.push(lines[i++].replace(/^> ?/, ''));
        i--;
        const paras = block.join('\n').split(/\n\s*\n/);
        paras.forEach((p, k) => {
            const kind = p.startsWith('⚠️') ? 'WARNING' : p.startsWith('💡') ? 'TIP' : null;
            const text = p.replace(/^(⚠️|💡)\s*/, '');
            if (k > 0) out.push('');
            if (kind) out.push(`> [!${kind}]`);
            out.push(...text.split('\n').map(l => `> ${l}`));
        });
    }
    return out.join('\n');
}

// Options citées par les guides (config/guides.php). Pas de nom de moteur : seulement la fonction.
const FEATURES = {
    'production-release': { env: 'PRODUCTION_REQUIRE_RELEASE=true', fr: 'bon pour fabrication obligatoire', en: 'mandatory release for production' },
    'qualified-supplier': { env: 'PURCHASES_REQUIRE_QUALIFIED_SUPPLIER=true', fr: 'fournisseur qualifié obligatoire', en: 'mandatory qualified supplier' },
    'access-log': { env: 'AUDIT_LOG_READS=true', fr: 'journal des consultations', en: 'access log' },
    'commercial': { env: 'APP_COMMERCIAL=true', fr: 'version commerciale', en: 'commercial edition' },
    'nesting': { fr: 'imbrication tôle (version commerciale)', en: 'sheet nesting (commercial edition)' },
};
const featureLabel = (name, lang) => {
    const f = FEATURES[name] ?? {};
    const label = f[lang] ?? name;
    return (lang === 'fr' ? `« ${label} »` : `"${label}"`) + (f.env ? ` (\`${f.env}\`)` : '');
};
const featureList = meta => (meta.features ?? '').replace(/[[\]]/g, '').split(',').map(f => f.trim()).filter(Boolean);

function features(body, lang) {
    return body.replace(/<!--\s*feature:\s*(!?)\s*([a-z0-9-]+)\s*-->\n?([\s\S]*?)<!--\s*\/feature\s*-->\n?/g, (_, not, name, content) => {
        const label = featureLabel(name, lang);
        const open = lang === 'fr'
            ? (not ? `Sans l'option ${label} :` : `Avec l'option ${label} :`)
            : (not ? `Without the ${label} option:` : `With the ${label} option:`);
        const close = lang === 'fr' ? `*Fin du passage propre à l'option ${label}.*` : `*End of the ${label} option section.*`;
        return `> [!NOTE]\n> ${open}\n\n${content.trim()}\n\n${close}\n\n`;
    });
}

function convert(body, slug, lang) {
    return callouts(features(body, lang)
        // Ancres `{#id}` des titres : utiles à l'appli (« ? » des écrans), affichées telles quelles par GitHub.
        .replace(/^(#{1,6} .*?)\s*\{#[a-z][a-z0-9-]*\}\s*$/gm, '$1')
        .replace(/```steps\n([\s\S]*?)```/g, (_, list) => list.trim().split('\n')
            .map((s, i) => `**${i + 1}.** ${s.trim()}`).join(' → ') + '\n')
        .replace(/\]\(img\//g, `](images/${slug}/`));
}

const guides = fs.readdirSync(root).filter(d => fs.existsSync(path.join(root, d, 'guide.md'))).map(slug => {
    const versions = {};
    for (const [lang, file] of [['fr', 'guide.md'], ['en', 'guide.en.md']]) {
        const f = path.join(root, slug, file);
        if (fs.existsSync(f)) versions[lang] = parse(f);
    }
    return { slug, versions };
});

const sortKey = (g, lang) => {
    const meta = (g.versions[lang] ?? g.versions.fr).meta;
    return [meta.category ?? '', Number(meta.order ?? 100), meta.title ?? ''];
};
const byLang = lang => guides.filter(g => g.versions[lang]).sort((a, b) => {
    const [x, y] = [sortKey(a, lang), sortKey(b, lang)];
    return categoryRank(x[0]) - categoryRank(y[0]) || x[0].localeCompare(y[0]) || x[1] - y[1] || x[2].localeCompare(y[2]);
});

for (const g of guides) {
    const img = path.join(root, g.slug, 'img');
    if (fs.existsSync(img)) {
        const dest = path.join(wiki, 'images', g.slug);
        fs.rmSync(dest, { recursive: true, force: true });
        fs.mkdirSync(dest, { recursive: true });
        for (const f of fs.readdirSync(img)) fs.copyFileSync(path.join(img, f), path.join(dest, f));
    }
    for (const [lang, { meta, body }] of Object.entries(g.versions)) {
        const other = lang === 'fr' ? 'en' : 'fr';
        const otherPage = g.versions[other] && pageName(g.versions[other].meta.title, other);
        const info = [
            meta.duration && `⏱ ${meta.duration} min`,
            meta.updated && (lang === 'fr' ? `mis à jour le ${meta.updated}` : `updated ${meta.updated}`),
            otherPage && `[${other === 'fr' ? 'Version française' : 'English version'}](${otherPage})`,
        ].filter(Boolean).join(' · ');
        const page = [
            `*${info}*`, '',
            meta.summary ? `**${meta.summary}**\n` : '',
            ...featureList(meta).map(f => (lang === 'fr'
                ? `> [!NOTE]
> Ce guide ne concerne que les instances où l'option ${featureLabel(f, lang)} est activée.
`
                : `> [!NOTE]
> This guide only applies to instances where the ${featureLabel(f, lang)} option is enabled.
`)),
            convert(body, g.slug, lang).trim(), '',
            '---',
            lang === 'fr'
                ? '*Ce guide est aussi disponible dans l’application : menu Guides.*'
                : '*This guide is also available in the application: Guides menu.*', '',
        ].join('\n');
        fs.writeFileSync(path.join(wiki, `${pageName(meta.title, lang)}.md`), page);
        console.log(`  ${pageName(meta.title, lang)}.md`);
    }
}

function list(lang) {
    const rows = [];
    let cat = null;
    for (const g of byLang(lang)) {
        const { meta } = g.versions[lang];
        if (meta.category !== cat) { cat = meta.category; rows.push(`\n**${cat}**\n`); }
        rows.push(`- [${meta.title}](${pageName(meta.title, lang)})`);
    }
    return rows.join('\n').trim();
}

const sidebar = [
    '**[Home](Home)**', '',
    '**Getting started**',
    '- [Installation (development)](Installation-Steps-(for-dev))',
    '- [Installation (production)](Installation-Steps-(for-production))', '',
    '### User guides', list('en'), '',
    '### Guides utilisateur (FR)', list('fr'), '',
    '**Reference**',
    '- [API](API)', '',
].join('\n');
fs.writeFileSync(path.join(wiki, '_Sidebar.md'), sidebar);
console.log('  _Sidebar.md');

const home = path.join(wiki, 'Home.md');
if (fs.existsSync(home)) {
    const block = `<!-- guides:start -->\n### User guides\n\n${list('en')}\n\n### Guides utilisateur (FR)\n\n${list('fr')}\n<!-- guides:end -->`;
    const content = fs.readFileSync(home, 'utf8');
    if (content.includes('<!-- guides:start -->')) {
        fs.writeFileSync(home, content.replace(/<!-- guides:start -->[\s\S]*<!-- guides:end -->/, block));
        console.log('  Home.md (liste des guides)');
    }
}
