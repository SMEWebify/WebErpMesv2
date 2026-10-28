import React, { useEffect } from 'react';

/*
 * Catégorie d'affaire dans les index (opportunités, devis, commandes) et les fenêtres
 * « Nouveau… ». La liste des catégories vient du serveur (`affair_categories` de la
 * réponse de liste, ou des données de sélection) : rien n'est affiché tant que le
 * référentiel est vide, l'écran reste alors exactement celui d'avant.
 */

/** Libellé affichable de la catégorie d'une ligne, ou null. */
export function affairCategoryLabel(row) {
    return row?.affair_category?.label ?? null;
}

/**
 * Valeur de filtre utilisable : un identifiant qui n'est plus dans la liste (catégorie
 * supprimée en base, filtre mémorisé sur une autre instance) retombe sur « toutes ».
 */
export function resolveCategoryFilter(value, categories) {
    if (value === '' || value === null || value === undefined) return '';
    return (categories ?? []).some(c => String(c.id) === String(value)) ? String(value) : '';
}

/** Colonne DataTable : badge du libellé, filtre texte de la page, masquable. */
export function affairCategoryColumn(trans) {
    return {
        key: 'affair_category',
        label: trans.affair_category ?? 'Catégorie',
        render: row => {
            const label = affairCategoryLabel(row);
            return label
                ? <span className="badge text-bg-secondary" title={row.affair_category.code}>{label}</span>
                : <span className="text-body-secondary">—</span>;
        },
        filterValue: affairCategoryLabel,
        filter: 'text',
    };
}

/** Filtre serveur de la barre d'outils. */
export function AffairCategoryFilter({ categories, value, onChange, trans }) {
    const list = categories ?? [];
    const resolved = resolveCategoryFilter(value, list);

    // Une catégorie mémorisée qui n'existe plus ne doit pas vider la liste.
    useEffect(() => {
        if (list.length > 0 && String(value ?? '') !== resolved) onChange('');
    }, [list.length, value, resolved]);

    if (list.length === 0) return null;

    return (
        <select
            className="form-select form-select-sm flex-shrink-0"
            style={{ width: 'auto', maxWidth: 220 }}
            aria-label={trans.affair_category ?? 'Catégorie'}
            value={resolved}
            onChange={e => onChange(e.target.value)}
        >
            <option value="">{trans.all_affair_categories ?? '—'}</option>
            {list.map(c => (
                <option key={c.id} value={c.id}>
                    {c.label}{c.active === false && trans.affair_category_inactive ? ` (${trans.affair_category_inactive})` : ''}
                </option>
            ))}
        </select>
    );
}

/** Champ de saisie des fenêtres « Nouveau… ». */
export function AffairCategoryField({ categories, value, onChange, trans, error, className = 'form-select', labelClassName }) {
    if (!categories || categories.length === 0) return null;

    return (
        <>
            <label className={labelClassName}>{trans.affair_category ?? 'Catégorie'}</label>
            <select className={`${className} ${error ? 'is-invalid' : ''}`} value={value ?? ''} onChange={e => onChange(e.target.value)}>
                <option value="">{trans.no_affair_category ?? '—'}</option>
                {categories.map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
            </select>
            {error && <div className="invalid-feedback">{error}</div>}
        </>
    );
}
