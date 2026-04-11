import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const TASKS_STATUS_CONFIG = {
    1: { badge: 'badge-secondary', label: 'Sans tâche' },
    2: { badge: 'badge-info',      label: 'Créé' },
    3: { badge: 'badge-warning',   label: 'En cours' },
    4: { badge: 'badge-success',   label: 'Terminé' },
};

const DELIVERY_STATUS_CONFIG = {
    1: { badge: 'badge-secondary', label: 'Non livré' },
    2: { badge: 'badge-warning',   label: 'Partiel' },
    3: { badge: 'badge-success',   label: 'Livré' },
    4: { badge: 'badge-primary',   label: 'Sans BL' },
};

const INVOICE_STATUS_CONFIG = {
    1: { badge: 'badge-secondary', label: 'Non facturé' },
    2: { badge: 'badge-warning',   label: 'Partiel' },
    3: { badge: 'badge-success',   label: 'Facturé' },
};

const EMPTY_FORM = {
    ordre: 1,
    product_id: '',
    code: '',
    label: '',
    qty: 1,
    methods_units_id: '',
    selling_price: 0,
    discount: 0,
    accounting_vats_id: '',
    delivery_date: '',
};

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
    if (!dateStr) return '—';
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat('fr-FR').format(new Date(y, m - 1, d));
    } catch { return dateStr; }
}

// ---------------------------------------------------------------------------
// PriceGrid
// ---------------------------------------------------------------------------

function PriceGrid({ priceList, currentQty, onApplyPrice }) {
    if (!priceList || priceList.length === 0) return null;

    const qty  = parseFloat(currentQty) || 0;
    const rows = priceList.map((e) => ({
        ...e,
        matches: qty >= e.min_qty && (e.max_qty === null || qty <= e.max_qty),
    }));

    return (
        <div className="mt-3">
            <div className="card card-outline card-info mb-0">
                <div className="card-header py-2">
                    <h6 className="card-title mb-0">
                        <i className="fas fa-tag mr-1 text-info" />
                        Grille tarifaire client
                    </h6>
                </div>
                <div className="card-body p-0">
                    <table className="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Source</th><th>Qté min</th><th>Qté max</th><th>Prix</th><th />
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((e) => (
                                <tr key={e.id} className={e.matches ? 'table-success' : ''}>
                                    <td>{e.scope_label}</td>
                                    <td>{e.min_qty}</td>
                                    <td>{e.max_qty ?? '∞'}</td>
                                    <td>{e.formatted_price}</td>
                                    <td>
                                        <button
                                            type="button"
                                            className="btn btn-xs btn-outline-primary"
                                            onClick={() => onApplyPrice(e.price)}
                                        >
                                            Appliquer
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// TaskModal
// ---------------------------------------------------------------------------

function TaskModal({ lineId, lineLabel, endpoints, isReadOnly, useCalculatedPrice, onLineUpdated, onClose }) {
    const [tasks, setTasks]         = useState(null);
    const [taskUrl, setTaskUrl]     = useState('#');
    const [calcPrice, setCalcPrice] = useState(useCalculatedPrice);
    const [toggling, setToggling]   = useState(false);

    useEffect(() => {
        if (!lineId || !endpoints.tasks) return;
        setTasks(null);
        fetch(endpoints.tasks.replace('__ID__', lineId), { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((d) => {
                setTasks(d.tasks ?? []);
                setTaskUrl(d.task_url ?? '#');
                setCalcPrice(d.use_calculated_price ?? false);
            })
            .catch(() => setTasks([]));
    }, [lineId]);

    const handleToggle = async () => {
        setToggling(true);
        const res = await apiFetch(endpoints.calculatedPrice.replace('__ID__', lineId), {
            method: 'PATCH',
            body: JSON.stringify({ enable: !calcPrice }),
        });
        if (res.ok) {
            const data = await res.json();
            setCalcPrice(!calcPrice);
            onLineUpdated(data.line);
        }
        setToggling(false);
    };

    return (
        <>
            {/* Backdrop */}
            <div onClick={onClose} style={{
                position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.5)',
                zIndex: 1055, cursor: 'pointer',
            }} />

            {/* Modal */}
            <div style={{
                position: 'fixed', top: '10%', left: '50%', transform: 'translateX(-50%)',
                width: '90%', maxWidth: 780, maxHeight: '80vh',
                background: '#fff', borderRadius: 6, zIndex: 1060,
                display: 'flex', flexDirection: 'column',
                boxShadow: '0 8px 32px rgba(0,0,0,0.25)',
            }}>
                {/* Header */}
                <div className="d-flex align-items-center justify-content-between px-3 py-2 border-bottom"
                    style={{ background: '#ffc107', borderRadius: '6px 6px 0 0' }}>
                    <strong className="text-dark">
                        <i className="fas fa-list mr-2" />
                        Tâches — {lineLabel}
                    </strong>
                    <button type="button" className="btn btn-sm btn-outline-dark" onClick={onClose}>
                        <i className="fas fa-times" />
                    </button>
                </div>

                {/* Body */}
                <div className="flex-grow-1 overflow-auto p-3">
                    {tasks === null ? (
                        <div className="text-center py-4">
                            <i className="fas fa-spinner fa-spin mr-2" />Chargement…
                        </div>
                    ) : tasks.length === 0 ? (
                        <p className="text-muted text-center py-4">Aucune tâche pour cette ligne.</p>
                    ) : (
                        <table className="table table-hover table-sm mb-0">
                            <thead className="thead-light">
                                <tr>
                                    <th>Ordre</th>
                                    <th>Libellé</th>
                                    <th>Service</th>
                                    <th className="text-right">Temps</th>
                                    <th className="text-right">Qté</th>
                                    <th className="text-right">Coût</th>
                                    <th className="text-right">Marge</th>
                                    <th className="text-right">Prix</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tasks.map((t) => (
                                    <tr key={t.id}>
                                        <td>{t.ordre}</td>
                                        <td>{t.label}</td>
                                        <td style={{ background: t.service?.color ?? undefined }}>
                                            {t.service?.label ?? '—'}
                                        </td>
                                        <td className="text-right">{t.total_time} h</td>
                                        <td className="text-right">{t.qty}</td>
                                        <td className="text-right">{t.unit_cost}</td>
                                        <td className="text-right">{t.margin != null ? `${t.margin} %` : '—'}</td>
                                        <td className="text-right">{t.unit_price}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Footer */}
                <div className="border-top px-3 py-2 d-flex align-items-center justify-content-between flex-wrap" style={{ gap: '0.5rem' }}>
                    <a href={taskUrl} className="btn btn-sm btn-info">
                        <i className="fas fa-folder mr-1" />Gérer les tâches
                    </a>
                    {!isReadOnly && (
                        <button
                            type="button"
                            className={`btn btn-sm ${calcPrice ? 'btn-warning' : 'btn-success'}`}
                            onClick={handleToggle}
                            disabled={toggling}
                        >
                            {toggling
                                ? <i className="fas fa-spinner fa-spin mr-1" />
                                : <i className={`fas ${calcPrice ? 'fa-toggle-on' : 'fa-toggle-off'} mr-1`} />}
                            {calcPrice ? 'Désactiver prix calculé' : 'Activer prix calculé'}
                        </button>
                    )}
                </div>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// LineDrawer
// ---------------------------------------------------------------------------

function LineDrawer({ open, onClose, onOpenCreate, editingLine, selectData, endpoints, onSaved, isReadOnly }) {
    const [form, setForm]                   = useState(EMPTY_FORM);
    const [priceList, setPriceList]         = useState([]);
    const [saving, setSaving]               = useState(false);
    const [errors, setErrors]               = useState({});
    const [productSearch, setProductSearch] = useState('');
    const [showProductList, setShowProductList] = useState(false);

    useEffect(() => {
        if (!open) return;

        if (editingLine) {
            setForm({
                ordre:              editingLine.ordre              ?? 1,
                product_id:         editingLine.product_id         ?? '',
                code:               editingLine.code               ?? '',
                label:              editingLine.label              ?? '',
                qty:                editingLine.qty                ?? 1,
                methods_units_id:   editingLine.methods_units_id   ?? '',
                selling_price:      editingLine.selling_price      ?? 0,
                discount:           editingLine.discount           ?? 0,
                accounting_vats_id: editingLine.accounting_vats_id ?? '',
                delivery_date:      editingLine.delivery_date      ?? '',
            });
            setProductSearch(
                editingLine.product_code
                    ? `${editingLine.product_code} — ${editingLine.label}`
                    : ''
            );
        } else {
            const defaultUnit = selectData.units?.find((u) => u.default) ?? selectData.units?.[0];
            const defaultVat  = selectData.vats?.find((v)  => v.default) ?? selectData.vats?.[0];
            setForm({
                ...EMPTY_FORM,
                ordre:              selectData._nextOrdre         ?? 1,
                methods_units_id:   defaultUnit?.id              ?? '',
                accounting_vats_id: defaultVat?.id               ?? '',
                discount:           selectData.customer_discount ?? 0,
                delivery_date:      selectData.default_delivery  ?? '',
            });
            setProductSearch('');
        }
        setErrors({});
        setPriceList([]);
    }, [open, editingLine]);

    // Load price list when product changes
    useEffect(() => {
        if (!form.product_id || !open) { setPriceList([]); return; }
        const url = endpoints.priceList.replace('__PRODUCT__', form.product_id);
        fetch(url, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((d) => setPriceList(d.price_list ?? []))
            .catch(() => setPriceList([]));
    }, [form.product_id, open]);

    const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));

    const handleProductSelect = (product) => {
        setForm((f) => ({
            ...f,
            product_id:       product.id,
            code:             product.code,
            label:            product.label ?? f.label,
            methods_units_id: product.methods_units_id ?? f.methods_units_id,
            selling_price:    product.selling_price     ?? f.selling_price,
        }));
        setProductSearch(`${product.code} — ${product.label}`);
        setShowProductList(false);
    };

    const handleProductClear = () => {
        set('product_id', '');
        setProductSearch('');
        setPriceList([]);
    };

    const filteredProducts = (selectData.products ?? [])
        .filter((p) => {
            const q = productSearch.toLowerCase();
            return !q || p.code.toLowerCase().includes(q) || p.label.toLowerCase().includes(q);
        })
        .slice(0, 30);

    const resetFormForCreate = (nextOrdre) => {
        const defaultUnit = selectData.units?.find((u) => u.default) ?? selectData.units?.[0];
        const defaultVat  = selectData.vats?.find((v)  => v.default) ?? selectData.vats?.[0];
        setForm({
            ...EMPTY_FORM,
            ordre:              nextOrdre,
            methods_units_id:   defaultUnit?.id              ?? '',
            accounting_vats_id: defaultVat?.id               ?? '',
            discount:           selectData.customer_discount ?? 0,
            delivery_date:      selectData.default_delivery  ?? '',
        });
        setProductSearch('');
        setErrors({});
        setPriceList([]);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (isReadOnly) return;
        setSaving(true);
        setErrors({});
        const isEdit = !!editingLine;
        const url    = isEdit
            ? endpoints.update.replace('__ID__', editingLine.id)
            : endpoints.store;
        try {
            const res  = await apiFetch(url, { method: isEdit ? 'PUT' : 'POST', body: JSON.stringify(form) });
            const data = await res.json();
            if (!res.ok) { setErrors(data.errors ?? { _global: data.message ?? 'Erreur' }); return; }
            onSaved(data.line, isEdit);
            if (isEdit) {
                onClose();
            } else {
                resetFormForCreate((data.line.ordre ?? form.ordre) + 1);
            }
        } catch {
            setErrors({ _global: 'Erreur réseau' });
        } finally {
            setSaving(false);
        }
    };

    const canAdd = !isReadOnly;

    return (
        <>
            {/* Backdrop */}
            <div onClick={onClose} style={{
                position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.35)',
                zIndex: 1040, opacity: open ? 1 : 0,
                pointerEvents: open ? 'auto' : 'none',
                transition: 'opacity 0.2s',
            }} />

            {/* Tab handle */}
            {canAdd && (
                <button
                    type="button"
                    onClick={onOpenCreate}
                    style={{
                        position: 'fixed',
                        right: 0,
                        top: '50%',
                        transform: 'translateY(-50%)',
                        width: 40,
                        padding: '18px 0',
                        background: '#28a745',
                        color: '#fff',
                        border: 'none',
                        borderRadius: '6px 0 0 6px',
                        boxShadow: '-3px 0 8px rgba(0,0,0,0.18)',
                        cursor: 'pointer',
                        opacity: open ? 0 : 1,
                        pointerEvents: open ? 'none' : 'auto',
                        transition: 'opacity 0.2s',
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        gap: 8,
                        zIndex: 1049,
                    }}
                    title="Ajouter une ligne"
                >
                    <i className="fas fa-plus" style={{ fontSize: 14 }} />
                    <span style={{
                        writingMode: 'vertical-rl',
                        transform: 'rotate(180deg)',
                        fontSize: 10,
                        fontWeight: 700,
                        letterSpacing: 1,
                        textTransform: 'uppercase',
                    }}>Ajouter</span>
                </button>
            )}

            {/* Panel */}
            <div style={{
                position: 'fixed', top: 0, right: 0, bottom: 0,
                width: 460, maxWidth: '95vw',
                background: '#fff', zIndex: 1050,
                display: 'flex', flexDirection: 'column',
                transform: open ? 'translateX(0)' : 'translateX(100%)',
                transition: 'transform 0.25s ease',
                boxShadow: '-4px 0 24px rgba(0,0,0,0.15)',
            }}>

                {/* Header */}
                <div className="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-light">
                    <strong>
                        {editingLine
                            ? <><i className="fas fa-edit mr-2 text-warning" />Modifier la ligne</>
                            : <><i className="fas fa-plus-circle mr-2 text-success" />Nouvelle ligne</>}
                    </strong>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onClose}>
                        <i className="fas fa-times" />
                    </button>
                </div>

                {/* Body */}
                <div className="flex-grow-1 overflow-auto p-3">
                    {errors._global && <div className="alert alert-danger py-2">{errors._global}</div>}

                    <form id="line-drawer-form" onSubmit={handleSubmit}>

                        {/* Product */}
                        <div className="form-group mb-2 position-relative">
                            <label className="mb-1 small font-weight-bold">Produit</label>
                            <div className="input-group input-group-sm">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-barcode" /></span>
                                </div>
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Rechercher un produit…"
                                    value={productSearch}
                                    onChange={(e) => { setProductSearch(e.target.value); setShowProductList(true); if (!e.target.value) handleProductClear(); }}
                                    onFocus={() => setShowProductList(true)}
                                    onBlur={() => setTimeout(() => setShowProductList(false), 150)}
                                    disabled={isReadOnly}
                                />
                                {form.product_id && (
                                    <div className="input-group-append">
                                        <button type="button" className="btn btn-sm btn-outline-secondary" onClick={handleProductClear}>
                                            <i className="fas fa-times" />
                                        </button>
                                    </div>
                                )}
                            </div>
                            {showProductList && filteredProducts.length > 0 && (
                                <div style={{
                                    position: 'absolute', zIndex: 1060, left: 0, right: 0,
                                    background: '#fff', border: '1px solid #ccc',
                                    borderRadius: '0 0 4px 4px', maxHeight: 200, overflowY: 'auto',
                                    boxShadow: '0 4px 8px rgba(0,0,0,0.12)',
                                }}>
                                    {filteredProducts.map((p) => (
                                        <div key={p.id} className="px-3 py-2"
                                            style={{ cursor: 'pointer', fontSize: '0.85rem' }}
                                            onMouseDown={() => handleProductSelect(p)}
                                            onMouseEnter={(e) => e.currentTarget.style.background = '#f0f4ff'}
                                            onMouseLeave={(e) => e.currentTarget.style.background = ''}>
                                            <strong>{p.code}</strong>
                                            <span className="text-muted ml-2">{p.label}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Code + Ordre */}
                        <div className="form-row">
                            <div className="form-group col-8 mb-2">
                                <label className="mb-1 small font-weight-bold">Réf. externe</label>
                                <div className="input-group input-group-sm">
                                    <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-external-link-square-alt" /></span></div>
                                    <input type="text" className={`form-control ${errors.code ? 'is-invalid' : ''}`}
                                        placeholder="Code externe" value={form.code}
                                        onChange={(e) => set('code', e.target.value)} disabled={isReadOnly} />
                                </div>
                            </div>
                            <div className="form-group col-4 mb-2">
                                <label className="mb-1 small font-weight-bold">Ordre</label>
                                <input type="number" className={`form-control form-control-sm ${errors.ordre ? 'is-invalid' : ''}`}
                                    min="1" value={form.ordre}
                                    onChange={(e) => set('ordre', e.target.value)} disabled={isReadOnly} />
                            </div>
                        </div>

                        {/* Label */}
                        <div className="form-group mb-2">
                            <label className="mb-1 small font-weight-bold">Description <span className="text-danger">*</span></label>
                            <div className="input-group input-group-sm">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-tags" /></span></div>
                                <input type="text" className={`form-control ${errors.label ? 'is-invalid' : ''}`}
                                    placeholder="Description de la ligne" value={form.label}
                                    onChange={(e) => set('label', e.target.value)} disabled={isReadOnly} />
                            </div>
                            {errors.label && <div className="invalid-feedback d-block">{errors.label[0]}</div>}
                        </div>

                        {/* Qty + Unit */}
                        <div className="form-row">
                            <div className="form-group col-5 mb-2">
                                <label className="mb-1 small font-weight-bold">Quantité <span className="text-danger">*</span></label>
                                <div className="input-group input-group-sm">
                                    <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-times" /></span></div>
                                    <input type="number" className={`form-control ${errors.qty ? 'is-invalid' : ''}`}
                                        min="0" step="0.001" value={form.qty}
                                        onChange={(e) => set('qty', e.target.value)} disabled={isReadOnly} />
                                </div>
                                {errors.qty && <div className="invalid-feedback d-block">{errors.qty[0]}</div>}
                            </div>
                            <div className="form-group col-7 mb-2">
                                <label className="mb-1 small font-weight-bold">Unité</label>
                                <select className="form-control form-control-sm" value={form.methods_units_id}
                                    onChange={(e) => set('methods_units_id', e.target.value)} disabled={isReadOnly}>
                                    <option value="">— Unité —</option>
                                    {(selectData.units ?? []).map((u) => (
                                        <option key={u.id} value={u.id}>{u.code} - {u.label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Price + Discount */}
                        <div className="form-row">
                            <div className="form-group col-6 mb-2">
                                <label className="mb-1 small font-weight-bold">Prix unitaire <span className="text-danger">*</span></label>
                                <div className="input-group input-group-sm">
                                    <div className="input-group-prepend"><span className="input-group-text">{selectData.currency ?? '€'}</span></div>
                                    <input type="number" className={`form-control ${errors.selling_price ? 'is-invalid' : ''}`}
                                        min="0" step="0.001" value={form.selling_price}
                                        onChange={(e) => set('selling_price', e.target.value)} disabled={isReadOnly} />
                                </div>
                                {errors.selling_price && <div className="invalid-feedback d-block">{errors.selling_price[0]}</div>}
                            </div>
                            <div className="form-group col-6 mb-2">
                                <label className="mb-1 small font-weight-bold">Remise %</label>
                                <div className="input-group input-group-sm">
                                    <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-percentage" /></span></div>
                                    <input type="number" className={`form-control ${errors.discount ? 'is-invalid' : ''}`}
                                        min="0" max="100" step="0.01" value={form.discount}
                                        onChange={(e) => set('discount', e.target.value)} disabled={isReadOnly} />
                                </div>
                            </div>
                        </div>

                        {/* VAT + Delivery */}
                        <div className="form-row">
                            <div className="form-group col-6 mb-2">
                                <label className="mb-1 small font-weight-bold">TVA</label>
                                <select className="form-control form-control-sm" value={form.accounting_vats_id}
                                    onChange={(e) => set('accounting_vats_id', e.target.value)} disabled={isReadOnly}>
                                    <option value="">— TVA —</option>
                                    {(selectData.vats ?? []).map((v) => (
                                        <option key={v.id} value={v.id}>{v.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group col-6 mb-2">
                                <label className="mb-1 small font-weight-bold">Date livraison</label>
                                <input type="date" className="form-control form-control-sm" value={form.delivery_date}
                                    onChange={(e) => set('delivery_date', e.target.value)} disabled={isReadOnly} />
                            </div>
                        </div>

                        <PriceGrid priceList={priceList} currentQty={form.qty}
                            onApplyPrice={(price) => set('selling_price', price)} />
                    </form>
                </div>

                {/* Footer */}
                <div className="border-top px-3 py-2 d-flex justify-content-between align-items-center bg-light">
                    {editingLine?.detail_url ? (
                        <a href={editingLine.detail_url} className="btn btn-sm btn-outline-info"
                            target="_blank" rel="noreferrer">
                            <i className="fas fa-info-circle mr-1" />Détails techniques
                        </a>
                    ) : <span />}
                    <div>
                        <button type="button" className="btn btn-sm btn-outline-secondary mr-2" onClick={onClose}>
                            Annuler
                        </button>
                        {!isReadOnly && (
                            <button type="submit" form="line-drawer-form" className="btn btn-sm btn-success" disabled={saving}>
                                {editingLine
                                    ? saving
                                        ? <><i className="fas fa-spinner fa-spin mr-1" />Enregistrement…</>
                                        : <><i className="fas fa-save mr-1" />Enregistrer</>
                                    : saving
                                        ? <><i className="fas fa-spinner fa-spin mr-1" />Ajout…</>
                                        : <><i className="fas fa-plus mr-1" />Ajouter</>}
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// LinesPopover — liste cliquable des BL / factures associés
// ---------------------------------------------------------------------------

function LinesPopover({ items, badgeClass, badgeLabel }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    useEffect(() => {
        if (!open) return;
        const close = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, [open]);

    if (!items || items.length === 0) {
        return <span className={`badge ${badgeClass}`}>{badgeLabel}</span>;
    }

    return (
        <span ref={ref} style={{ position: 'relative' }}>
            <span className={`badge ${badgeClass}`} style={{ cursor: 'pointer' }} onClick={() => setOpen((o) => !o)}>
                {badgeLabel} <i className="fas fa-external-link-alt ml-1" style={{ fontSize: 9 }} />
            </span>
            {open && (
                <div style={{
                    position: 'absolute', zIndex: 1050, top: '100%', left: 0, minWidth: 200,
                    background: '#fff', border: '1px solid #dee2e6', borderRadius: 4,
                    boxShadow: '0 4px 12px rgba(0,0,0,.15)', padding: '0.5rem',
                }}>
                    <ul className="list-unstyled mb-0">
                        {items.map((item) => (
                            <li key={item.id} className="mb-1">
                                <a href={item.url} className="text-primary font-weight-bold" target="_blank" rel="noreferrer">
                                    {item.code}
                                </a>
                                {' — '}<small className="text-muted">{item.qty}</small>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </span>
    );
}

// ---------------------------------------------------------------------------
// LineRow
// ---------------------------------------------------------------------------

function LineRow({
    line, isReadOnly, onEdit, onDelete, onDuplicate, onOpenTaskModal, onToggleSelect, selected,
    onDragStart, onDragEnter, onDragEnd, isDragOver, isDragging,
}) {
    const [menuOpen, setMenuOpen] = useState(false);
    const [menuStyle, setMenuStyle] = useState({});
    const menuRef   = useRef(null);
    const toggleRef = useRef(null);

    useEffect(() => {
        if (!menuOpen) return;
        const close = (e) => { if (menuRef.current && !menuRef.current.contains(e.target)) setMenuOpen(false); };
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, [menuOpen]);

    const handleToggleMenu = () => {
        if (!menuOpen && toggleRef.current) {
            const rect   = toggleRef.current.getBoundingClientRect();
            const menuH  = 180;
            const openUp = rect.bottom + menuH > window.innerHeight;
            setMenuStyle(openUp
                ? { position: 'fixed', bottom: window.innerHeight - rect.top, top: 'auto', left: 'auto', right: window.innerWidth - rect.right, width: 'auto', minWidth: 140, maxWidth: 180, zIndex: 1060 }
                : { position: 'fixed', top: rect.bottom, bottom: 'auto', left: 'auto', right: window.innerWidth - rect.right, width: 'auto', minWidth: 140, maxWidth: 180, zIndex: 1060 }
            );
        }
        setMenuOpen((v) => !v);
    };

    const tasksCfg    = TASKS_STATUS_CONFIG[line.tasks_status]    ?? { badge: 'badge-secondary', label: '—' };
    const deliveryCfg = DELIVERY_STATUS_CONFIG[line.delivery_status] ?? { badge: 'badge-secondary', label: '—' };
    const invoiceCfg  = INVOICE_STATUS_CONFIG[line.invoice_status]   ?? { badge: 'badge-secondary', label: '—' };

    const rowStyle = {
        opacity:    isDragging ? 0.4  : 1,
        borderTop:  isDragOver ? '3px solid #007bff' : undefined,
        transition: 'border-top 0.1s, opacity 0.15s',
        background: selected  ? '#eef4ff' : undefined,
    };

    return (
        <tr
            draggable={!isReadOnly}
            onDragStart={!isReadOnly ? onDragStart : undefined}
            onDragEnter={!isReadOnly ? onDragEnter : undefined}
            onDragEnd={!isReadOnly ? onDragEnd : undefined}
            onDragOver={!isReadOnly ? (e) => e.preventDefault() : undefined}
            style={rowStyle}
        >
            {/* Drag + ordre */}
            <td style={{ width: 48, cursor: !isReadOnly ? 'grab' : 'default', userSelect: 'none', color: '#aaa' }}>
                <i className="fas fa-grip-vertical mr-1" />
                <span className="text-muted small">{line.ordre}</span>
            </td>

            {/* Select */}
            <td style={{ width: 32 }}>
                <input type="checkbox" checked={selected} onChange={() => onToggleSelect(line.id)} />
            </td>

            {/* Code */}
            <td className="small">{line.code || '—'}</td>

            {/* Product link */}
            <td style={{ width: 36 }}>
                {line.product_id && line.product_url
                    ? <a href={line.product_url} className="btn btn-xs btn-outline-secondary" target="_blank" rel="noreferrer"><i className="fas fa-cube" /></a>
                    : null}
            </td>

            {/* Label */}
            <td style={{ maxWidth: 180, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                <span title={line.label}>{line.label}</span>
            </td>

            {/* Qty */}
            <td className="text-right">{line.qty}</td>

            {/* Unit */}
            <td className="small">{line.unit_label ?? '—'}</td>

            {/* Price */}
            <td className={`text-right font-weight-bold ${line.use_calculated_price ? 'bg-warning' : ''}`}
                title={line.use_calculated_price ? 'Prix calculé' : ''}>
                {line.formatted_price}
            </td>

            {/* Discount */}
            <td className="text-right">{line.discount} %</td>

            {/* VAT */}
            <td className="small">{line.vat_label ?? '—'}</td>

            {/* Delivery date */}
            <td className="small" style={{ whiteSpace: 'nowrap' }}>{formatDate(line.delivery_date)}</td>

            {/* Tasks status */}
            <td><span className={`badge ${tasksCfg.badge}`}>{tasksCfg.label}</span></td>

            {/* Delivery status — cliquable si des BL existent */}
            <td>
                <LinesPopover
                    items={(line.delivery_lines ?? []).map((dl) => ({
                        id: dl.id, qty: dl.qty, code: dl.delivery_code, url: dl.delivery_url,
                    }))}
                    badgeClass={deliveryCfg.badge}
                    badgeLabel={`${deliveryCfg.label}${line.delivered_qty > 0 ? ` (${line.delivered_qty})` : ''}`}
                />
            </td>

            {/* Invoice status — cliquable si des factures existent */}
            <td>
                <LinesPopover
                    items={(line.invoice_lines ?? []).map((il) => ({
                        id: il.id, qty: il.qty, code: il.invoice_code, url: il.invoice_url,
                    }))}
                    badgeClass={invoiceCfg.badge}
                    badgeLabel={`${invoiceCfg.label}${line.invoiced_qty > 0 ? ` (${line.invoiced_qty})` : ''}`}
                />
            </td>

            {/* Actions */}
            <td>
                <div className="btn-group btn-group-xs" ref={menuRef}>
                    <a href={line.detail_url} className="btn btn-xs bg-teal" target="_blank" rel="noreferrer" title="Détails techniques">
                        <i className="fas fa-info-circle" />
                    </a>
                    {line.task_count > 0 && (
                        <button type="button" className="btn btn-xs btn-warning"
                            title={`Tâches (${line.task_count})`}
                            onClick={() => onOpenTaskModal(line)}>
                            <span className="badge badge-dark">{line.task_count}</span>
                        </button>
                    )}
                    <button ref={toggleRef} className="btn btn-xs btn-default dropdown-toggle dropdown-toggle-split"
                        onClick={handleToggleMenu} />
                    {menuOpen && (
                        <div className="dropdown-menu show" style={menuStyle}>
                            {!isReadOnly && (
                                <button className="dropdown-item"
                                    onClick={() => { onEdit(line); setMenuOpen(false); }}>
                                    <i className="fas fa-edit fa-fw mr-2 text-warning" />Modifier
                                </button>
                            )}
                            {!isReadOnly && (
                                <button className="dropdown-item"
                                    onClick={() => { onDuplicate(line.id); setMenuOpen(false); }}>
                                    <i className="fas fa-copy fa-fw mr-2 text-info" />Dupliquer
                                </button>
                            )}
                            {!isReadOnly && (
                                <button className="dropdown-item text-danger"
                                    onClick={() => { onDelete(line.id); setMenuOpen(false); }}>
                                    <i className="fas fa-trash fa-fw mr-2" />Supprimer
                                </button>
                            )}
                            <div className="dropdown-divider" />
                            <a className="dropdown-item" href={line.detail_url} target="_blank" rel="noreferrer">
                                <i className="fas fa-info-circle fa-fw mr-2 text-teal" />Détails techniques
                            </a>
                            <button className="dropdown-item"
                                onClick={() => { onOpenTaskModal(line); setMenuOpen(false); }}>
                                <i className="fas fa-list fa-fw mr-2 text-warning" />Tâches ({line.task_count})
                            </button>
                        </div>
                    )}
                </div>
            </td>
        </tr>
    );
}

// ---------------------------------------------------------------------------
// OrderLinesPage
// ---------------------------------------------------------------------------

export default function OrderLinesPage({ orderId, orderStatu: initialStatu, orderType: initialType, canManageStock, endpoints }) {
    const [lines, setLines]             = useState([]);
    const [orderStatu, setOrderStatu]   = useState(Number(initialStatu));
    const [orderType]                   = useState(Number(initialType));
    const [selectData, setSelectData]   = useState({});
    const [loading, setLoading]         = useState(true);
    const [drawerOpen, setDrawerOpen]   = useState(false);
    const [editingLine, setEditingLine] = useState(null);
    const [search, setSearch]           = useState('');
    const [selected, setSelected]       = useState(new Set());
    const [priceIncAmt, setPriceIncAmt] = useState('');
    const [flash, setFlash]             = useState(null);
    const [taskModalLine, setTaskModalLine] = useState(null);
    const [removeFromStock, setRemoveFromStock]       = useState(false);
    const [createSerialNumber, setCreateSerialNumber] = useState(false);

    // Drag-and-drop state
    const dragIndexRef  = useRef(null);
    const [dragOver, setDragOver] = useState(null);

    const isReadOnly = orderStatu === 6 || orderType === 2;

    const showFlash = (type, msg) => {
        setFlash({ type, msg });
        setTimeout(() => setFlash(null), 3500);
    };

    const refreshNextOrdre = (updated) => {
        const next = updated.reduce((m, l) => Math.max(m, l.ordre), 0) + 1;
        setSelectData((sd) => ({ ...sd, _nextOrdre: next }));
    };

    // Initial load
    useEffect(() => {
        Promise.all([
            fetch(endpoints.lines,      { headers: { Accept: 'application/json' } }).then((r) => r.json()),
            fetch(endpoints.selectData, { headers: { Accept: 'application/json' } }).then((r) => r.json()),
        ])
        .then(([linesData, sd]) => {
            const ls = linesData.lines ?? [];
            setLines(ls);
            setOrderStatu(Number(linesData.order_statu ?? initialStatu));
            const next = ls.reduce((m, l) => Math.max(m, l.ordre), 0) + 1;
            setSelectData({ ...sd, _nextOrdre: next });
        })
        .catch(() => showFlash('danger', 'Erreur lors du chargement'))
        .finally(() => setLoading(false));
    }, []);

    // ---------- Drag handlers ----------

    const handleDragStart = useCallback((e, index) => {
        dragIndexRef.current = index;
        e.dataTransfer.effectAllowed = 'move';
        const ghost = document.createElement('span');
        ghost.style.cssText = 'position:fixed;top:-999px';
        document.body.appendChild(ghost);
        e.dataTransfer.setDragImage(ghost, 0, 0);
        setTimeout(() => document.body.removeChild(ghost), 0);
    }, []);

    const handleDragEnter = useCallback((e, index) => {
        e.preventDefault();
        setDragOver(index);
    }, []);

    const handleDragEnd = useCallback(async () => {
        const fromIndex = dragIndexRef.current;
        const toIndex   = dragOver;
        dragIndexRef.current = null;
        setDragOver(null);

        if (fromIndex === null || toIndex === null || fromIndex === toIndex) return;

        const sorted = [...lines].sort((a, b) => a.ordre - b.ordre);
        const currentSearch = search.toLowerCase();
        const filtered = sorted.filter((l) =>
            !currentSearch ||
            (l.label ?? '').toLowerCase().includes(currentSearch) ||
            (l.code  ?? '').toLowerCase().includes(currentSearch) ||
            (l.product_code ?? '').toLowerCase().includes(currentSearch)
        );

        const [moved] = filtered.splice(fromIndex, 1);
        filtered.splice(toIndex, 0, moved);

        const filteredIds = new Set(filtered.map((l) => l.id));
        const hidden      = sorted.filter((l) => !filteredIds.has(l.id));

        const reordered = [
            ...filtered.map((l, i) => ({ ...l, ordre: i + 1 })),
            ...hidden.map((l, i) => ({ ...l, ordre: filtered.length + i + 1 })),
        ];

        setLines(reordered);
        refreshNextOrdre(reordered);

        try {
            await apiFetch(endpoints.reorder, {
                method: 'POST',
                body: JSON.stringify({ order: reordered.map((l) => ({ id: l.id, ordre: l.ordre })) }),
            });
        } catch {
            showFlash('danger', 'Erreur lors de la sauvegarde de l\'ordre');
        }
    }, [lines, dragOver, search, endpoints]);

    // ---------- CRUD ----------

    const handleOpenCreate = () => { setEditingLine(null); setDrawerOpen(true); };
    const handleEdit        = (line) => { setEditingLine(line); setDrawerOpen(true); };

    const handleSaved = (savedLine, isEdit) => {
        setLines((prev) => {
            const updated = isEdit
                ? prev.map((l) => (l.id === savedLine.id ? savedLine : l))
                : [...prev, savedLine];
            refreshNextOrdre(updated);
            return updated;
        });
        showFlash('success', isEdit ? 'Ligne mise à jour' : 'Ligne ajoutée');
    };

    const handleDelete = async (id) => {
        if (!confirm('Supprimer cette ligne ?')) return;
        const res = await apiFetch(endpoints.destroy.replace('__ID__', id), { method: 'DELETE' });
        if (res.ok) {
            setLines((prev) => { const u = prev.filter((l) => l.id !== id); refreshNextOrdre(u); return u; });
            setSelected((s) => { s.delete(id); return new Set(s); });
            showFlash('success', 'Ligne supprimée');
        } else {
            showFlash('danger', 'Erreur lors de la suppression');
        }
    };

    const handleDuplicate = async (id) => {
        const res  = await apiFetch(endpoints.duplicate.replace('__ID__', id), { method: 'POST' });
        const data = await res.json();
        if (res.ok) {
            setLines((prev) => { const u = [...prev, data.line]; refreshNextOrdre(u); return u; });
            showFlash('success', 'Ligne dupliquée');
        } else {
            showFlash('danger', 'Erreur lors de la duplication');
        }
    };

    const handlePriceIncrease = async () => {
        const amt = parseFloat(priceIncAmt);
        if (!amt || amt <= 0) return;
        const res  = await apiFetch(endpoints.priceIncrease, { method: 'POST', body: JSON.stringify({ amount: amt }) });
        if (res.ok) {
            const linesRes  = await fetch(endpoints.lines, { headers: { Accept: 'application/json' } });
            const linesData = await linesRes.json();
            setLines(linesData.lines ?? []);
            setPriceIncAmt('');
            showFlash('success', 'Prix augmenté');
        } else {
            showFlash('danger', 'Erreur lors de la mise à jour des prix');
        }
    };

    const handleToggleSelect = (id) => {
        setSelected((s) => { const ns = new Set(s); ns.has(id) ? ns.delete(id) : ns.add(id); return ns; });
    };

    const handleStoreDelivery = async () => {
        const ids = [...selected];
        if (ids.length === 0) return;
        if (!confirm(`Créer un BL à partir des ${ids.length} ligne(s) sélectionnée(s) ?`)) return;
        try {
            const res  = await apiFetch(endpoints.storeDelivery, {
                method: 'POST',
                body: JSON.stringify({ line_ids: ids, remove_from_stock: removeFromStock, create_serial_number: createSerialNumber }),
            });
            const data = await res.json();
            if (res.ok && data.redirect) {
                window.location.href = data.redirect;
            } else {
                showFlash('danger', data.error ?? 'Erreur lors de la création du BL');
            }
        } catch {
            showFlash('danger', 'Erreur réseau');
        }
    };

    const handleStoreInvoice = async () => {
        const ids = [...selected];
        if (ids.length === 0) return;
        if (!confirm(`Créer une facture à partir des ${ids.length} ligne(s) sélectionnée(s) ?`)) return;
        try {
            const res  = await apiFetch(endpoints.storeInvoice, {
                method: 'POST',
                body: JSON.stringify({ line_ids: ids, remove_from_stock: removeFromStock, create_serial_number: createSerialNumber }),
            });
            const data = await res.json();
            if (res.ok && data.redirect) {
                window.location.href = data.redirect;
            } else {
                showFlash('danger', data.error ?? 'Erreur lors de la création de la facture');
            }
        } catch {
            showFlash('danger', 'Erreur réseau');
        }
    };

    const handleCreateProducts = async () => {
        const ids = [...selected];
        if (ids.length === 0) return;
        if (!confirm(`Créer des produits à partir des ${ids.length} ligne(s) sélectionnée(s) ?`)) return;
        try {
            const res  = await apiFetch(endpoints.createProducts, {
                method: 'POST',
                body: JSON.stringify({ line_ids: ids }),
            });
            const data = await res.json();
            if (res.ok) {
                // Update product_id on the affected lines
                setLines((prev) => prev.map((l) => {
                    const match = (data.created ?? []).find((c) => c.line_id === l.id);
                    return match ? { ...l, product_id: match.product_id, product_url: match.product_url } : l;
                }));
                showFlash('success', `${data.created?.length ?? 0} produit(s) créé(s)`);
            } else {
                showFlash('danger', data.error ?? 'Erreur lors de la création des produits');
            }
        } catch {
            showFlash('danger', 'Erreur réseau');
        }
    };

    const filteredLines = lines
        .filter((l) => {
            const q = search.toLowerCase();
            return !q || (l.label ?? '').toLowerCase().includes(q)
                       || (l.code  ?? '').toLowerCase().includes(q)
                       || (l.product_code ?? '').toLowerCase().includes(q);
        })
        .sort((a, b) => a.ordre - b.ordre);

    const allSelected = filteredLines.length > 0 && filteredLines.every((l) => selected.has(l.id));
    const handleToggleAll = () => {
        const ids = filteredLines.map((l) => l.id);
        if (allSelected) {
            setSelected((s) => { const ns = new Set(s); ids.forEach((id) => ns.delete(id)); return ns; });
        } else {
            setSelected((s) => { const ns = new Set(s); ids.forEach((id) => ns.add(id)); return ns; });
        }
    };

    return (
        <div>
            {/* Flash */}
            {flash && (
                <div className={`alert alert-${flash.type} alert-dismissible py-2 mb-2`}>
                    {flash.msg}
                    <button type="button" className="close" onClick={() => setFlash(null)}><span>&times;</span></button>
                </div>
            )}

            {/* Toolbar */}
            <div className="d-flex flex-wrap align-items-center mb-3" style={{ gap: '0.5rem' }}>
                <div className="input-group input-group-sm" style={{ maxWidth: 260 }}>
                    <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-search" /></span></div>
                    <input type="text" className="form-control" placeholder="Rechercher…"
                        value={search} onChange={(e) => setSearch(e.target.value)} />
                    {search && (
                        <div className="input-group-append">
                            <button className="btn btn-outline-secondary" onClick={() => setSearch('')}><i className="fas fa-times" /></button>
                        </div>
                    )}
                </div>
                {!isReadOnly && (
                    <div className="input-group input-group-sm" style={{ maxWidth: 280 }}>
                        <input type="number" className="form-control" placeholder="Augmentation de prix…"
                            min="0" step="0.01" value={priceIncAmt}
                            onChange={(e) => setPriceIncAmt(e.target.value)} />
                        <div className="input-group-append">
                            <button className="btn btn-primary"
                                onClick={handlePriceIncrease}
                                disabled={!priceIncAmt || parseFloat(priceIncAmt) <= 0}>
                                <i className="fas fa-plus-circle mr-1" />Appliquer
                            </button>
                        </div>
                    </div>
                )}
                <span className="badge badge-secondary ml-auto">
                    {filteredLines.length} ligne{filteredLines.length > 1 ? 's' : ''}
                </span>
            </div>

            {/* Bulk actions — only when lines are selected and order not cancelled */}
            {selected.size > 0 && !isReadOnly && (
                <div className="card card-outline card-primary mb-3">
                    <div className="card-body py-2 px-3">
                        <div className="d-flex flex-wrap align-items-center" style={{ gap: '0.75rem' }}>
                            {/* Stock / serial options (guarded by canManageStock) */}
                            {canManageStock && (
                                <>
                                    <div className="form-check form-check-inline mb-0">
                                        <input className="form-check-input" type="checkbox" id="removeFromStock"
                                            checked={removeFromStock}
                                            onChange={(e) => setRemoveFromStock(e.target.checked)} />
                                        <label className="form-check-label small" htmlFor="removeFromStock">
                                            Retirer du stock
                                        </label>
                                    </div>
                                    <div className="form-check form-check-inline mb-0">
                                        <input className="form-check-input" type="checkbox" id="createSerialNumber"
                                            checked={createSerialNumber}
                                            onChange={(e) => setCreateSerialNumber(e.target.checked)} />
                                        <label className="form-check-label small" htmlFor="createSerialNumber">
                                            Créer n° série
                                        </label>
                                    </div>
                                    <span className="text-muted" style={{ borderLeft: '1px solid #dee2e6', height: 20 }} />
                                </>
                            )}

                            {/* Action buttons */}
                            <button className="btn btn-success btn-sm" onClick={handleCreateProducts}>
                                <i className="fas fa-barcode mr-1" />
                                Créer produits ({selected.size})
                            </button>
                            <button className="btn btn-primary btn-sm" onClick={handleStoreDelivery}>
                                <i className="fas fa-folder mr-1" />
                                Nouveau BL ({selected.size})
                            </button>
                            <button className="btn btn-primary btn-sm" onClick={handleStoreInvoice}>
                                <i className="fas fa-file-invoice mr-1" />
                                Nouvelle facture ({selected.size})
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Hint drag */}
            {!isReadOnly && filteredLines.length > 1 && !search && (
                <p className="text-muted small mb-2">
                    <i className="fas fa-grip-vertical mr-1" />
                    Glissez les lignes pour modifier l'ordre.
                </p>
            )}

            {/* Table */}
            <div className="table-responsive p-0">
                <table className="table table-hover table-sm mb-0">
                    <thead className="thead-light">
                        <tr>
                            <th style={{ width: 48 }} title="Glisser pour réordonner">
                                <i className="fas fa-grip-vertical text-muted" />
                            </th>
                            <th style={{ width: 32 }}>
                                <input type="checkbox" checked={allSelected} onChange={handleToggleAll}
                                    disabled={filteredLines.length === 0} />
                            </th>
                            <th>Réf. ext.</th>
                            <th style={{ width: 36 }} />
                            <th>Description</th>
                            <th className="text-right">Qté</th>
                            <th>Unité</th>
                            <th className="text-right">Prix</th>
                            <th className="text-right">Remise</th>
                            <th>TVA</th>
                            <th>Livraison</th>
                            <th>Tâches</th>
                            <th>Livraison</th>
                            <th>Facturation</th>
                            <th style={{ width: 120 }}>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={15} className="text-center py-4">
                                    <i className="fas fa-spinner fa-spin mr-2" />Chargement…
                                </td>
                            </tr>
                        ) : filteredLines.length === 0 ? (
                            <tr>
                                <td colSpan={15} className="text-center py-4 text-muted">
                                    {search ? 'Aucune ligne ne correspond.' : 'Aucune ligne dans cette commande.'}
                                    {!search && !isReadOnly && (
                                        <button className="btn btn-link p-0 ml-2" onClick={handleOpenCreate}>
                                            Ajouter la première ligne
                                        </button>
                                    )}
                                </td>
                            </tr>
                        ) : filteredLines.map((line, index) => (
                            <LineRow
                                key={line.id}
                                line={line}
                                isReadOnly={isReadOnly}
                                onEdit={handleEdit}
                                onDelete={handleDelete}
                                onDuplicate={handleDuplicate}
                                onOpenTaskModal={setTaskModalLine}
                                onToggleSelect={handleToggleSelect}
                                selected={selected.has(line.id)}
                                onDragStart={(e) => handleDragStart(e, index)}
                                onDragEnter={(e) => handleDragEnter(e, index)}
                                onDragEnd={handleDragEnd}
                                isDragOver={dragOver === index}
                                isDragging={dragIndexRef.current === index}
                            />
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Drawer */}
            <LineDrawer
                open={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                onOpenCreate={handleOpenCreate}
                editingLine={editingLine}
                selectData={selectData}
                endpoints={endpoints}
                onSaved={handleSaved}
                isReadOnly={isReadOnly}
            />

            {/* Task modal */}
            {taskModalLine && (
                <TaskModal
                    lineId={taskModalLine.id}
                    lineLabel={taskModalLine.label}
                    endpoints={endpoints}
                    isReadOnly={isReadOnly}
                    useCalculatedPrice={taskModalLine.use_calculated_price}
                    onLineUpdated={(updatedLine) => {
                        setLines((prev) => prev.map((l) => l.id === updatedLine.id ? updatedLine : l));
                        setTaskModalLine((prev) => prev ? { ...prev, use_calculated_price: updatedLine.use_calculated_price } : null);
                    }}
                    onClose={() => setTaskModalLine(null)}
                />
            )}
        </div>
    );
}
