import React, { useState } from 'react';
import useMediaQuery from './useMediaQuery.js';
import { MOBILE_QUERY } from './DataTable.jsx';

/**
 * Groupes de filtres d'une barre d'outils (statuts, types, priorités…).
 *
 * Sur PC : les enfants sont rendus tels quels, à leur place — le HTML ne change pas.
 * Sous `md` : ils sont repliés derrière un bouton « Filtres » (cible de 44 px) qui indique
 * le nombre de filtres actifs ; dépliés, ils occupent toute la largeur sous la barre.
 *
 * `count` : nombre de filtres qui restreignent réellement la liste (0 = aucun badge).
 */
export default function MobileFilters({ count = 0, trans = {}, children }) {
    const isMobile = useMediaQuery(MOBILE_QUERY);
    const [open, setOpen] = useState(false);

    if (!isMobile) return <>{children}</>;

    return (
        <>
            <button
                type="button"
                className="btn btn-outline-secondary"
                style={{ minHeight: 44 }}
                aria-expanded={open}
                onClick={() => setOpen(o => !o)}
            >
                <i className="fas fa-filter mr-1" />
                {trans.filters ?? 'Filtres'}
                {count > 0 && <span className="badge badge-primary ml-1">{count}</span>}
                <i className={`fas fa-chevron-${open ? 'up' : 'down'} ml-1`} />
            </button>
            {open && (
                <div className="wem-mobile-filters w-100 d-flex flex-column" style={{ gap: '0.5rem' }}>
                    {children}
                </div>
            )}
        </>
    );
}
