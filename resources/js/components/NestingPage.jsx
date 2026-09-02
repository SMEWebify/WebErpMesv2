import { useState, useMemo, useCallback, useEffect } from 'react';
import { geometryFor } from '../lib/nesting/geometry';
import { formatQty } from '../utils';

// ─── Formats standards proposés ─────────────────────────────────────────────
const DEFAULT_FORMATS = [
    { id: 'f1', label: '4000 × 2000', x: 4000, y: 2000, selected: true },
    { id: 'f2', label: '3000 × 1500', x: 3000, y: 1500, selected: true },
    { id: 'f3', label: '2500 × 1250', x: 2500, y: 1250, selected: true },
    { id: 'f4', label: '2000 × 1000', x: 2000, y: 1000, selected: true },
];

const DEFAULT_BAR_LENGTHS = [
    { id: 'b1', label: '3000 mm', length: 3000, selected: true },
    { id: 'b2', label: '6000 mm', length: 6000, selected: true },
    { id: 'b3', label: '12000 mm', length: 12000, selected: false },
];

const DEFAULT_KERF_MM = 3;

const profileLabel = g => {
    if (!g.y_size) return '—';
    return g.z_size ? `${g.y_size} × ${g.z_size}` : `Ø ${g.y_size}`;
};

// ─── Palette ────────────────────────────────────────────────────────────────
const COLORS = [
    '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f',
    '#edc948', '#b07aa1', '#ff9da7', '#9c755f', '#bab0ac',
];
const pieceColor = i => COLORS[i % COLORS.length];

// ─── Shelf packing 2D multi-formats ─────────────────────────────────────────
// Same algorithm as the previous incarnation, kept local so nothing else in the
// project depends on the internal shape of a "sheet".
function nestSheets(pieces, formats) {
    const rects = [];
    pieces.forEach((p, pi) => {
        for (let i = 0; i < p.qty; i++) {
            rects.push({
                id: p.line_id,
                pieceIndex: pi,
                label: p.label,
                code: p.product_code,
                w: p.bb.x,
                h: p.bb.y,
                colorIdx: pi,
                geometry: p.geometry,
            });
        }
    });
    rects.sort((a, b) => b.h - a.h || b.w - a.w);

    const sheets = [];

    for (const rect of rects) {
        let placed = false;

        for (const sheet of sheets) {
            const { format } = sheet;
            if (rect.w > format.x || rect.h > format.y) continue;
            if (sheet.rowX + rect.w <= format.x) {
                sheet.placements.push({ ...rect, px: sheet.rowX, py: sheet.rowY });
                sheet.rowX += rect.w;
                sheet.rowH = Math.max(sheet.rowH, rect.h);
                placed = true; break;
            } else if (sheet.rowY + sheet.rowH + rect.h <= format.y) {
                sheet.rowY += sheet.rowH; sheet.rowH = rect.h; sheet.rowX = rect.w;
                sheet.placements.push({ ...rect, px: 0, py: sheet.rowY });
                placed = true; break;
            }
        }

        if (!placed) {
            const fitting = formats.filter(f => rect.w <= f.x && rect.h <= f.y);
            if (!fitting.length) continue;
            const best = fitting.reduce((a, b) => a.x * a.y <= b.x * b.y ? a : b);
            sheets.push({
                format: best,
                placements: [{ ...rect, px: 0, py: 0 }],
                rowY: 0, rowH: rect.h, rowX: rect.w,
            });
        }
    }

    return sheets.map(s => ({
        format: s.format,
        placements: s.placements,
        usedArea: s.placements.reduce((sum, p) => sum + p.w * p.h, 0),
        totalArea: s.format.x * s.format.y,
    }));
}

// ─── 1D packing (barres / tubes) — First-Fit Decreasing avec kerf ──────────
// Pour chaque nouvelle barre, on choisit la longueur standard la plus courte
// qui accepte encore la pièce en cours (moins de chutes qu'un choix par défaut
// systématiquement sur 6000 mm).
function nestBars(pieces, barLengths, kerf) {
    if (!barLengths.length) return [];

    const items = [];
    pieces.forEach((p, pi) => {
        for (let i = 0; i < p.qty; i++) {
            items.push({
                id: p.line_id,
                pieceIndex: pi,
                label: p.label,
                code: p.product_code,
                length: p.length,
                colorIdx: pi,
            });
        }
    });
    items.sort((a, b) => b.length - a.length);

    const maxBarLen = Math.max(...barLengths.map(b => b.length));
    const bars = [];

    for (const item of items) {
        if (item.length > maxBarLen) continue; // trop long pour toute barre dispo

        let placed = false;
        for (const bar of bars) {
            const needsKerf = bar.placements.length > 0;
            const extra = needsKerf ? kerf : 0;
            if (bar.used + extra + item.length <= bar.length) {
                bar.placements.push({ ...item, px: bar.used + extra });
                bar.used += extra + item.length;
                placed = true;
                break;
            }
        }

        if (!placed) {
            const fitting = barLengths.filter(b => item.length <= b.length);
            const best = fitting.reduce((a, b) => a.length <= b.length ? a : b);
            bars.push({
                length: best.length,
                label: best.label,
                used: item.length,
                placements: [{ ...item, px: 0 }],
            });
        }
    }

    return bars.map(b => ({
        length: b.length,
        label: b.label,
        placements: b.placements,
        usedLength: b.used,
    }));
}

// ─── Rendu d'une pièce placée (forme reconstruite si dispo, sinon BB) ───────
function PieceShape({ placement, scale }) {
    const { px, py, w, h, colorIdx, code, label, geometry } = placement;
    const fill  = pieceColor(colorIdx);
    const wPx   = w * scale;
    const hPx   = h * scale;
    const showLabel = wPx > 30 && hPx > 14;
    const hasShape  = geometry?.pathD;

    return (
        <g>
            {!hasShape && (
                <rect
                    x={px * scale}
                    y={py * scale}
                    width={wPx}
                    height={hPx}
                    fill={fill}
                    fillOpacity="0.55"
                    stroke="#222"
                    strokeWidth="0.5"
                />
            )}

            {geometry && (
                <svg
                    x={px * scale}
                    y={py * scale}
                    width={wPx}
                    height={hPx}
                    viewBox={`${geometry.minX} ${geometry.minY} ${geometry.bb.x} ${geometry.bb.y}`}
                    preserveAspectRatio="none"
                    style={{ overflow: 'hidden' }}
                >
                    {/* DXF's Y axis points up; flip for a natural on-screen preview. */}
                    <g transform={geometry.source === 'dxf'
                        ? `translate(0, ${geometry.bb.y + 2 * geometry.minY}) scale(1, -1)`
                        : undefined}>
                        {hasShape && (
                            <path
                                d={geometry.pathD}
                                fill={fill}
                                fillOpacity="0.55"
                                fillRule="evenodd"
                                stroke="#222"
                                strokeWidth={0.6}
                                vectorEffect="non-scaling-stroke"
                            />
                        )}
                        {/* Open segments and non-closed primitives on top for visual context */}
                        {hasShape
                            ? (geometry.openLines || []).map((prim, i) => renderPrimitive(prim, `o${i}`))
                            : geometry.primitives.map((prim, i) => renderPrimitive(prim, i))}
                    </g>
                </svg>
            )}

            {showLabel && (
                <text
                    x={px * scale + 3}
                    y={py * scale + 12}
                    fontSize="9"
                    fill="#111"
                    style={{ pointerEvents: 'none' }}
                >
                    {code || label}
                </text>
            )}
        </g>
    );
}

function renderPrimitive(p, key) {
    const stroke = '#111';
    const sw = 'max(0.5, 1)';

    switch (p.kind) {
        case 'line':
            return <line key={key} x1={p.x1} y1={p.y1} x2={p.x2} y2={p.y2}
                stroke={stroke} strokeWidth={0.8} vectorEffect="non-scaling-stroke" />;
        case 'polyline': {
            const pts = p.points.map(pt => `${pt.x},${pt.y}`).join(' ');
            return p.closed
                ? <polygon key={key} points={pts} fill="none" stroke={stroke} strokeWidth={0.8} vectorEffect="non-scaling-stroke" />
                : <polyline key={key} points={pts} fill="none" stroke={stroke} strokeWidth={0.8} vectorEffect="non-scaling-stroke" />;
        }
        case 'circle':
            return <circle key={key} cx={p.cx} cy={p.cy} r={p.r}
                fill="none" stroke={stroke} strokeWidth={0.8} vectorEffect="non-scaling-stroke" />;
        case 'arc': {
            const largeArc = Math.abs(p.a1 - p.a0) > Math.PI ? 1 : 0;
            const x0 = p.cx + p.r * Math.cos(p.a0);
            const y0 = p.cy + p.r * Math.sin(p.a0);
            const x1 = p.cx + p.r * Math.cos(p.a1);
            const y1 = p.cy + p.r * Math.sin(p.a1);
            return <path key={key}
                d={`M ${x0} ${y0} A ${p.r} ${p.r} 0 ${largeArc} 1 ${x1} ${y1}`}
                fill="none" stroke={stroke} strokeWidth={0.8} vectorEffect="non-scaling-stroke" />;
        }
        case 'svg-inner':
            return <g key={key} dangerouslySetInnerHTML={{ __html: p.html }} />;
        default:
            return null;
    }
}

// ─── SVG tôle ───────────────────────────────────────────────────────────────
function SheetSvg({ sheet, index }) {
    const { format, placements, usedArea, totalArea } = sheet;
    const displayW = 520;
    const scale = displayW / format.x;
    const displayH = format.y * scale;
    const usage = Math.round(usedArea / totalArea * 100);

    return (
        <div className="mb-2 border p-1 bg-white">
            <div className="d-flex justify-content-between align-items-center px-1 mb-1">
                <small><strong>Tôle {index + 1}</strong> - {format.label}</small>
                <small className={usage < 40 ? 'text-warning' : 'text-success'}>
                    Utilisation {usage}%
                </small>
            </div>
            <svg width={displayW} height={displayH} style={{ background: '#f6f6f6', border: '1px solid #999' }}>
                {placements.map((p, i) => (
                    <PieceShape key={i} placement={p} scale={scale} />
                ))}
            </svg>
        </div>
    );
}

// ─── Groupe rendu par NestEngine (forme exacte, calcul serveur asynchrone) ──
function NestEngineGroupPanel({ group, service }) {
    const [expanded, setExpanded] = useState(false);
    const [meta, setMeta] = useState({ status: 'pending', files: [] });
    const jobId = group.job_id;

    useEffect(() => {
        if (!jobId) return;
        let cancelled = false;

        const poll = async () => {
            try {
                const res = await window.axios.get(`/nesting/engine/status/${jobId}`);
                if (cancelled) return;
                setMeta(res.data);
                if (res.data.status === 'pending' || res.data.status === 'running') {
                    setTimeout(poll, 2000);
                }
            } catch (e) {
                if (cancelled) return;
                setMeta({ status: 'error', error: e.response?.data?.error || e.message });
            }
        };
        poll();

        return () => { cancelled = true; };
    }, [jobId]);

    const totalPieces = (group.pieces || []).reduce((s, p) => s + p.qty, 0);
    const sheetsCount = meta.files?.length || 0;
    const avgUsage = sheetsCount
        ? Math.round(meta.files.reduce((s, f) => s + (f.efficiency || 0), 0) / sheetsCount)
        : 0;
    const isRunning = meta.status === 'pending' || meta.status === 'running';

    return (
        <div className="mb-3 border rounded">
            <div className="d-flex align-items-center p-2 bg-light" style={{ cursor: 'pointer' }} onClick={() => setExpanded(!expanded)}>
                <i className={`fas fa-chevron-${expanded ? 'down' : 'right'} mr-2`} />
                <strong className="mr-2">{group.material}</strong>
                <span className="badge badge-secondary mr-2">{group.thickness} mm</span>
                {service && (
                    <span className="badge mr-2" style={{ background: service.service_color || '#6c757d', color: '#fff' }}>
                        {service.service_label}
                    </span>
                )}
                <span className="badge badge-info mr-2">{formatQty(totalPieces)} pièce(s)</span>
                {isRunning ? (
                    <span className="badge badge-warning mr-2">
                        <i className="fas fa-spinner fa-spin mr-1" />{meta.status}
                    </span>
                ) : meta.status === 'done' ? (
                    <>
                        <span className="badge badge-primary mr-2">{sheetsCount} tôle(s)</span>
                        <span className={`badge mr-2 ${avgUsage < 40 ? 'badge-warning' : 'badge-success'}`}>{avgUsage}%</span>
                    </>
                ) : meta.status === 'error' ? (
                    <span className="badge badge-danger mr-2">Erreur</span>
                ) : null}
                <span className="ml-auto badge badge-dark">
                    <i className="fas fa-star mr-1" />Forme exacte
                </span>
            </div>

            {expanded && (
                <div className="p-2">
                    {meta.status === 'error' && (
                        <div className="alert alert-danger py-1 px-2 small mb-2">
                            NestEngine : {meta.error || 'erreur inconnue'}
                        </div>
                    )}

                    {meta.status === 'done' && meta.unplaced?.length > 0 && (
                        <div className="alert alert-warning py-1 px-2 small mb-2">
                            {meta.unplaced.length} pièce(s) non placée(s) : {meta.unplaced.slice(0, 8).join(', ')}
                            {meta.unplaced.length > 8 && ` … et ${meta.unplaced.length - 8} de plus`}
                        </div>
                    )}

                    {isRunning && (
                        <div className="text-center py-3 text-muted">
                            <i className="fas fa-spinner fa-spin fa-2x" />
                            <div className="mt-2 small">Imbrication en cours ({group.parts_prepared || '?'} pièce(s), format {group.sheet_format?.x} × {group.sheet_format?.y} mm)</div>
                        </div>
                    )}

                    {meta.status === 'done' && (meta.files || []).map(f => {
                        const num = f.sheetIdx + 1;
                        return (
                            <div key={num} className="mb-2 border p-1 bg-white">
                                <div className="d-flex justify-content-between align-items-center px-1 mb-1">
                                    <small>
                                        <strong>Tôle {num}</strong>
                                        <span className="text-muted ml-2">{f.placed} pièce(s)</span>
                                    </small>
                                    <div>
                                        <small className={f.efficiency < 40 ? 'text-warning' : 'text-success'}>
                                            Utilisation {Math.round(f.efficiency)}%
                                        </small>
                                        <a
                                            className="btn btn-sm btn-outline-secondary ml-2 py-0"
                                            href={`/nesting/engine/download/${jobId}/${num}`}
                                            title="Télécharger le DXF"
                                        >
                                            <i className="fas fa-download" />
                                        </a>
                                    </div>
                                </div>
                                <img
                                    src={`/nesting/engine/preview/${jobId}/${num}`}
                                    alt={`Tôle ${num}`}
                                    style={{ width: '100%', maxWidth: 720, background: '#f6f6f6', border: '1px solid #999' }}
                                />
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}

// ─── Groupe (matière + épaisseur) ───────────────────────────────────────────
function GroupPanel({ group, formats, service }) {
    const [expanded, setExpanded] = useState(false);
    const pieces = group.piecesWithBB;
    const results = useMemo(
        () => (pieces.length && formats.length) ? nestSheets(pieces, formats) : [],
        [pieces, formats]
    );

    const totalPieces = pieces.reduce((s, p) => s + p.qty, 0);
    const byFormat = results.reduce((acc, r) => {
        acc[r.format.label] = (acc[r.format.label] || 0) + 1;
        return acc;
    }, {});
    const avgUsage = results.length
        ? Math.round(results.reduce((s, r) => s + r.usedArea / r.totalArea, 0) / results.length * 100)
        : 0;

    return (
        <div className="mb-3 border rounded">
            <div
                className="d-flex align-items-center p-2 bg-light"
                style={{ cursor: 'pointer' }}
                onClick={() => setExpanded(!expanded)}
            >
                <i className={`fas fa-chevron-${expanded ? 'down' : 'right'} mr-2`} />
                <strong className="mr-2">{group.material}</strong>
                <span className="badge badge-secondary mr-2">{group.thickness} mm</span>
                {service && (
                    <span
                        className="badge mr-2"
                        style={{
                            background: service.service_color || '#6c757d',
                            color: '#fff',
                        }}
                    >
                        {service.service_label}
                    </span>
                )}
                <span className="badge badge-info mr-2">{formatQty(totalPieces)} pièce(s)</span>
                <span className="badge badge-primary mr-2">{results.length} tôle(s)</span>
                {results.length > 0 && (
                    <span className={`badge mr-2 ${avgUsage < 40 ? 'badge-warning' : 'badge-success'}`}>
                        {avgUsage}%
                    </span>
                )}
                <span className="ml-auto text-muted small">
                    {Object.entries(byFormat).map(([f, n]) => `${n}× ${f}`).join('  |  ')}
                </span>
            </div>

            {expanded && (
                <div className="p-2">
                    <table className="table table-sm table-striped mb-2" style={{ fontSize: '0.82em' }}>
                        <thead className="thead-light">
                            <tr>
                                <th>Article</th>
                                <th>Commande</th>
                                <th className="text-right">X mm</th>
                                <th className="text-right">Y mm</th>
                                <th className="text-right">Qté</th>
                                <th className="text-center">Src</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pieces.map((p, i) => (
                                <tr key={p.line_id}>
                                    <td>
                                        <span style={{
                                            display: 'inline-block',
                                            width: 8, height: 8,
                                            background: pieceColor(i),
                                            borderRadius: 2,
                                            marginRight: 4,
                                        }} />
                                        <code>{p.product_code}</code> - {p.label}
                                    </td>
                                    <td><a href={`/orders/${p.order_id}`} target="_blank" rel="noreferrer">{p.order_code}</a></td>
                                    <td className="text-right">{Math.round(p.bb.x)}</td>
                                    <td className="text-right">{Math.round(p.bb.y)}</td>
                                    <td className="text-right">{formatQty(p.qty)}</td>
                                    <td className="text-center">
                                        <small className="text-muted" title={p.bb.source}>
                                            {p.bb.source === 'line' ? '📐' : '📄'}
                                        </small>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {results.length === 0 && (
                        <div className="alert alert-warning py-1 px-2 small">
                            Aucun format sélectionné ne peut accueillir ces pièces.
                        </div>
                    )}

                    {results.map((s, i) => <SheetSvg key={i} sheet={s} index={i} />)}
                </div>
            )}
        </div>
    );
}

// ─── Rendu SVG d'une barre imbriquée (horizontal 1D) ───────────────────────
function BarSvg({ bar, index, kerf }) {
    const { length, placements, usedLength, label } = bar;
    const displayW = 640;
    const barH    = 26;
    const scale   = displayW / length;
    const usage   = Math.round(usedLength / length * 100);
    const waste   = length - usedLength;

    return (
        <div className="mb-2 border p-1 bg-white">
            <div className="d-flex justify-content-between align-items-center px-1 mb-1">
                <small>
                    <strong>Barre {index + 1}</strong> - {label}
                    <span className="text-muted ml-2">chute {Math.round(waste)} mm</span>
                </small>
                <small className={usage < 60 ? 'text-warning' : 'text-success'}>
                    Utilisation {usage}%
                </small>
            </div>
            <svg width={displayW} height={barH + 4} style={{ background: '#f6f6f6', border: '1px solid #999' }}>
                {placements.map((p, i) => {
                    const wPx = p.length * scale;
                    const xPx = p.px * scale;
                    return (
                        <g key={i}>
                            <rect
                                x={xPx}
                                y={2}
                                width={wPx}
                                height={barH}
                                fill={pieceColor(p.colorIdx)}
                                fillOpacity="0.7"
                                stroke="#222"
                                strokeWidth="0.5"
                            />
                            {wPx > 30 && (
                                <text
                                    x={xPx + wPx / 2}
                                    y={barH / 2 + 6}
                                    fontSize="9"
                                    fill="#111"
                                    textAnchor="middle"
                                    style={{ pointerEvents: 'none' }}
                                >
                                    {Math.round(p.length)}
                                </text>
                            )}
                        </g>
                    );
                })}
                {/* chute finale (visuelle) */}
                {waste > 0 && (
                    <rect
                        x={usedLength * scale}
                        y={2}
                        width={waste * scale}
                        height={barH}
                        fill="url(#hatch)"
                        fillOpacity="0.15"
                    />
                )}
                <defs>
                    <pattern id="hatch" width="6" height="6" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                        <rect width="6" height="6" fill="#666" />
                        <line x1="0" y1="0" x2="0" y2="6" stroke="#fff" strokeWidth="2" />
                    </pattern>
                </defs>
            </svg>
        </div>
    );
}

// ─── Groupe barre (matière + profil) ────────────────────────────────────────
function BarGroupPanel({ group, barLengths, kerf, service }) {
    const [expanded, setExpanded] = useState(false);
    const pieces  = group.pieces;
    const results = useMemo(
        () => (pieces.length && barLengths.length) ? nestBars(pieces, barLengths, kerf) : [],
        [pieces, barLengths, kerf]
    );

    const totalPieces = pieces.reduce((s, p) => s + p.qty, 0);
    const totalLength = pieces.reduce((s, p) => s + p.length * p.qty, 0);
    const byLength = results.reduce((acc, r) => {
        acc[r.label] = (acc[r.label] || 0) + 1;
        return acc;
    }, {});
    const avgUsage = results.length
        ? Math.round(results.reduce((s, r) => s + r.usedLength / r.length, 0) / results.length * 100)
        : 0;

    return (
        <div className="mb-3 border rounded">
            <div
                className="d-flex align-items-center p-2 bg-light"
                style={{ cursor: 'pointer' }}
                onClick={() => setExpanded(!expanded)}
            >
                <i className={`fas fa-chevron-${expanded ? 'down' : 'right'} mr-2`} />
                <i className="fas fa-grip-lines-vertical mr-2 text-muted" title="Nesting barre / tube" />
                <strong className="mr-2">{group.material}</strong>
                <span className="badge badge-secondary mr-2">{profileLabel(group)}</span>
                {service && (
                    <span
                        className="badge mr-2"
                        style={{ background: service.service_color || '#6c757d', color: '#fff' }}
                    >
                        {service.service_label}
                    </span>
                )}
                <span className="badge badge-info mr-2">{formatQty(totalPieces)} pièce(s)</span>
                <span className="badge badge-primary mr-2">{results.length} barre(s)</span>
                {results.length > 0 && (
                    <span className={`badge mr-2 ${avgUsage < 60 ? 'badge-warning' : 'badge-success'}`}>
                        {avgUsage}%
                    </span>
                )}
                <span className="ml-auto text-muted small">
                    {Object.entries(byLength).map(([l, n]) => `${n}× ${l}`).join('  |  ')}
                    {totalLength > 0 && <span className="ml-2">total débit {Math.round(totalLength)} mm</span>}
                </span>
            </div>

            {expanded && (
                <div className="p-2">
                    <table className="table table-sm table-striped mb-2" style={{ fontSize: '0.82em' }}>
                        <thead className="thead-light">
                            <tr>
                                <th>Article</th>
                                <th>Commande</th>
                                <th className="text-right">Longueur mm</th>
                                <th className="text-right">Qté</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pieces.map((p, i) => (
                                <tr key={p.line_id}>
                                    <td>
                                        <span style={{
                                            display: 'inline-block',
                                            width: 8, height: 8,
                                            background: pieceColor(i),
                                            borderRadius: 2,
                                            marginRight: 4,
                                        }} />
                                        <code>{p.product_code}</code> - {p.label}
                                    </td>
                                    <td><a href={`/orders/${p.order_id}`} target="_blank" rel="noreferrer">{p.order_code}</a></td>
                                    <td className="text-right">{Math.round(p.length)}</td>
                                    <td className="text-right">{formatQty(p.qty)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {results.length === 0 && (
                        <div className="alert alert-warning py-1 px-2 small">
                            Aucune longueur de barre sélectionnée ne peut accueillir ces pièces.
                        </div>
                    )}

                    {results.map((b, i) => <BarSvg key={i} bar={b} index={i} kerf={kerf} />)}
                </div>
            )}
        </div>
    );
}

// ─── Animation de nesting (illustration pendant le loading) ─────────────────
function NestingAnimation() {
    const SHEET_W = 480;
    const SHEET_H = 240;

    // A curated palette of "pieces" — varied enough that shelf packing looks alive.
    const CANDIDATES = useMemo(() => [
        { w: 120, h: 80 }, { w: 90, h: 60 }, { w: 60, h: 60 }, { w: 140, h: 40 },
        { w: 100, h: 100 }, { w: 50, h: 80 }, { w: 70, h: 50 }, { w: 160, h: 30 },
        { w: 80, h: 40 }, { w: 40, h: 40 }, { w: 110, h: 55 }, { w: 45, h: 90 },
    ], []);

    const [placed, setPlaced] = useState([]);
    const stateRef = useMemo(() => ({ rowX: 0, rowY: 0, rowH: 0, idx: 0 }), []);

    useEffect(() => {
        const id = setInterval(() => {
            setPlaced(prev => {
                const c = CANDIDATES[stateRef.idx % CANDIDATES.length];
                stateRef.idx++;

                // fits on current row
                if (stateRef.rowX + c.w <= SHEET_W) {
                    const placement = {
                        x: stateRef.rowX, y: stateRef.rowY, w: c.w, h: c.h,
                        color: pieceColor(stateRef.idx),
                    };
                    stateRef.rowX += c.w;
                    stateRef.rowH = Math.max(stateRef.rowH, c.h);
                    return [...prev, placement];
                }

                // new row
                const newRowY = stateRef.rowY + stateRef.rowH;
                if (newRowY + c.h <= SHEET_H) {
                    stateRef.rowY = newRowY;
                    stateRef.rowH = c.h;
                    stateRef.rowX = c.w;
                    const placement = {
                        x: 0, y: newRowY, w: c.w, h: c.h,
                        color: pieceColor(stateRef.idx),
                    };
                    return [...prev, placement];
                }

                // sheet full — reset and start over
                stateRef.rowX = 0;
                stateRef.rowY = 0;
                stateRef.rowH = 0;
                return [];
            });
        }, 220);

        return () => clearInterval(id);
    }, [CANDIDATES, stateRef]);

    return (
        <svg
            width={SHEET_W}
            height={SHEET_H}
            style={{
                background: '#f6f6f6',
                border: '2px solid #444',
                borderRadius: 4,
                maxWidth: '100%',
            }}
        >
            {placed.map((p, i) => (
                <rect
                    key={i}
                    x={p.x}
                    y={p.y}
                    width={p.w}
                    height={p.h}
                    fill={p.color}
                    fillOpacity="0.8"
                    stroke="#222"
                    strokeWidth="0.8"
                    style={{
                        animation: 'nesting-drop 0.25s ease-out',
                        transformOrigin: `${p.x + p.w / 2}px ${p.y + p.h / 2}px`,
                    }}
                />
            ))}
            <style>{`
                @keyframes nesting-drop {
                    0%   { opacity: 0; transform: scale(0.6); }
                    100% { opacity: 1; transform: scale(1); }
                }
            `}</style>
        </svg>
    );
}

// ─── Composant principal ────────────────────────────────────────────────────
export default function NestingPage() {
    const [formats, setFormats]       = useState(DEFAULT_FORMATS);
    const [barLengths, setBarLengths] = useState(DEFAULT_BAR_LENGTHS);
    const [kerf, setKerf]             = useState(DEFAULT_KERF_MM);
    const [includeOpen, setIncludeOpen] = useState(false);
    const [services, setServices]     = useState([]);
    const [serviceIds, setServiceIds] = useState(new Set());
    const [sheetStock, setSheetStock] = useState([]);
    const [barStock, setBarStock]     = useState([]);
    const [data, setData]             = useState(null);
    const [loading, setLoading]       = useState(false);
    const [error, setError]           = useState(null);
    const [resolvingBBs, setResolvingBBs] = useState(false);
    const [progress, setProgress]     = useState({ current: 0, total: 0, label: '' });

    const activeFormats    = formats.filter(f => f.selected);
    const activeBarLengths = barLengths.filter(b => b.selected);

    const toggleFormat = id => setFormats(fs =>
        fs.map(f => f.id === id ? { ...f, selected: !f.selected } : f)
    );

    const toggleBarLength = id => setBarLengths(bs =>
        bs.map(b => b.id === id ? { ...b, selected: !b.selected } : b)
    );

    const toggleService = id => setServiceIds(prev => {
        const next = new Set(prev);
        if (next.has(id)) next.delete(id); else next.add(id);
        return next;
    });

    useEffect(() => {
        window.axios.get('/nesting/services')
            .then(r => {
                setServices(r.data);
                setServiceIds(new Set(r.data.map(s => s.id)));
            })
            .catch(err => {
                console.error('Chargement services nesting échoué', err);
                setError('Impossible de charger les moyens de débit. '
                    + (err.response?.status === 404
                        ? 'La route /nesting/services n\'existe pas côté serveur (déploiement backend requis).'
                        : 'Vérifie la console.'));
                setServices([]);
            });

        window.axios.get('/nesting/sheet-stock')
            .then(r => setSheetStock(r.data))
            .catch(err => {
                console.warn('Chargement stock tôles échoué', err);
                setSheetStock([]);
            });

        window.axios.get('/nesting/bar-stock')
            .then(r => setBarStock(r.data))
            .catch(err => {
                console.warn('Chargement stock barres échoué', err);
                setBarStock([]);
            });
    }, []);

    // Index the raw material stock by (material|thickness|format) for O(1) lookup.
    // Format matching is non-oriented: 3000×1500 also matches 1500×3000.
    const stockIndex = useMemo(() => {
        const map = new Map();
        for (const s of sheetStock) {
            const mat = s.material.toLowerCase().replace(/\s+/g, '');
            const th  = s.thickness.toFixed(3);
            const dims = [s.x_size, s.y_size].sort((a, b) => b - a);
            const key = `${mat}|${th}|${dims[0]}x${dims[1]}`;
            const cur = map.get(key) || { stock_qty: 0, incoming_qty: 0, products: [] };
            cur.stock_qty += s.stock_qty;
            cur.incoming_qty += s.incoming_qty || 0;
            cur.products.push({ id: s.id, code: s.code, label: s.label });
            map.set(key, cur);
        }
        return map;
    }, [sheetStock]);

    const lookupStock = useCallback((material, thickness, formatLabel) => {
        const mat = material.toLowerCase().replace(/\s+/g, '');
        const th  = Number(thickness).toFixed(3);
        // formatLabel comes in like "3000 × 1500" — parse and sort
        const parts = formatLabel.split(/\s*×\s*/).map(Number);
        if (parts.length !== 2 || parts.some(isNaN)) return null;
        const dims = parts.sort((a, b) => b - a);
        return stockIndex.get(`${mat}|${th}|${dims[0]}x${dims[1]}`) || null;
    }, [stockIndex]);

    const handleCompute = useCallback(async () => {
        setLoading(true);
        setError(null);
        setData(null);
        setProgress({ current: 0, total: 0, label: 'Analyse des commandes…' });

        try {
            const res = await window.axios.post('/nesting/compute', {
                include_open: includeOpen,
                service_ids: Array.from(serviceIds),
            });

            setResolvingBBs(true);

            // Count total pieces for the progress bar
            const totalPieces = res.data.services.reduce(
                (s, svc) => s + svc.groups.reduce((ss, g) => ss + g.pieces.length, 0), 0
            );
            setProgress({ current: 0, total: totalPieces, label: 'Analyse des géométries…' });

            let done = 0;
            const debugRows = [];

            // Resolve BB — DXF/SVG geometry is the source of truth when a file is
            // attached (drawings age better than the numbers typed on the line).
            // Line details are used as fallback, then a tiny placeholder rectangle.
            // Bar groups skip the geometry pass: 1D packing only needs `length`.
            for (const svc of res.data.services) {
                for (const group of svc.groups) {
                    if (group.nest_type === 'bar') {
                        done += group.pieces.length;
                        setProgress(p => ({ ...p, current: done }));
                        continue;
                    }
                    // NestEngine handles the geometry server-side and returns
                    // its own SVG previews — no need to parse anything here.
                    if (group.job_id) {
                        done += group.pieces.length;
                        setProgress(p => ({ ...p, current: done }));
                        continue;
                    }
                    for (const piece of group.pieces) {
                        let bb = null;
                        const candidates = piece.files || [];
                        const tried = [];

                        // Try each attached CAD file in order (DXF first, SVG fallback).
                        // Full geometry parse also gives us the BB — one fetch, two uses.
                        for (const f of candidates) {
                            const geo = await geometryFor(f);
                            tried.push(`${f.file_kind}#${f.file_id}${geo ? '✓' : '✗'}`);
                            if (geo) {
                                bb = { x: geo.bb.x, y: geo.bb.y, source: geo.source };
                                piece.geometry = geo;
                                piece.file_used = f;
                                break;
                            }
                        }

                        if (!bb && piece.x_line > 0 && piece.y_line > 0) {
                            bb = { x: piece.x_line, y: piece.y_line, source: 'line' };
                        }

                        piece.bb = bb || { x: 100, y: 100, source: 'fallback' };

                        debugRows.push({
                            line: piece.line_id,
                            order: piece.order_code,
                            label: piece.label,
                            files: candidates.length,
                            tried: tried.join(' '),
                            used: piece.file_used ? `#${piece.file_used.file_id} ${piece.file_used.file_kind}` : null,
                            bb_source: piece.bb.source,
                            bb: `${Math.round(piece.bb.x)}x${Math.round(piece.bb.y)}`,
                            primitives: piece.geometry?.primitives?.length ?? 0,
                        });

                        done++;
                        if (done % 5 === 0 || done === totalPieces) {
                            setProgress(p => ({ ...p, current: done }));
                        }
                    }
                    group.piecesWithBB = group.pieces;
                }
            }

            console.groupCollapsed(`[nest] BB resolution summary — ${debugRows.length} pieces`);
            console.table(debugRows);
            console.groupEnd();

            setProgress({ current: totalPieces, total: totalPieces, label: 'Imbrication…' });
            setData(res.data);
        } catch (e) {
            setError(e.response?.data?.message || 'Erreur serveur.');
        } finally {
            setLoading(false);
            setResolvingBBs(false);
            setProgress({ current: 0, total: 0, label: '' });
        }
    }, [includeOpen, serviceIds]);

    // Compute summary — total sheets per format across every service/group
    const summary = useMemo(() => {
        if (!data) return null;
        const counter = new Map();
        for (const svc of data.services) {
            for (const g of svc.groups) {
                if (g.nest_type === 'bar') continue;
                if (!g.piecesWithBB?.length) continue;
                const results = nestSheets(g.piecesWithBB, activeFormats);
                for (const r of results) {
                    const key = `${g.material}|${g.thickness}|${r.format.label}`;
                    const cur = counter.get(key) || {
                        material: g.material, thickness: g.thickness,
                        format: r.format.label, count: 0,
                    };
                    cur.count += 1;
                    counter.set(key, cur);
                }
            }
        }
        return Array.from(counter.values()).sort((a, b) =>
            a.material.localeCompare(b.material)
            || a.thickness - b.thickness
            || a.format.localeCompare(b.format)
        );
    }, [data, activeFormats]);

    // Bar summary — total bars per (material, profile, standard length)
    const barSummary = useMemo(() => {
        if (!data) return null;
        const counter = new Map();
        for (const svc of data.services) {
            for (const g of svc.groups) {
                if (g.nest_type !== 'bar' || !g.pieces?.length) continue;
                const results = nestBars(g.pieces, activeBarLengths, kerf);
                for (const r of results) {
                    const profile = profileLabel(g);
                    const key = `${g.material}|${profile}|${r.label}`;
                    const cur = counter.get(key) || {
                        material: g.material,
                        profile,
                        y_size: g.y_size,
                        z_size: g.z_size,
                        bar_label: r.label,
                        bar_length: r.length,
                        count: 0,
                    };
                    cur.count += 1;
                    counter.set(key, cur);
                }
            }
        }
        return Array.from(counter.values()).sort((a, b) =>
            a.material.localeCompare(b.material)
            || a.profile.localeCompare(b.profile)
            || a.bar_length - b.bar_length
        );
    }, [data, activeBarLengths, kerf]);

    // Index bar stock by (material|profile) — unlike sheets the standard length
    // is not part of the identity: a "20×20 S235" article covers 3m and 6m stock.
    const barStockIndex = useMemo(() => {
        const map = new Map();
        for (const s of barStock) {
            const mat = s.material.toLowerCase().replace(/\s+/g, '');
            const profileKey = s.z_size
                ? `${s.y_size}x${s.z_size}`
                : `d${s.y_size}`;
            const key = `${mat}|${profileKey}|${s.x_size}`;
            const cur = map.get(key) || { stock_qty: 0, incoming_qty: 0, products: [] };
            cur.stock_qty += s.stock_qty;
            cur.incoming_qty += s.incoming_qty || 0;
            cur.products.push({ id: s.id, code: s.code, label: s.label });
            map.set(key, cur);
        }
        return map;
    }, [barStock]);

    const lookupBarStock = useCallback((material, profile, barLength) => {
        const mat = material.toLowerCase().replace(/\s+/g, '');
        // profile arrives as "20 × 20" or "Ø 20" — normalise to match the index
        const clean = profile.replace(/\s+/g, '');
        const profileKey = clean.startsWith('Ø')
            ? `d${clean.slice(1)}`
            : clean.replace('×', 'x');
        return barStockIndex.get(`${mat}|${profileKey}|${barLength}`) || null;
    }, [barStockIndex]);

    return (
        <div className="row">
            {/* ═══════ Panneau paramètres ═══════ */}
            <div className="col-lg-3">
                <div className="card card-primary card-outline">
                    <div className="card-header">
                        <h3 className="card-title">
                            <i className="fas fa-cut mr-1" />Paramètres
                        </h3>
                    </div>
                    <div className="card-body">

                        <label className="small font-weight-bold mb-1">Formats tôle</label>
                        <div className="mb-3">
                            {formats.map(f => (
                                <div key={f.id} className="form-check">
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        id={`fmt-${f.id}`}
                                        checked={f.selected}
                                        onChange={() => toggleFormat(f.id)}
                                    />
                                    <label className="form-check-label" htmlFor={`fmt-${f.id}`}>
                                        {f.label} mm
                                    </label>
                                </div>
                            ))}
                        </div>

                        <label className="small font-weight-bold mb-1">Longueurs de barre</label>
                        <div className="mb-3">
                            {barLengths.map(b => (
                                <div key={b.id} className="form-check">
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        id={`bar-${b.id}`}
                                        checked={b.selected}
                                        onChange={() => toggleBarLength(b.id)}
                                    />
                                    <label className="form-check-label" htmlFor={`bar-${b.id}`}>
                                        {b.label}
                                    </label>
                                </div>
                            ))}
                        </div>

                        <label className="small font-weight-bold mb-1" htmlFor="kerf-input">
                            Trait de scie (kerf)
                        </label>
                        <div className="input-group input-group-sm mb-3">
                            <input
                                id="kerf-input"
                                type="number"
                                min="0"
                                step="0.5"
                                className="form-control"
                                value={kerf}
                                onChange={e => setKerf(Math.max(0, Number(e.target.value) || 0))}
                            />
                            <div className="input-group-append">
                                <span className="input-group-text">mm</span>
                            </div>
                        </div>

                        <div className="form-check mb-3">
                            <input
                                type="checkbox"
                                className="form-check-input"
                                id="include-open"
                                checked={includeOpen}
                                onChange={e => setIncludeOpen(e.target.checked)}
                            />
                            <label className="form-check-label small" htmlFor="include-open">
                                Inclure les commandes <strong>ouvertes</strong>
                            </label>
                        </div>

                        {services.length > 0 && (
                            <div className="mb-3">
                                <label className="small font-weight-bold mb-1">Moyens de débit</label>
                                {services.map(s => (
                                    <div key={s.id} className="form-check">
                                        <input
                                            type="checkbox"
                                            className="form-check-input"
                                            id={`svc-${s.id}`}
                                            checked={serviceIds.has(s.id)}
                                            onChange={() => toggleService(s.id)}
                                        />
                                        <label className="form-check-label" htmlFor={`svc-${s.id}`}>
                                            {s.color && (
                                                <span style={{
                                                    display: 'inline-block',
                                                    width: 10, height: 10,
                                                    background: s.color,
                                                    border: '1px solid #999',
                                                    borderRadius: 2,
                                                    marginRight: 6,
                                                    verticalAlign: 'middle',
                                                }} />
                                            )}
                                            {s.label}
                                        </label>
                                    </div>
                                ))}
                            </div>
                        )}

                        <button
                            className="btn btn-primary btn-block"
                            onClick={handleCompute}
                            disabled={loading || (!activeFormats.length && !activeBarLengths.length) || !serviceIds.size}
                        >
                            {loading
                                ? <><i className="fas fa-spinner fa-spin mr-1" />
                                    {resolvingBBs ? 'Analyse géométries…' : 'Calcul…'}</>
                                : <><i className="fas fa-calculator mr-1" />Calculer le besoin</>
                            }
                        </button>

                        {!activeFormats.length && !activeBarLengths.length && (
                            <small className="text-dark d-block mt-2">
                                <i className="fas fa-info-circle mr-1" />Sélectionne au moins un format tôle ou une longueur de barre.
                            </small>
                        )}
                        {(activeFormats.length || activeBarLengths.length) && !serviceIds.size && (
                            <small className="text-dark d-block mt-2">
                                <i className="fas fa-info-circle mr-1" />Sélectionne au moins un moyen de débit.
                            </small>
                        )}

                        {data && (
                            <div className="mt-3 pt-3 border-top small">
                                <div className="text-muted">
                                    {data.stats.orders_included} commande(s) analysée(s)<br />
                                    {data.stats.lines_scanned} ligne(s) parcourue(s)
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* ═══════ Zone résultats ═══════ */}
            <div className="col-lg-9">
                {error && (
                    <div className="alert alert-danger">
                        <i className="fas fa-exclamation-triangle mr-1" />{error}
                    </div>
                )}

                {!data && !loading && (
                    <div className="text-center text-muted mt-5 pt-5">
                        <i className="fas fa-th" style={{ fontSize: 48, opacity: 0.2 }} />
                        <p className="mt-3">Sélectionne les formats et lance le calcul.</p>
                    </div>
                )}

                {loading && (
                    <div className="card">
                        <div className="card-body text-center py-4">
                            <NestingAnimation />
                            <h5 className="mb-3 mt-3">{progress.label || 'Calcul en cours…'}</h5>

                            {progress.total > 0 ? (
                                <>
                                    <div className="progress mx-auto" style={{ maxWidth: 480, height: 20 }}>
                                        <div
                                            className="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                            role="progressbar"
                                            style={{
                                                width: `${Math.round(progress.current / progress.total * 100)}%`,
                                                transition: 'width 0.2s ease',
                                            }}
                                            aria-valuenow={progress.current}
                                            aria-valuemin="0"
                                            aria-valuemax={progress.total}
                                        >
                                            {Math.round(progress.current / progress.total * 100)}%
                                        </div>
                                    </div>
                                    <small className="text-muted mt-2 d-block">
                                        {progress.current} / {progress.total} pièce(s)
                                    </small>
                                </>
                            ) : (
                                <div className="progress mx-auto" style={{ maxWidth: 480, height: 20 }}>
                                    <div
                                        className="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                        role="progressbar"
                                        style={{ width: '100%' }}
                                    />
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {data && summary && summary.length > 0 && (
                    <div className="card mb-3">
                        <div className="card-header py-2">
                            <h3 className="card-title h5 mb-0">
                                <i className="fas fa-shopping-cart mr-1" />
                                Besoin tôles
                                {sheetStock.length > 0 && (
                                    <small className="text-muted ml-2">
                                        (croisement stock — {sheetStock.length} article(s) tôle matière)
                                    </small>
                                )}
                            </h3>
                        </div>
                        <div className="card-body p-0">
                            <table className="table table-sm table-striped mb-0">
                                <thead className="thead-light">
                                    <tr>
                                        <th>Matière</th>
                                        <th className="text-right">Épaisseur</th>
                                        <th>Format</th>
                                        <th className="text-right">Besoin</th>
                                        <th>Article stock</th>
                                        <th className="text-right">Stock</th>
                                        <th className="text-right" title="Commandes d'achat émises non encore réceptionnées">
                                            En attente
                                        </th>
                                        <th className="text-right">À commander</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {summary.map((row, i) => {
                                        const match = lookupStock(row.material, row.thickness, row.format);
                                        const stockQty = match ? match.stock_qty : 0;
                                        const incomingQty = match ? match.incoming_qty : 0;
                                        const toBuy = Math.max(0, row.count - stockQty - incomingQty);
                                        const covered = (stockQty + incomingQty) >= row.count;
                                        return (
                                            <tr key={i}>
                                                <td><strong>{row.material}</strong></td>
                                                <td className="text-right">{row.thickness} mm</td>
                                                <td>{row.format} mm</td>
                                                <td className="text-right">
                                                    <span className="badge badge-primary">{row.count}</span>
                                                </td>
                                                <td>
                                                    {match ? (
                                                        match.products.map((p, j) => (
                                                            <span key={p.id}>
                                                                {j > 0 && ', '}
                                                                <code>{p.code}</code>
                                                            </span>
                                                        ))
                                                    ) : (
                                                        <span className="text-muted small">— aucun article correspondant —</span>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    {match ? (
                                                        <span className={stockQty > 0 ? 'text-dark' : 'text-muted'}>
                                                            {stockQty}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted">—</span>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    {match && incomingQty > 0 ? (
                                                        <span className="badge badge-info">{incomingQty}</span>
                                                    ) : (
                                                        <span className="text-muted">—</span>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    {match ? (
                                                        <span className={`badge ${covered ? 'badge-success' : 'badge-warning'}`}>
                                                            {toBuy}
                                                        </span>
                                                    ) : (
                                                        <span className="badge badge-danger">{row.count}</span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                                <tfoot className="thead-light">
                                    <tr>
                                        <th colSpan="7" className="text-right">Total à commander</th>
                                        <th className="text-right">
                                            <span className="badge badge-dark">
                                                {summary.reduce((s, row) => {
                                                    const match = lookupStock(row.material, row.thickness, row.format);
                                                    const stockQty = match ? match.stock_qty : 0;
                                                    const incomingQty = match ? match.incoming_qty : 0;
                                                    return s + Math.max(0, row.count - stockQty - incomingQty);
                                                }, 0)}
                                            </span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                )}

                {data && barSummary && barSummary.length > 0 && (
                    <div className="card mb-3">
                        <div className="card-header py-2">
                            <h3 className="card-title h5 mb-0">
                                <i className="fas fa-grip-lines-vertical mr-1" />
                                Besoin barres / tubes
                                {barStock.length > 0 && (
                                    <small className="text-muted ml-2">
                                        (croisement stock — {barStock.length} article(s) barre matière)
                                    </small>
                                )}
                            </h3>
                        </div>
                        <div className="card-body p-0">
                            <table className="table table-sm table-striped mb-0">
                                <thead className="thead-light">
                                    <tr>
                                        <th>Matière</th>
                                        <th>Profil</th>
                                        <th>Longueur barre</th>
                                        <th className="text-right">Besoin</th>
                                        <th>Article stock</th>
                                        <th className="text-right">Stock</th>
                                        <th className="text-right" title="Commandes d'achat émises non encore réceptionnées">
                                            En attente
                                        </th>
                                        <th className="text-right">À commander</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {barSummary.map((row, i) => {
                                        const match = lookupBarStock(row.material, row.profile, row.bar_length);
                                        const stockQty = match ? match.stock_qty : 0;
                                        const incomingQty = match ? match.incoming_qty : 0;
                                        const toBuy = Math.max(0, row.count - stockQty - incomingQty);
                                        const covered = (stockQty + incomingQty) >= row.count;
                                        return (
                                            <tr key={i}>
                                                <td><strong>{row.material}</strong></td>
                                                <td>{row.profile}</td>
                                                <td>{row.bar_label}</td>
                                                <td className="text-right">
                                                    <span className="badge badge-primary">{row.count}</span>
                                                </td>
                                                <td>
                                                    {match ? (
                                                        match.products.map((p, j) => (
                                                            <span key={p.id}>
                                                                {j > 0 && ', '}
                                                                <code>{p.code}</code>
                                                            </span>
                                                        ))
                                                    ) : (
                                                        <span className="text-muted small">— aucun article correspondant —</span>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    {match ? (
                                                        <span className={stockQty > 0 ? 'text-dark' : 'text-muted'}>
                                                            {stockQty}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted">—</span>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    {match && incomingQty > 0 ? (
                                                        <span className="badge badge-info">{incomingQty}</span>
                                                    ) : (
                                                        <span className="text-muted">—</span>
                                                    )}
                                                </td>
                                                <td className="text-right">
                                                    {match ? (
                                                        <span className={`badge ${covered ? 'badge-success' : 'badge-warning'}`}>
                                                            {toBuy}
                                                        </span>
                                                    ) : (
                                                        <span className="badge badge-danger">{row.count}</span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                                <tfoot className="thead-light">
                                    <tr>
                                        <th colSpan="7" className="text-right">Total à commander</th>
                                        <th className="text-right">
                                            <span className="badge badge-dark">
                                                {barSummary.reduce((s, row) => {
                                                    const match = lookupBarStock(row.material, row.profile, row.bar_length);
                                                    const stockQty = match ? match.stock_qty : 0;
                                                    const incomingQty = match ? match.incoming_qty : 0;
                                                    return s + Math.max(0, row.count - stockQty - incomingQty);
                                                }, 0)}
                                            </span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                )}

                {data && data.services.map(svc => (
                    <div key={svc.service_id} className="card mb-3">
                        <div className="card-header py-2" style={{
                            borderLeft: `4px solid ${svc.service_color || '#666'}`,
                        }}>
                            <h3 className="card-title h6 mb-0">
                                <i className="fas fa-cog mr-1" />{svc.service_label}
                            </h3>
                        </div>
                        <div className="card-body p-2">
                            {svc.groups.map(g => g.nest_type === 'bar' ? (
                                <BarGroupPanel
                                    key={`bar|${g.material}|${g.y_size}|${g.z_size}`}
                                    group={g}
                                    barLengths={activeBarLengths}
                                    kerf={kerf}
                                    service={svc}
                                />
                            ) : g.job_id ? (
                                <NestEngineGroupPanel
                                    key={`ne|${g.material}|${g.thickness}|${g.job_id}`}
                                    group={g}
                                    service={svc}
                                />
                            ) : (
                                <GroupPanel
                                    key={`sheet|${g.material}|${g.thickness}`}
                                    group={g}
                                    formats={activeFormats}
                                    service={svc}
                                />
                            ))}
                        </div>
                    </div>
                ))}

                {data && data.missing_geometry.length > 0 && (
                    <AnomalyList
                        title="Sans géométrie ou longueur"
                        subtitle="Tôles : ni fichier vectoriel ni cotes x/y sur la ligne. Barres : longueur (x_size) non renseignée."
                        rows={data.missing_geometry}
                        showMaterial
                    />
                )}

                {data && data.missing_material.length > 0 && (
                    <AnomalyList
                        title="Sans matière / profil"
                        subtitle="Ces pièces n'ont pas de composant matière associé (nest_type sheet ou bar)."
                        rows={data.missing_material}
                    />
                )}

                {data && data.missing_service?.length > 0 && (
                    <AnomalyList
                        title="Sans moyen de débit"
                        subtitle="Ces pièces n'ont aucune tâche associée à un service de débit (is_nesting = true). Elles sont exclues du nesting."
                        rows={data.missing_service}
                        hideService
                    />
                )}
            </div>
        </div>
    );
}

// ─── Liste anomalies ────────────────────────────────────────────────────────
function AnomalyList({ title, subtitle, rows, showMaterial = false, hideService = false }) {
    const [expanded, setExpanded] = useState(false);

    return (
        <div className="card mb-3 border-warning">
            <div
                className="card-header py-2 bg-warning-subtle"
                style={{ cursor: 'pointer' }}
                onClick={() => setExpanded(!expanded)}
            >
                <h3 className="card-title h6 mb-0">
                    <i className={`fas fa-chevron-${expanded ? 'down' : 'right'} mr-2`} />
                    <i className="fas fa-exclamation-triangle mr-1 text-warning" />
                    {title}
                    <span className="badge badge-warning ml-2">{rows.length}</span>
                </h3>
            </div>
            {expanded && (
                <div className="card-body p-0">
                    <p className="p-2 mb-0 small text-muted">{subtitle}</p>
                    <table className="table table-sm mb-0" style={{ fontSize: '0.82em' }}>
                        <thead className="thead-light">
                            <tr>
                                <th>Article</th>
                                <th>Commande</th>
                                {!hideService && <th>Moyen de débit</th>}
                                {showMaterial && <>
                                    <th>Matière</th>
                                    <th className="text-right">Épaisseur</th>
                                </>}
                                <th className="text-right">Qté</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map(r => (
                                <tr key={r.line_id}>
                                    <td>
                                        <code>{r.product_code}</code> - {r.label}
                                    </td>
                                    <td>
                                        <a href={`/orders/${r.order_id}`} target="_blank" rel="noreferrer">
                                            {r.order_code}
                                        </a>
                                    </td>
                                    {!hideService && <td>{r.service_label}</td>}
                                    {showMaterial && <>
                                        <td>{r.material}</td>
                                        <td className="text-right">{r.thickness} mm</td>
                                    </>}
                                    <td className="text-right">{formatQty(r.qty)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
