/**
 * Mise en page des lignes de devis côté saisie : sections, sous-totaux,
 * textes, articles masqués et ouvrages au forfait.
 *
 * Miroir de App\Services\Documents\SalesPrintLayout : mêmes règles, pour que
 * ce que l'on voit à l'écran soit ce qui s'imprime.
 * - une section porte de son titre jusqu'à la section suivante ;
 * - un sous-total additionne les articles (masqués compris) depuis le
 *   sous-total précédent, le début de la section ou le début du devis ;
 * - une section au forfait (pdf_package > 0) imprime un seul montant, sauf si
 *   elle n'a aucune ligne : elle reste un simple titre (pas de forfait à 0 €).
 */

export const LINE_TYPES = { ARTICLE: 'article', SECTION: 'section', SUBTOTAL: 'subtotal', TEXT: 'text' };

export const PACKAGE = { NONE: 0, AMOUNT: 1, UNIT: 2 };

export function isArticle(line) {
    return (line?.line_type ?? LINE_TYPES.ARTICLE) === LINE_TYPES.ARTICLE;
}

const round2 = (n) => Math.round(n * 100) / 100;

/** Total HT net d'un article, arrondi comme dans les calculateurs PHP. */
export function lineNet(line) {
    if (!isArticle(line)) return 0;
    const qty      = parseFloat(line.qty ?? 0) || 0;
    const price    = parseFloat(line.effective_price ?? line.selling_price ?? 0) || 0;
    const discount = parseFloat(line.discount ?? 0) || 0;
    return round2(qty * price * (1 - discount / 100));
}

/**
 * @param {Array} lines lignes du devis (dans n'importe quel ordre)
 * @returns {{
 *   byId: Object<number, {sectionId: ?number, inPackage: boolean, amount: ?number, empty?: boolean}>,
 *   hiddenOutsidePackage: {count: number, amount: number},
 * }}
 *   amount = total de la section pour une section, montant calculé pour un sous-total ;
 *   empty  = section sans aucune ligne article en dessous
 */
export function computeLayout(lines) {
    const sorted  = [...lines].sort((a, b) => a.ordre - b.ordre);
    const byId    = {};
    const hidden  = { count: 0, amount: 0 };

    // Nombre d'articles par section, pour savoir si un forfait a un contenu.
    const articleCount = {};
    let current = null;
    for (const line of sorted) {
        if (line.line_type === LINE_TYPES.SECTION) { current = line.id; articleCount[current] = 0; }
        else if (current !== null && isArticle(line)) articleCount[current] += 1;
    }

    let section = null;
    let running = 0;
    const sectionTotals = {};

    for (const line of sorted) {
        if (line.line_type === LINE_TYPES.SECTION) {
            section = line;
            running = 0;
            sectionTotals[line.id] = 0;
            byId[line.id] = { sectionId: line.id, inPackage: false, amount: 0, empty: articleCount[line.id] === 0 };
            continue;
        }

        const inPackage = !!(section && Number(section.pdf_package) > 0 && articleCount[section.id] > 0);

        if (line.line_type === LINE_TYPES.SUBTOTAL) {
            byId[line.id] = { sectionId: section?.id ?? null, inPackage, amount: round2(running) };
            running = 0; // le sous-total suivant repart d'ici
            continue;
        }

        if (line.line_type === LINE_TYPES.TEXT) {
            byId[line.id] = { sectionId: section?.id ?? null, inPackage, amount: null };
            continue;
        }

        const net = lineNet(line);
        running += net;
        if (section) sectionTotals[section.id] += net;
        byId[line.id] = { sectionId: section?.id ?? null, inPackage, amount: net };

        if (line.hide_on_pdf && !inPackage) {
            hidden.count  += 1;
            hidden.amount += net;
        }
    }

    for (const [id, total] of Object.entries(sectionTotals)) {
        byId[id].amount = round2(total);
    }
    hidden.amount = round2(hidden.amount);

    return { byId, hiddenOutsidePackage: hidden };
}

/**
 * Déplace une ligne — ou une section avec toutes ses lignes — dans la liste
 * triée, puis renumérote `ordre` de 1 à N.
 *
 * Comme le glisser-déposer d'origine : en remontant, le bloc se place avant
 * la ligne cible ; en descendant, après elle.
 *
 * @param {Array} lines   toutes les lignes du devis
 * @param {number} fromId ligne déplacée
 * @param {number} toId   ligne sur laquelle on dépose
 * @returns {Array} nouvelles lignes, `ordre` continu
 */
export function moveLine(lines, fromId, toId) {
    const sorted = [...lines].sort((a, b) => a.ordre - b.ordre);
    const from   = sorted.findIndex((l) => l.id === fromId);
    const to     = sorted.findIndex((l) => l.id === toId);
    if (from === -1 || to === -1 || fromId === toId) return renumber(sorted);

    // Bloc déplacé : la ligne seule, ou la section et tout ce qui la suit jusqu'à la suivante.
    let end = from + 1;
    if (sorted[from].line_type === LINE_TYPES.SECTION) {
        while (end < sorted.length && sorted[end].line_type !== LINE_TYPES.SECTION) end++;
    }
    const block = sorted.slice(from, end);

    // Déposer une section dans son propre bloc ne veut rien dire.
    if (block.some((l) => l.id === toId)) return renumber(sorted);

    const rest   = [...sorted.slice(0, from), ...sorted.slice(end)];
    const target = rest.findIndex((l) => l.id === toId);
    const at     = to > from ? target + 1 : target;

    return renumber([...rest.slice(0, at), ...block, ...rest.slice(at)]);
}

function renumber(sorted) {
    return sorted.map((l, i) => (l.ordre === i + 1 ? l : { ...l, ordre: i + 1 }));
}
