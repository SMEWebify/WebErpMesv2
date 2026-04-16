import React, { useState, useEffect, useRef } from 'react';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function apiFetch(url, options = {}) {
    return fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers ?? {}),
        },
        ...options,
    });
}

function formatDate(dateStr) {
    if (!dateStr) return null;
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat('fr-FR').format(new Date(y, m - 1, d));
    } catch { return dateStr; }
}

function fmt(n) {
    if (n == null) return '—';
    return new Intl.NumberFormat('fr-FR').format(n);
}

// ---------------------------------------------------------------------------
// InspectionDrawer
// ---------------------------------------------------------------------------

const EMPTY_INSPECTION = {
    inspected_by: '',
    inspection_date: '',
    accepted_qty: '',
    rejected_qty: '',
    inspection_result: '',
    quality_non_conformity_id: '',
    create_non_conformity: false,
    new_nc_label: '',
    new_nc_comment: '',
};

function InspectionDrawer({ open, line, selectData, endpoints, onClose, onSaved }) {
    const [form, setForm]     = useState(EMPTY_INSPECTION);
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});

    useEffect(() => {
        if (!open || !line) return;
        setForm({
            inspected_by:               line.inspected_by               ?? '',
            inspection_date:            line.inspection_date            ?? '',
            accepted_qty:               line.accepted_qty               ?? '',
            rejected_qty:               line.rejected_qty               ?? '',
            inspection_result:          line.inspection_result          ?? '',
            quality_non_conformity_id:  line.quality_non_conformity_id ?? '',
            create_non_conformity: false,
            new_nc_label: '',
            new_nc_comment: '',
        });
        setErrors({});
    }, [open, line]);

    const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const url = endpoints.inspection.replace('__ID__', line.id);
            const res = await apiFetch(url, { method: 'POST', body: JSON.stringify(form) });
            const data = await res.json();
            if (!res.ok) { setErrors(data.errors ?? { _global: data.message ?? 'Erreur' }); return; }
            onSaved(data.line);
            onClose();
        } catch {
            setErrors({ _global: 'Erreur réseau' });
        } finally {
            setSaving(false);
        }
    };

    const teal = '#008080';

    return (
        <>
            {/* Backdrop */}
            <div onClick={onClose} style={{
                position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.35)',
                zIndex: 1040, opacity: open ? 1 : 0,
                pointerEvents: open ? 'auto' : 'none', transition: 'opacity 0.2s',
            }} />

            {/* Tab handle */}
            <button type="button" onClick={onClose} style={{
                position: 'fixed', right: 0, top: '50%',
                transform: 'translateY(-50%)',
                width: 40, padding: '18px 0',
                background: teal, color: '#fff',
                border: 'none', borderRadius: '6px 0 0 6px',
                boxShadow: '-3px 0 8px rgba(0,0,0,0.18)',
                cursor: 'pointer',
                opacity: open ? 0 : 1,
                pointerEvents: open ? 'none' : 'auto',
                transition: 'opacity 0.2s',
                display: 'flex', flexDirection: 'column',
                alignItems: 'center', gap: 8, zIndex: 1049,
            }} title="Fermer">
                <i className="fas fa-clipboard-check" style={{ fontSize: 14 }} />
            </button>

            {/* Panel */}
            <div style={{
                position: 'fixed', top: 0, right: 0, bottom: 0,
                width: 520, maxWidth: '95vw',
                background: '#fff', zIndex: 1050,
                display: 'flex', flexDirection: 'column',
                transform: open ? 'translateX(0)' : 'translateX(100%)',
                transition: 'transform 0.25s ease',
                boxShadow: '-4px 0 24px rgba(0,0,0,0.15)',
            }}>
                {/* Header */}
                <div className="d-flex align-items-center justify-content-between px-3 py-2 border-bottom" style={{ background: teal, color: '#fff' }}>
                    <strong>
                        <i className="fas fa-clipboard-check mr-2" />
                        Détails d'inspection
                        {line && <span className="ml-2 font-weight-normal" style={{ opacity: 0.85, fontSize: '0.85em' }}>
                            — {line.purchase_line_label}
                        </span>}
                    </strong>
                    <button type="button" className="btn btn-sm btn-outline-light" onClick={onClose}>
                        <i className="fas fa-times" />
                    </button>
                </div>

                {/* Body */}
                <div className="flex-grow-1 overflow-auto p-3">
                    {errors._global && <div className="alert alert-danger py-2">{errors._global}</div>}

                    {line && (
                        <div className="alert alert-light py-2 mb-3 small">
                            <strong>Qté reçue :</strong> {fmt(line.receipt_qty)}
                            {line.accepted_qty != null && <span className="ml-3"><strong>Acceptée :</strong> <span className="text-success">{fmt(line.accepted_qty)}</span></span>}
                            {line.rejected_qty != null && line.rejected_qty > 0 && <span className="ml-3"><strong>Rejetée :</strong> <span className="text-danger">{fmt(line.rejected_qty)}</span></span>}
                        </div>
                    )}

                    <form id="inspection-form" onSubmit={handleSubmit}>
                        <div className="form-row">
                            <div className="form-group col-md-6 mb-2">
                                <label className="mb-1 small font-weight-bold">Inspecté par</label>
                                <select className="form-control form-control-sm" value={form.inspected_by}
                                    onChange={(e) => set('inspected_by', e.target.value)}>
                                    <option value="">— Sélectionner —</option>
                                    {(selectData.users ?? []).map((u) => (
                                        <option key={u.id} value={u.id}>{u.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group col-md-6 mb-2">
                                <label className="mb-1 small font-weight-bold">Date d'inspection</label>
                                <input type="date" className="form-control form-control-sm" value={form.inspection_date}
                                    onChange={(e) => set('inspection_date', e.target.value)} />
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-group col-md-4 mb-2">
                                <label className="mb-1 small font-weight-bold">Qté acceptée</label>
                                <input type="number" min="0" className={`form-control form-control-sm ${errors.accepted_qty ? 'is-invalid' : ''}`}
                                    value={form.accepted_qty}
                                    onChange={(e) => set('accepted_qty', e.target.value)} />
                                {errors.accepted_qty && <div className="invalid-feedback d-block">{errors.accepted_qty[0]}</div>}
                            </div>
                            <div className="form-group col-md-4 mb-2">
                                <label className="mb-1 small font-weight-bold">Qté rejetée</label>
                                <input type="number" min="0" className={`form-control form-control-sm ${errors.rejected_qty ? 'is-invalid' : ''}`}
                                    value={form.rejected_qty}
                                    onChange={(e) => set('rejected_qty', e.target.value)} />
                                {errors.rejected_qty && <div className="invalid-feedback d-block">{errors.rejected_qty[0]}</div>}
                            </div>
                            <div className="form-group col-md-4 mb-2">
                                <label className="mb-1 small font-weight-bold">Non-conformité</label>
                                <select className="form-control form-control-sm" value={form.quality_non_conformity_id}
                                    onChange={(e) => set('quality_non_conformity_id', e.target.value)}>
                                    <option value="">— Aucune —</option>
                                    {(selectData.non_conformities ?? []).map((nc) => (
                                        <option key={nc.id} value={nc.id}>{nc.code}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="form-group mb-3">
                            <label className="mb-1 small font-weight-bold">Résultat d'inspection</label>
                            <input type="text" className="form-control form-control-sm" value={form.inspection_result}
                                onChange={(e) => set('inspection_result', e.target.value)}
                                placeholder="Observations ou résultat…" />
                        </div>

                        <div className="border rounded p-3">
                            <div className="custom-control custom-switch mb-2">
                                <input type="checkbox" className="custom-control-input" id="create-nc-toggle"
                                    checked={form.create_non_conformity}
                                    onChange={(e) => set('create_non_conformity', e.target.checked)} />
                                <label className="custom-control-label" htmlFor="create-nc-toggle">
                                    Créer une NC rapide
                                </label>
                            </div>
                            {form.create_non_conformity && (
                                <div className="form-row">
                                    <div className="form-group col-md-6 mb-0">
                                        <label className="mb-1 small">Libellé NC</label>
                                        <input type="text" className="form-control form-control-sm" value={form.new_nc_label}
                                            onChange={(e) => set('new_nc_label', e.target.value)}
                                            placeholder="Libellé de la non-conformité" />
                                    </div>
                                    <div className="form-group col-md-6 mb-0">
                                        <label className="mb-1 small">Commentaire</label>
                                        <textarea className="form-control form-control-sm" rows="2"
                                            value={form.new_nc_comment}
                                            onChange={(e) => set('new_nc_comment', e.target.value)}
                                            placeholder="Commentaire…" />
                                    </div>
                                </div>
                            )}
                        </div>
                    </form>
                </div>

                {/* Footer */}
                <div className="border-top px-3 py-2 d-flex justify-content-end align-items-center bg-light" style={{ gap: '0.5rem' }}>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onClose}>Annuler</button>
                    <button type="submit" form="inspection-form" className="btn btn-sm" style={{ background: teal, color: '#fff' }} disabled={saving}>
                        {saving
                            ? <><i className="fas fa-spinner fa-spin mr-1" />Enregistrement…</>
                            : <><i className="fas fa-save mr-1" />Enregistrer</>}
                    </button>
                </div>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// StockDrawer
// ---------------------------------------------------------------------------

function StockDrawer({ open, line, receiptCode, selectData, endpoints, userId, onClose, onSaved }) {
    const [activeTab, setActiveTab]         = useState('new');
    const [stockLocationId, setStockLocationId] = useState('');
    const [existingStockId, setExistingStockId] = useState('');
    const [saving, setSaving]               = useState(false);
    const [errors, setErrors]               = useState({});

    useEffect(() => {
        if (!open) return;
        setStockLocationId('');
        setExistingStockId('');
        setErrors({});
        setActiveTab('new');
    }, [open, line?.id]);

    const filteredStockProducts = (line?.product_id)
        ? (selectData.stock_location_products ?? []).filter((slp) => slp.products_id == line.product_id)
        : [];

    const handleStoreNew = async (e) => {
        e.preventDefault();
        if (!stockLocationId) { setErrors({ _global: 'Sélectionnez un emplacement de stock.' }); return; }
        setSaving(true);
        setErrors({});
        try {
            const today = new Date().toISOString().slice(0, 10);
            const payload = {
                products_id:             line.product_id,
                code:                    `STOCK|${receiptCode}|${line.id}|${today}`,
                mini_qty:                line.receipt_qty,
                component_price:         line.selling_price,
                task_id:                 line.tasks_id ?? null,
                purchase_receipt_line_id: line.id,
                user_id:                 userId,
                stock_locations_id:      stockLocationId,
            };
            const res  = await apiFetch(endpoints.storeNewStock, { method: 'POST', body: JSON.stringify(payload) });
            const data = await res.json();
            if (!res.ok) { setErrors(data.errors ?? { _global: data.message ?? 'Erreur' }); return; }
            onSaved(line.id, data);
            onClose();
        } catch {
            setErrors({ _global: 'Erreur réseau' });
        } finally {
            setSaving(false);
        }
    };

    const handleEntryExisting = async (e) => {
        e.preventDefault();
        if (!existingStockId) { setErrors({ _global: 'Sélectionnez un emplacement existant.' }); return; }
        setSaving(true);
        setErrors({});
        try {
            const payload = {
                user_id:                  userId,
                qty:                      line.receipt_qty,
                task_id:                  line.tasks_id ?? null,
                purchase_receipt_line_id: line.id,
                component_price:          line.selling_price,
                typ_move:                 3,
                stock_location_products_id: existingStockId,
            };
            const res  = await apiFetch(endpoints.entryExistingStock, { method: 'POST', body: JSON.stringify(payload) });
            const data = await res.json();
            if (!res.ok) { setErrors(data.errors ?? { _global: data.message ?? 'Erreur' }); return; }
            onSaved(line.id, data);
            onClose();
        } catch {
            setErrors({ _global: 'Erreur réseau' });
        } finally {
            setSaving(false);
        }
    };

    const alreadyStocked = !!(line?.stock_location_products_id);
    const hasProduct     = !!(line?.product_id);

    return (
        <>
            <div onClick={onClose} style={{
                position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.35)',
                zIndex: 1040, opacity: open ? 1 : 0,
                pointerEvents: open ? 'auto' : 'none', transition: 'opacity 0.2s',
            }} />

            <div style={{
                position: 'fixed', top: 0, right: 0, bottom: 0,
                width: 480, maxWidth: '95vw',
                background: '#fff', zIndex: 1050,
                display: 'flex', flexDirection: 'column',
                transform: open ? 'translateX(0)' : 'translateX(100%)',
                transition: 'transform 0.25s ease',
                boxShadow: '-4px 0 24px rgba(0,0,0,0.15)',
            }}>
                {/* Header */}
                <div className="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-success text-white">
                    <strong>
                        <i className="fas fa-warehouse mr-2" />
                        Mise en stock
                        {line && <span className="ml-2 font-weight-normal" style={{ opacity: 0.85, fontSize: '0.85em' }}>
                            — {line.purchase_line_label}
                        </span>}
                    </strong>
                    <button type="button" className="btn btn-sm btn-outline-light" onClick={onClose}>
                        <i className="fas fa-times" />
                    </button>
                </div>

                {/* Body */}
                <div className="flex-grow-1 overflow-auto p-3">
                    {errors._global && <div className="alert alert-danger py-2">{errors._global}</div>}

                    {alreadyStocked ? (
                        <div className="alert alert-success">
                            <i className="fas fa-check-circle mr-2" />
                            Stock assigné :{' '}
                            <a href={line.stock_url} className="font-weight-bold alert-link">{line.stock_code}</a>
                        </div>
                    ) : !hasProduct ? (
                        <div className="alert alert-warning">
                            <i className="fas fa-exclamation-triangle mr-2" />
                            Aucun produit associé à cette ligne.
                        </div>
                    ) : (
                        <>
                            <div className="alert alert-light py-2 mb-3 small">
                                <strong>Qté reçue :</strong> {fmt(line?.receipt_qty)}
                            </div>

                            <ul className="nav nav-tabs mb-3">
                                <li className="nav-item">
                                    <a className={`nav-link ${activeTab === 'new' ? 'active' : ''}`} href="#"
                                        onClick={(e) => { e.preventDefault(); setActiveTab('new'); }}>
                                        <i className="fas fa-plus-circle mr-1" />Nouvel emplacement
                                    </a>
                                </li>
                                {filteredStockProducts.length > 0 && (
                                    <li className="nav-item">
                                        <a className={`nav-link ${activeTab === 'existing' ? 'active' : ''}`} href="#"
                                            onClick={(e) => { e.preventDefault(); setActiveTab('existing'); }}>
                                            <i className="fas fa-boxes mr-1" />Existant ({filteredStockProducts.length})
                                        </a>
                                    </li>
                                )}
                            </ul>

                            {activeTab === 'new' && (
                                <form id="stock-new-form" onSubmit={handleStoreNew}>
                                    <div className="form-group mb-2">
                                        <label className="mb-1 small font-weight-bold">
                                            Emplacement de stock <span className="text-danger">*</span>
                                        </label>
                                        <div className="input-group input-group-sm">
                                            <div className="input-group-prepend">
                                                <span className="input-group-text"><i className="fas fa-map-marker-alt" /></span>
                                            </div>
                                            <select className="form-control" value={stockLocationId}
                                                onChange={(e) => setStockLocationId(e.target.value)}>
                                                <option value="">— Sélectionner —</option>
                                                {(selectData.stock_locations ?? []).map((sl) => (
                                                    <option key={sl.id} value={sl.id}>
                                                        Stock : {sl.stock_code} | Emplacement : {sl.code}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            )}

                            {activeTab === 'existing' && (
                                <form id="stock-existing-form" onSubmit={handleEntryExisting}>
                                    <div className="form-group mb-2">
                                        <label className="mb-1 small font-weight-bold">
                                            Ligne de stock existante <span className="text-danger">*</span>
                                        </label>
                                        <div className="input-group input-group-sm">
                                            <div className="input-group-prepend">
                                                <span className="input-group-text"><i className="fas fa-boxes" /></span>
                                            </div>
                                            <select className="form-control" value={existingStockId}
                                                onChange={(e) => setExistingStockId(e.target.value)}>
                                                <option value="">— Sélectionner —</option>
                                                {filteredStockProducts.map((slp) => (
                                                    <option key={slp.id} value={slp.id}>
                                                        Emplacement : {slp.location_code} | Stock : {slp.code}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            )}
                        </>
                    )}
                </div>

                {/* Footer */}
                {!alreadyStocked && hasProduct && (
                    <div className="border-top px-3 py-2 d-flex justify-content-end align-items-center bg-light" style={{ gap: '0.5rem' }}>
                        <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onClose}>Annuler</button>
                        <button
                            type="submit"
                            form={activeTab === 'new' ? 'stock-new-form' : 'stock-existing-form'}
                            className="btn btn-sm btn-success"
                            disabled={saving}
                        >
                            {saving
                                ? <><i className="fas fa-spinner fa-spin mr-1" />Traitement…</>
                                : <><i className="fas fa-check mr-1" />Mettre en stock</>}
                        </button>
                    </div>
                )}
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// ManualLineForm
// ---------------------------------------------------------------------------

function ManualLineForm({ products, endpoints, onAdded }) {
    const [productId, setProductId] = useState('');
    const [qty, setQty]             = useState(1);
    const [saving, setSaving]       = useState(false);
    const [errors, setErrors]       = useState({});
    const [open, setOpen]           = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const res  = await apiFetch(endpoints.manualLine, { method: 'POST', body: JSON.stringify({ product_id: productId, qty }) });
            const data = await res.json();
            if (!res.ok) { setErrors(data.errors ?? { _global: data.message ?? 'Erreur' }); return; }
            setProductId('');
            setQty(1);
            setOpen(false);
            onAdded(data.line);
        } catch {
            setErrors({ _global: 'Erreur réseau' });
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="card card-outline card-info mb-3">
            <div className="card-header py-2 d-flex align-items-center justify-content-between">
                <strong className="small">
                    <i className="fas fa-plus-circle mr-2 text-info" />Ajouter une ligne manuelle
                </strong>
                <button type="button" className="btn btn-sm btn-outline-info"
                    onClick={() => setOpen((v) => !v)}>
                    <i className={`fas fa-${open ? 'minus' : 'plus'}`} />
                </button>
            </div>
            {open && (
                <div className="card-body py-2">
                    {errors._global && <div className="alert alert-danger py-2 mb-2 small">{errors._global}</div>}
                    <form onSubmit={handleSubmit}>
                        <div className="form-row align-items-end">
                            <div className="form-group col-md-7 mb-0">
                                <label className="mb-1 small font-weight-bold">Produit <span className="text-danger">*</span></label>
                                <select className={`form-control form-control-sm ${errors.product_id ? 'is-invalid' : ''}`}
                                    value={productId} onChange={(e) => setProductId(e.target.value)}>
                                    <option value="">— Sélectionner un produit —</option>
                                    {products.map((p) => (
                                        <option key={p.id} value={p.id}>{p.code} — {p.label}</option>
                                    ))}
                                </select>
                                {errors.product_id && <div className="invalid-feedback d-block">{errors.product_id[0]}</div>}
                            </div>
                            <div className="form-group col-md-3 mb-0">
                                <label className="mb-1 small font-weight-bold">Qté <span className="text-danger">*</span></label>
                                <input type="number" min="1" className={`form-control form-control-sm ${errors.qty ? 'is-invalid' : ''}`}
                                    value={qty} onChange={(e) => setQty(e.target.value)} />
                                {errors.qty && <div className="invalid-feedback d-block">{errors.qty[0]}</div>}
                            </div>
                            <div className="form-group col-md-2 mb-0">
                                <button type="submit" className="btn btn-info btn-sm btn-block" disabled={saving || !productId}>
                                    {saving ? <i className="fas fa-spinner fa-spin" /> : <><i className="fas fa-plus mr-1" />Ajouter</>}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            )}
        </div>
    );
}

// ---------------------------------------------------------------------------
// LineRow
// ---------------------------------------------------------------------------

function LineRow({ line, onInspect, onStock }) {
    const hasInspection = !!(line.inspection_date || line.inspection_result || line.inspected_by);
    const hasStock      = !!(line.stock_location_products_id);
    const hasProduct    = !!(line.product_id);

    return (
        <tr>
            {/* Commande */}
            <td style={{ whiteSpace: 'nowrap' }}>
                {line.order_url
                    ? <a href={line.order_url} className="btn btn-xs btn-outline-primary">
                        <i className="fas fa-folder-open mr-1" />{line.order_code}
                      </a>
                    : <span className="text-muted small">Générique</span>}
            </td>

            {/* Bon d'achat */}
            <td style={{ whiteSpace: 'nowrap' }}>
                {line.purchase_url
                    ? <a href={line.purchase_url} className="btn btn-xs btn-primary">
                        <i className="fas fa-folder mr-1" />{line.purchase_code}
                      </a>
                    : '—'}
            </td>

            {/* Qté ordre */}
            <td className="text-right">
                {line.order_line_qty != null
                    ? `${line.order_line_qty} × ${line.task_qty}`
                    : <span className="text-muted">—</span>}
            </td>

            {/* Libellé commande */}
            <td style={{ maxWidth: 130, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                <span title={line.order_line_label}>{line.order_line_label ?? <span className="text-muted">—</span>}</span>
            </td>

            {/* Libellé ligne */}
            <td style={{ maxWidth: 180, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                {line.task_url && (
                    <a href={line.task_url} className="btn btn-xs btn-success mr-1" title={`Tâche #${line.task_id}`}>
                        <i className="fas fa-eye" />
                    </a>
                )}
                <span title={line.task_label ?? line.purchase_line_label}>
                    {line.task_id
                        ? `#${line.task_id} — ${line.task_label}${line.component_label ? ` — ${line.component_label}` : ''}`
                        : line.purchase_line_label}
                </span>
            </td>

            {/* Produit */}
            <td style={{ width: 36 }}>
                {line.product_url
                    ? <a href={line.product_url} className="btn btn-xs btn-outline-secondary" title="Voir le produit">
                        <i className="fas fa-cube" />
                      </a>
                    : '—'}
            </td>

            {/* Qté requise */}
            <td className="text-right">
                {line.quality_required != null ? fmt(line.quality_required) : <span className="text-muted">—</span>}
            </td>

            {/* Qté achat */}
            <td className="text-right">{fmt(line.purchase_line_qty)}</td>

            {/* Qté reçue */}
            <td className="text-right font-weight-bold">{fmt(line.receipt_qty)}</td>

            {/* Qté acceptée */}
            <td className="text-right">
                {line.accepted_qty != null
                    ? <span className="badge badge-success">{fmt(line.accepted_qty)}</span>
                    : <span className="text-muted">—</span>}
            </td>

            {/* Qté rejetée */}
            <td className="text-right">
                {line.rejected_qty != null && line.rejected_qty > 0
                    ? <span className="badge badge-danger">{fmt(line.rejected_qty)}</span>
                    : <span className="text-muted">—</span>}
            </td>

            {/* Résultat inspection */}
            <td style={{ maxWidth: 130, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                <span title={line.inspection_result}>{line.inspection_result ?? <span className="text-muted">—</span>}</span>
            </td>

            {/* Date inspection */}
            <td style={{ whiteSpace: 'nowrap' }}>
                {line.inspection_date ? formatDate(line.inspection_date) : <span className="text-muted">—</span>}
            </td>

            {/* Inspecté par */}
            <td>{line.inspector_name ?? <span className="text-muted">—</span>}</td>

            {/* NC */}
            <td>
                {line.nc_code
                    ? <span className="badge badge-warning">{line.nc_code}</span>
                    : <span className="text-muted">—</span>}
            </td>

            {/* Actions */}
            <td style={{ whiteSpace: 'nowrap' }}>
                <button
                    type="button"
                    className={`btn btn-xs mr-1 ${hasInspection ? 'btn-teal' : 'btn-outline-secondary'}`}
                    style={hasInspection ? { background: '#008080', color: '#fff', border: '1px solid #008080' } : {}}
                    title="Détails d'inspection"
                    onClick={() => onInspect(line)}
                >
                    <i className="fas fa-clipboard-check" />
                </button>
                {hasProduct && (
                    <button
                        type="button"
                        className={`btn btn-xs ${hasStock ? 'btn-success' : 'btn-outline-success'}`}
                        title={hasStock ? `Stock : ${line.stock_code}` : 'Mise en stock'}
                        onClick={() => onStock(line)}
                    >
                        <i className="fas fa-warehouse" />
                        {hasStock && <i className="fas fa-check ml-1" style={{ fontSize: 9 }} />}
                    </button>
                )}
            </td>
        </tr>
    );
}

// ---------------------------------------------------------------------------
// PurchaseReceiptLinesPage — main component
// ---------------------------------------------------------------------------

export default function PurchaseReceiptLinesPage({ receiptId, receiptCode, userId, endpoints }) {
    const [lines, setLines]               = useState([]);
    const [selectData, setSelectData]     = useState({});
    const [loading, setLoading]           = useState(true);
    const [flash, setFlash]               = useState(null);
    const [inspectionLine, setInspectionLine] = useState(null);
    const [stockLine, setStockLine]       = useState(null);

    const showFlash = (type, msg) => {
        setFlash({ type, msg });
        setTimeout(() => setFlash(null), 4000);
    };

    useEffect(() => {
        fetch(endpoints.lines, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((data) => {
                setLines(data.lines ?? []);
                setSelectData(data.select ?? {});
            })
            .catch(() => showFlash('danger', 'Erreur lors du chargement des lignes'))
            .finally(() => setLoading(false));
    }, []);

    const handleInspectionSaved = (updatedLine) => {
        setLines((prev) => prev.map((l) => l.id === updatedLine.id ? updatedLine : l));
        showFlash('success', 'Inspection mise à jour');
    };

    const handleStockSaved = (lineId, data) => {
        setLines((prev) => prev.map((l) => l.id === lineId
            ? { ...l, stock_location_products_id: data.stock_location_products_id, stock_code: data.stock_code, stock_url: data.stock_url }
            : l
        ));
        showFlash('success', 'Ligne mise en stock avec succès');
    };

    const handleManualLineAdded = (newLine) => {
        setLines((prev) => [...prev, newLine]);
        showFlash('success', 'Ligne manuelle ajoutée');
    };

    const totalReceipt  = lines.reduce((s, l) => s + (l.receipt_qty  ?? 0), 0);
    const totalAccepted = lines.reduce((s, l) => s + (l.accepted_qty ?? 0), 0);
    const totalRejected = lines.reduce((s, l) => s + (l.rejected_qty ?? 0), 0);

    return (
        <div>
            {/* Flash */}
            {flash && (
                <div className={`alert alert-${flash.type} alert-dismissible py-2 mb-3`}>
                    {flash.msg}
                    <button type="button" className="close" onClick={() => setFlash(null)}><span>&times;</span></button>
                </div>
            )}

            {/* Add manual line */}
            <ManualLineForm
                products={selectData.products ?? []}
                endpoints={endpoints}
                onAdded={handleManualLineAdded}
            />

            {/* Table */}
            <div className="table-responsive p-0">
                <table className="table table-striped table-sm mb-0" style={{ fontSize: '0.8rem' }}>
                    <thead className="thead-light">
                        <tr>
                            <th>Commande</th>
                            <th>Bon d'achat</th>
                            <th className="text-right">Qté cmd</th>
                            <th>Libellé cmd</th>
                            <th>Libellé</th>
                            <th style={{ width: 36 }} />
                            <th className="text-right">Qté req.</th>
                            <th className="text-right">Qté achat</th>
                            <th className="text-right">Qté reçue</th>
                            <th className="text-right">Accepté</th>
                            <th className="text-right">Rejeté</th>
                            <th>Résultat insp.</th>
                            <th>Date insp.</th>
                            <th>Inspecté par</th>
                            <th>NC</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={16} className="text-center py-4">
                                    <i className="fas fa-spinner fa-spin mr-2" />Chargement…
                                </td>
                            </tr>
                        ) : lines.length === 0 ? (
                            <tr>
                                <td colSpan={16} className="text-center py-4 text-muted">
                                    Aucune ligne dans ce bon de réception.
                                </td>
                            </tr>
                        ) : (
                            lines.map((line) => (
                                <LineRow
                                    key={line.id}
                                    line={line}
                                    onInspect={(l) => setInspectionLine(l)}
                                    onStock={(l) => setStockLine(l)}
                                />
                            ))
                        )}
                    </tbody>
                    {lines.length > 0 && (
                        <tfoot>
                            <tr className="font-weight-bold bg-light">
                                <td colSpan={8} className="text-right pr-2 small text-muted">Total :</td>
                                <td className="text-right">{fmt(totalReceipt)}</td>
                                <td className="text-right">
                                    {totalAccepted > 0 && <span className="badge badge-success">{fmt(totalAccepted)}</span>}
                                </td>
                                <td className="text-right">
                                    {totalRejected > 0 && <span className="badge badge-danger">{fmt(totalRejected)}</span>}
                                </td>
                                <td colSpan={5} />
                            </tr>
                        </tfoot>
                    )}
                </table>
            </div>

            {/* Inspection overlay */}
            <InspectionDrawer
                open={!!inspectionLine}
                line={inspectionLine}
                selectData={selectData}
                endpoints={endpoints}
                onClose={() => setInspectionLine(null)}
                onSaved={handleInspectionSaved}
            />

            {/* Stock overlay */}
            <StockDrawer
                open={!!stockLine}
                line={stockLine}
                receiptCode={receiptCode}
                selectData={selectData}
                endpoints={endpoints}
                userId={userId}
                onClose={() => setStockLine(null)}
                onSaved={handleStockSaved}
            />
        </div>
    );
}
