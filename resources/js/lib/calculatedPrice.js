// Interrupteur « prix calculé » d'une ligne de commande. `state` vient du
// serveur (OrderLineCalculatedPrice::state) :
// { enabled, can_enable, needs_reason, blocked_reason }.
// Sans état (ancienne réponse), on retombe sur le drapeau seul.

/**
 * Rendu de l'interrupteur : grisé avec l'explication quand l'activation est
 * refusée, motif demandé quand la commande n'est plus en préparation.
 * La désactivation reste toujours possible (elle fige le prix du moment).
 */
export function calculatedPriceToggle(state, fallbackEnabled = false) {
    const enabled = state ? Boolean(state.enabled) : Boolean(fallbackEnabled);

    if (enabled) {
        return { enabled, disabled: false, needsReason: false, explanation: null };
    }

    const blocked = state?.blocked_reason ?? null;

    return {
        enabled,
        disabled: Boolean(state) && !state.can_enable,
        needsReason: Boolean(state?.needs_reason) && !blocked,
        explanation: blocked,
    };
}

/** Motif recevable (même longueur minimale que le serveur). */
export function isValidReason(reason, min = 3) {
    return String(reason ?? '').trim().length >= min;
}
