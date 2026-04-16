import React, { useState } from 'react';

// ─── Helpers ──────────────────────────────────────────────────────────────────

function niceMax(value) {
    if (value <= 0) return 100;
    const exp = Math.pow(10, Math.floor(Math.log10(value)));
    return Math.ceil(value / exp) * exp;
}

function shortNum(v) {
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + 'M';
    if (v >= 1_000) return Math.round(v / 1_000) + 'k';
    return Math.round(v).toString();
}

function formatTooltip(value, currency, locale) {
    if (!currency) return shortNum(value);
    try {
        return new Intl.NumberFormat(locale || 'fr-FR', {
            style: 'currency',
            currency,
            maximumFractionDigits: 0,
        }).format(value);
    } catch {
        return shortNum(value);
    }
}

// ─── LineChart ────────────────────────────────────────────────────────────────

/**
 * LineChart — SVG multi-séries générique, sans dépendance externe
 *
 * Props:
 *   labels    string[]         — étiquettes axe X (ex: ['Jan', 'Fév', ...])
 *   series    Array<{
 *               label: string,
 *               color: string,
 *               values: number[],
 *               area?: bool,    — remplissage sous la courbe (défaut false)
 *               dashed?: bool,  — ligne en pointillés (défaut false)
 *             }>
 *   currency  string           — code devise pour le tooltip (ex: 'EUR'), null = chiffre brut
 *   locale    string           — locale Intl (défaut 'fr-FR')
 *   height    number           — hauteur SVG px (défaut 240)
 *   yTicks    number           — nb graduations Y (défaut 4)
 *   showLegend bool            — afficher la légende (défaut true)
 */
export default function LineChart({
    labels = [],
    series = [],
    currency = null,
    locale = 'fr-FR',
    height = 240,
    yTicks = 4,
    showLegend = true,
}) {
    const [hovered, setHovered] = useState(null); // { seriesIdx, pointIdx }

    const n = labels.length;
    if (n < 2 || series.length === 0) {
        return <p className="text-muted text-center small py-3">—</p>;
    }

    const W = 560;
    const H = height ?? 240;
    const PAD = { top: 16, right: 16, bottom: 40, left: 52 };
    const plotW = W - PAD.left - PAD.right;
    const plotH = H - PAD.top - PAD.bottom;

    const allValues = series.flatMap(s => s.values ?? []);
    const maxVal = niceMax(Math.max(...allValues, 1));

    const xPos = (i) => PAD.left + (i / (n - 1)) * plotW;
    const yPos = (v) => PAD.top + plotH - (Math.max(0, v) / maxVal) * plotH;

    return (
        <div style={{ height: '100%', display: 'flex', flexDirection: 'column' }}>
            <svg viewBox={`0 0 ${W} ${H}`} style={{ width: '100%', flex: 1, minHeight: 0, display: 'block' }}>
                {/* Y grid + ticks */}
                {Array.from({ length: yTicks + 1 }, (_, t) => {
                    const v = (maxVal / yTicks) * t;
                    const yp = yPos(v);
                    return (
                        <g key={t}>
                            <line x1={PAD.left} y1={yp} x2={W - PAD.right} y2={yp} stroke="#e9ecef" strokeWidth="1" />
                            <text x={PAD.left - 4} y={yp + 4} textAnchor="end" fontSize="8" fill="#6c757d">
                                {shortNum(v)}
                            </text>
                        </g>
                    );
                })}

                {/* Series */}
                {series.map((s, si) => {
                    const vals = s.values ?? [];
                    const pts = vals.map((v, i) => ({ x: xPos(i), y: yPos(v), v }));
                    const lineStr = pts.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');
                    const areaPath = pts.length
                        ? `${lineStr} L ${pts[pts.length - 1].x} ${PAD.top + plotH} L ${pts[0].x} ${PAD.top + plotH} Z`
                        : '';

                    const color = s.color ?? '#3c8dbc';
                    const hex = color.replace(/rgba?\(.*\)/, '#888');

                    return (
                        <g key={si}>
                            {/* Area fill */}
                            {s.area && (
                                <path
                                    d={areaPath}
                                    fill={color}
                                    opacity="0.12"
                                />
                            )}
                            {/* Line */}
                            <path
                                d={lineStr}
                                fill="none"
                                stroke={color}
                                strokeWidth="2"
                                strokeLinejoin="round"
                                strokeDasharray={s.dashed ? '6 3' : undefined}
                            />
                            {/* Points */}
                            {pts.map((p, pi) => {
                                const isHov = hovered?.seriesIdx === si && hovered?.pointIdx === pi;
                                return (
                                    <circle
                                        key={pi}
                                        cx={p.x}
                                        cy={p.y}
                                        r={isHov ? 5 : 3}
                                        fill={color}
                                        stroke="#fff"
                                        strokeWidth="1.5"
                                        style={{ cursor: 'default', transition: 'r 0.1s' }}
                                        onMouseEnter={() => setHovered({ seriesIdx: si, pointIdx: pi })}
                                        onMouseLeave={() => setHovered(null)}
                                    />
                                );
                            })}
                        </g>
                    );
                })}

                {/* Tooltip */}
                {hovered !== null && (() => {
                    const s = series[hovered.seriesIdx];
                    const val = (s?.values ?? [])[hovered.pointIdx];
                    if (val === undefined || val === 0) return null;
                    const rawX = xPos(hovered.pointIdx);
                    const rawY = yPos(val);
                    const bw = 80, bh = 28;
                    const bx = Math.min(Math.max(rawX - bw / 2, PAD.left), W - PAD.right - bw);
                    return (
                        <g>
                            <rect x={bx} y={rawY - bh - 6} width={bw} height={bh} rx="4" fill="#343a40" opacity="0.88" />
                            <text x={bx + bw / 2} y={rawY - bh + 9} textAnchor="middle" fontSize="7.5" fill="#adb5bd">
                                {s.label}
                            </text>
                            <text x={bx + bw / 2} y={rawY - 9} textAnchor="middle" fontSize="8.5" fontWeight="600" fill="#fff">
                                {formatTooltip(val, currency, locale)}
                            </text>
                        </g>
                    );
                })()}

                {/* X labels */}
                {labels.map((lbl, i) => (
                    <text
                        key={i}
                        x={xPos(i)}
                        y={PAD.top + plotH + 16}
                        textAnchor="middle"
                        fontSize="8"
                        fill="#6c757d"
                    >
                        {lbl}
                    </text>
                ))}
            </svg>

            {/* Légende */}
            {showLegend && series.length > 1 && (
                <div
                    style={{
                        display: 'flex',
                        flexWrap: 'wrap',
                        gap: '0.6rem 1.2rem',
                        justifyContent: 'center',
                        marginTop: '0.4rem',
                    }}
                >
                    {series.map((s, i) => (
                        <div key={i} className="d-flex align-items-center" style={{ gap: '0.35rem' }}>
                            <span
                                style={{
                                    width: 20,
                                    height: 3,
                                    background: s.dashed ? 'transparent' : (s.color ?? '#888'),
                                    borderRadius: 2,
                                    flexShrink: 0,
                                    borderBottom: s.dashed ? '2px dashed ' + (s.color ?? '#888') : undefined,
                                }}
                            />
                            <span style={{ fontSize: '0.75rem', color: '#495057' }}>{s.label}</span>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
