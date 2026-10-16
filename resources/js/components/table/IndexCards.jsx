import React from 'react';
import { toISODate } from './dates.js';

/**
 * Vue « cartes » des index (devis, commandes, opportunités, pistes) : une carte par
 * document, entièrement cliquable (stretched-link, donc Ctrl + clic = nouvel onglet),
 * bordure gauche à la couleur du statut.
 *
 *   <IndexCardGrid items={rows} loading={loading} emptyText={…} renderCard={row => (
 *       <IndexCard
 *           href={row.url}
 *           status="text-bg-info"            // classe du badge de statut → couleur de bordure
 *           code={row.code}                  // petit code en tête (optionnel)
 *           badges={<StatusBadge … />}       // à droite du code
 *           title={row.label}
 *           details={[{ icon: 'fa-building', text: …, title: … }]}   // lignes grises, vides ignorées
 *           meta={[{ icon: 'fa-calendar-alt', text: …, danger: true }]} // pied, à gauche
 *           amount={…}                        // pied, à droite, en gras
 *       />
 *   )} />
 */

export function todayIso() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// Nombre de jours écoulés depuis une date passée (0 si future, vide ou illisible).
export function daysPast(value, today = todayIso()) {
    const iso = toISODate(value);
    if (!iso || iso >= today) return 0;
    return Math.round((Date.parse(today) - Date.parse(iso)) / 86_400_000);
}

const isEmpty = v => v === null || v === undefined || v === '' || v === false;

function InfoLine({ icon, text, title, danger }) {
    return (
        <div className={`text-truncate${danger ? ' text-danger fw-semibold' : ''}`} title={title}>
            {icon && <i className={`fas ${icon} fa-fw me-1`} />}{text}
        </div>
    );
}

export function IndexCard({ href, status, code, badges, title, details = [], meta = [], amount }) {
    const border  = (status ?? 'text-bg-secondary').replace(/^(badge|text-bg|bg)-/, 'border-');
    const lines   = details.filter(d => d && !isEmpty(d.text));
    const footer  = meta.filter(m => m && !isEmpty(m.text));
    const hasFoot = footer.length > 0 || !isEmpty(amount);

    return (
        <div className={`card h-100 wem-card-link border-start border-4 ${border}`}>
            <div className="card-body p-3 d-flex flex-column" style={{ gap: '0.35rem' }}>
                {(code || badges) && (
                    <div className="d-flex justify-content-between align-items-center" style={{ gap: '0.5rem' }}>
                        {code ? <code className="small text-truncate">{code}</code> : <span />}
                        {badges && <div className="d-flex flex-wrap justify-content-end" style={{ gap: '0.25rem' }}>{badges}</div>}
                    </div>
                )}

                <a href={href} className="stretched-link text-body text-decoration-none fw-bold wem-clamp-2"
                   title={typeof title === 'string' ? title : undefined}>
                    {isEmpty(title) ? (code || '—') : title}
                </a>

                {lines.length > 0 && (
                    <div className="small text-body-secondary">
                        {lines.map((d, i) => <InfoLine key={i} {...d} />)}
                    </div>
                )}

                {hasFoot && (
                    <div className="mt-auto pt-2 border-top d-flex justify-content-between align-items-end" style={{ gap: '0.5rem' }}>
                        <div className="small text-body-secondary lh-sm" style={{ minWidth: 0 }}>
                            {footer.map((m, i) => <InfoLine key={i} {...m} />)}
                        </div>
                        <div className="fw-bold text-end" style={{ whiteSpace: 'nowrap' }}>
                            {isEmpty(amount) ? <span className="text-body-secondary fw-normal">—</span> : amount}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

export function IndexCardGrid({ items, loading, emptyText, renderCard, rowKey = 'id' }) {
    if (loading) return <div className="text-center py-4"><i className="fas fa-spinner fa-spin" /></div>;
    if (items.length === 0) return <div className="text-center text-muted py-3">{emptyText ?? '—'}</div>;

    return (
        <div className="row g-3 mb-3">
            {items.map(item => (
                <div key={item[rowKey]} className="col-sm-6 col-lg-4 col-xxl-3">
                    {renderCard(item)}
                </div>
            ))}
        </div>
    );
}
