import React, { useState, useEffect } from 'react';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });
    if (!res.ok) throw new Error(res.statusText);
    return res.json();
}

function PropertiesTable({ properties }) {
    const attributes = properties?.attributes ?? {};
    const old        = properties?.old ?? {};

    if (!Object.keys(attributes).length) return null;

    return (
        <table className="table table-bordered table-sm mb-0">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Old Value</th>
                    <th>New Value</th>
                </tr>
            </thead>
            <tbody>
                {Object.entries(attributes).map(([key, newVal]) => {
                    const oldVal   = old[key];
                    const changed  = oldVal !== undefined && String(oldVal) !== String(newVal);
                    const display  = (v) => (v === null || v === undefined) ? 'N/A' : String(v);

                    return (
                        <tr key={key}>
                            <td>{key.charAt(0).toUpperCase() + key.slice(1)}</td>
                            <td>{display(oldVal)}</td>
                            <td>
                                {changed
                                    ? <span className="badge badge-warning">{display(newVal)}</span>
                                    : display(newVal)
                                }
                            </td>
                        </tr>
                    );
                })}
            </tbody>
        </table>
    );
}

export default function LogsViewer({ endpoints, subjectType, subjectId, trans }) {
    const today = new Date().toISOString().split('T')[0];

    const [meta, setMeta]         = useState({ available_models: [], available_users: [] });
    const [model, setModel]       = useState('');
    const [userId, setUserId]     = useState('');
    const [startDate, setStart]   = useState(today);
    const [endDate, setEnd]       = useState(today);
    const [logs, setLogs]         = useState(null);
    const [loading, setLoading]   = useState(false);
    const [errors, setErrors]     = useState({});

    const t = (key) => trans?.[key] ?? key;

    useEffect(() => {
        apiFetch(endpoints.meta).then(setMeta).catch(console.error);
    }, []);

    async function handleSubmit(e) {
        e.preventDefault();

        const newErrors = {};
        if (!startDate)             newErrors.startDate = 'Requis';
        if (!endDate)               newErrors.endDate   = 'Requis';
        if (endDate < startDate)    newErrors.endDate   = 'Doit être après la date de début';
        if (!subjectType && !model) newErrors.model     = 'Requis';

        if (Object.keys(newErrors).length) {
            setErrors(newErrors);
            return;
        }

        setErrors({});
        setLoading(true);

        try {
            const params = new URLSearchParams({ startDate, endDate });
            if (subjectType) params.set('subjectType', subjectType);
            if (subjectId)   params.set('subjectId',   subjectId);
            if (model)       params.set('model',        model);
            if (userId)      params.set('userId',       userId);

            const data = await apiFetch(`${endpoints.list}?${params}`);
            setLogs(data.logs);
        } catch {
            // silently keep previous state
        } finally {
            setLoading(false);
        }
    }

    return (
        <div className="table-responsive">
            <form onSubmit={handleSubmit}>
                <div className="row">
                    {!subjectType && (
                        <div className="form-group col-md-3">
                            <label>Subject Type</label>
                            <select
                                className="form-control"
                                value={model}
                                onChange={(e) => setModel(e.target.value)}
                            >
                                <option value="">Subject Type</option>
                                {meta.available_models.map((m) => (
                                    <option key={m} value={m}>{m}</option>
                                ))}
                            </select>
                            {errors.model && <span className="text-danger">{errors.model}</span>}
                        </div>
                    )}

                    <div className="form-group col-md-3">
                        <label>{t('user')}</label>
                        <select
                            className="form-control"
                            value={userId}
                            onChange={(e) => setUserId(e.target.value)}
                        >
                            <option value="">{t('view_all')}</option>
                            {meta.available_users.map((u) => (
                                <option key={u.id} value={u.id}>{u.name}</option>
                            ))}
                        </select>
                    </div>

                    <div className="form-group col-md-3">
                        <label>{t('start_date')}</label>
                        <input
                            type="date"
                            className="form-control"
                            value={startDate}
                            onChange={(e) => setStart(e.target.value)}
                        />
                        {errors.startDate && <span className="text-danger">{errors.startDate}</span>}
                    </div>

                    <div className="form-group col-md-3">
                        <label>{t('end_date')}</label>
                        <input
                            type="date"
                            className="form-control"
                            value={endDate}
                            onChange={(e) => setEnd(e.target.value)}
                        />
                        {errors.endDate && <span className="text-danger">{errors.endDate}</span>}
                    </div>

                    <div className="form-group col-md-3">
                        <button
                            type="submit"
                            className="btn btn-danger mt-4"
                            disabled={loading}
                        >
                            <i className="fas fa-search mr-1" />
                            {loading ? '...' : t('submit')}
                        </button>
                    </div>
                </div>
            </form>

            {logs !== null && (
                <table className="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{t('label')}</th>
                            <th>Subject Type</th>
                            <th>Causer Type</th>
                            <th>Properties</th>
                            <th>{t('created_at')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.length === 0 ? (
                            <tr>
                                <td colSpan="6" className="text-center text-muted">{t('no_data')}</td>
                            </tr>
                        ) : logs.map((log) => (
                            <tr key={log.id}>
                                <th>{log.id}</th>
                                <td>{log.description}</td>
                                <td>{log.subject_type}</td>
                                <td>{log.causer_type}</td>
                                <td><PropertiesTable properties={log.properties} /></td>
                                <td>{log.created_at}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}
