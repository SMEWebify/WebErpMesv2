import React, { useState } from 'react';
import { apiFetch, DOC_TYPES, DocBadge, Modal } from './company/companyShared';

// ---------------------------------------------------------------------------
// Contact form (shared by create + edit)
// ---------------------------------------------------------------------------

const EMPTY = {
    ordre: '', civility: '', first_name: '', name: '',
    function: '', number: '', mobile: '', mail: '', default: false,
    docTypes: [],
};

const CIVILITIES = ['', 'Miss', 'Ms', 'Mr', 'Mrs'];

function ContactForm({ initial, onSubmit, saving, errors, trans, formId }) {
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
                        <label>{trans.civility}</label>
                        <select className={`form-control ${errors.civility ? 'is-invalid' : ''}`}
                            value={form.civility ?? ''} onChange={set('civility')}>
                            {CIVILITIES.map(c => (
                                <option key={c} value={c}>{c || `— ${trans.civility} —`}</option>
                            ))}
                        </select>
                        {errors.civility && <span className="text-danger small">{errors.civility[0]}</span>}
                    </div>
                </div>
            </div>
            <div className="row">
                <div className="col-md-6">
                    <div className="form-group">
                        <label>{trans.first_name}</label>
                        <input type="text" className={`form-control ${errors.first_name ? 'is-invalid' : ''}`}
                            value={form.first_name ?? ''} onChange={set('first_name')} placeholder={trans.first_name} />
                        {errors.first_name && <span className="text-danger small">{errors.first_name[0]}</span>}
                    </div>
                </div>
                <div className="col-md-6">
                    <div className="form-group">
                        <label>{trans.name}</label>
                        <input type="text" className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                            value={form.name ?? ''} onChange={set('name')} placeholder={trans.name} />
                        {errors.name && <span className="text-danger small">{errors.name[0]}</span>}
                    </div>
                </div>
            </div>
            <div className="form-group">
                <label>{trans.function}</label>
                <input type="text" className="form-control"
                    value={form.function ?? ''} onChange={set('function')} placeholder={trans.function} />
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
                        <label>{trans.mobile}</label>
                        <input type="text" className="form-control"
                            value={form.mobile ?? ''} onChange={set('mobile')} placeholder={trans.mobile} />
                    </div>
                </div>
            </div>
            <div className="form-group">
                <label>{trans.email}</label>
                <input type="email" className="form-control"
                    value={form.mail ?? ''} onChange={set('mail')} placeholder={trans.email} />
            </div>

            <hr />

            {/* Global default */}
            <div className="custom-control custom-switch mb-3">
                <input type="checkbox" className="custom-control-input" id={`contact-default-${formId}`}
                    checked={!!form.default}
                    onChange={e => setForm(f => ({ ...f, default: e.target.checked }))} />
                <label className="custom-control-label" htmlFor={`contact-default-${formId}`}>{trans.by_default}</label>
            </div>

            {/* Per-document-type defaults */}
            <div className="form-group">
                <label style={{ display: 'block', marginBottom: '0.4rem' }}>{trans.default_by_doc ?? 'Interlocuteur par défaut pour :'}</label>
                <div style={{ display: 'flex', gap: '1.5rem', flexWrap: 'wrap' }}>
                    {DOC_TYPES.map(({ key, label, color }) => {
                        const checked = form.docTypes.includes(key);
                        return (
                            <div key={key} className="custom-control custom-switch">
                                <input
                                    type="checkbox"
                                    className="custom-control-input"
                                    id={`contact-doc-${key}-${formId}`}
                                    checked={checked}
                                    onChange={() => toggleDocType(key)}
                                />
                                <label
                                    className="custom-control-label"
                                    htmlFor={`contact-doc-${key}-${formId}`}
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
// Derive which doc types a contact is default for, from the global defaults map
// ---------------------------------------------------------------------------

function docTypesForContact(contactId, docDefaults) {
    return DOC_TYPES
        .filter(({ key }) => docDefaults[key]?.contact_id === contactId)
        .map(({ key }) => key);
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function CompanyContacts({
    initialContacts, storeUrl, updateBaseUrl, companieId, trans,
    initialDocDefaults, syncContactBaseUrl,
}) {
    const [contacts, setContacts]         = useState(initialContacts ?? []);
    const [docDefaults, setDocDefaults]   = useState(initialDocDefaults ?? {});
    const [editItem, setEditItem]         = useState(null);
    const [createErrors, setCreateErrors] = useState({});
    const [editErrors, setEditErrors]     = useState({});
    const [creating, setCreating]         = useState(false);
    const [updating, setUpdating]         = useState(false);

    const openEdit = (c) => {
        setEditItem({ ...c, docTypes: docTypesForContact(c.id, docDefaults) });
        setEditErrors({});
    };

    const syncDocTypes = async (contactId, docTypes) => {
        if (!syncContactBaseUrl) return;
        const url = syncContactBaseUrl.replace('__ID__', contactId);
        await apiFetch(url, { document_types: docTypes });

        // Update local docDefaults state
        setDocDefaults(prev => {
            const next = { ...prev };
            for (const { key } of DOC_TYPES) {
                next[key] = {
                    ...next[key],
                    contact_id: docTypes.includes(key) ? contactId : (next[key]?.contact_id === contactId ? null : next[key]?.contact_id),
                };
            }
            return next;
        });
    };

    const handleCreate = async (form) => {
        setCreating(true);
        setCreateErrors({});
        try {
            const { docTypes, ...contactData } = form;
            const created = await apiFetch(storeUrl, { ...contactData, companies_id: companieId });
            setContacts(c => {
                const base = created.default ? c.map(x => ({ ...x, default: false })) : c;
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
            const { docTypes, ...contactData } = form;
            const updated = await apiFetch(updateBaseUrl.replace('__ID__', editItem.id), contactData);
            setContacts(c => c.map(x => {
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
                        <h3 className="card-title">{trans.contacts}</h3>
                    </div>
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style={thStyle}>#</th>
                                        <th style={thStyle}>{trans.civility}</th>
                                        <th style={thStyle}>{trans.first_name}</th>
                                        <th style={thStyle}>{trans.name}</th>
                                        <th style={thStyle}>{trans.function}</th>
                                        <th style={thStyle}>{trans.phone}</th>
                                        <th style={thStyle}>{trans.mobile}</th>
                                        <th style={thStyle}>{trans.email}</th>
                                        <th style={{ ...thStyle, minWidth: '100px' }}>{trans.by_default}</th>
                                        <th style={{ ...thStyle, width: '1px' }}></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {contacts.length === 0 ? (
                                        <tr><td colSpan="10" className="text-center text-muted py-3">{trans.no_data}</td></tr>
                                    ) : contacts.map(c => {
                                        const activeTypes = docTypesForContact(c.id, docDefaults);
                                        return (
                                            <tr key={c.id}>
                                                <td className="align-middle">{c.ordre}</td>
                                                <td className="align-middle">{c.civility}</td>
                                                <td className="align-middle">{c.first_name}</td>
                                                <td className="align-middle">{c.name}</td>
                                                <td className="align-middle">{c.function}</td>
                                                <td className="align-middle">{c.number}</td>
                                                <td className="align-middle">{c.mobile}</td>
                                                <td className="align-middle">{c.mail}</td>
                                                <td className="align-middle">
                                                    {!!c.default && (
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
                                                        onClick={() => openEdit(c)}
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
                        <h3 className="card-title">{trans.new_contact}</h3>
                    </div>
                    <div className="card-body">
                        <ContactForm
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
                <Modal title={`${trans.edit} — ${editItem.first_name} ${editItem.name}`} icon="fa-user" onClose={() => setEditItem(null)}>
                    <ContactForm
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
