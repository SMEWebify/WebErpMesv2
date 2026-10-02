import React, { useEffect, useMemo, useState } from 'react';

// Catalogue d'outillage de presse plieuse (poinçons, matrices, adaptateurs), extrait des
// bibliothèques constructeurs Radbend (XML/SMX), Trumpf (MDB) et UKB (DXF).
const TYPE_FR = { punch: 'Poinçon', die: 'Matrice', adapter: 'Adaptateur' };
const SHAPE_FR = { straight: 'Poinçon droit', gooseneck: 'Poinçon col de cygne', die: 'Matrice', adapter: 'Adaptateur' };
const SORTS = [
    ['brand', 'Tri : marque, référence'],
    ['name', 'Tri : référence'],
    ['height', 'Tri : hauteur'],
    ['angle', 'Tri : angle'],
    ['v', 'Tri : ouverture V'],
    ['force', 'Tri : charge max'],
];
const PAGE = 120;
const EMPTY_RANGES = { aMin: '', aMax: '', vMin: '', vMax: '', hMin: '', hMax: '' };

const fmt = (n) => (n == null ? '—' : Number(n).toLocaleString('fr-FR', { maximumFractionDigits: 2 }));
const num = (v) => (v === '' ? null : +v);

function shape(t) {
    if (t.type === 'adapter') return 'adapter';
    if (t.type === 'die') return 'die';
    return t.gooseneck || /goose|cygne|col/i.test(t.subtype || '') ? 'gooseneck' : 'straight';
}

function prepare(tools) {
    return tools.map((t) => ({
        ...t,
        shape: shape(t),
        hay: [t.brand, t.family, t.name, t.subtype, t.note, t.clamp, t.angle && t.angle + '°', t.v && 'V' + t.v]
            .filter(Boolean).join(' ').toLowerCase(),
    }));
}

function countBy(tools, key) {
    const c = {};
    tools.forEach((t) => { c[t[key]] = (c[t[key]] || 0) + 1; });
    return c;
}

function Profile({ tool, big }) {
    if (!tool.svg) return <span className="text-muted small">pas de géométrie</span>;
    const [x, y, w, h] = tool.bbox;
    const m = Math.max(w, h) * 0.06;
    const sw = Math.max(w, h) / (big ? 260 : 120);
    return (
        <svg viewBox={`${x - m} ${y - m} ${w + 2 * m} ${h + 2 * m}`} preserveAspectRatio="xMidYMid meet" style={{ width: '100%', height: '100%' }}>
            <path d={tool.svg} fill={tool.open ? 'none' : 'var(--bs-primary)'} fillOpacity=".14" fillRule="evenodd"
                stroke="currentColor" strokeWidth={sw} strokeLinejoin="round" />
        </svg>
    );
}

function CheckList({ tools, field, labels, selected, onToggle }) {
    const counts = useMemo(() => countBy(tools, field), [tools, field]);
    const keys = Object.keys(counts).sort((a, b) => (labels?.[a] || a).localeCompare(labels?.[b] || b));
    return keys.map((k) => (
        <div className="form-check" key={k}>
            <input className="form-check-input" type="checkbox" id={`tc-${field}-${k}`}
                checked={selected.has(k)} onChange={() => onToggle(field, k)} />
            <label className="form-check-label d-flex w-100" htmlFor={`tc-${field}-${k}`}>
                {labels?.[k] || k}<span className="ms-auto text-muted small">{counts[k]}</span>
            </label>
        </div>
    ));
}

function Range({ label, unit, min, max, ranges, setRanges }) {
    return (
        <>
            <h6 className="text-muted small mt-3 mb-1">{label}</h6>
            <div className="d-flex gap-1">
                {[[min, 'min'], [max, 'max']].map(([k, p]) => (
                    <input key={k} type="number" className="form-control form-control-sm" placeholder={`${p} ${unit}`}
                        value={ranges[k]} onChange={(e) => setRanges((r) => ({ ...r, [k]: e.target.value }))} />
                ))}
            </div>
        </>
    );
}

function ToolModal({ tool, onClose }) {
    useEffect(() => {
        const onKey = (e) => { if (e.key === 'Escape') onClose(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [onClose]);

    const t = tool;
    const rows = [
        ['Type', TYPE_FR[t.type] + (t.subtype ? ' · ' + t.subtype : '')],
        ['Marque', t.brand + (t.family ? ' · ' + t.family : '')],
        ['Attache', t.clamp],
        ['Angle', t.angle != null ? fmt(t.angle) + '°' : null],
        ['Ouverture V', t.v != null ? fmt(t.v) + ' mm' : null],
        ['Rayon', t.radius != null ? fmt(t.radius) + ' mm' : null],
        ['Hauteur de travail', t.height != null ? fmt(t.height) + ' mm' : null],
        ['Largeur', t.width != null ? fmt(t.width) + ' mm' : null],
        ['Charge max.', t.force ? fmt(t.force) + ' t/m' : null],
        ['Encombrement', t.bbox ? fmt(t.bbox[2]) + ' × ' + fmt(t.bbox[3]) + ' mm' : null],
        ['Note', t.note],
    ].filter((r) => r[1]);

    return (
        <>
            <div className="modal d-block" tabIndex="-1" onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}>
                <div className="modal-dialog modal-xl modal-dialog-centered">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title">{t.name} <small className="text-muted">{t.brand} — {TYPE_FR[t.type]}</small></h5>
                            <button type="button" className="btn-close" aria-label="Fermer" onClick={onClose} />
                        </div>
                        <div className="modal-body">
                            <div className="row g-3">
                                <div className="col-md-8">
                                    <div className="bg-body-tertiary border rounded p-3 d-flex align-items-center justify-content-center" style={{ height: '60vh' }}>
                                        <Profile tool={t} big />
                                    </div>
                                </div>
                                <div className="col-md-4">
                                    <table className="table table-sm mb-2">
                                        <tbody>
                                            {rows.map(([k, v]) => (
                                                <tr key={k}><td className="text-muted">{k}</td><td className="text-end fw-semibold">{v}</td></tr>
                                            ))}
                                        </tbody>
                                    </table>
                                    {t.parts?.length > 0 && (
                                        <>
                                            <div className="small text-muted mb-1">Longueurs disponibles ({t.parts.length})</div>
                                            <div className="d-flex flex-wrap gap-1">
                                                {t.parts.map(([len, qty], i) => (
                                                    <span key={i} className="badge text-bg-light border">{fmt(len)} mm{qty > 1 ? ' ×' + qty : ''}</span>
                                                ))}
                                            </div>
                                        </>
                                    )}
                                    <div className="small text-muted mt-3 text-break">Source : {t.src}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div className="modal-backdrop show" />
        </>
    );
}

function exportCsv(tools) {
    const cols = ['brand', 'family', 'type', 'subtype', 'name', 'clamp', 'angle', 'radius', 'v', 'height', 'width', 'force', 'note', 'src'];
    const hdr = ['Marque', 'Famille', 'Type', 'Sous-type', 'Référence', 'Attache', 'Angle', 'Rayon', 'V', 'Hauteur', 'Largeur', 'Charge max t/m', 'Note', 'Source', 'Longueurs'];
    const rows = tools.map((t) => [...cols.map((c) => t[c] ?? ''), (t.parts || []).map((p) => p[0] + (p[1] > 1 ? 'x' + p[1] : '')).join(' ')]);
    const csv = '﻿' + [hdr, ...rows].map((r) => r.map((v) => '"' + String(v).replace(/"/g, '""') + '"').join(';')).join('\r\n');
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
    const a = document.createElement('a');
    a.href = url;
    a.download = 'catalogue-outillage.csv';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}

export default function ToolCatalog({ libraryUrl }) {
    const [tools, setTools] = useState(null);
    const [error, setError] = useState(null);
    const [q, setQ] = useState('');
    const [sel, setSel] = useState({ type: new Set(), brand: new Set(), shape: new Set() });
    const [ranges, setRanges] = useState(EMPTY_RANGES);
    const [sort, setSort] = useState('brand');
    const [limit, setLimit] = useState(PAGE);
    const [open, setOpen] = useState(null);

    // ~2,8 Mo de profils : chargés à la demande, hors du bundle principal.
    useEffect(() => {
        fetch(libraryUrl, { headers: { Accept: 'application/json' } })
            .then(async (res) => {
                const data = await res.json().catch(() => null);
                if (!res.ok) throw new Error(data?.message || `Erreur ${res.status}`);
                setTools(prepare(data));
            })
            .catch((e) => setError(e.message || String(e)));
    }, []);

    const toggle = (field, k) => setSel((s) => {
        const next = new Set(s[field]);
        next.has(k) ? next.delete(k) : next.add(k);
        return { ...s, [field]: next };
    });
    const clear = () => {
        setSel({ type: new Set(), brand: new Set(), shape: new Set() });
        setRanges(EMPTY_RANGES);
        setQ('');
    };

    const shown = useMemo(() => {
        if (!tools) return [];
        const words = q.trim().toLowerCase().split(/\s+/).filter(Boolean);
        const aMin = num(ranges.aMin), aMax = num(ranges.aMax), vMin = num(ranges.vMin), vMax = num(ranges.vMax), hMin = num(ranges.hMin), hMax = num(ranges.hMax);
        const inRange = (v, lo, hi) => (lo == null || (v != null && v >= lo)) && (hi == null || (v != null && v <= hi));
        const out = tools.filter((t) =>
            (!sel.type.size || sel.type.has(t.type)) && (!sel.brand.size || sel.brand.has(t.brand)) && (!sel.shape.size || sel.shape.has(t.shape))
            && inRange(t.angle, aMin, aMax) && inRange(t.v, vMin, vMax) && inRange(t.height, hMin, hMax)
            && words.every((w) => t.hay.includes(w)));
        out.sort((a, b) => (sort === 'brand'
            ? a.brand.localeCompare(b.brand) || a.type.localeCompare(b.type) || a.name.localeCompare(b.name, undefined, { numeric: true })
            : sort === 'name' ? a.name.localeCompare(b.name, undefined, { numeric: true })
                : (b[sort] ?? -1) - (a[sort] ?? -1)));
        return out;
    }, [tools, q, sel, ranges, sort]);

    useEffect(() => setLimit(PAGE), [q, sel, ranges, sort]);

    if (error) return <div className="alert alert-danger">Impossible de charger le catalogue : {error}</div>;
    if (!tools) return <div className="text-center text-muted p-5"><i className="fas fa-spinner fa-spin me-2" />Chargement du catalogue…</div>;

    const brands = [...new Set(tools.map((t) => t.brand))];

    return (
        <>
            <div className="d-flex flex-wrap align-items-end gap-2 mb-3">
                <div className="text-muted small">
                    {tools.length.toLocaleString('fr-FR')} outils · {brands.join(', ')} — extraits des bibliothèques Radbend (XML/SMX), Trumpf (MDB) et UKB (DXF)
                </div>
                <input type="search" className="form-control ms-auto" style={{ maxWidth: 360 }} autoComplete="off"
                    placeholder="Rechercher une référence, un angle, un V…" value={q} onChange={(e) => setQ(e.target.value)} />
            </div>

            <div className="row g-3">
                <div className="col-lg-3 col-xl-2">
                    <div className="card card-body p-3" style={{ position: 'sticky', top: 10 }}>
                        <h6 className="text-muted small mb-1">Type</h6>
                        <CheckList tools={tools} field="type" labels={TYPE_FR} selected={sel.type} onToggle={toggle} />
                        <h6 className="text-muted small mt-3 mb-1">Marque</h6>
                        <CheckList tools={tools} field="brand" selected={sel.brand} onToggle={toggle} />
                        <Range label="Angle" unit="°" min="aMin" max="aMax" ranges={ranges} setRanges={setRanges} />
                        <Range label="Ouverture V (matrices)" unit="mm" min="vMin" max="vMax" ranges={ranges} setRanges={setRanges} />
                        <Range label="Hauteur" unit="mm" min="hMin" max="hMax" ranges={ranges} setRanges={setRanges} />
                        <h6 className="text-muted small mt-3 mb-1">Forme</h6>
                        <CheckList tools={tools} field="shape" labels={SHAPE_FR} selected={sel.shape} onToggle={toggle} />
                        <button type="button" className="btn btn-link btn-sm px-0 mt-2 text-start" onClick={clear}>Effacer les filtres</button>
                    </div>
                </div>

                <div className="col-lg-9 col-xl-10">
                    <div className="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span className="fs-4 fw-semibold">{shown.length.toLocaleString('fr-FR')}</span>
                        <span className="text-muted">outils</span>
                        <button type="button" className="btn btn-sm btn-outline-primary" onClick={() => exportCsv(shown)} disabled={!shown.length}>
                            <i className="fas fa-file-csv me-1" />Exporter la sélection (CSV)
                        </button>
                        <select className="form-select form-select-sm ms-auto w-auto" aria-label="Tri" value={sort} onChange={(e) => setSort(e.target.value)}>
                            {SORTS.map(([k, l]) => <option key={k} value={k}>{l}</option>)}
                        </select>
                    </div>

                    {shown.length === 0 && <div className="text-center text-muted p-5">Aucun outil ne correspond à ces filtres.</div>}

                    <div className="d-grid gap-2" style={{ gridTemplateColumns: 'repeat(auto-fill, minmax(190px, 1fr))' }}>
                        {shown.slice(0, limit).map((t) => {
                            const meta = [t.angle != null ? t.angle + '°' : null, t.v != null ? 'V ' + fmt(t.v) : null,
                                t.radius != null ? 'R' + fmt(t.radius) : null, t.height != null ? 'H ' + fmt(t.height) : null].filter(Boolean).join(' · ');
                            return (
                                <button type="button" key={t.id} className="card card-body p-2 text-start mb-0" onClick={() => setOpen(t)}>
                                    <div className="bg-body-tertiary d-flex align-items-center justify-content-center mb-2 w-100" style={{ height: 150 }}>
                                        <Profile tool={t} />
                                    </div>
                                    <div className="mb-1">
                                        <span className={`badge border me-1 ${t.type === 'die' ? 'text-primary border-primary' : 'text-secondary'}`}>{TYPE_FR[t.type]}</span>
                                        <span className="badge border text-secondary">{t.brand}</span>
                                    </div>
                                    <b className="text-truncate d-block w-100" title={t.name}>{t.name}</b>
                                    <div className="small text-muted">{meta || ' '}</div>
                                </button>
                            );
                        })}
                    </div>

                    {shown.length > limit && (
                        <div className="text-center mt-3">
                            <button type="button" className="btn btn-outline-primary" onClick={() => setLimit((l) => l + PAGE)}>Afficher {PAGE} de plus</button>
                        </div>
                    )}
                </div>
            </div>

            {open && <ToolModal tool={open} onClose={() => setOpen(null)} />}
        </>
    );
}
