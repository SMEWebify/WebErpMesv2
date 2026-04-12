import React, { useState, useEffect, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json',
            ...options.headers,
        },
        ...options,
    });
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw { status: res.status, data };
    }
    return res.json();
}

const STATUS_LABELS = [
    { value: 1, label: 'En cours' },
    { value: 2, label: 'Envoyé' },
    { value: 3, label: 'Partiellement reçu' },
    { value: 4, label: 'Reçu' },
    { value: 5, label: 'BC partiellement créé' },
    { value: 6, label: 'BC créé' },
];

// ---------------------------------------------------------------------------
// Arrow Steps
// ---------------------------------------------------------------------------

function ArrowSteps({ statu, onChangeStatu, disabled }) {
    return (
        <div className="row mb-3">
            <div className="col-12">
                <div className="arrow-steps clearfix">
                    {STATUS_LABELS.map(s => (
                        <div
                            key={s.value}
                            className={[
                                'step',
                                statu === s.value ? 'current' : '',
                                statu > s.value  ? 'done'    : '',
                            ].join(' ')}
                            style={{ cursor: disabled ? 'default' : 'pointer' }}
                        >
                            <span>
                                <a
                                    href="#"
                                    onClick={e => { e.preventDefault(); if (!disabled) onChangeStatu(s.value); }}
                                >
                                    {s.label}
                                </a>
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Info Tab (left col)
// ---------------------------------------------------------------------------

function InfoForm({ quotation, suppliers, addresses, contacts, endpoints, trans, onUpdated, onSupplierChange }) {
    const [label,      setLabel]      = useState(quotation.label ?? '');
    const [companiesId,setCompaniesId]= useState(quotation.companies_id ?? '');
    const [addressId,  setAddressId]  = useState(quotation.companies_addresses_id ?? '');
    const [contactId,  setContactId]  = useState(quotation.companies_contacts_id ?? '');
    const [delay,      setDelay]      = useState(quotation.delay ?? '');
    const [comment,    setComment]    = useState(quotation.comment ?? '');
    const [saving,     setSaving]     = useState(false);
    const [flash,      setFlash]      = useState(null);
    const [errors,     setErrors]     = useState({});

    const [localAddresses, setLocalAddresses] = useState(addresses ?? []);
    const [localContacts,  setLocalContacts]  = useState(contacts  ?? []);

    async function handleSupplierChange(e) {
        const id = e.target.value;
        setCompaniesId(id);
        setAddressId('');
        setContactId('');
        if (!id) { setLocalAddresses([]); setLocalContacts([]); return; }
        try {
            const data = await apiFetch(`${endpoints.companyData}?company_id=${id}`);
            setLocalAddresses(data.addresses);
            setLocalContacts(data.contacts);
            if (onSupplierChange) onSupplierChange(id);
        } catch {}
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});
        setSaving(true);
        try {
            await apiFetch(endpoints.update, {
                method: 'PATCH',
                body: JSON.stringify({
                    label,
                    companies_id:           companiesId || null,
                    companies_addresses_id: addressId   || null,
                    companies_contacts_id:  contactId   || null,
                    delay:   delay   || null,
                    comment: comment || null,
                }),
            });
            setFlash({ type: 'success', msg: trans.update_success });
            if (onUpdated) onUpdated({ label, companies_id: companiesId, companies_addresses_id: addressId, companies_contacts_id: contactId });
        } catch (err) {
            if (err.status === 422 && err.data?.errors) {
                setErrors(err.data.errors);
            } else {
                setFlash({ type: 'danger', msg: trans.error_generic });
            }
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            <div className="card card-primary">
                <div className="card-header">
                    <h3 className="card-title">{trans.informations}</h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool" data-card-widget="maximize">
                            <i className="fas fa-expand" />
                        </button>
                    </div>
                </div>
                <div className="card-body">
                    {flash && (
                        <div className={`alert alert-${flash.type} alert-dismissible`}>
                            <button type="button" className="close" onClick={() => setFlash(null)}>
                                <span>&times;</span>
                            </button>
                            {flash.msg}
                        </div>
                    )}

                    <div className="row">
                        <div className="form-group col-md-3">
                            <label className="text-success">{trans.external_id}</label>
                            <span className="ml-2">{quotation.code}</span>
                        </div>
                        <div className="form-group col-md-6">
                            <label>{trans.name_quote}</label>
                            <input
                                type="text"
                                className={`form-control${errors.label ? ' is-invalid' : ''}`}
                                value={label}
                                onChange={e => setLabel(e.target.value)}
                            />
                            {errors.label && <div className="invalid-feedback">{errors.label[0]}</div>}
                        </div>
                    </div>

                    <div className="row">
                        <label className="col-12">{trans.supplier_info}</label>
                        {(!quotation.companies_contacts_id || quotation.companies_contacts_id === 0) &&
                         (!quotation.companies_addresses_id || quotation.companies_addresses_id === 0) && (
                            <div className="col-12">
                                <div className="alert alert-info">{trans.update_valid}</div>
                            </div>
                        )}
                    </div>

                    <div className="row">
                        <div className="form-group col-md-6">
                            <label>{trans.supplier}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-building" /></span>
                                </div>
                                <select className="form-control" value={companiesId} onChange={handleSupplierChange}>
                                    <option value="">{trans.select_supplier}</option>
                                    {suppliers.map(s => (
                                        <option key={s.id} value={s.id}>{s.code} - {s.label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="row">
                        <div className="form-group col-md-6">
                            <label>{trans.address}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-map-marker-alt" /></span>
                                </div>
                                <select className="form-control" value={addressId} onChange={e => setAddressId(e.target.value)}>
                                    <option value="">{trans.select_address}</option>
                                    {localAddresses.map(a => (
                                        <option key={a.id} value={a.id}>{a.label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="form-group col-md-6">
                            <label>{trans.contact}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-user" /></span>
                                </div>
                                <select className="form-control" value={contactId} onChange={e => setContactId(e.target.value)}>
                                    <option value="">{trans.select_contact}</option>
                                    {localContacts.map(c => (
                                        <option key={c.id} value={c.id}>{c.label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="row">
                        <label className="col-12">{trans.date_pay_info}</label>
                        <div className="form-group col-md-6">
                            <label>{trans.validity_date}</label>
                            <input
                                type="date"
                                className="form-control"
                                value={delay ?? ''}
                                onChange={e => setDelay(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="row">
                        <div className="form-group col-md-12">
                            <label>{trans.comment}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-comment" /></span>
                                </div>
                                <textarea
                                    className="form-control"
                                    rows="3"
                                    name="comment"
                                    value={comment ?? ''}
                                    onChange={e => setComment(e.target.value)}
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card-footer">
                    <button type="submit" className="btn btn-info btn-flat" disabled={saving}>
                        <i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-save'} mr-1`} />
                        {trans.update}
                    </button>
                </div>
            </div>
        </form>
    );
}

// ---------------------------------------------------------------------------
// Options sidebar
// ---------------------------------------------------------------------------

function OptionsSidebar({ quotation, trans }) {
    const canPdf = quotation.companies_contacts_id &&
                   quotation.companies_contacts_id !== 0 &&
                   quotation.companies_addresses_id &&
                   quotation.companies_addresses_id !== 0;

    return (
        <>
            <div className="card card-warning">
                <div className="card-header">
                    <h3 className="card-title">{trans.options}</h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool" data-card-widget="maximize">
                            <i className="fas fa-expand" />
                        </button>
                    </div>
                </div>
                <div className="card-body p-0">
                    <table className="table table-hover mb-0">
                        <tbody>
                            <tr>
                                <td style={{ width: '50%' }}>{trans.rfq_label}</td>
                                <td>
                                    {canPdf && quotation.pdf_url ? (
                                        <a href={quotation.pdf_url} className="btn btn-outline-danger btn-sm" target="_blank" rel="noreferrer">
                                            <i className="fas fa-file-pdf mr-1" />PDF
                                        </a>
                                    ) : (
                                        <span className="text-muted small">{trans.update_valid}</span>
                                    )}
                                </td>
                            </tr>
                            {quotation.email_url && (
                                <tr>
                                    <td>{trans.email}</td>
                                    <td>
                                        <a href={quotation.email_url} className="btn btn-outline-info btn-sm">
                                            <i className="fas fa-envelope mr-1" />{trans.email}
                                        </a>
                                    </td>
                                </tr>
                            )}
                            {quotation.compare_url && (
                                <tr>
                                    <td>{trans.compare_rfq}</td>
                                    <td>
                                        <a href={quotation.compare_url} className="btn btn-outline-primary btn-sm">
                                            <i className="fas fa-balance-scale mr-1" />{trans.compare_rfq}
                                        </a>
                                    </td>
                                </tr>
                            )}
                            <tr>
                                <td>{trans.duplicate}</td>
                                <td>
                                    <a href={quotation.duplicate_url} className="btn btn-outline-secondary btn-sm">
                                        <i className="fas fa-copy mr-1" />{trans.duplicate}
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Email logs */}
            {quotation.email_logs && quotation.email_logs.length > 0 && (
                <div className="card card-secondary">
                    <div className="card-header">
                        <h3 className="card-title">{trans.email_history}</h3>
                    </div>
                    <div className="card-body p-0">
                        <table className="table table-sm mb-0">
                            <tbody>
                                {quotation.email_logs.map(log => (
                                    <tr key={log.id}>
                                        <td>
                                            <i className="fas fa-envelope text-muted mr-1" />
                                            {log.subject}
                                        </td>
                                        <td className="text-muted small">{log.created_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// Line Form
// ---------------------------------------------------------------------------

function LineForm({ quotation, products, currency, endpoints, trans, onLineAdded, onLineUpdated, editingLine, onCancelEdit }) {
    const nextOrder = useCallback(() => {
        if (!quotation.lines || quotation.lines.length === 0) return 10;
        const max = Math.max(...quotation.lines.map(l => l.ordre ?? 0));
        return max + 10;
    }, [quotation.lines]);

    const [ordre,      setOrdre]      = useState(editingLine?.ordre      ?? nextOrder());
    const [productId,  setProductId]  = useState(editingLine?.product_id ?? '');
    const [code,       setCode]       = useState(editingLine?.code       ?? '');
    const [lineLabel,  setLineLabel]  = useState(editingLine?.label      ?? '');
    const [qty,        setQty]        = useState(editingLine?.qty_to_order ?? 0);
    const [unitPrice,  setUnitPrice]  = useState(editingLine?.unit_price  ?? 0);
    const [saving,     setSaving]     = useState(false);
    const [errors,     setErrors]     = useState({});

    const isEditing = !!editingLine;

    // Sync fields when editingLine changes
    useEffect(() => {
        if (editingLine) {
            setOrdre(editingLine.ordre);
            setProductId(editingLine.product_id ?? '');
            setCode(editingLine.code ?? '');
            setLineLabel(editingLine.label ?? '');
            setQty(editingLine.qty_to_order);
            setUnitPrice(editingLine.unit_price);
        } else {
            setOrdre(nextOrder());
            setProductId('');
            setCode('');
            setLineLabel('');
            setQty(0);
            setUnitPrice(0);
        }
    }, [editingLine]);

    function handleProductChange(e) {
        const id = e.target.value;
        setProductId(id);
        if (!id) { setCode(''); setLineLabel(''); setUnitPrice(0); return; }
        const product = products.find(p => p.id === Number(id));
        if (product) {
            setCode(product.code ?? '');
            setLineLabel(product.label ?? '');
            setUnitPrice(product.selling_price ?? 0);
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});
        setSaving(true);
        const body = {
            ordre:        Number(ordre),
            label:        lineLabel,
            qty_to_order: Number(qty),
            unit_price:   Number(unitPrice),
            product_id:   productId ? Number(productId) : null,
            code,
        };
        try {
            if (isEditing) {
                const data = await apiFetch(endpoints.updateLine.replace('{line}', editingLine.id), {
                    method: 'PATCH',
                    body: JSON.stringify(body),
                });
                onLineUpdated(data.line);
            } else {
                const data = await apiFetch(endpoints.storeLine, {
                    method: 'POST',
                    body: JSON.stringify(body),
                });
                onLineAdded(data.line);
                setOrdre(Number(ordre) + 10);
                setProductId(''); setCode(''); setLineLabel(''); setQty(0); setUnitPrice(0);
            }
        } catch (err) {
            if (err.status === 422 && err.data?.errors) {
                setErrors(err.data.errors);
            }
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            <div className="form-row">
                {/* Sort */}
                <div className="form-group col-md-2">
                    <label>{trans.sort}</label>
                    <div className="input-group">
                        <div className="input-group-prepend">
                            <span className="input-group-text"><i className="fas fa-sort-numeric-down" /></span>
                        </div>
                        <input
                            type="number"
                            className={`form-control${errors.ordre ? ' is-invalid' : ''}`}
                            value={ordre}
                            min="0"
                            onChange={e => setOrdre(e.target.value)}
                        />
                    </div>
                    {errors.ordre && <span className="text-danger small">{errors.ordre[0]}</span>}
                </div>

                {/* Product */}
                <div className="form-group col-md-2">
                    <label>{trans.product}</label>
                    <div className="input-group">
                        <div className="input-group-prepend">
                            <span className="input-group-text bg-gradient-info"><i className="fas fa-barcode" /></span>
                        </div>
                        <select
                            className="form-control"
                            value={productId}
                            onChange={handleProductChange}
                        >
                            <option value="">{trans.select_product}</option>
                            {products.map(p => (
                                <option key={p.id} value={p.id}>{p.code} - {p.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Code */}
                <div className="form-group col-md-2">
                    <label>{trans.external_id}</label>
                    <div className="input-group">
                        <div className="input-group-prepend">
                            <span className="input-group-text"><i className="fas fa-external-link-square-alt" /></span>
                        </div>
                        <input
                            type="text"
                            className={`form-control${errors.code ? ' is-invalid' : ''}`}
                            value={code}
                            onChange={e => setCode(e.target.value)}
                        />
                    </div>
                </div>

                {/* Label */}
                <div className="form-group col-md-2">
                    <label>{trans.description}</label>
                    <div className="input-group">
                        <div className="input-group-prepend">
                            <span className="input-group-text"><i className="fas fa-tags" /></span>
                        </div>
                        <input
                            type="text"
                            className={`form-control${errors.label ? ' is-invalid' : ''}`}
                            value={lineLabel}
                            onChange={e => setLineLabel(e.target.value)}
                        />
                    </div>
                    {errors.label && <span className="text-danger small">{errors.label[0]}</span>}
                </div>

                {/* Qty */}
                <div className="form-group col-md-1">
                    <label>{trans.qty}</label>
                    <div className="input-group">
                        <div className="input-group-prepend">
                            <span className="input-group-text"><i className="fas fa-times" /></span>
                        </div>
                        <input
                            type="number"
                            className={`form-control${errors.qty_to_order ? ' is-invalid' : ''}`}
                            value={qty}
                            min="0"
                            onChange={e => setQty(e.target.value)}
                        />
                    </div>
                    {errors.qty_to_order && <span className="text-danger small">{errors.qty_to_order[0]}</span>}
                </div>

                {/* Unit price */}
                <div className="form-group col-md-2">
                    <label>{trans.price}</label>
                    <div className="input-group">
                        <div className="input-group-prepend">
                            <span className="input-group-text">{currency}</span>
                        </div>
                        <input
                            type="number"
                            className={`form-control${errors.unit_price ? ' is-invalid' : ''}`}
                            value={unitPrice}
                            min="0"
                            step="0.001"
                            onChange={e => setUnitPrice(e.target.value)}
                        />
                    </div>
                    {errors.unit_price && <span className="text-danger small">{errors.unit_price[0]}</span>}
                </div>

                {/* Submit */}
                <div className="form-group col-md-1 d-flex align-items-end">
                    <button type="submit" className="btn btn-success btn-block" disabled={saving}>
                        {saving ? <i className="fas fa-spinner fa-spin" /> : trans.submit}
                    </button>
                </div>

                {isEditing && (
                    <div className="form-group col-md-1 d-flex align-items-end">
                        <button type="button" className="btn btn-secondary btn-block" onClick={onCancelEdit}>
                            {trans.cancel}
                        </button>
                    </div>
                )}
            </div>
        </form>
    );
}

// ---------------------------------------------------------------------------
// Lines Tab
// ---------------------------------------------------------------------------

function LinesTab({ quotation, products, currency, endpoints, trans }) {
    const [lines,       setLines]       = useState(quotation.lines ?? []);
    const [editingLine, setEditingLine] = useState(null);

    function handleLineAdded(line) {
        setLines(prev => [...prev, line]);
    }

    function handleLineUpdated(line) {
        setLines(prev => prev.map(l => l.id === line.id ? line : l));
        setEditingLine(null);
    }

    const canEdit = quotation.statu === 1;
    const canOrder = quotation.statu !== 6;

    return (
        <div className="tab-pane" id="PurchaseQuotationLines">
            <div className="row">
                <div className="col-12">
                    <div className="card">
                        <div className="card-body">
                            {canEdit ? (
                                <LineForm
                                    quotation={{ ...quotation, lines }}
                                    products={products}
                                    currency={currency}
                                    endpoints={endpoints}
                                    trans={trans}
                                    onLineAdded={handleLineAdded}
                                    onLineUpdated={handleLineUpdated}
                                    editingLine={editingLine}
                                    onCancelEdit={() => setEditingLine(null)}
                                />
                            ) : (
                                <div className="alert alert-info">
                                    <i className="fas fa-info-circle mr-1" />{trans.info_statu}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="row">
                <div className="col-12 table-responsive">
                    <form method="POST" action={endpoints.storeOrder}>
                        <input type="hidden" name="_token" value={csrfToken()} />
                        <table className="table table-striped">
                            <thead>
                                <tr>
                                    <th>{trans.order}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.order} {trans.label}</th>
                                    <th>{trans.label}</th>
                                    <th>{trans.product}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.price}</th>
                                    <th>{trans.total_price}</th>
                                    <th>{trans.action}</th>
                                    <th>{trans.qty_accepted}</th>
                                    <th>{trans.qty_canceled}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {lines.length === 0 ? (
                                    <tr>
                                        <td colSpan="11" className="text-center text-muted py-3">{trans.no_data}</td>
                                    </tr>
                                ) : lines.map(line => (
                                    <tr key={line.id}>
                                        <td>
                                            {line.order_url ? (
                                                <a href={line.order_url} className="btn btn-outline-secondary btn-sm">
                                                    {line.order_code}
                                                </a>
                                            ) : <span>{trans.generic}</span>}
                                        </td>
                                        <td>
                                            {line.order_line_qty != null
                                                ? `${line.order_line_qty} x ${line.task_qty ?? ''}`
                                                : trans.generic}
                                        </td>
                                        <td>
                                            {line.order_line_label || line.label}
                                        </td>
                                        <td>
                                            {line.task_id ? (
                                                <>
                                                    <a href={line.task_url} className="btn btn-sm btn-success mr-1">{trans.view}</a>
                                                    #{line.task_id} - {line.task_label}
                                                    {line.component_label ? ` - ${line.component_label}` : ''}
                                                </>
                                            ) : line.label}
                                        </td>
                                        <td>
                                            {line.component_url && (
                                                <a href={line.component_url} className="btn btn-sm btn-outline-secondary mr-1">
                                                    <i className="fas fa-eye" />
                                                </a>
                                            )}
                                            {!line.component_url && line.product_url && (
                                                <a href={line.product_url} className="btn btn-sm btn-outline-secondary">
                                                    <i className="fas fa-eye" />
                                                </a>
                                            )}
                                        </td>
                                        <td>{Number(line.qty_to_order).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}</td>
                                        <td>{line.formatted_unit}</td>
                                        <td>{line.formatted_total}</td>
                                        <td>
                                            {canEdit && (
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm mb-1"
                                                    onClick={() => setEditingLine(line)}
                                                >
                                                    <i className="fas fa-edit" /> {trans.edit}
                                                </button>
                                            )}
                                            {line.qty_to_order > line.qty_accepted && (
                                                <div className="form-group mb-0">
                                                    <div className="custom-control custom-checkbox">
                                                        <input type="hidden" value={line.task_id ?? 0} name="PurchaseQuotationLineTaskid[]" />
                                                        <input
                                                            className="custom-control-input"
                                                            type="checkbox"
                                                            id={`line-${line.id}`}
                                                            value={line.id}
                                                            name="PurchaseQuotationLine[]"
                                                        />
                                                        <label className="custom-control-label" htmlFor={`line-${line.id}`}>+</label>
                                                    </div>
                                                    <label htmlFor={`price-${line.id}`} className="small">{trans.proposed_purchase_price}</label>
                                                    <input
                                                        type="number"
                                                        className="form-control form-control-sm"
                                                        name="PurchaseQuotationLinePrice[]"
                                                        id={`price-${line.id}`}
                                                        step="0.001"
                                                        defaultValue={line.unit_price ?? 0}
                                                    />
                                                </div>
                                            )}
                                        </td>
                                        <td>{Number(line.qty_accepted).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}</td>
                                        <td>{Number(line.canceled_qty).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{trans.order}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.order} {trans.label}</th>
                                    <th>{trans.label}</th>
                                    <th>{trans.product}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.price}</th>
                                    <th>{trans.total_price}</th>
                                    <th>
                                        {canOrder && (
                                            <button type="submit" className="btn btn-primary">
                                                {trans.new_order}
                                            </button>
                                        )}
                                    </th>
                                    <th>{trans.qty_accepted}</th>
                                    <th>{trans.qty_canceled}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function PurchasesQuotationShow({
    initialQuotation,
    suppliers,
    initialAddresses,
    initialContacts,
    currency,
    endpoints,
    trans,
}) {
    const [quotation,  setQuotation]  = useState(initialQuotation);
    const [products,   setProducts]   = useState([]);
    const [activeTab,  setActiveTab]  = useState('info');
    const [statuSaving,setStatuSaving]= useState(false);

    // Fetch products for line form
    useEffect(() => {
        apiFetch(endpoints.products)
            .then(data => setProducts(data.products))
            .catch(() => {});
    }, []);

    async function handleChangeStatu(statu) {
        if (statuSaving) return;
        setStatuSaving(true);
        try {
            const data = await apiFetch(endpoints.updateStatu, {
                method: 'PATCH',
                body: JSON.stringify({ statu }),
            });
            setQuotation(prev => ({ ...prev, statu: data.statu }));
        } catch {}
        finally { setStatuSaving(false); }
    }

    function handleInfoUpdated(updated) {
        setQuotation(prev => ({ ...prev, ...updated }));
    }

    return (
        <div className="card">
            {/* Tabs header */}
            <div className="card-header p-2">
                <ul className="nav nav-pills">
                    <li className="nav-item">
                        <a
                            className={`nav-link${activeTab === 'info' ? ' active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('info'); }}
                        >
                            {trans.purchase_quotation_info}
                        </a>
                    </li>
                    <li className="nav-item">
                        <a
                            className={`nav-link${activeTab === 'lines' ? ' active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('lines'); }}
                        >
                            {trans.purchase_quotation_lines}
                        </a>
                    </li>
                </ul>
            </div>

            <div className="card-body">
                <div className="tab-content">
                    {/* Info tab */}
                    <div className={`tab-pane${activeTab === 'info' ? ' active' : ''}`} id="PurchaseQuotation">
                        <ArrowSteps
                            statu={quotation.statu}
                            onChangeStatu={handleChangeStatu}
                            disabled={statuSaving}
                        />
                        <div className="row">
                            <div className="col-md-9">
                                <InfoForm
                                    quotation={quotation}
                                    suppliers={suppliers}
                                    addresses={initialAddresses}
                                    contacts={initialContacts}
                                    endpoints={endpoints}
                                    trans={trans}
                                    onUpdated={handleInfoUpdated}
                                />
                            </div>
                            <div className="col-md-3">
                                <OptionsSidebar quotation={quotation} trans={trans} />
                            </div>
                        </div>
                    </div>

                    {/* Lines tab */}
                    {activeTab === 'lines' && (
                        <LinesTab
                            quotation={quotation}
                            products={products}
                            currency={currency}
                            endpoints={endpoints}
                            trans={trans}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}
