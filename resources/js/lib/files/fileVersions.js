/**
 * Versions of a GED file (FileVersioning on the server).
 *
 * A file outside any lineage is its own version 1, without history.
 */

/**
 * Short caption of a version: "v2 · B", "v2", "B" or '' for a plain file.
 */
export function versionCaption(file, versionLabel = 'v:version') {
    if (!file) return '';

    const number = Number(file.version ?? 1);
    const parts = [];

    // A first version without history is shown as before: no "v1" badge on
    // every document of the factory.
    if (number > 1 || file.has_history) parts.push(versionLabel.replace(':version', number));
    if (file.revision_label) parts.push(file.revision_label);

    return parts.join(' · ');
}

/**
 * A file with versions is design history: it is corrected by a new version,
 * never deleted (the server answers 409).
 */
export function canDeleteFile(file) {
    return !file?.has_history;
}

/**
 * Only the latest version of a lineage may be replaced.
 */
export function canReplaceFile(file) {
    return !!file && !file.is_superseded;
}

/**
 * Revision label proposed on upload or replacement: the item's current index
 * (products.ind), editable. Trimmed to the 20 characters the column holds.
 */
export function proposedRevision(defaultRevision) {
    return String(defaultRevision ?? '').trim().slice(0, 20);
}

/**
 * Office editing of a file (« Modifier dans Excel / Word ») : the server sends
 * the scheme (ms-excel, ms-word) when the option is on and the format allows
 * it; only the latest version is edited, like a replacement.
 */
export function officeEditApp(file) {
    if (!canReplaceFile(file)) return null;
    return { 'ms-excel': 'excel', 'ms-word': 'word' }[file.office_edit] ?? null;
}
