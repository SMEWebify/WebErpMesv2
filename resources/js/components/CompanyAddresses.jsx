import React, { useState } from 'react';
import { createPortal } from 'react-dom';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept':       'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw json;
    return json;
}

// ---------------------------------------------------------------------------
// Document-type badge config
// ---------------------------------------------------------------------------

const DOC_TYPES = [
    { key: 'quote',    label: 'DEV', color: '#007bff' },
    { key: 'order',    label: 'CMD', color: '#28a745' },
    { key: 'delivery', label: 'BL',  color: '#fd7e14' },
    { key: 'invoice',  label: 'FAC', color: '#dc3545' },
];

function DocBadge({ type, active }) {
    const cfg = DOC_TYPES.find(d => d.key === type);
    if (!cfg) return null;
    return (
        <span style={{
            display: 'inline-block',
            padding: '1px 5px',
            borderRadius: '3px',
            fontSize: '0.7rem',
            fontWeight: 700,
            marginRight: '2px',
            backgroundColor: active ? cfg.color : '#e9ecef',
            color: active ? '#fff' : '#adb5bd',
            border: `1px solid ${active ? cfg.color : '#dee2e6'}`,
        }}>
            {cfg.label}
        </span>
    );
}

// ---------------------------------------------------------------------------
// Modal overlay — rendered via portal to avoid AdminLTE overflow clipping
// ---------------------------------------------------------------------------

function Modal({ title, icon, onClose, children }) {
    return createPortal(
        <div
            style={{
                position: 'fixed', top: 0, left: 0,
                width: '100vw', height: '100vh',
                zIndex: 9999,
                backgroundColor: 'rgba(0,0,0,0.55)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                padding: '1rem',
            }}
            onClick={e => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div style={{
                width: '760px',
                maxWidth: '100%',
                maxHeight: '90vh',
                display: 'flex',
                flexDirection: 'column',
                borderRadius: '6px',
                overflow: 'hidden',
                boxShadow: '0 10px 40px rgba(0,0,0,0.4)',
            }}>
                {/* Header */}
                <div style={{
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    padding: '0.85rem 1.25rem',
                    backgroundColor: '#17a2b8',
                    flexShrink: 0,
                }}>
                    <h5 style={{ margin: 0, color: '#fff', fontWeight: 600, fontSize: '1rem' }}>
                        <i className={`fas ${icon} mr-2`} />{title}
                    </h5>
                    <button
                        type="button"
                        onClick={onClose}
                        style={{
                            background: 'none', border: 'none', color: '#fff',
                            fontSize: '1.4rem', lineHeight: 1, cursor: 'pointer',
                            opacity: 0.8, padding: '0 4px',
                        }}
                        onMouseOver={e => e.currentTarget.style.opacity = 1}
                        onMouseOut={e => e.currentTarget.style.opacity = 0.8}
                    >
                        &times;
                    </button>
                </div>
                {/* Body */}
                <div style={{
                    backgroundColor: '#fff',
                    padding: '1.5rem',
                    overflowY: 'auto',
                    flex: 1,
                }}>
                    {children}
                </div>
            </div>
        </div>,
        document.body
    );
}

// ---------------------------------------------------------------------------
// Address form (shared by create + edit)
// ---------------------------------------------------------------------------

const EMPTY = {
    ordre: '', label: '', adress: '', zipcode: '',
    city: '', province: '', country: '', number: '', mail: '', default: false,
    docTypes: [],
};

function AddressForm({ initial, onSubmit, saving, errors, trans, formId }) {
    const [form, setForm] = useState({ ...EMPTY, ...initial });

    const set = field => e => setForm(f => ({ ...f, [field]: e.target.value }));

    const toggleDocType = key => {
        setForm(f => ({
            ...f,
            docTypes: f.docTypes.includes(key)
                ? f.docTypes.filter(t => t !== key)
                : [...f.docTypes, key],
        }));
    };

    const handleSubmit = e => {
        e.preventDefault();
        onSubmit(form);
    };

    return (
        <form onSubmit={handleSubmit}>
            <div className="row">
                <div className="col-md-6">
                    <div className="form-group">
                        <label>{trans.sort}</label>
                        <div className="input-group">
                            <div className="input-group-prepend">
                                <span className="input-group-text"><i className="fas fa-sort-numeric-down" /></span>
                            </div>
                            <input type="number" className={`form-control ${errors.ordre ? 'is-invalid' : ''}`}
                                value={form.ordre ?? ''} onChange={set('ordre')} placeholder={trans.sort} />
                        </div>
                        {errors.ordre && <span className="text-danger small">{errors.ordre[0]}</span>}
                    </div>
                </div>
                <div className="col-md-6">
                    <div className="form-group">
                        <label>{trans.label}</label>
                        <div className="input-group">
                            <div className="input-group-prepend">
                                <span className="input-group-text"><i className="fas fa-tags" /></span>
                            </div>
                            <input type="text" className={`form-control ${errors.label ? 'is-invalid' : ''}`}
                                value={form.label ?? ''} onChange={set('label')} placeholder={trans.label} />
                        </div>
                        {errors.label && <span className="text-danger small">{errors.label[0]}</span>}
                    </div>
                </div>
            </div>
            <hr />
            <div className="form-group">
                <label>{trans.adress}</label>
                <input type="text" className={`form-control ${errors.adress ? 'is-invalid' : ''}`}
                    value={form.adress ?? ''} onChange={set('adress')} placeholder={trans.adress} />
                {errors.adress && <span className="text-danger small">{errors.adress[0]}</span>}
            </div>
            <div className="row">
                <div className="col-md-4">
                    <div className="form-group">
                        <label>{trans.zipcode}</label>
                        <input type="text" className={`form-control ${errors.zipcode ? 'is-invalid' : ''}`}
                            value={form.zipcode ?? ''} onChange={set('zipcode')} placeholder={trans.zipcode} />
                        {errors.zipcode && <span className="text-danger small">{errors.zipcode[0]}</span>}
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label>{trans.city}</label>
                        <input type="text" className={`form-control ${errors.city ? 'is-invalid' : ''}`}
                            value={form.city ?? ''} onChange={set('city')} placeholder={trans.city} />
                        {errors.city && <span className="text-danger small">{errors.city[0]}</span>}
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-group">
                        <label>{trans.province}</label>
                        <input type="text" className="form-control"
                            value={form.province ?? ''} onChange={set('province')} placeholder={trans.province} />
                    </div>
                </div>
            </div>
            <div className="form-group">
                <label>{trans.country}</label>
                <input type="text" className={`form-control ${errors.country ? 'is-invalid' : ''}`}
                    value={form.country ?? ''} onChange={set('country')} placeholder={trans.country} />
                {errors.country && <span className="text-danger small">{errors.country[0]}</span>}
            </div>
            <div className="row">
                <div className="col-md-6">
                    <div className="form-group">
                        <label>{trans.phone}</label>
                        <input type="text" className="form-control"
                            value={form.number ?? ''} onChange={set('number')} placeholder={trans.phone} />
                    </div>
                </div>
                <div className="col-md-6">
                    <div className="form-group">
                        <label>{trans.email}</label>
                        <input type="email" className="form-control"
                            value={form.mail ?? ''} onChange={set('mail')} placeholder={trans.email} />
                    </div>
                </div>
            </div>

            <hr />

            {/* Global default */}
            <div className="custom-control custom-switch mb-3">
                <input type="checkbox" className="custom-control-input" id={`addr-default-${formId}`}
                    checked={!!form.default}
                    onChange={e => setForm(f => ({ ...f, default: e.target.checked }))} />
                <label className="custom-control-label" htmlFor={`addr-default-${formId}`}>{trans.by_default}</label>
            </div>

            {/* Per-document-type defaults */}
            <div className="form-group">
                <label style={{ display: 'block', marginBottom: '0.4rem' }}>{trans.default_by_doc ?? 'Adresse par défaut pour :'}</label>
                <div style={{ display: 'flex', gap: '1.5rem', flexWrap: 'wrap' }}>
                    {DOC_TYPES.map(({ key, label, color }) => {
                        const checked = form.docTypes.includes(key);
                        return (
                            <div key={key} className="custom-control custom-switch">
                                <input
                                    type="checkbox"
                                    className="custom-control-input"
                                    id={`addr-doc-${key}-${formId}`}
                                    checked={checked}
                                    onChange={() => toggleDocType(key)}
                                />
                                <label
                                    className="custom-control-label"
                                    htmlFor={`addr-doc-${key}-${formId}`}
                                    style={{ fontWeight: checked ? 600 : 400, color: checked ? color : undefined }}
                                >
                                    {label}
                                </label>
                            </div>
                        );
                    })}
                </div>
            </div>

            <button type="submit" className="btn btn-info btn-flat" disabled={saving}>
                <i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-save'} mr-1`} />
                {saving ? trans.saving : trans.save}
            </button>
        </form>
    );
}

// ---------------------------------------------------------------------------
// Derive which doc types an address is default for, from the global defaults map
// ---------------------------------------------------------------------------

function docTypesForAddress(addressId, docDefaults) {
    return DOC_TYPES
        .filter(({ key }) => docDefaults[key]?.address_id === addressId)
        .map(({ key }) => key);
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function CompanyAddresses({
    initialAddresses, storeUrl, updateBaseUrl, companieId, trans,
    initialDocDefaults, syncAddressBaseUrl,
}) {
    const [addresses, setAddresses]       = useState(initialAddresses ?? []);
    const [docDefaults, setDocDefaults]   = useState(initialDocDefaults ?? {});
    const [editItem, setEditItem]         = useState(null);
    const [createErrors, setCreateErrors] = useState({});
    const [editErrors, setEditErrors]     = useState({});
    const [creating, setCreating]         = useState(false);
    const [updating, setUpdating]         = useState(false);

    const openEdit = (a) => {
        setEditItem({ ...a, docTypes: docTypesForAddress(a.id, docDefaults) });
        setEditErrors({});
    };

    const syncDocTypes = async (addressId, docTypes) => {
        if (!syncAddressBaseUrl) return;
        const url = syncAddressBaseUrl.replace('__ID__', addressId);
        await apiFetch(url, { document_types: docTypes });

        setDocDefaults(prev => {
            const next = { ...prev };
            for (const { key } of DOC_TYPES) {
                next[key] = {
                    ...next[key],
                    address_id: docTypes.includes(key) ? addressId : (next[key]?.address_id === addressId ? null : next[key]?.address_id),
                };
            }
            return next;
        });
    };

    const handleCreate = async (form) => {
        setCreating(true);
        setCreateErrors({});
        try {
            const { docTypes, ...addressData } = form;
            const created = await apiFetch(storeUrl, { ...addressData, companies_id: companieId });
            setAddresses(a => {
                const base = created.default ? a.map(x => ({ ...x, default: false })) : a;
                return [...base, created];
            });
            if (docTypes.length > 0) {
                await syncDocTypes(created.id, docTypes);
            }
        } catch (err) {
            if (err.errors) setCreateErrors(err.errors);
        } finally {
            setCreating(false);
        }
    };

    const handleUpdate = async (form) => {
        setUpdating(true);
        setEditErrors({});
        try {
            const { docTypes, ...addressData } = form;
            const updated = await apiFetch(updateBaseUrl.replace('__ID__', editItem.id), addressData);
            setAddresses(a => a.map(x => {
                if (x.id === updated.id) return updated;
                return updated.default ? { ...x, default: false } : x;
            }));
            await syncDocTypes(updated.id, docTypes);
            setEditItem(null);
        } catch (err) {
            if (err.errors) setEditErrors(err.errors);
        } finally {
            setUpdating(false);
        }
    };

    const thStyle = { whiteSpace: 'nowrap', verticalAlign: 'middle' };

    return (
        <div className="row">
            {/* Table */}
            <div className="col-md-8">
                <div className="card card-primary">
                    <div className="card-header">
                        <h3 className="card-title">{trans.addresses}</h3>
                    </div>
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style={thStyle}>#</th>
                                        <th style={thStyle}>{trans.label}</th>
                                        <th style={thStyle}>{trans.adress}</th>
                                        <th style={thStyle}>{trans.zipcode}</th>
                                        <th style={thStyle}>{trans.city}</th>
                                        <th style={thStyle}>{trans.country}</th>
                                        <th style={thStyle}>{trans.phone}</th>
                                        <th style={thStyle}>{trans.email}</th>
                                        <th style={{ ...thStyle, minWidth: '100px' }}>{trans.by_default}</th>
                                        <th style={{ ...thStyle, width: '1px' }}></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {addresses.length === 0 ? (
                                        <tr><td colSpan="10" className="text-center text-muted py-3">{trans.no_data}</td></tr>
                                    ) : addresses.map(a => {
                                        const activeTypes = docTypesForAddress(a.id, docDefaults);
                                        return (
                                            <tr key={a.id}>
                                                <td className="align-middle">{a.ordre}</td>
                                                <td className="align-middle">{a.label}</td>
                                                <td className="align-middle">{a.adress}</td>
                                                <td className="align-middle">{a.zipcode}</td>
                                                <td className="align-middle">{a.city}</td>
                                                <td className="align-middle">{a.country}</td>
                                                <td className="align-middle">{a.number}</td>
                                                <td className="align-middle">{a.mail}</td>
                                                <td className="align-middle">
                                                    {!!a.default && (
                                                        <i className="fas fa-check-circle text-success mr-1" title={trans.by_default} />
                                                    )}
                                                    {DOC_TYPES.map(({ key }) => (
                                                        <DocBadge key={key} type={key} active={activeTypes.includes(key)} />
                                                    ))}
                                                </td>
                                                <td className="py-1 align-middle" style={{ whiteSpace: 'nowrap' }}>
                                                    <button
                                                        type="button"
                                                        className="btn btn-xs btn-info"
                                                        onClick={() => openEdit(a)}
                                                    >
                                                        <i className="fas fa-pen" />
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {/* Create form */}
            <div className="col-md-4">
                <div className="card card-secondary">
                    <div className="card-header">
                        <h3 className="card-title">{trans.new_address}</h3>
                    </div>
                    <div className="card-body">
                        <AddressForm
                            initial={{}}
                            onSubmit={handleCreate}
                            saving={creating}
                            errors={createErrors}
                            trans={trans}
                            formId="create"
                        />
                    </div>
                </div>
            </div>

            {/* Edit modal */}
            {editItem && (
                <Modal title={`${trans.edit} — ${editItem.label}`} icon="fa-map-marker-alt" onClose={() => setEditItem(null)}>
                    <AddressForm
                        initial={editItem}
                        onSubmit={handleUpdate}
                        saving={updating}
                        errors={editErrors}
                        trans={trans}
                        formId={`edit-${editItem.id}`}
                    />
                </Modal>
            )}
        </div>
    );
}
