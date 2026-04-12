import React, { useState, useEffect, useRef, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
        ...options,
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) {
        const err = new Error(json.message || `HTTP ${res.status}`);
        err.errors = json.errors ?? {};
        throw err;
    }
    return json;
}

function fmt(str) {
    if (!str) return '—';
    return new Date(str).toLocaleString('fr-FR');
}

function url(template, id) {
    return template.replace('__ID__', id);
}

const STATUS_OPTIONS = [
    { value: 'draft',    label: 'Brouillon' },
    { value: 'active',   label: 'Actif' },
    { value: 'closed',   label: 'Clôturé' },
    { value: 'archived', label: 'Archivé' },
];

const STATUS_BADGE = {
    draft:    'badge-secondary',
    active:   'badge-success',
    closed:   'badge-danger',
    archived: 'badge-dark',
};

function StatusBadge({ status }) {
    return (
        <span className={`badge ${STATUS_BADGE[status] ?? 'badge-secondary'}`}>
            {STATUS_OPTIONS.find(o => o.value === status)?.label ?? status}
        </span>
    );
}

// ---------------------------------------------------------------------------
// Field helpers
// ---------------------------------------------------------------------------

function Field({ label, children }) {
    return (
        <div className="form-group">
            <label>{label}</label>
            {children}
        </div>
    );
}

function Input({ id, value, onChange, type = 'text', required, min, step }) {
    return (
        <input
            id={id}
            className="form-control"
            type={type}
            value={value}
            onChange={e => onChange(e.target.value)}
            required={required}
            min={min}
            step={step}
        />
    );
}

function Select({ id, value, onChange, options }) {
    return (
        <select id={id} className="form-control" value={value} onChange={e => onChange(e.target.value)}>
            {options.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
        </select>
    );
}

function Checkbox({ id, checked, onChange, label }) {
    return (
        <div className="form-check">
            <input
                id={id}
                className="form-check-input"
                type="checkbox"
                checked={checked}
                onChange={e => onChange(e.target.checked)}
            />
            <label className="form-check-label" htmlFor={id}>{label}</label>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Create / Update Project Form
// ---------------------------------------------------------------------------

const EMPTY_PROJECT_FORM = {
    title: '', companies_id: '', orders_id: '', order_lines_id: '',
    of_id: '', status: 'draft', quantity_planned: '', serial_tracking: false,
};

function ProjectForm({ title, theme, icon, initialValues, submitLabel, onSubmit, loading }) {
    const [form, setForm] = useState(initialValues ?? EMPTY_PROJECT_FORM);

    useEffect(() => {
        if (initialValues) setForm(initialValues);
    }, [initialValues]);

    const set = (key) => (val) => setForm(prev => ({ ...prev, [key]: val }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        await onSubmit(form);
    };

    return (
        <div className={`card card-${theme} card-outline`}>
            <div className="card-header">
                <h3 className="card-title"><i className={`${icon} mr-1`} />{title}</h3>
            </div>
            <div className="card-body">
                <form onSubmit={handleSubmit}>
                    <Field label="Titre">
                        <Input id={`${title}-title`} value={form.title} onChange={set('title')} required />
                    </Field>
                    <Field label="ID société">
                        <Input id={`${title}-company`} value={form.companies_id} onChange={set('companies_id')} type="number" required />
                    </Field>
                    <Field label="ID commande">
                        <Input id={`${title}-order`} value={form.orders_id} onChange={set('orders_id')} type="number" />
                    </Field>
                    <Field label="ID ligne commande">
                        <Input id={`${title}-order-line`} value={form.order_lines_id} onChange={set('order_lines_id')} type="number" />
                    </Field>
                    <Field label="ID OF">
                        <Input id={`${title}-of`} value={form.of_id} onChange={set('of_id')} type="number" />
                    </Field>
                    <Field label="Statut">
                        <Select id={`${title}-status`} value={form.status} onChange={set('status')} options={STATUS_OPTIONS} />
                    </Field>
                    <Field label="Quantité prévue">
                        <Input id={`${title}-qty`} value={form.quantity_planned} onChange={set('quantity_planned')} type="number" min="0" />
                    </Field>
                    <div className="form-group">
                        <Checkbox
                            id={`${title}-serial`}
                            checked={Boolean(form.serial_tracking)}
                            onChange={set('serial_tracking')}
                            label="Suivi par numéro de série"
                        />
                    </div>
                    <button className={`btn btn-${theme}`} type="submit" disabled={loading}>
                        {loading ? 'Enregistrement...' : submitLabel}
                    </button>
                </form>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Projects Table
// ---------------------------------------------------------------------------

function ProjectsTable({ projects, onSelect, selectedId }) {
    return (
        <div className="card card-secondary card-outline">
            <div className="card-header">
                <h3 className="card-title"><i className="fas fa-list mr-1" />Projets d'inspection</h3>
            </div>
            <div className="card-body p-0">
                <div className="table-responsive">
                    <table className="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Titre</th>
                                <th>Statut</th>
                                <th>Créé le</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {projects.length === 0 && (
                                <tr><td colSpan={6} className="text-center text-muted">Aucun projet</td></tr>
                            )}
                            {projects.map(p => (
                                <tr key={p.id} className={selectedId === p.id ? 'table-primary' : ''}>
                                    <td>{p.id}</td>
                                    <td>{p.code ?? '—'}</td>
                                    <td>{p.title ?? ''}</td>
                                    <td><StatusBadge status={p.status} /></td>
                                    <td>{fmt(p.created_at)}</td>
                                    <td>
                                        <button
                                            className="btn btn-sm btn-outline-primary"
                                            onClick={() => onSelect(p.id)}
                                        >
                                            Sélectionner
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
// Documents Section
// ---------------------------------------------------------------------------

const DOC_STATUS_BADGE = {
    draft:            'badge-secondary',
    pending_approval: 'badge-warning',
    approved:         'badge-success',
    obsolete:         'badge-dark',
};

const DOC_STATUS_LABEL = {
    draft:            'Brouillon',
    pending_approval: 'En attente',
    approved:         'Approuvé',
    obsolete:         'Obsolète',
};

function DocumentStatusBadge({ status }) {
    return (
        <span className={`badge ${DOC_STATUS_BADGE[status] ?? 'badge-secondary'}`}>
            {DOC_STATUS_LABEL[status] ?? status}
        </span>
    );
}

function DocumentsSection({ documents, projectId, endpoints, onRefresh, canApprove }) {
    const [type, setType] = useState('plan');
    const [version, setVersion] = useState('');
    const [loading, setLoading] = useState(false);
    const [actionLoading, setActionLoading] = useState(null);
    const fileRef = useRef(null);

    const handleUpload = async (e) => {
        e.preventDefault();
        if (!projectId) { alert('Sélectionnez un projet.'); return; }
        setLoading(true);
        try {
            const fd = new FormData();
            fd.append('file', fileRef.current.files[0]);
            fd.append('type', type);
            if (version) fd.append('version_label', version);
            await fetch(url(endpoints.projectDocuments, projectId), {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: fd,
            }).then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); });
            fileRef.current.value = '';
            setVersion('');
            await onRefresh();
        } finally {
            setLoading(false);
        }
    };

    const handleAction = async (docId, action) => {
        const endpointKey = { submit: 'documentSubmit', approve: 'documentApprove', obsolete: 'documentObsolete' }[action];
        setActionLoading(docId + action);
        try {
            await apiFetch(url(endpoints[endpointKey], docId), { method: 'POST' });
            await onRefresh();
        } finally {
            setActionLoading(null);
        }
    };

    return (
        <div>
            <h5>Documents</h5>
            <form onSubmit={handleUpload}>
                <Field label="Fichier">
                    <input ref={fileRef} className="form-control" type="file" accept=".pdf,.png,.jpg,.jpeg" required />
                </Field>
                <Field label="Type">
                    <Select
                        id="doc-type"
                        value={type}
                        onChange={setType}
                        options={[
                            { value: 'plan',  label: 'Plan' },
                            { value: 'spec',  label: 'Spécification' },
                            { value: 'photo', label: 'Photo' },
                            { value: 'other', label: 'Autre' },
                        ]}
                    />
                </Field>
                <Field label="Version">
                    <Input id="doc-version" value={version} onChange={setVersion} />
                </Field>
                <button className="btn btn-outline-primary btn-sm" type="submit" disabled={loading}>
                    {loading ? '...' : 'Ajouter document'}
                </button>
            </form>

            <ul className="list-group mt-3">
                {documents.length === 0 && (
                    <li className="list-group-item text-muted">Aucun document</li>
                )}
                {documents.map(doc => (
                    <li key={doc.id} className="list-group-item">
                        <div className="d-flex justify-content-between align-items-start">
                            <div>
                                <span className="font-weight-bold">{doc.file_name ?? doc.file_path}</span>
                                {doc.version_label && (
                                    <span className="text-muted small ml-2">v{doc.version_label}</span>
                                )}
                                <div className="mt-1">
                                    <span className="badge badge-light mr-1">{doc.type ?? ''}</span>
                                    <DocumentStatusBadge status={doc.status} />
                                    {doc.approved_at && (
                                        <span className="text-muted small ml-2">
                                            par {doc.approver?.name ?? '—'} le {fmt(doc.approved_at)}
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="btn-group btn-group-sm ml-2">
                                {doc.status === 'draft' && (
                                    <button
                                        className="btn btn-outline-warning"
                                        disabled={actionLoading === doc.id + 'submit'}
                                        onClick={() => handleAction(doc.id, 'submit')}
                                        title="Soumettre pour approbation"
                                    >
                                        <i className="fas fa-paper-plane" />
                                    </button>
                                )}
                                {doc.status === 'pending_approval' && canApprove && (
                                    <button
                                        className="btn btn-outline-success"
                                        disabled={actionLoading === doc.id + 'approve'}
                                        onClick={() => handleAction(doc.id, 'approve')}
                                        title="Approuver"
                                    >
                                        <i className="fas fa-check" />
                                    </button>
                                )}
                                {doc.status === 'approved' && canApprove && (
                                    <button
                                        className="btn btn-outline-dark"
                                        disabled={actionLoading === doc.id + 'obsolete'}
                                        onClick={() => handleAction(doc.id, 'obsolete')}
                                        title="Rendre obsolète"
                                    >
                                        <i className="fas fa-archive" />
                                    </button>
                                )}
                            </div>
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Sessions Section
// ---------------------------------------------------------------------------

function SessionsSection({ sessions, projectId, endpoints, onRefresh }) {
    const [type, setType] = useState('lot');
    const [qty, setQty] = useState('');
    const [startedAt, setStartedAt] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!projectId) { alert('Sélectionnez un projet.'); return; }
        setLoading(true);
        try {
            await apiFetch(url(endpoints.projectSessions, projectId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type,
                    quantity_to_measure: qty ? Number(qty) : null,
                    started_at: startedAt || null,
                }),
            });
            setQty(''); setStartedAt('');
            await onRefresh();
        } finally {
            setLoading(false);
        }
    };

    const handleAction = async (sessionId, action) => {
        const endpoint = action === 'submit' ? endpoints.sessionSubmit : endpoints.sessionClose;
        await apiFetch(url(endpoint, sessionId), { method: 'POST' });
        await onRefresh();
    };

    return (
        <div>
            <h5>Sessions de mesure</h5>
            <form onSubmit={handleSubmit}>
                <Field label="Type">
                    <Select
                        id="session-type"
                        value={type}
                        onChange={setType}
                        options={[
                            { value: 'lot',     label: 'Lot' },
                            { value: 'serial',  label: 'Série' },
                            { value: 'recheck', label: 'Recontrôle' },
                        ]}
                    />
                </Field>
                <Field label="Quantité à mesurer">
                    <Input id="session-qty" value={qty} onChange={setQty} type="number" min="0" />
                </Field>
                <Field label="Date de démarrage">
                    <Input id="session-started" value={startedAt} onChange={setStartedAt} type="datetime-local" />
                </Field>
                <button className="btn btn-outline-primary btn-sm" type="submit" disabled={loading}>
                    {loading ? '...' : 'Créer session'}
                </button>
            </form>
            <ul className="list-group mt-3">
                {sessions.map(s => (
                    <li key={s.id} className="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{s.session_code}</strong>
                            <div className="text-muted small">{s.type} — {s.status}</div>
                        </div>
                        <div className="btn-group btn-group-sm">
                            <button className="btn btn-outline-success" onClick={() => handleAction(s.id, 'submit')}>Soumettre</button>
                            <button className="btn btn-outline-danger" onClick={() => handleAction(s.id, 'close')}>Clôturer</button>
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Control Points Section
// ---------------------------------------------------------------------------

const EMPTY_POINT = {
    id: '', number: '', label: '', category: 'dimension', unit: '',
    nominal_value: '', tol_min: '', tol_max: '', plan_page: '',
    plan_ref: '', phase: '', instrument_type: '', is_critical: false,
};

function ControlPointsSection({ points, projectId, endpoints, onRefresh }) {
    const [form, setForm] = useState(EMPTY_POINT);
    const [loading, setLoading] = useState(false);

    const set = (key) => (val) => setForm(prev => ({ ...prev, [key]: val }));

    const reset = () => setForm(EMPTY_POINT);

    const editPoint = (point) => {
        setForm({
            id:              point.id,
            number:          point.number ?? '',
            label:           point.label ?? '',
            category:        point.category ?? 'dimension',
            unit:            point.unit ?? '',
            nominal_value:   point.nominal_value ?? '',
            tol_min:         point.tol_min ?? '',
            tol_max:         point.tol_max ?? '',
            plan_page:       point.plan_page ?? '',
            plan_ref:        point.plan_ref ?? '',
            phase:           point.phase ?? '',
            instrument_type: point.instrument_type ?? '',
            is_critical:     Boolean(point.is_critical),
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!projectId) { alert('Sélectionnez un projet.'); return; }
        setLoading(true);
        try {
            const payload = {
                number:          Number(form.number),
                label:           form.label,
                category:        form.category,
                unit:            form.unit || null,
                nominal_value:   form.nominal_value !== '' ? Number(form.nominal_value) : null,
                tol_min:         form.tol_min !== ''       ? Number(form.tol_min)       : null,
                tol_max:         form.tol_max !== ''       ? Number(form.tol_max)       : null,
                plan_page:       form.plan_page !== ''     ? Number(form.plan_page)     : null,
                plan_ref:        form.plan_ref || null,
                phase:           form.phase || null,
                instrument_type: form.instrument_type || null,
                is_critical:     Boolean(form.is_critical),
            };
            if (form.id) {
                await apiFetch(url(endpoints.controlPointUpdate, form.id), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
            } else {
                await apiFetch(url(endpoints.projectControlPoints, projectId), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
            }
            reset();
            await onRefresh();
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (pointId) => {
        if (!confirm('Supprimer ce point de contrôle ?')) return;
        await apiFetch(url(endpoints.controlPointDelete, pointId), { method: 'DELETE' });
        await onRefresh();
    };

    return (
        <div>
            <h5>Points de contrôle</h5>
            <form onSubmit={handleSubmit}>
                <div className="form-row">
                    <div className="form-group col-md-2">
                        <label>N°</label>
                        <Input id="cp-number" value={form.number} onChange={set('number')} type="number" required />
                    </div>
                    <div className="form-group col-md-4">
                        <label>Libellé</label>
                        <Input id="cp-label" value={form.label} onChange={set('label')} required />
                    </div>
                    <div className="form-group col-md-3">
                        <label>Catégorie</label>
                        <Select
                            id="cp-category"
                            value={form.category}
                            onChange={set('category')}
                            options={[
                                { value: 'dimension', label: 'Dimension' },
                                { value: 'visual',    label: 'Visuel' },
                                { value: 'attribute', label: 'Attribut' },
                            ]}
                        />
                    </div>
                    <div className="form-group col-md-3">
                        <label>Unité</label>
                        <Input id="cp-unit" value={form.unit} onChange={set('unit')} />
                    </div>
                </div>
                <div className="form-row">
                    <div className="form-group col-md-3">
                        <label>Nominal</label>
                        <Input id="cp-nominal" value={form.nominal_value} onChange={set('nominal_value')} type="number" step="0.01" />
                    </div>
                    <div className="form-group col-md-3">
                        <label>Tol min</label>
                        <Input id="cp-tol-min" value={form.tol_min} onChange={set('tol_min')} type="number" step="0.01" />
                    </div>
                    <div className="form-group col-md-3">
                        <label>Tol max</label>
                        <Input id="cp-tol-max" value={form.tol_max} onChange={set('tol_max')} type="number" step="0.01" />
                    </div>
                    <div className="form-group col-md-3">
                        <label>Plan page</label>
                        <Input id="cp-plan-page" value={form.plan_page} onChange={set('plan_page')} type="number" />
                    </div>
                </div>
                <div className="form-row">
                    <div className="form-group col-md-4">
                        <label>Plan ref</label>
                        <Input id="cp-plan-ref" value={form.plan_ref} onChange={set('plan_ref')} />
                    </div>
                    <div className="form-group col-md-4">
                        <label>Phase</label>
                        <Input id="cp-phase" value={form.phase} onChange={set('phase')} />
                    </div>
                    <div className="form-group col-md-4">
                        <label>Instrument</label>
                        <Input id="cp-instrument" value={form.instrument_type} onChange={set('instrument_type')} />
                    </div>
                </div>
                <div className="form-group">
                    <Checkbox id="cp-critical" checked={form.is_critical} onChange={set('is_critical')} label="Point critique" />
                </div>
                <button className="btn btn-outline-primary btn-sm mr-1" type="submit" disabled={loading}>
                    {loading ? '...' : 'Enregistrer'}
                </button>
                <button className="btn btn-outline-secondary btn-sm" type="button" onClick={reset}>
                    Réinitialiser
                </button>
            </form>

            <div className="table-responsive mt-3">
                <table className="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Libellé</th>
                            <th>Catégorie</th>
                            <th>Nominal</th>
                            <th>Tol.</th>
                            <th>Unité</th>
                            <th>Plan</th>
                            <th>Critique</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {points.length === 0 && (
                            <tr><td colSpan={9} className="text-center text-muted">Aucun point</td></tr>
                        )}
                        {points.map(p => (
                            <tr key={p.id}>
                                <td>{p.number}</td>
                                <td>{p.label}</td>
                                <td>{p.category ?? '—'}</td>
                                <td>{p.nominal_value ?? '—'}</td>
                                <td>{p.tol_min ?? '—'} / {p.tol_max ?? '—'}</td>
                                <td>{p.unit ?? '—'}</td>
                                <td>{p.plan_page ? `P.${p.plan_page}` : ''} {p.plan_ref ?? ''}</td>
                                <td>{p.is_critical ? <span className="badge badge-danger">Oui</span> : 'Non'}</td>
                                <td>
                                    <button className="btn btn-xs btn-outline-info mr-1" onClick={() => editPoint(p)}>Éditer</button>
                                    <button className="btn btn-xs btn-outline-danger" onClick={() => handleDelete(p.id)}>Suppr.</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Project Details Panel
// ---------------------------------------------------------------------------

function ProjectDetails({ project, endpoints, onRefresh, canApprove }) {
    if (!project) {
        return (
            <div className="card card-secondary card-outline">
                <div className="card-header">
                    <h3 className="card-title"><i className="fas fa-search mr-1" />Détails du projet</h3>
                </div>
                <div className="card-body text-muted">
                    Sélectionnez un projet pour charger ses détails.
                </div>
            </div>
        );
    }

    return (
        <div className="card card-secondary card-outline">
            <div className="card-header">
                <h3 className="card-title"><i className="fas fa-search mr-1" />Détails du projet</h3>
            </div>
            <div className="card-body">
                <div className="mb-3">
                    <strong>{project.title ?? ''}</strong>
                    {project.code && <span className="text-muted ml-2">({project.code})</span>}
                    <div>Statut : <StatusBadge status={project.status} /></div>
                    <div>Quantité prévue : {project.quantity_planned ?? '—'}</div>
                    <div>Client : {project.company?.label ?? '—'}</div>
                </div>

                <div className="d-flex flex-wrap mb-3" style={{ gap: '0.5rem' }}>
                    <a
                        href={url(endpoints.exportPdf, project.id)}
                        className="btn btn-outline-danger btn-sm"
                        target="_blank"
                        rel="noreferrer"
                    >
                        <i className="fas fa-file-pdf mr-1" />Exporter PDF
                    </a>
                    <a
                        href={url(endpoints.exportXlsx, project.id)}
                        className="btn btn-outline-success btn-sm"
                        target="_blank"
                        rel="noreferrer"
                    >
                        <i className="fas fa-file-excel mr-1" />Exporter XLSX
                    </a>
                </div>

                <div className="row">
                    <div className="col-lg-6">
                        <DocumentsSection
                            documents={project.documents ?? []}
                            projectId={project.id}
                            endpoints={endpoints}
                            onRefresh={onRefresh}
                            canApprove={canApprove}
                        />
                    </div>
                    <div className="col-lg-6">
                        <SessionsSection
                            sessions={project.measure_sessions ?? []}
                            projectId={project.id}
                            endpoints={endpoints}
                            onRefresh={onRefresh}
                        />
                    </div>
                </div>

                <hr />

                <ControlPointsSection
                    points={project.control_points ?? []}
                    projectId={project.id}
                    endpoints={endpoints}
                    onRefresh={onRefresh}
                />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Root App
// ---------------------------------------------------------------------------

export default function InspectionProjectsApp({ endpoints, canApprove }) {
    const [projects, setProjects] = useState([]);
    const [selectedProject, setSelectedProject] = useState(null);
    const [createLoading, setCreateLoading] = useState(false);
    const [updateLoading, setUpdateLoading] = useState(false);
    const [error, setError] = useState(null);

    const loadProjects = useCallback(async () => {
        try {
            const data = await apiFetch(endpoints.projectsIndex);
            setProjects(data.data ?? data);
        } catch (e) {
            setError(e.message);
        }
    }, [endpoints.projectsIndex]);

    const loadProjectDetails = useCallback(async (projectId) => {
        try {
            const data = await apiFetch(url(endpoints.projectShow, projectId));
            setSelectedProject(data);
        } catch (e) {
            setError(e.message);
        }
    }, [endpoints.projectShow]);

    useEffect(() => { loadProjects(); }, [loadProjects]);

    const handleCreate = async (form) => {
        setCreateLoading(true);
        try {
            await apiFetch(endpoints.projectStore, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title:           form.title,
                    companies_id:    Number(form.companies_id),
                    orders_id:       form.orders_id       ? Number(form.orders_id)       : null,
                    order_lines_id:  form.order_lines_id  ? Number(form.order_lines_id)  : null,
                    of_id:           form.of_id           ? Number(form.of_id)           : null,
                    status:          form.status,
                    quantity_planned: form.quantity_planned ? Number(form.quantity_planned) : null,
                    serial_tracking: Boolean(form.serial_tracking),
                }),
            });
            await loadProjects();
        } catch (e) {
            setError(e.message);
        } finally {
            setCreateLoading(false);
        }
    };

    const handleUpdate = async (form) => {
        if (!selectedProject) { alert('Sélectionnez un projet.'); return; }
        setUpdateLoading(true);
        try {
            await apiFetch(url(endpoints.projectUpdate, selectedProject.id), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    title:           form.title || undefined,
                    companies_id:    form.companies_id    ? Number(form.companies_id)    : undefined,
                    orders_id:       form.orders_id       ? Number(form.orders_id)       : null,
                    order_lines_id:  form.order_lines_id  ? Number(form.order_lines_id)  : null,
                    of_id:           form.of_id           ? Number(form.of_id)           : null,
                    status:          form.status || undefined,
                    quantity_planned: form.quantity_planned ? Number(form.quantity_planned) : null,
                    serial_tracking: Boolean(form.serial_tracking),
                }),
            });
            await loadProjects();
            await loadProjectDetails(selectedProject.id);
        } catch (e) {
            setError(e.message);
        } finally {
            setUpdateLoading(false);
        }
    };

    const handleSelect = async (projectId) => {
        await loadProjectDetails(projectId);
    };

    const handleRefreshDetails = useCallback(async () => {
        if (selectedProject) await loadProjectDetails(selectedProject.id);
    }, [selectedProject, loadProjectDetails]);

    const updateInitialValues = selectedProject ? {
        title:           selectedProject.title ?? '',
        companies_id:    selectedProject.companies_id ?? '',
        orders_id:       selectedProject.orders_id ?? '',
        order_lines_id:  selectedProject.order_lines_id ?? '',
        of_id:           selectedProject.of_id ?? '',
        status:          selectedProject.status ?? 'draft',
        quantity_planned: selectedProject.quantity_planned ?? '',
        serial_tracking: Boolean(selectedProject.serial_tracking),
    } : null;

    return (
        <div>
            {error && (
                <div className="alert alert-danger alert-dismissible">
                    {error}
                    <button type="button" className="close" onClick={() => setError(null)}>
                        <span>&times;</span>
                    </button>
                </div>
            )}

            <div className="row">
                <div className="col-lg-4">
                    <ProjectForm
                        title="Créer un projet d'inspection"
                        theme="primary"
                        icon="fas fa-clipboard-check"
                        submitLabel="Créer"
                        onSubmit={handleCreate}
                        loading={createLoading}
                    />
                    <ProjectForm
                        title="Mettre à jour le projet"
                        theme="info"
                        icon="fas fa-edit"
                        submitLabel="Mettre à jour"
                        initialValues={updateInitialValues}
                        onSubmit={handleUpdate}
                        loading={updateLoading}
                    />
                </div>

                <div className="col-lg-8">
                    <ProjectsTable
                        projects={projects}
                        onSelect={handleSelect}
                        selectedId={selectedProject?.id}
                    />
                    <ProjectDetails
                        project={selectedProject}
                        endpoints={endpoints}
                        onRefresh={handleRefreshDetails}
                        canApprove={canApprove}
                    />
                </div>
            </div>
        </div>
    );
}
