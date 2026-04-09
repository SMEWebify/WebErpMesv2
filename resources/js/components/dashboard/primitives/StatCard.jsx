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
 */
export default function StatCard({ value, label, icon, theme = 'info', url, urlText = '→', trend }) {
    return (
        <div className={`small-box bg-${theme}`}>
            <div className="inner">
                <h3 style={{ fontSize: '1.4rem', wordBreak: 'break-word' }}>{value ?? '—'}</h3>
                <p style={{ fontSize: '0.85rem' }}>{label}</p>
            </div>
            <div className="icon">
                <i className={`fas ${icon}`} />
            </div>
            {trend !== undefined && trend !== null && (
                <span
                    className={`badge badge-${trend >= 0 ? 'success' : 'danger'}`}
                    style={{ position: 'absolute', top: 8, right: 8, fontSize: '0.7rem' }}
                >
                    {trend >= 0 ? '↑' : '↓'} {Math.abs(trend)}%
                </span>
            )}
            {url && (
                <a href={url} className="small-box-footer">
                    {urlText} <i className="fas fa-arrow-circle-right" />
                </a>
            )}
        </div>
    );
}
