import React, { useState } from 'react';

/**
 * DonutChart — SVG donut générique, sans dépendance externe
 *
 * Props:
 *   data         Array<{ label: string, value: number, color: string }>
 *   centerLabel  string  — texte sous le total au centre (ex: "Total")
 *   size         number  — viewBox px (défaut 220)
 *   showLegend   bool    — afficher la légende sous le donut (défaut true)
 */
export default function DonutChart({ data = [], centerLabel = '', size = 220, showLegend = true }) {
    const [hovered, setHovered] = useState(null);

    const items = data.filter(i => (i.value ?? 0) > 0);
    const total = items.reduce((s, i) => s + i.value, 0);

    if (!total) {
        return <p className="text-muted text-center small py-3">—</p>;
    }

    const R = size * 0.36;   // outer radius
    const r = size * 0.2;    // inner radius (donut hole)
    const cx = size / 2;
    const cy = size / 2;

    let angle = -Math.PI / 2;
    const slices = items.map((item) => {
        const sweep = (item.value / total) * 2 * Math.PI;
        const x1 = cx + R * Math.cos(angle);
        const y1 = cy + R * Math.sin(angle);
        angle += sweep;
        const x2 = cx + R * Math.cos(angle);
        const y2 = cy + R * Math.sin(angle);
        const ix1 = cx + r * Math.cos(angle);
        const iy1 = cy + r * Math.sin(angle);
        const ix2 = cx + r * Math.cos(angle - sweep);
        const iy2 = cy + r * Math.sin(angle - sweep);
        const large = sweep > Math.PI ? 1 : 0;
        const midAngle = angle - sweep / 2;
        return {
            ...item,
            path: `M ${x1} ${y1} A ${R} ${R} 0 ${large} 1 ${x2} ${y2} L ${ix1} ${iy1} A ${r} ${r} 0 ${large} 0 ${ix2} ${iy2} Z`,
            midAngle,
        };
    });

    const hov = hovered !== null ? slices[hovered] : null;

    return (
        <div>
            <svg viewBox={`0 0 ${size} ${size}`} style={{ width: '100%', maxWidth: size, display: 'block', margin: '0 auto' }}>
                {slices.map((s, i) => {
                    const isHov = hovered === i;
                    const ox = isHov ? Math.cos(s.midAngle) * 5 : 0;
                    const oy = isHov ? Math.sin(s.midAngle) * 5 : 0;
                    return (
                        <path
                            key={i}
                            d={s.path}
                            fill={s.color}
                            stroke="#fff"
                            strokeWidth="2"
                            transform={`translate(${ox}, ${oy})`}
                            style={{ cursor: 'pointer', transition: 'transform 0.15s ease' }}
                            onMouseEnter={() => setHovered(i)}
                            onMouseLeave={() => setHovered(null)}
                        />
                    );
                })}
                {/* Centre */}
                <text x={cx} y={cy - 6} textAnchor="middle" fontSize={size * 0.09} fontWeight="700" fill="#343a40">
                    {(hov ? hov.value : total).toFixed(2)}
                </text>
                <text x={cx} y={cy + size * 0.055} textAnchor="middle" fontSize={size * 0.042} fill="#6c757d">
                    {hov ? hov.label : centerLabel}
                </text>
            </svg>

            {showLegend && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.3rem', marginTop: '0.5rem' }}>
                    {slices.map((s, i) => (
                        <div
                            key={i}
                            className="d-flex align-items-center"
                            style={{
                                gap: '0.4rem',
                                cursor: 'default',
                                opacity: hovered !== null && hovered !== i ? 0.4 : 1,
                                transition: 'opacity 0.15s',
                            }}
                            onMouseEnter={() => setHovered(i)}
                            onMouseLeave={() => setHovered(null)}
                        >
                            <span style={{ width: 10, height: 10, borderRadius: 2, background: s.color, flexShrink: 0 }} />
                            <span style={{ fontSize: '0.78rem', flex: 1 }}>{s.label}</span>
                            <span style={{ fontSize: '0.78rem', fontWeight: 600 }}>
                                {s.value.toFixed(2)}{' '}
                                <span style={{ color: '#aaa', fontWeight: 400 }}>
                                    ({Math.round((s.value / total) * 100)}%)
                                </span>
                            </span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
