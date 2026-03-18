import { useState, useCallback } from 'react';

// ─── Palette de couleurs ────────────────────────────────────────────────────
const COLORS = [
    '#4e79a7', '#f28e2b', '#e15759', '#76b7b2', '#59a14f',
    '#edc948', '#b07aa1', '#ff9da7', '#9c755f', '#bab0ac',
];
const pieceColor = i => COLORS[i % COLORS.length];

// ─── Algorithme 2D shelf packing multi-formats ──────────────────────────────
// formats : [{ id, label, x, y }, ...]
function nestSheets(pieces, formats) {
    const rects = [];
    pieces.forEach((p, pi) => {
        for (let i = 0; i < p.qty; i++) {
            rects.push({ id: p.task_id, label: p.label, w: p.x, h: p.y, colorIdx: pi });
        }
    });
    rects.sort((a, b) => b.h - a.h || b.w - a.w);

    // sheets = [{ format, placements, rowY, rowH, rowX }]
    const sheets = [];

    for (const rect of rects) {
        let placed = false;

        // Essayer de placer dans une tôle déjà ouverte
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
            // Ouvrir une nouvelle tôle — choisir le plus petit format qui accepte la pièce
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

// ─── Algorithme 1D first fit decreasing ────────────────────────────────────
function nestBars(pieces, barLength) {
    const items = [];
    pieces.forEach((p, pi) => {
        for (let i = 0; i < p.qty; i++) {
            items.push({ id: p.task_id, label: p.label, l: p.x, colorIdx: pi });
        }
    });
    items.sort((a, b) => b.l - a.l);

    const bars = [];
    for (const item of items) {
        if (item.l > barLength) continue;
        let placed = false;
        for (const bar of bars) {
            const used = bar.reduce((s, p) => s + p.l, 0);
            if (used + item.l <= barLength) {
                bar.push({ ...item, pos: used });
                placed = true;
                break;
            }
        }
        if (!placed) bars.push([{ ...item, pos: 0 }]);
    }
    return bars.map(placements => ({
        placements,
        usedLength: placements.reduce((s, p) => s + p.l, 0),
        barLength,
    }));
}

// ─── SVG Tôle ───────────────────────────────────────────────────────────────
function SheetSvg({ sheet, index }) {
    const { format, placements, usedArea, totalArea } = sheet;
    const displayW = 560;
    const scale    = displayW / format.x;
    const displayH = Math.round(format.y * scale);
    const efficiency = Math.round((usedArea / totalArea) * 100);
    return (
        <div className="mb-3">
            <div className="d-flex justify-content-between align-items-center mb-1">
                <small className="font-weight-bold text-secondary">
                    Tôle {index + 1} — {format.label} ({format.x}×{format.y} mm)
                </small>
                <span className={`badge badge-${efficiency >= 70 ? 'success' : efficiency >= 50 ? 'warning' : 'danger'}`}>
                    {efficiency}%
                </span>
            </div>
            <svg width={displayW} height={displayH}
                style={{ border: '1px solid #aaa', background: '#f8f9fa', display: 'block' }}>
                {placements.map((p, i) => (
                    <g key={i}>
                        <rect x={p.px * scale} y={p.py * scale}
                            width={p.w * scale} height={p.h * scale}
                            fill={pieceColor(p.colorIdx)} stroke="#fff" strokeWidth={1} opacity={0.85} />
                        {p.w * scale > 30 && p.h * scale > 12 && (
                            <text x={(p.px + p.w / 2) * scale} y={(p.py + p.h / 2) * scale}
                                textAnchor="middle" dominantBaseline="middle" fontSize={10} fill="#fff"
                                style={{ pointerEvents: 'none', userSelect: 'none' }}>
                                {p.label.length > 12 ? p.label.slice(0, 11) + '…' : p.label}
                            </text>
                        )}
                    </g>
                ))}
            </svg>
            <small className="text-muted">{placements.length} pièce(s)</small>
        </div>
    );
}

// ─── SVG Barre ──────────────────────────────────────────────────────────────
function BarSvg({ bar, barLength, index }) {
    const displayW = 560;
    const barH = 36;
    const scale = displayW / barLength;
    const efficiency = Math.round((bar.usedLength / barLength) * 100);
    return (
        <div className="mb-3">
            <div className="d-flex justify-content-between align-items-center mb-1">
                <small className="font-weight-bold text-secondary">Barre {index + 1}</small>
                <span className={`badge badge-${efficiency >= 70 ? 'success' : efficiency >= 50 ? 'warning' : 'danger'}`}>
                    {efficiency}%
                </span>
            </div>
            <svg width={displayW} height={barH}
                style={{ border: '1px solid #aaa', background: '#f8f9fa', display: 'block' }}>
                {bar.placements.map((p, i) => (
                    <g key={i}>
                        <rect x={p.pos * scale} y={0} width={p.l * scale} height={barH}
                            fill={pieceColor(p.colorIdx)} stroke="#fff" strokeWidth={1} opacity={0.85} />
                        {p.l * scale > 30 && (
                            <text x={(p.pos + p.l / 2) * scale} y={barH / 2}
                                textAnchor="middle" dominantBaseline="middle" fontSize={10} fill="#fff"
                                style={{ pointerEvents: 'none', userSelect: 'none' }}>
                                {p.l}mm
                            </text>
                        )}
                    </g>
                ))}
            </svg>
            <small className="text-muted">
                L={barLength} mm — {bar.placements.length} pièce(s) — chute {barLength - bar.usedLength} mm
            </small>
        </div>
    );
}

// ─── Groupe de matière ───────────────────────────────────────────────────────
function GroupResult({ group }) {
    const { nest_type, material, thickness, y_size, z_size, pieces, available_sheets } = group;
    const [selectedIds, setSelectedIds] = useState(new Set());
    const [results, setResults]         = useState(null);

    const groupLabel = nest_type === 'sheet'
        ? `${material}${thickness ? ' — ' + thickness + ' mm' : ''}`
        : `${material} — ${y_size}×${z_size} mm`;

    const totalPieces = pieces.reduce((s, p) => s + p.qty, 0);
    const hasWarnings = pieces.some(p => p.thickness_warn || p.material_warn);

    // Filtrer par matière ET épaisseur concordantes
    const filteredSheets = (available_sheets ?? []).filter(s => {
        const matOk = !s.material || !material || s.material.toLowerCase() === material.toLowerCase();
        const epOk  = !s.thickness || !thickness || s.thickness === thickness;
        return matOk && epOk;
    });

    const toggleSheet = (id) => {
        setSelectedIds(prev => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
        setResults(null);
    };

    const selectedFormats = filteredSheets.filter(s => selectedIds.has(s.id));

    const handleNest = () => {
        if (!selectedFormats.length) return;
        if (nest_type === 'sheet') {
            setResults(nestSheets(pieces, selectedFormats));
        } else {
            setResults(nestBars(pieces, selectedFormats[0].x));
        }
    };

    return (
        <div className="mb-4">
            {/* En-tête groupe */}
            <div className="d-flex align-items-center mb-2 border-bottom pb-1">
                <strong className="mr-2">{groupLabel}</strong>
                <span className="badge badge-secondary mr-2">{totalPieces} pcs</span>
                {hasWarnings && (
                    <span className="badge badge-warning">⚠ Dimensions à vérifier</span>
                )}
            </div>

            <div className="row">
                {/* ── Liste des pièces ── */}
                <div className="col-md-5">
                    <p className="small font-weight-bold text-muted mb-1">Pièces</p>
                    <table className="table table-sm table-bordered mb-0" style={{ fontSize: '0.82em' }}>
                        <thead className="thead-light">
                            <tr>
                                <th>Pièce</th>
                                <th className="text-right">X mm</th>
                                <th className="text-right">Y mm</th>
                                <th className="text-right">Qté</th>
                                <th className="text-center" title="Épaisseur">Ép.</th>
                                <th className="text-center" title="Matière">Mat.</th>
                            </tr>
                        </thead>
                        <tbody>
                            {pieces.map((p, i) => (
                                <tr key={p.task_id}>
                                    <td>
                                        <span style={{
                                            display: 'inline-block',
                                            width: 8, height: 8,
                                            background: pieceColor(i),
                                            borderRadius: 2,
                                            marginRight: 4,
                                        }} />
                                        {p.label}
                                    </td>
                                    <td className="text-right">{p.x}</td>
                                    <td className="text-right">{p.y}</td>
                                    <td className="text-right">{p.qty}</td>
                                    <td className="text-center">
                                        {p.thickness_warn ? (
                                            <span className="text-warning" style={{ cursor: 'help', fontWeight: 'bold' }}
                                                title={`Composant: ${p.thickness_comp} mm / Ligne: ${p.thickness_detail} mm`}>
                                                ⚠
                                            </span>
                                        ) : '✓'}
                                    </td>
                                    <td className="text-center">
                                        {p.material_warn ? (
                                            <span className="text-warning" style={{ cursor: 'help', fontWeight: 'bold' }}
                                                title={`Composant: ${p.material_comp} / Ligne: ${p.material_detail}`}>
                                                ⚠
                                            </span>
                                        ) : '✓'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* ── Liste des formats concordants (multi-sélection) ── */}
                <div className="col-md-4">
                    <p className="small font-weight-bold text-muted mb-1">
                        {nest_type === 'sheet'
                            ? `Tôles — ${material}${thickness ? ` / ${thickness} mm` : ''}`
                            : `Barres — ${material}`}
                        {selectedIds.size > 0 && (
                            <span className="badge badge-primary ml-1">{selectedIds.size} sél.</span>
                        )}
                    </p>
                    {filteredSheets.length > 0 ? (
                        <div style={{ maxHeight: 220, overflowY: 'auto' }}>
                            {filteredSheets.map(s => (
                                <label key={s.id}
                                    className={`d-flex align-items-center p-2 mb-1 border rounded small mb-0 ${selectedIds.has(s.id) ? 'border-primary bg-light' : ''}`}
                                    style={{ cursor: 'pointer', fontWeight: selectedIds.has(s.id) ? 600 : 400 }}>
                                    <input
                                        type="checkbox"
                                        className="mr-2"
                                        checked={selectedIds.has(s.id)}
                                        onChange={() => toggleSheet(s.id)}
                                    />
                                    <span>
                                        {s.label}
                                        <span className="text-muted ml-1">
                                            {nest_type === 'sheet'
                                                ? `${s.x}×${s.y} mm`
                                                : `L=${s.x} mm`}
                                        </span>
                                    </span>
                                </label>
                            ))}
                        </div>
                    ) : (
                        <p className="text-muted small">
                            Aucun format {material}{thickness ? ` / ${thickness} mm` : ''} dans le catalogue.
                        </p>
                    )}
                </div>

                {/* ── Bouton nesting ── */}
                <div className="col-md-3 d-flex flex-column">
                    <p className="small font-weight-bold text-muted mb-1">&nbsp;</p>
                    <button
                        className="btn btn-primary"
                        onClick={handleNest}
                        disabled={!selectedFormats.length}>
                        <i className="fas fa-th mr-1" />Nesting
                    </button>
                    {!selectedFormats.length && (
                        <small className="text-muted mt-1">Sélectionnez au moins un format</small>
                    )}
                    {selectedFormats.length > 0 && (
                        <small className="text-muted mt-1">
                            {selectedFormats.map(f => f.label).join(', ')}
                        </small>
                    )}
                </div>
            </div>

            {/* ── Résultat SVG ── */}
            {results && (
                <div className="mt-3">
                    <div className="alert alert-info py-1 px-2 mb-2" style={{ fontSize: '0.85em' }}>
                        {nest_type === 'sheet'
                            ? `${results.length} tôle(s) — taux moyen ${Math.round(results.reduce((s, r) => s + r.usedArea / r.totalArea, 0) / results.length * 100)}%`
                            : `${results.length} barre(s) — taux moyen ${Math.round(results.reduce((s, r) => s + r.usedLength / r.barLength, 0) / results.length * 100)}%`
                        }
                    </div>
                    {nest_type === 'sheet'
                        ? results.map((s, i) => <SheetSvg key={i} sheet={s} index={i} />)
                        : results.map((b, i) => <BarSvg key={i} bar={b}
                            barLength={selectedFormats[0]?.x} index={i} />)
                    }
                </div>
            )}
        </div>
    );
}

// ─── Composant principal ─────────────────────────────────────────────────────
export default function NestingPage() {
    const [codeInput, setCodeInput]             = useState('');
    const [document, setDocument]               = useState(null);
    const [services, setServices]               = useState([]);
    const [selectedService, setSelectedService] = useState(null);
    const [partsData, setPartsData]             = useState(null);
    const [loadingDoc, setLoadingDoc]           = useState(false);
    const [loadingParts, setLoadingParts]       = useState(false);
    const [error, setError]                     = useState(null);

    const groupKey = g => (g.nest_type ?? '') + '|' + g.material + '|' + (g.thickness ?? '') + (g.y_size ?? '') + (g.z_size ?? '');

    const handleSearchDoc = useCallback(() => {
        if (!codeInput.trim()) return;
        setLoadingDoc(true);
        setError(null);
        setDocument(null);
        setServices([]);
        setSelectedService(null);
        setPartsData(null);

        window.axios.get('/nesting/document', { params: { code: codeInput.trim() } })
            .then(r => {
                setDocument(r.data.document);
                setServices(r.data.services);
                if (r.data.services.length === 1) setSelectedService(r.data.services[0]);
            })
            .catch(e => setError(e.response?.status === 404 ? 'Document introuvable.' : 'Erreur serveur.'))
            .finally(() => setLoadingDoc(false));
    }, [codeInput]);

    const handleLoadParts = useCallback(() => {
        if (!document || !selectedService) return;
        setLoadingParts(true);
        setPartsData(null);
        setError(null);

        window.axios.get('/nesting/parts', {
            params: { type: document.type, id: document.id, service_id: selectedService.id }
        })
            .then(r => setPartsData(r.data))
            .catch(e => setError(e.response?.data?.message || 'Erreur lors du chargement.'))
            .finally(() => setLoadingParts(false));
    }, [document, selectedService]);

    return (
        <div className="row">
            {/* ── Panneau gauche ── */}
            <div className="col-md-3">
                <div className="card card-primary card-outline">
                    <div className="card-header">
                        <h3 className="card-title">
                            <i className="fas fa-cut mr-1" />Nesting
                        </h3>
                    </div>
                    <div className="card-body">

                        <div className="form-group">
                            <label className="small font-weight-bold">Code document</label>
                            <div className="input-group input-group-sm">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="DEV-2024-042"
                                    value={codeInput}
                                    onChange={e => setCodeInput(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && handleSearchDoc()}
                                />
                                <div className="input-group-append">
                                    <button className="btn btn-outline-secondary" onClick={handleSearchDoc}
                                        disabled={loadingDoc}>
                                        {loadingDoc
                                            ? <i className="fas fa-spinner fa-spin" />
                                            : <i className="fas fa-search" />}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {document && (
                            <div className="alert alert-success py-1 px-2 mb-3" style={{ fontSize: '0.85em' }}>
                                <strong>{document.code}</strong> — {document.label}
                                <br />
                                <span className="text-muted">{document.type === 'quote' ? 'Devis' : 'Commande'}</span>
                            </div>
                        )}

                        {services.length > 0 && (
                            <div className="form-group">
                                <label className="small font-weight-bold">Moyen de débit</label>
                                <select
                                    className="form-control form-control-sm"
                                    value={selectedService?.id || ''}
                                    onChange={e => {
                                        const s = services.find(s => s.id === parseInt(e.target.value));
                                        setSelectedService(s || null);
                                        setPartsData(null);
                                    }}
                                >
                                    <option value="">— sélectionner —</option>
                                    {services.map(s => (
                                        <option key={s.id} value={s.id}>
                                            {s.label}
                                            {' '}({(s.nest_types ?? []).map(t => t === 'sheet' ? 'Tôle' : 'Barre').join(', ')})
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {services.length === 0 && document && (
                            <div className="alert alert-warning py-1 px-2" style={{ fontSize: '0.85em' }}>
                                Aucun moyen de débit configuré sur ce document.
                            </div>
                        )}

                        {selectedService && (
                            <button
                                className="btn btn-primary btn-block btn-sm"
                                onClick={handleLoadParts}
                                disabled={loadingParts}
                            >
                                {loadingParts
                                    ? <><i className="fas fa-spinner fa-spin mr-1" />Chargement…</>
                                    : <><i className="fas fa-layer-group mr-1" />Charger les pièces</>
                                }
                            </button>
                        )}

                        {partsData && (
                            <div className="mt-3">
                                <hr />
                                <small className="text-muted">
                                    <strong>{partsData.groups.length}</strong> groupe(s) —{' '}
                                    {partsData.groups.reduce((s, g) =>
                                        s + g.pieces.reduce((ss, p) => ss + p.qty, 0), 0
                                    )} pièce(s)
                                </small>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* ── Zone résultats ── */}
            <div className="col-md-9">
                {error && (
                    <div className="alert alert-danger">
                        <i className="fas fa-exclamation-triangle mr-1" />{error}
                    </div>
                )}

                {!partsData && !loadingParts && (
                    <div className="text-center text-muted mt-5 pt-5">
                        <i className="fas fa-th" style={{ fontSize: 48, opacity: 0.2 }} />
                        <p className="mt-3">Entrez un code document et sélectionnez un moyen de débit.</p>
                    </div>
                )}

                {partsData && partsData.groups.length === 0 && (
                    <div className="alert alert-warning">
                        Aucune pièce trouvée pour ce moyen de débit sur ce document.
                    </div>
                )}

                {partsData && partsData.groups.map(g => (
                    <GroupResult key={groupKey(g)} group={g} />
                ))}
            </div>
        </div>
    );
}
