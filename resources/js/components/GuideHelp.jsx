import React from 'react';

// Guides lisibles par l'utilisateur (slug → { url, label }), posés par le gabarit
// (resources/views/vendor/adminlte/master.blade.php, <meta name="guide-links">).
let links = null;
function guideLinks() {
    if (links === null) {
        try {
            links = JSON.parse(document.querySelector('meta[name="guide-links"]')?.content || '{}');
        } catch {
            links = {};
        }
    }
    return links;
}

/**
 * « ? » qui ouvre la section d'un guide (ancre `## Titre {#section}` du guide).
 * Rien n'est affiché si l'utilisateur ne peut pas lire le guide (permission, option du .env).
 * Écrire `guide` avant `section` : tests/Feature/GuideHelpTest.php relit ces attributs dans le code.
 */
export default function GuideHelp({ guide, section, className = '' }) {
    const link = guideLinks()[guide];
    if (!link) return null;

    return (
        <a href={section ? `${link.url}#${section}` : link.url} target="_blank" rel="noopener"
            className={`wem-guide-help ${className}`.trim()} title={link.label} aria-label={link.label}>
            <i className="fa-regular fa-circle-question" aria-hidden="true" />
        </a>
    );
}
