// Messages des configurateurs externes (docs/conception/configurateurs-externes.md § 4.4).
//
// Le navigateur ne transporte que des identifiants : le serveur de l'ERP relit ensuite
// le résultat chez le configurateur. Tout message d'une autre origine, d'une autre
// fenêtre ou d'une autre forme est ignoré.

export const DONE_TYPE = 'wem-configurator:done';
export const CANCEL_TYPE = 'wem-configurator:cancel';
export const RETURN_TYPE = 'wem-configurator:return';

/** Identifiant lisible : chaîne de 1 à 255 caractères, sans caractère de contrôle. */
export function isIdentifier(value) {
    return typeof value === 'string' && value.length > 0 && value.length <= 255 && !/[\u0000-\u001f\u007f]/.test(value);
}

/**
 * Message de l'iframe du configurateur : { type: 'done', resultId } ou { type: 'cancel' }, sinon null.
 *
 * @param {MessageEvent} event
 * @param {string} expectedOrigin  origine de l'API du configurateur, donnée par le serveur
 * @param {Window|null} [expectedSource]  fenêtre de l'iframe ouverte (contentWindow)
 */
export function readIframeMessage(event, expectedOrigin, expectedSource = null) {
    if (!event || !expectedOrigin || event.origin !== expectedOrigin) return null;
    if (expectedSource && event.source !== expectedSource) return null;

    const data = event.data;
    if (!data || typeof data !== 'object') return null;

    if (data.type === CANCEL_TYPE) return { type: 'cancel' };
    if (data.type === DONE_TYPE && isIdentifier(data.resultId)) return { type: 'done', resultId: data.resultId };

    return null;
}

/**
 * Message de la page de retour de l'ERP (configurateur ouvert dans un onglet) :
 * même origine que l'ERP et même session, sinon null. Rend le resultId.
 *
 * @param {MessageEvent} event
 * @param {string} ownOrigin  origine de l'ERP (window.location.origin)
 * @param {string} session    identifiant de la session ouverte par l'écran
 */
export function readReturnMessage(event, ownOrigin, session) {
    if (!event || event.origin !== ownOrigin) return null;

    const data = event.data;
    if (!data || typeof data !== 'object' || data.type !== RETURN_TYPE || data.session !== session) return null;

    return isIdentifier(data.resultId) ? data.resultId : null;
}
