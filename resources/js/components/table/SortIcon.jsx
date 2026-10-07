import React from 'react';

/**
 * Icône de tri d'un en-tête de colonne.
 * `field` absent (colonne non triable) → icône neutre.
 * `size="sm"` : variante réduite (0.7rem) utilisée par l'index Sociétés.
 */
export default function SortIcon({ field, sortField, sortAsc, size }) {
    const style = size === 'sm' ? { fontSize: '0.7rem' } : undefined;
    if (!field || field !== sortField) return <i className="fas fa-sort text-muted ml-1" style={style} />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} style={style} />;
}
