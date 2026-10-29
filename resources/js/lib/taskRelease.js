// Visa de libération des phases (App\Services\Production\TaskRelease).
// `phase_release` n'existe que sur les tâches concernées : sans poste à visa,
// aucune pastille n'est produite et l'affichage reste celui d'avant.

/**
 * Pastilles d'une tâche : état de son propre visa, puis avertissement si une
 * opération précédente attend le sien (jamais bloquant).
 *
 * @param {{state?: string|null, warning?: string|null}|null|undefined} phaseRelease
 * @param {Record<string, string>} trans  textes de resources/lang/{fr,en}/task_release.php
 * @returns {Array<{key: string, className: string, icon: string, label: string, title?: string}>}
 */
export function releaseBadges(phaseRelease, trans = {}) {
    if (!phaseRelease) return [];
    const t = (key, fallback) => trans?.[key] ?? fallback;
    const badges = [];

    if (phaseRelease.state === 'released') {
        badges.push({ key: 'state', className: 'text-bg-success', icon: 'fas fa-stamp', label: t('badge_released', 'Libérée') });
    } else if (phaseRelease.state === 'awaiting') {
        badges.push({ key: 'state', className: 'text-bg-warning', icon: 'fas fa-stamp', label: t('badge_awaiting', 'À viser') });
    } else if (phaseRelease.state === 'to_finish') {
        badges.push({ key: 'state', className: 'text-bg-secondary', icon: 'fas fa-stamp', label: t('badge_to_finish', 'Visa requis') });
    }

    if (phaseRelease.warning) {
        badges.push({
            key: 'predecessor',
            className: 'text-bg-warning',
            icon: 'fas fa-exclamation-triangle',
            label: t('badge_predecessor', 'Précédente non libérée'),
            title: phaseRelease.warning,
        });
    }

    return badges;
}

/**
 * Reporte sur les colonnes affichées l'état de visa renvoyé par le serveur après
 * une synchronisation, sans toucher à l'ordre des cartes (un autre glisser peut
 * être en cours). Une tâche absente de la réponse garde son état.
 *
 * @param {Array<{tasks: Array<object>}>} statuses       colonnes affichées
 * @param {Array<{tasks: Array<object>}>} serverStatuses colonnes renvoyées par /task/sync
 */
export function mergePhaseRelease(statuses, serverStatuses) {
    if (!Array.isArray(serverStatuses)) return statuses;

    const byId = new Map();
    serverStatuses.forEach((status) => (status.tasks ?? []).forEach((task) => byId.set(task.id, task)));

    return statuses.map((status) => ({
        ...status,
        tasks: status.tasks.map((task) => {
            if (!byId.has(task.id)) return task;
            const fresh = byId.get(task.id).phase_release ?? null;
            if ((task.phase_release ?? null) === null && fresh === null) return task;
            return { ...task, phase_release: fresh };
        }),
    }));
}
