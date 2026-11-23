import React from 'react';

/**
 * StatCard — AdminLTE small-box style
 *
 * Props:
 *   value   string|number  — main figure
 *   label   string         — description below the figure
 *   icon    string         — FontAwesome class e.g. "fa-calculator"
 *   theme   string         — AdminLTE color: info|primary|success|warning|danger|teal|purple|orange|lime|...
 *   url     string         — optional link on "View details"
 *   urlText string         — link label (default "→")
 *   trend   number         — optional % change shown as ↑ / ↓ badge
 *   fill    boolean        — occupe toute la hauteur du parent, sans marge basse
 *                            (lien « Voir détail » collé en bas) : widgets du tableau de bord
 */
export default function StatCard({ value, label, icon, theme = 'info', url, urlText = '→', trend, fill = false }) {
    return (
        <div
            className={`small-box text-bg-${theme}`}
            style={fill ? { height: '100%', marginBottom: 0, display: 'flex', flexDirection: 'column' } : undefined}
        >
            <div className="inner" style={fill ? { flex: '1 1 auto' } : undefined}>
                {/* Tailles d'AdminLTE (h3 2,2 rem, p 1 rem) ; retour à la ligne permis
                    pour qu'un montant long ne soit pas coupé dans une colonne étroite. */}
                <h3 style={{ whiteSpace: 'normal', wordBreak: 'break-word' }}>{value ?? '—'}</h3>
                <p>{label}</p>
            </div>
            <i className={`small-box-icon fas ${icon}`} aria-hidden="true" />
            {trend !== undefined && trend !== null && (
                <span
                    className={`badge text-bg-${trend >= 0 ? 'success' : 'danger'}`}
                    style={{ position: 'absolute', top: 8, right: 8, fontSize: '0.7rem' }}
                >
                    {trend >= 0 ? '↑' : '↓'} {Math.abs(trend)}%
                </span>
            )}
            {url && (
                <a href={url} className="small-box-footer link-underline-opacity-0 link-underline-opacity-50-hover">
                    {urlText} <i className="fas fa-arrow-circle-right" />
                </a>
            )}
        </div>
    );
}
