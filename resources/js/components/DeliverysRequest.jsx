import React, { useState, useEffect } from 'react';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept':           'application/json',
            'X-CSRF-TOKEN':     csrfToken(),
            'Content-Type':     'application/json',
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

const TASKS_STATUS = {
    1: { badge: 'badge-info',    key: 'no_task' },
    2: { badge: 'badge-warning', key: 'created' },
    3: { badge: 'badge-success', key: 'in_progress' },
    4: { badge: 'badge-danger',  key: 'finished' },
};

// ---------------------------------------------------------------------------
// SortIcon
// ---------------------------------------------------------------------------

function SortIcon({ field, sortField, sortAsc }) {
    if (sortField !== field) return <i className="fas fa-sort ml-1 text-muted" />;
    return <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'} ml-1`} />;
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function DeliverysRequest({
    initialCode,
    userId,
    users,
    companies,
    canManageStock,
    endpoints,
    trans,
}) {
    const [form, setForm] = useState({
        code:                   initialCode ?? '',
        label:                  initialCode ?? '',
        companiesId:            '',
        companiesAddressesId:   '',
        companiesContactsId:    '',
        userId:                 userId ?? '',
        removeFromStock:        false,
        createSerialNumber:     false,
    });

    const [addresses,  setAddresses]  = useState([]);
    const [contacts,   setContacts]   = useState([]);
    const [lines,      setLines]      = useState([]);
    const [selections, setSelections] = useState({}); // { [lineId]: { checked, qty } }
    const [sortField,  setSortField]  = useState('label');
    const [sortAsc,    setSortAsc]    = useState(true);
    const [loading,    setLoading]    = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [errors,     setErrors]     = useState({});

    // Fetch addresses, contacts and order lines when company changes
    useEffect(() => {
        if (!form.companiesId) {
            setAddresses([]);
            setContacts([]);
            setLines([]);
            setSelections({});
            return;
        }

        setLoading(true);
        apiFetch(`${endpoints.companyData}?company_id=${form.companiesId}`)
            .then(data => {
                setAddresses(data.addresses);
                setContacts(data.contacts);
                setLines(data.lines);
                setSelections({});
                setForm(f => ({ ...f, companiesAddressesId: '', companiesContactsId: '' }));
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [form.companiesId]);

    // ---------------------------------------------------------------------------
    // Sort (client-side — all lines are loaded at once)
    // ---------------------------------------------------------------------------

    const sortedLines = [...lines].sort((a, b) => {
        let va = a[sortField] ?? '';
        let vb = b[sortField] ?? '';
        if (typeof va === 'string') va = va.toLowerCase();
        if (typeof vb === 'string') vb = vb.toLowerCase();
        if (va < vb) return sortAsc ? -1 : 1;
        if (va > vb) return sortAsc ? 1 : -1;
        return 0;
    });

    function handleSort(field) {
        if (sortField === field) {
            setSortAsc(v => !v);
        } else {
            setSortField(field);
            setSortAsc(true);
        }
    }

    // ---------------------------------------------------------------------------
    // Selection helpers
    // ---------------------------------------------------------------------------

    const allChecked = lines.length > 0 && lines.every(l => selections[l.id]?.checked);

    function handleSelectAll(checked) {
        const next = {};
        lines.forEach(l => {
            next[l.id] = { checked, qty: selections[l.id]?.qty ?? '' };
        });
        setSelections(next);
    }

    function handleLineCheck(lineId, checked) {
        setSelections(s => ({ ...s, [lineId]: { ...s[lineId], checked } }));
    }

    function handleLineQty(lineId, qty) {
        setSelections(s => ({
            ...s,
            [lineId]: {
                ...s[lineId],
                qty,
                checked: Number(qty) > 0 ? true : s[lineId]?.checked ?? false,
            },
        }));
    }

    // ---------------------------------------------------------------------------
    // Submit
    // ---------------------------------------------------------------------------

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});

        const selectedLines = lines
            .filter(l => selections[l.id]?.checked && Number(selections[l.id]?.qty) > 0)
            .map(l => ({ order_line_id: l.id, qty: Number(selections[l.id].qty) }));

        if (selectedLines.length === 0) {
            setErrors({ lines: trans.no_lines ?? 'No lines selected' });
            return;
        }

        setSubmitting(true);
        try {
            const data = await apiFetch(endpoints.store, {
                method: 'POST',
                body: JSON.stringify({
                    code:                    form.code,
                    label:                   form.label,
                    companies_id:            Number(form.companiesId),
                    companies_addresses_id:  Number(form.companiesAddressesId),
                    companies_contacts_id:   Number(form.companiesContactsId),
                    user_id:                 Number(form.userId),
                    remove_from_stock:       form.removeFromStock,
                    create_serial_number:    form.createSerialNumber,
                    lines:                   selectedLines,
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
                                        <option key={c.id} value={c.id}>{c.code} - {c.label}</option>
                                    ))}
                                </select>
                            </div>
                            {fieldError('companies_id')}
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

                        {/* Stock options (permission gate) */}
                        {canManageStock && (
                            <div className="form-group col-md-3">
                                <label className="d-block">
                                    <input
                                        type="checkbox"
                                        className="mr-1"
                                        checked={form.removeFromStock}
                                        onChange={e => setField('removeFromStock', e.target.checked)}
                                    />
                                    {trans.remove_stock}
                                </label>
                                <label className="d-block">
                                    <input
                                        type="checkbox"
                                        className="mr-1"
                                        checked={form.createSerialNumber}
                                        onChange={e => setField('createSerialNumber', e.target.checked)}
                                    />
                                    {trans.create_serial}
                                </label>
                            </div>
                        )}

                        {/* Submit */}
                        <div className="form-group col-md-3 d-flex align-items-end">
                            <button type="submit" className="btn btn-success" disabled={submitting}>
                                {submitting
                                    ? <><i className="fas fa-spinner fa-spin mr-1" />{trans.new_delivery}</>
                                    : trans.new_delivery
                                }
                            </button>
                        </div>
                    </div>
                </div>
            </form>

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
                                <th>
                                    <button type="button" className="btn btn-secondary btn-sm" onClick={() => handleSort('order_code')}>
                                        {trans.order} <SortIcon field="order_code" sortField={sortField} sortAsc={sortAsc} />
                                    </button>
                                </th>
                                <th>{trans.customer}</th>
                                <th>
                                    <button type="button" className="btn btn-secondary btn-sm" onClick={() => handleSort('code')}>
                                        {trans.external_id} <SortIcon field="code" sortField={sortField} sortAsc={sortAsc} />
                                    </button>
                                </th>
                                <th>
                                    <button type="button" className="btn btn-secondary btn-sm" onClick={() => handleSort('label')}>
                                        {trans.label} <SortIcon field="label" sortField={sortField} sortAsc={sortAsc} />
                                    </button>
                                </th>
                                <th>{trans.task_status}</th>
                                <th>{trans.qty}</th>
                                <th>{trans.scum_qty}</th>
                                <th>{trans.unit}</th>
                                <th>{trans.price}</th>
                                <th>{trans.discount}</th>
                                <th>{trans.vat}</th>
                                <th>{trans.delivery_date}</th>
                                <th>
                                    {trans.action}
                                    {form.companiesId && lines.length > 0 && (
                                        <div className="custom-control custom-checkbox mt-2">
                                            <input
                                                className="custom-control-input"
                                                id="selectAllLines"
                                                type="checkbox"
                                                checked={allChecked}
                                                onChange={e => handleSelectAll(e.target.checked)}
                                            />
                                            <label className="custom-control-label" htmlFor="selectAllLines">
                                                {allChecked ? trans.deselect_all : trans.select_all}
                                            </label>
                                        </div>
                                    )}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {sortedLines.length === 0 ? (
                                <tr>
                                    <td colSpan="13" className="text-center text-muted py-3">
                                        {trans.no_data}
                                    </td>
                                </tr>
                            ) : sortedLines.map(line => {
                                const sel        = selections[line.id] ?? { checked: false, qty: '' };
                                const taskStatus = TASKS_STATUS[line.tasks_status];
                                return (
                                    <tr key={line.id}>
                                        <td>
                                            {line.order_url
                                                ? <a href={line.order_url} className="btn btn-outline-secondary btn-sm">{line.order_code}</a>
                                                : line.order_code}
                                        </td>
                                        <td>
                                            {line.order_type == 1
                                                ? (line.companie_url
                                                    ? <a href={line.companie_url} className="btn btn-outline-secondary btn-sm">{line.companie_label}</a>
                                                    : line.companie_label)
                                                : trans.internal_order}
                                        </td>
                                        <td>{line.code}</td>
                                        <td>{line.label}</td>
                                        <td>
                                            {taskStatus && (
                                                <span className={`badge ${taskStatus.badge}`}>
                                                    {trans[taskStatus.key]}
                                                </span>
                                            )}
                                        </td>
                                        <td>{line.delivered_remaining_qty}</td>
                                        <td>
                                            <input
                                                type="number"
                                                className="form-control"
                                                style={{ width: '90px' }}
                                                value={sel.qty ?? ''}
                                                onChange={e => handleLineQty(line.id, e.target.value)}
                                                placeholder={trans.qty}
                                                min="0"
                                                step="any"
                                            />
                                        </td>
                                        <td>{line.unit_label}</td>
                                        <td>{line.selling_price}</td>
                                        <td>{line.discount} %</td>
                                        <td>{line.vat_label}</td>
                                        <td>{line.delivery_date}</td>
                                        <td>
                                            <div className="custom-control custom-checkbox">
                                                <input
                                                    className="custom-control-input"
                                                    type="checkbox"
                                                    id={`line-${line.id}`}
                                                    checked={sel.checked ?? false}
                                                    onChange={e => handleLineCheck(line.id, e.target.checked)}
                                                />
                                                <label className="custom-control-label" htmlFor={`line-${line.id}`}>
                                                    {trans.add_to_note}
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>{trans.order}</th>
                                <th>{trans.customer}</th>
                                <th>{trans.external_id}</th>
                                <th>{trans.label}</th>
                                <th>{trans.task_status}</th>
                                <th>{trans.qty}</th>
                                <th>{trans.scum_qty}</th>
                                <th>{trans.unit}</th>
                                <th>{trans.price}</th>
                                <th>{trans.discount}</th>
                                <th>{trans.vat}</th>
                                <th>{trans.delivery_date}</th>
                                <th>{trans.action}</th>
                            </tr>
                        </tfoot>
                    </table>
                )}
            </div>
        </div>
    );
}
