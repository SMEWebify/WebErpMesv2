// Messages de l'iframe NEST2BEND (postMessage).
//
// Le seul message attendu est { type: "nest2bend:done", resultId }. Il ne porte jamais le
// résultat : le serveur de l'ERP relit GET /api/v1/results/{resultId} avec la clé API.
// Tout message d'une autre origine, d'une autre fenêtre ou d'une autre forme est ignoré.

export const DONE_TYPE = 'nest2bend:done';

const UUID = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

/** Origine (schéma, hôte, port) d'une URL, ou null si elle n'est pas lisible. */
export function originOf(url) {
    try {
        const parsed = new URL(url);
        return parsed.origin && parsed.origin !== 'null' ? parsed.origin : null;
    } catch {
        return null;
    }
}

/**
 * resultId d'un message « terminé » de NEST2BEND, sinon null.
 *
 * @param {MessageEvent} event
 * @param {string} expectedOrigin  origine de NEST2BEND, donnée par le serveur
 * @param {Window|null} [expectedSource]  fenêtre de l'iframe ouverte (contentWindow)
 */
export function readDoneMessage(event, expectedOrigin, expectedSource = null) {
    if (!event || !expectedOrigin || event.origin !== expectedOrigin) return null;
    if (expectedSource && event.source !== expectedSource) return null;

    const data = event.data;
    if (!data || typeof data !== 'object' || data.type !== DONE_TYPE) return null;

    const resultId = data.resultId;
    return typeof resultId === 'string' && UUID.test(resultId) ? resultId : null;
}

/** Le nom de fichier est-il un STEP (.step / .stp) ? */
export function isStepFile(file) {
    const ext = String(file?.extension ?? '').toLowerCase()
        || String(file?.name ?? '').toLowerCase().split('.').pop();
    return ext === 'step' || ext === 'stp';
}
