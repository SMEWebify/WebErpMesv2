// Visa documentaire d'une ligne de réception : certificat reçu comparé au type exigé
// sur la ligne d'achat. Le serveur (App\Services\Purchases\ReceiptDocumentCheck) calcule
// l'écart et la décision qui font foi ; ce module ne sert qu'à l'affichage et à
// l'aperçu de l'écart pendant la saisie dans le tiroir.

export const RESULTS = ['pending', 'compliant', 'non_compliant'];

const STATUS_THEMES = {
    not_required:  'secondary',
    pending:       'warning',
    compliant:     'success',
    non_compliant: 'danger',
};

const DECISION_THEMES = {
    accepted: 'success',
    rejected: 'danger',
    pending:  'warning',
};

// Badge d'un statut de contrôle (documentaire ou physique)
export function statusBadge(status, texts = {}) {
    const key = STATUS_THEMES[status] ? status : 'pending';
    return { theme: STATUS_THEMES[key], label: texts[`status_${key}`] ?? key };
}

export function decisionBadge(decision, texts = {}) {
    const key = DECISION_THEMES[decision] ? decision : 'pending';
    return { theme: DECISION_THEMES[key], label: texts[`decision_${key}`] ?? key };
}

const blank = (value) => value === null || value === undefined || value === '';

// Même règle que ReceiptDocumentCheck::certificateGap() : rang = ordre de la liste
// des types (EN 10204), un certificat de rang supérieur couvre l'exigence.
export function certificateGap(required, received, certificateTypes = []) {
    if (blank(required)) return null;
    if (blank(received)) return { code: 'missing', required, received: null };
    if (received === required) return null;

    const requiredRank = certificateTypes.indexOf(required);
    const receivedRank = certificateTypes.indexOf(received);
    if (requiredRank < 0 || receivedRank < 0) return { code: 'different', required, received };

    return receivedRank < requiredRank ? { code: 'insufficient', required, received } : null;
}

export function gapMessage(gap, texts = {}) {
    if (!gap) return null;
    const template = texts[`gap_${gap.code}`] ?? gap.code;
    return template
        .replace(':required', gap.required ?? '—')
        .replace(':received', gap.received ?? '—');
}

// Un visa « conforme » malgré un écart doit être justifié (le serveur refuse sinon).
export function commentRequired(result, gap) {
    return result === 'compliant' && !!gap;
}
