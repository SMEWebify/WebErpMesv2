import React, { useState, useEffect, useCallback } from 'react';
import { formatQty } from '../utils';

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

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function InvoicesRequest({
    initialCode,
    userId,
    users,
    companies,
    endpoints,
    trans,
}) {
    const [form, setForm] = useState({
        code:                  initialCode ?? '',
        label:                 initialCode ?? '',
        companiesId:           '',
        companiesAddressesId:  '',
        companiesContactsId:   '',
        userId:                userId ?? '',
        dateStart:             '',
        dateEnd:               '',
    });

    const [addresses,   setAddresses]   = useState([]);
    const [contacts,    setContacts]    = useState([]);
    const [lines,       setLines]       = useState([]);
    const [selected,    setSelected]    = useState(new Set()); // Set of delivery_line ids
    const [loading,     setLoading]     = useState(false);
    const [submitting,  setSubmitting]  = useState(false);
    const [generating,  setGenerating]  = useState(false);
    const [errors,      setErrors]      = useState({});
    const [flashMsg,    setFlashMsg]    = useState(null);

    // ---------------------------------------------------------------------------
    // Fetch lines whenever company or dates change
    // ---------------------------------------------------------------------------

    const fetchLines = useCallback(() => {
        if (!form.companiesId) {
            setAddresses([]);
            setContacts([]);
            setLines([]);
            setSelected(new Set());
            return;
        }

        setLoading(true);
        const params = new URLSearchParams({ company_id: form.companiesId });
        if (form.dateStart) params.append('date_start', form.dateStart);
        if (form.dateEnd)   params.append('date_end',   form.dateEnd);

        apiFetch(`${endpoints.lines}?${params}`)
            .then(data => {
                setAddresses(data.addresses);
                setContacts(data.contacts);
                setLines(data.lines);
                setSelected(new Set());
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [form.companiesId, form.dateStart, form.dateEnd]);

    useEffect(() => {
        fetchLines();
        // Reset address/contact when company changes
        setForm(f => ({ ...f, companiesAddressesId: '', companiesContactsId: '' }));
    }, [form.companiesId]);

    useEffect(() => {
        if (!form.companiesId) return;
        fetchLines();
    }, [form.dateStart, form.dateEnd]);

    // ---------------------------------------------------------------------------
    // Selection helpers
    // ---------------------------------------------------------------------------

    const allChecked = lines.length > 0 && lines.every(l => selected.has(l.id));

    function handleSelectAll(checked) {
        setSelected(checked ? new Set(lines.map(l => l.id)) : new Set());
    }

    function handleLineCheck(id, checked) {
        setSelected(prev => {
            const next = new Set(prev);
            checked ? next.add(id) : next.delete(id);
            return next;
        });
    }

    // ---------------------------------------------------------------------------
    // Submit — create invoice from selected lines
    // ---------------------------------------------------------------------------

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});
        setFlashMsg(null);

        if (selected.size === 0) {
            setErrors({ lines: trans.no_lines });
            return;
        }

        setSubmitting(true);
        try {
            const data = await apiFetch(endpoints.store, {
                method: 'POST',
                body: JSON.stringify({
                    code:                   form.code,
                    label:                  form.label,
                    companies_id:           Number(form.companiesId),
                    companies_addresses_id: Number(form.companiesAddressesId),
                    companies_contacts_id:  Number(form.companiesContactsId),
                    user_id:                Number(form.userId),
                    lines:                  [...selected],
                }),
            });
            window.location.href = data.redirect;
        } catch (err) {
            if (err.status === 422 && err.data?.errors) {
                setErrors(err.data.errors);
            } else {
                setErrors({ general: 'An error occurred. Please try again.' });
            }
            setSubmitting(false);
        }
    }

    // ---------------------------------------------------------------------------
    // Generate all — one invoice per order for all open lines
    // ---------------------------------------------------------------------------

    async function handleGenerateAll() {
        setErrors({});
        setFlashMsg(null);
        setGenerating(true);
        try {
            const data = await apiFetch(endpoints.generateAll, {
                method: 'POST',
                body: JSON.stringify({
                    companies_id:           Number(form.companiesId),
                    companies_addresses_id: Number(form.companiesAddressesId),
                    companies_contacts_id:  Number(form.companiesContactsId),
                    user_id:                Number(form.userId),
                    date_start:             form.dateStart || null,
                    date_end:               form.dateEnd   || null,
                }),
            });
            setFlashMsg({ type: 'success', text: data.message });
            fetchLines();
        } catch (err) {
            if (err.status === 422 && err.data?.errors) {
                setErrors(err.data.errors);
            } else if (err.data?.message) {
                setErrors({ general: err.data.message });
            } else {
                setErrors({ general: 'An error occurred. Please try again.' });
            }
        } finally {
            setGenerating(false);
        }
    }

    function setField(name, value) {
        setForm(f => ({ ...f, [name]: value }));
    }

    function fieldError(key) {
        const e = errors[key];
        if (!e) return null;
        return <span className="text-danger">{Array.isArray(e) ? e[0] : e}</span>;
    }

    // ---------------------------------------------------------------------------
    // Render
    // ---------------------------------------------------------------------------

    return (
        <div className="card">
            <form onSubmit={handleSubmit}>
                <div className="card-body">
                    {errors.general && (
                        <div className="alert alert-danger">{errors.general}</div>
                    )}
                    {flashMsg && (
                        <div className={`alert alert-${flashMsg.type}`}>{flashMsg.text}</div>
                    )}

                    <div className="form-row">
                        {/* Company */}
                        <div className="form-group col-md-3">
                            <label>{trans.company}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-building" /></span>
                                </div>
                                <select
                                    className="form-control"
                                    value={form.companiesId}
                                    onChange={e => setField('companiesId', e.target.value)}
                                >
                                    <option value="">{companies.length ? trans.select_company : trans.no_company}</option>
                                    {companies.map(c => (
                                        <option key={c.id} value={c.id}>{c.label}</option>
                                    ))}
                                </select>
                            </div>
                            {fieldError('companies_id')}
                        </div>

                        {/* Date from */}
                        <div className="form-group col-md-3">
                            <label>{trans.date_from}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-calendar-alt" /></span>
                                </div>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={form.dateStart}
                                    onChange={e => setField('dateStart', e.target.value)}
                                />
                            </div>
                        </div>

                        {/* Date to */}
                        <div className="form-group col-md-3">
                            <label>{trans.date_to}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-calendar-alt" /></span>
                                </div>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={form.dateEnd}
                                    onChange={e => setField('dateEnd', e.target.value)}
                                />
                            </div>
                        </div>

                        {/* Code */}
                        <div className="form-group col-md-3">
                            <label>{trans.external_id}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-external-link-square-alt" /></span>
                                </div>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.code}
                                    onChange={e => setField('code', e.target.value)}
                                />
                            </div>
                            {fieldError('code')}
                        </div>

                        {/* Label */}
                        <div className="form-group col-md-3">
                            <label>{trans.label}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-tags" /></span>
                                </div>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.label}
                                    onChange={e => setField('label', e.target.value)}
                                />
                            </div>
                            {fieldError('label')}
                        </div>

                        {/* User */}
                        <div className="form-group col-md-3">
                            <label>{trans.user}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-user" /></span>
                                </div>
                                <select
                                    className="form-control"
                                    value={form.userId}
                                    onChange={e => setField('userId', e.target.value)}
                                >
                                    <option value="">{trans.select_user}</option>
                                    {users.map(u => (
                                        <option key={u.id} value={u.id}>{u.name}</option>
                                    ))}
                                </select>
                            </div>
                            {fieldError('user_id')}
                        </div>

                        {/* Address */}
                        <div className="form-group col-md-3">
                            <label>{trans.address}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-map-marked-alt" /></span>
                                </div>
                                <select
                                    className="form-control"
                                    value={form.companiesAddressesId}
                                    onChange={e => setField('companiesAddressesId', e.target.value)}
                                >
                                    <option value="">{addresses.length ? trans.select_address : trans.no_address}</option>
                                    {addresses.map(a => (
                                        <option key={a.id} value={a.id}>{a.label} - {a.adress}</option>
                                    ))}
                                </select>
                            </div>
                            {fieldError('companies_addresses_id')}
                        </div>

                        {/* Contact */}
                        <div className="form-group col-md-3">
                            <label>{trans.contact}</label>
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span className="input-group-text"><i className="fas fa-user" /></span>
                                </div>
                                <select
                                    className="form-control"
                                    value={form.companiesContactsId}
                                    onChange={e => setField('companiesContactsId', e.target.value)}
                                >
                                    <option value="">{contacts.length ? trans.select_contact : trans.no_contact}</option>
                                    {contacts.map(c => (
                                        <option key={c.id} value={c.id}>{c.first_name} - {c.name}</option>
                                    ))}
                                </select>
                            </div>
                            {fieldError('companies_contacts_id')}
                        </div>

                        {/* Actions */}
                        <div className="form-group col-md-3 d-flex align-items-end flex-wrap" style={{ gap: '8px' }}>
                            <button type="submit" className="btn btn-success" disabled={submitting}>
                                {submitting
                                    ? <><i className="fas fa-spinner fa-spin mr-1" />{trans.new_invoice}</>
                                    : trans.new_invoice}
                            </button>
                            {form.companiesId && (() => {
                                const canGenerate = form.companiesAddressesId && form.companiesContactsId && form.userId;
                                return (
                                    <button
                                        type="button"
                                        className="btn btn-primary"
                                        disabled={generating || !canGenerate}
                                        title={!canGenerate ? (trans.select_address + ' / ' + trans.select_contact) : ''}
                                        onClick={handleGenerateAll}
                                    >
                                        {generating
                                            ? <><i className="fas fa-spinner fa-spin mr-1" />{trans.generate_all}</>
                                            : trans.generate_all}
                                    </button>
                                );
                            })()}
                        </div>
                    </div>
                </div>

                {/* Lines table */}
                <div className="card-body table-responsive p-0">
                    {errors.lines && (
                        <div className="alert alert-danger mx-3">{errors.lines}</div>
                    )}

                    {loading ? (
                        <div className="text-center p-4">
                            <i className="fas fa-spinner fa-spin mr-2" />Loading...
                        </div>
                    ) : (
                        <table className="table table-hover">
                            <thead>
                                <tr>
                                    <th>{trans.order}</th>
                                    <th>{trans.delivery_note}</th>
                                    <th>{trans.customer}</th>
                                    <th>{trans.external_id}</th>
                                    <th>{trans.label}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.unit}</th>
                                    <th>{trans.price}</th>
                                    <th>{trans.discount}</th>
                                    <th>{trans.vat}</th>
                                    <th>
                                        {trans.action}
                                        {form.companiesId && lines.length > 0 && (
                                            <div className="custom-control custom-checkbox mt-2">
                                                <input
                                                    className="custom-control-input"
                                                    id="selectAllInvoiceLines"
                                                    type="checkbox"
                                                    checked={allChecked}
                                                    onChange={e => handleSelectAll(e.target.checked)}
                                                />
                                                <label className="custom-control-label" htmlFor="selectAllInvoiceLines">
                                                    {allChecked ? 'Tout désélect.' : 'Tout sélect.'}
                                                </label>
                                            </div>
                                        )}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {lines.length === 0 ? (
                                    <tr>
                                        <td colSpan="11" className="text-center text-muted py-3">
                                            {trans.no_data}
                                        </td>
                                    </tr>
                                ) : lines.map(line => (
                                    <tr key={line.id}>
                                        <td>
                                            {line.order_url
                                                ? <a href={line.order_url} className="btn btn-outline-secondary btn-sm">{line.order_code}</a>
                                                : line.order_code}
                                        </td>
                                        <td>
                                            {line.delivery_url
                                                ? <a href={line.delivery_url} className="btn btn-primary btn-sm">
                                                    <i className="fas fa-folder mr-1" />{line.delivery_code}
                                                  </a>
                                                : line.delivery_code}
                                        </td>
                                        <td>
                                            {line.order_type == 1
                                                ? (line.companie_url
                                                    ? <a href={line.companie_url} className="btn btn-outline-secondary btn-sm">{line.companie_label}</a>
                                                    : line.companie_label)
                                                : trans.internal_order}
                                        </td>
                                        <td>{line.line_code}</td>
                                        <td>{line.line_label}</td>
                                        <td>{formatQty(line.qty)}</td>
                                        <td>{line.unit_label}</td>
                                        <td>{line.selling_price}</td>
                                        <td>{line.discount} %</td>
                                        <td>{line.vat_label} %</td>
                                        <td>
                                            <div className="custom-control custom-checkbox">
                                                <input
                                                    className="custom-control-input"
                                                    type="checkbox"
                                                    id={`inv-line-${line.id}`}
                                                    checked={selected.has(line.id)}
                                                    onChange={e => handleLineCheck(line.id, e.target.checked)}
                                                />
                                                <label className="custom-control-label" htmlFor={`inv-line-${line.id}`}>
                                                    {trans.add_to_document}
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{trans.order}</th>
                                    <th>{trans.delivery_note}</th>
                                    <th>{trans.customer}</th>
                                    <th>{trans.external_id}</th>
                                    <th>{trans.label}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.unit}</th>
                                    <th>{trans.price}</th>
                                    <th>{trans.discount}</th>
                                    <th>{trans.vat}</th>
                                    <th>{trans.action}</th>
                                </tr>
                            </tfoot>
                        </table>
                    )}
                </div>
            </form>
        </div>
    );
}
