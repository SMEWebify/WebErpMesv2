import React, { useState, useEffect, useRef, useCallback } from 'react';
import { formatQty } from '../utils';

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const RECEIPT_STATUS_CONFIG = {
    none:    { badge: 'badge-secondary', label: 'Non reçu' },
    partial: { badge: 'badge-warning',   label: 'Partiel' },
    full:    { badge: 'badge-success',   label: 'Reçu' },
};

const INVOICE_STATUS_CONFIG = {
    none:    { badge: 'badge-secondary', label: 'Non facturé' },
    partial: { badge: 'badge-warning',   label: 'Partiel' },
    full:    { badge: 'badge-success',   label: 'Facturé' },
};

const EMPTY_FORM = {
    ordre:              1,
    product_id:         '',
    code:               '',
    supplier_ref:       '',
    label:              '',
    qty:                1,
    methods_units_id:   '',
    selling_price:      0,
    discount:           0,
    accounting_vats_id: '',
    delivery_date:      '',
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
    if (!dateStr) return null;
    try {
        const [y, m, d] = dateStr.split('-').map(Number);
        return new Intl.DateTimeFormat('fr-FR').format(new Date(y, m - 1, d));
    } catch { return dateStr; }
}

function getReceiptStatus(qty, receiptQty) {
    if (!qty || qty <= 0) return 'none';
    if (receiptQty >= qty) return 'full';
    if (receiptQty > 0)   return 'partial';
    return 'none';
}

function getInvoiceStatus(qty, invoicedQty) {
    if (!qty || qty <= 0) return 'none';
    if (invoicedQty >= qty) return 'full';
    if (invoicedQty > 0)   return 'partial';
    return 'none';
}

// ---------------------------------------------------------------------------
// ReceiptPopover — liste cliquable des BR associés
// ---------------------------------------------------------------------------

function ReceiptPopover({ items, badgeClass, badgeLabel }) {
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
            <span
                className={`badge ${badgeClass}`}
                style={{ cursor: 'pointer' }}
                onClick={() => setOpen((o) => !o)}
            >
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
                                <a href={item.receipt_url} className="text-primary font-weight-bold"
                                    target="_blank" rel="noreferrer">
                                    {item.receipt_code}
                                </a>
                                {' — '}<small className="text-muted">{formatQty(item.qty)}</small>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </span>
    );
}

// ---------------------------------------------------------------------------
// DeliveryDateBadge
// ---------------------------------------------------------------------------

function DeliveryDateBadge({ dateStr }) {
    if (!dateStr) return <span className="text-muted">—</span>;

    const formatted = formatDate(dateStr);
    const date      = new Date(dateStr);
    const now       = new Date();
    now.setHours(0, 0, 0, 0);

    const isPast = date < now;

    return (
        <span className={`badge ${isPast ? 'badge-danger' : 'badge-success'}`} title="Date de livraison prévue">
            {isPast && <i className="fas fa-exclamation-triangle mr-1" style={{ fontSize: 9 }} />}
            {formatted}
        </span>
    );
}

// ---------------------------------------------------------------------------
// LineDrawer
// ---------------------------------------------------------------------------

function LineDrawer({ open, onClose, onOpenCreate, editingLine, selectData, endpoints, onSaved, isReadOnly }) {
    const [form, setForm]                   = useState(EMPTY_FORM);
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
                supplier_ref:       editingLine.supplier_ref        ?? '',
                label:              editingLine.label              ?? '',
                qty:                editingLine.qty                ?? 1,
                methods_units_id:   editingLine.methods_units_id   ?? '',
                selling_price:      editingLine.selling_price      ?? 0,
                discount:           editingLine.discount           ?? 0,
                accounting_vats_id: editingLine.accounting_vats_id ?? '',
                delivery_date:      editingLine.delivery_date       ?? '',
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
                ordre:              selectData._nextOrdre ?? 1,
                methods_units_id:   defaultUnit?.id       ?? '',
                accounting_vats_id: defaultVat?.id        ?? '',
            });
            setProductSearch('');
        }
        setErrors({});
    }, [open, editingLine]);

    const set = (field, value) => setForm((f) => ({ ...f, [field]: value }));

    const handleProductSelect = (product) => {
        setForm((f) => ({
            ...f,
            product_id:       product.id,
            code:             product.code   ?? f.code,
            label:            product.label  ?? f.label,
            methods_units_id: product.methods_units_id ?? f.methods_units_id,
            selling_price:    product.selling_price    ?? f.selling_price,
        }));
        setProductSearch(`${product.code} — ${product.label}`);
        setShowProductList(false);
    };

    const handleProductClear = () => {
        set('product_id', '');
        setProductSearch('');
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
            methods_units_id:   defaultUnit?.id ?? '',
            accounting_vats_id: defaultVat?.id  ?? '',
        });
        setProductSearch('');
        setErrors({});
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
                        position: 'fixed', right: 0, top: '50%',
                        transform: 'translateY(-50%)',
                        width: 40, padding: '18px 0',
                        background: '#17a2b8', color: '#fff',
                        border: 'none', borderRadius: '6px 0 0 6px',
                        boxShadow: '-3px 0 8px rgba(0,0,0,0.18)',
                        cursor: 'pointer',
                        opacity: open ? 0 : 1,
                        pointerEvents: open ? 'none' : 'auto',
                        transition: 'opacity 0.2s',
                        display: 'flex', flexDirection: 'column',
                        alignItems: 'center', gap: 8, zIndex: 1049,
                    }}
                    title="Ajouter une ligne"
                >
                    <i className="fas fa-plus" style={{ fontSize: 14 }} />
                    <span style={{
                        writingMode: 'vertical-rl',
                        transform: 'rotate(180deg)',
                        fontSize: 10, fontWeight: 700,
                        letterSpacing: 1, textTransform: 'uppercase',
                    }}>Ajouter</span>
                </button>
            )}

            {/* Panel */}
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
                <div className="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bg-light">
                    <strong>
                        {editingLine
                            ? <><i className="fas fa-edit mr-2 text-warning" />Modifier la ligne</>
                            : <><i className="fas fa-plus-circle mr-2 text-info" />Nouvelle ligne d'achat</>}
                    </strong>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={onClose}>
                        <i className="fas fa-times" />
                    </button>
                </div>

                {/* Body */}
                <div className="flex-grow-1 overflow-auto p-3">
                    {errors._global && <div className="alert alert-danger py-2">{errors._global}</div>}

                    <form id="purchase-line-drawer-form" onSubmit={handleSubmit}>

                        {/* Product */}
                        <div className="form-group mb-2 position-relative">
                            <label className="mb-1 small font-weight-bold">Produit fournisseur</label>
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

                        {/* Code externe + Réf fournisseur + Ordre */}
                        <div className="form-row">
                            <div className="form-group col-5 mb-2">
                                <label className="mb-1 small font-weight-bold">Réf. interne</label>
                                <div className="input-group input-group-sm">
                                    <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-tag" /></span></div>
                                    <input type="text" className="form-control"
                                        placeholder="Code interne" value={form.code}
                                        onChange={(e) => set('code', e.target.value)} disabled={isReadOnly} />
                                </div>
                            </div>
                            <div className="form-group col-5 mb-2">
                                <label className="mb-1 small font-weight-bold">Réf. fournisseur</label>
                                <div className="input-group input-group-sm">
                                    <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-external-link-square-alt" /></span></div>
                                    <input type="text" className="form-control"
                                        placeholder="Réf. fournisseur" value={form.supplier_ref}
                                        onChange={(e) => set('supplier_ref', e.target.value)} disabled={isReadOnly} />
                                </div>
                            </div>
                            <div className="form-group col-2 mb-2">
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
                                    <input type="number" className="form-control"
                                        min="0" max="100" step="0.01" value={form.discount}
                                        onChange={(e) => set('discount', e.target.value)} disabled={isReadOnly} />
                                </div>
                            </div>
                        </div>

                        {/* VAT + Delivery date */}
                        <div className="form-row">
                            <div className="form-group col-6 mb-2">
                                <label className="mb-1 small font-weight-bold">TVA</label>
                                <select className="form-control form-control-sm" value={form.accounting_vats_id}
                                    onChange={(e) => set('accounting_vats_id', e.target.value)} disabled={isReadOnly}>
                                    <option value="">— TVA —</option>
                                    {(selectData.vats ?? []).map((v) => (
                                        <option key={v.id} value={v.id}>{v.label} ({v.rate} %)</option>
                                    ))}
                                </select>
                            </div>
                            <div className="form-group col-6 mb-2">
                                <label className="mb-1 small font-weight-bold">Date livraison prévue</label>
                                <input type="date" className="form-control form-control-sm" value={form.delivery_date}
                                    onChange={(e) => set('delivery_date', e.target.value)} disabled={isReadOnly} />
                            </div>
                        </div>
                    </form>
                </div>

                {/* Footer */}
                <div className="border-top px-3 py-2 d-flex justify-content-between align-items-center bg-light">
                    <span />
                    <div>
                        <button type="button" className="btn btn-sm btn-outline-secondary mr-2" onClick={onClose}>
                            Annuler
                        </button>
                        {!isReadOnly && (
                            <button type="submit" form="purchase-line-drawer-form" className="btn btn-sm btn-info" disabled={saving}>
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
// LineRow
// ---------------------------------------------------------------------------

function LineRow({
    line, isReadOnly, onEdit, onDelete, onDuplicate, onToggleSelect, selected,
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
            const rect  = toggleRef.current.getBoundingClientRect();
            const menuH = 160;
            const openUp = rect.bottom + menuH > window.innerHeight;
            setMenuStyle(openUp
                ? { position: 'fixed', bottom: window.innerHeight - rect.top, top: 'auto', left: 'auto', right: window.innerWidth - rect.right, minWidth: 180, maxWidth: 220, zIndex: 1060 }
                : { position: 'fixed', top: rect.bottom, bottom: 'auto', left: 'auto', right: window.innerWidth - rect.right, minWidth: 180, maxWidth: 220, zIndex: 1060 }
            );
        }
        setMenuOpen((v) => !v);
    };

    const receiptStatus = getReceiptStatus(line.qty, line.receipt_qty);
    const invoiceStatus = getInvoiceStatus(line.qty, line.invoiced_qty);
    const receiptCfg    = RECEIPT_STATUS_CONFIG[receiptStatus];
    const invoiceCfg    = INVOICE_STATUS_CONFIG[invoiceStatus];

    const remainingToReceive = Math.max(0, (line.qty ?? 0) - (line.receipt_qty ?? 0));
    const canReceive = remainingToReceive > 0;

    const rowStyle = {
        opacity:    isDragging ? 0.4  : 1,
        borderTop:  isDragOver ? '3px solid #17a2b8' : undefined,
        transition: 'border-top 0.1s, opacity 0.15s',
        background: selected ? '#e8f7fa' : undefined,
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
            {/* Drag handle + ordre */}
            <td style={{ width: 48, cursor: !isReadOnly ? 'grab' : 'default', userSelect: 'none', padding: '2px 4px', verticalAlign: 'middle' }}>
                <i className="fas fa-grip-vertical mr-1" style={{ color: '#aaa' }} />
                <span className="text-muted small">{line.ordre}</span>
            </td>

            {/* Select (uniquement si reste à recevoir) */}
            <td style={{ width: 32 }}>
                {!isReadOnly && canReceive && (
                    <input type="checkbox" checked={selected} onChange={() => onToggleSelect(line.id)} />
                )}
            </td>

            {/* Réf. interne */}
            <td className="small text-muted">{line.code || '—'}</td>

            {/* Réf. fournisseur */}
            <td className="small text-muted">{line.supplier_ref || '—'}</td>

            {/* Produit */}
            <td style={{ width: 36 }}>
                {line.product_id && line.product_url
                    ? <a href={line.product_url} className="btn btn-xs btn-outline-secondary" target="_blank" rel="noreferrer" title={line.product_code}>
                        <i className="fas fa-cube" />
                      </a>
                    : null}
            </td>

            {/* Description */}
            <td style={{ maxWidth: 200, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                <span title={line.label}>{line.label}</span>
            </td>

            {/* Qté commandée */}
            <td className="text-right">{formatQty(line.qty)}</td>

            {/* Reçu */}
            <td className="text-right">
                <ReceiptPopover
                    items={line.receipt_lines ?? []}
                    badgeClass={receiptCfg.badge}
                    badgeLabel={`${line.receipt_qty > 0 ? formatQty(line.receipt_qty) : receiptCfg.label}`}
                />
            </td>

            {/* Facturé */}
            <td className="text-right">
                <span className={`badge ${invoiceCfg.badge}`}>
                    {line.invoiced_qty > 0 ? formatQty(line.invoiced_qty) : invoiceCfg.label}
                </span>
            </td>

            {/* Unité */}
            <td className="small">{line.unit_label ?? '—'}</td>

            {/* Date livraison */}
            <td className="small" style={{ whiteSpace: 'nowrap' }}>
                <DeliveryDateBadge dateStr={line.delivery_date} />
            </td>

            {/* Prix */}
            <td className="text-right font-weight-bold">{line.formatted_price}</td>

            {/* Remise */}
            <td className="text-right small">{line.discount} %</td>

            {/* TVA */}
            <td className="small">{line.vat_rate != null ? `${line.vat_rate} %` : '—'}</td>

            {/* Total */}
            <td className="text-right text-muted small">{line.formatted_total}</td>

            {/* Actions */}
            <td>
                <div className="btn-group btn-group-xs" ref={menuRef}>
                    {!isReadOnly && (
                        <button
                            className="btn btn-xs btn-warning"
                            title="Modifier"
                            onClick={() => onEdit(line)}
                        >
                            <i className="fas fa-edit" />
                        </button>
                    )}
                    <button
                        ref={toggleRef}
                        className="btn btn-xs btn-default dropdown-toggle dropdown-toggle-split"
                        onClick={handleToggleMenu}
                    />
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
                        </div>
                    )}
                </div>
            </td>
        </tr>
    );
}

// ---------------------------------------------------------------------------
// PurchaseLinesPage — composant principal
// ---------------------------------------------------------------------------

export default function PurchaseLinesPage({ purchaseId, purchaseStatu: initialStatu, endpoints }) {
    const [lines, setLines]             = useState([]);
    const [purchaseStatu, setPurchaseStatu] = useState(Number(initialStatu));
    const [selectData, setSelectData]   = useState({});
    const [loading, setLoading]         = useState(true);
    const [drawerOpen, setDrawerOpen]   = useState(false);
    const [editingLine, setEditingLine] = useState(null);
    const [search, setSearch]           = useState('');
    const [selected, setSelected]       = useState(new Set());
    const [flash, setFlash]             = useState(null);
    const [totals, setTotals]           = useState({ ht: '—', count: 0 });

    // Drag-and-drop
    const dragIndexRef = useRef(null);
    const [dragOver, setDragOver]       = useState(null);

    const isReadOnly = purchaseStatu !== 1;

    const showFlash = (type, msg) => {
        setFlash({ type, msg });
        setTimeout(() => setFlash(null), 3500);
    };

    const refreshNextOrdre = (updated) => {
        const next = updated.reduce((m, l) => Math.max(m, l.ordre), 0) + 1;
        setSelectData((sd) => ({ ...sd, _nextOrdre: next }));
    };

    const computeTotals = (ls) => {
        const count = ls.length;
        setTotals({ count });
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
            setPurchaseStatu(Number(linesData.purchase_statu ?? initialStatu));
            const next = ls.reduce((m, l) => Math.max(m, l.ordre), 0) + 1;
            setSelectData({ ...sd, _nextOrdre: next });
            computeTotals(ls);
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

        const sorted   = [...lines].sort((a, b) => a.ordre - b.ordre);
        const q        = search.toLowerCase();
        const filtered = sorted.filter((l) =>
            !q || (l.label ?? '').toLowerCase().includes(q)
               || (l.code  ?? '').toLowerCase().includes(q)
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
            computeTotals(updated);
            return updated;
        });
        showFlash('success', isEdit ? 'Ligne mise à jour' : 'Ligne ajoutée');
    };

    const handleDelete = async (id) => {
        if (!confirm('Supprimer cette ligne ?')) return;
        const res = await apiFetch(endpoints.destroy.replace('__ID__', id), { method: 'DELETE' });
        if (res.ok) {
            setLines((prev) => { const u = prev.filter((l) => l.id !== id); refreshNextOrdre(u); computeTotals(u); return u; });
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
            setLines((prev) => { const u = [...prev, data.line]; refreshNextOrdre(u); computeTotals(u); return u; });
            showFlash('success', 'Ligne dupliquée');
        } else {
            showFlash('danger', 'Erreur lors de la duplication');
        }
    };

    const handleToggleSelect = (id) => {
        setSelected((s) => { const ns = new Set(s); ns.has(id) ? ns.delete(id) : ns.add(id); return ns; });
    };

    const handleStoreReceipt = async () => {
        const ids = [...selected];
        if (ids.length === 0) return;
        if (!confirm(`Créer un bon de réception à partir des ${ids.length} ligne(s) sélectionnée(s) ?`)) return;
        try {
            const res  = await apiFetch(endpoints.storeReceipt, {
                method: 'POST',
                body: JSON.stringify({ line_ids: ids }),
            });
            const data = await res.json();
            if (res.ok && data.redirect) {
                window.location.href = data.redirect;
            } else {
                showFlash('danger', data.error ?? 'Erreur lors de la création du BR');
            }
        } catch {
            showFlash('danger', 'Erreur réseau');
        }
    };

    const filteredLines = lines
        .filter((l) => {
            const q = search.toLowerCase();
            return !q
                || (l.label        ?? '').toLowerCase().includes(q)
                || (l.code         ?? '').toLowerCase().includes(q)
                || (l.supplier_ref ?? '').toLowerCase().includes(q)
                || (l.product_code ?? '').toLowerCase().includes(q);
        })
        .sort((a, b) => a.ordre - b.ordre);

    // Sélection globale (uniquement les lignes avec reste à recevoir)
    const selectableLines = filteredLines.filter((l) => (l.qty ?? 0) > (l.receipt_qty ?? 0));
    const allSelected     = selectableLines.length > 0 && selectableLines.every((l) => selected.has(l.id));
    const handleToggleAll = () => {
        const ids = selectableLines.map((l) => l.id);
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

            {/* Statut lecture seule */}
            {isReadOnly && (
                <div className="alert alert-info py-2 mb-3">
                    <i className="fas fa-lock mr-2" />
                    Commande verrouillée — les lignes sont en lecture seule.
                </div>
            )}

            {/* Toolbar */}
            <div className="d-flex flex-wrap align-items-center mb-3" style={{ gap: '0.5rem' }}>
                <div className="input-group input-group-sm" style={{ maxWidth: 280 }}>
                    <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-search" /></span></div>
                    <input type="text" className="form-control" placeholder="Rechercher (libellé, code, réf)…"
                        value={search} onChange={(e) => setSearch(e.target.value)} />
                    {search && (
                        <div className="input-group-append">
                            <button className="btn btn-outline-secondary" onClick={() => setSearch('')}><i className="fas fa-times" /></button>
                        </div>
                    )}
                </div>
                <span className="badge badge-secondary ml-auto">
                    {filteredLines.length} ligne{filteredLines.length > 1 ? 's' : ''}
                </span>
            </div>

            {/* Bulk actions — création BR */}
            {selected.size > 0 && !isReadOnly && (
                <div className="card card-outline card-info mb-3">
                    <div className="card-body py-2 px-3">
                        <div className="d-flex flex-wrap align-items-center" style={{ gap: '0.75rem' }}>
                            <span className="text-info font-weight-bold small">
                                <i className="fas fa-check-square mr-1" />
                                {selected.size} ligne{selected.size > 1 ? 's' : ''} sélectionnée{selected.size > 1 ? 's' : ''}
                            </span>
                            <button className="btn btn-info btn-sm" onClick={handleStoreReceipt}>
                                <i className="fas fa-folder mr-1" />
                                Nouveau bon de réception ({selected.size})
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
                            <th style={{ width: 32 }} title="Sélectionner pour BR">
                                {!isReadOnly && selectableLines.length > 0 && (
                                    <input type="checkbox" checked={allSelected} onChange={handleToggleAll} />
                                )}
                            </th>
                            <th>Réf. int.</th>
                            <th>Réf. fourn.</th>
                            <th style={{ width: 36 }} />
                            <th>Description</th>
                            <th className="text-right">Qté</th>
                            <th className="text-right">Reçu</th>
                            <th className="text-right">Facturé</th>
                            <th>Unité</th>
                            <th>Livraison</th>
                            <th className="text-right">Prix u.</th>
                            <th className="text-right">Remise</th>
                            <th>TVA</th>
                            <th className="text-right">Total HT</th>
                            <th style={{ width: 90 }}>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            <tr>
                                <td colSpan={16} className="text-center py-4">
                                    <i className="fas fa-spinner fa-spin mr-2" />Chargement…
                                </td>
                            </tr>
                        ) : filteredLines.length === 0 ? (
                            <tr>
                                <td colSpan={16} className="text-center py-4 text-muted">
                                    {search ? 'Aucune ligne ne correspond.' : 'Aucune ligne dans cette commande achat.'}
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
                    {filteredLines.length > 0 && (
                        <tfoot>
                            <tr className="font-weight-bold bg-light">
                                <td colSpan={14} className="text-right pr-3 small text-muted">
                                    {filteredLines.length} ligne{filteredLines.length > 1 ? 's' : ''}
                                </td>
                                <td colSpan={2} />
                            </tr>
                        </tfoot>
                    )}
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
        </div>
    );
}
