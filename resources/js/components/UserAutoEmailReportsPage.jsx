import React, { useState } from 'react';

function apiFetch(url, options = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    return fetch(url, {
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        ...options,
    });
}

export default function UserAutoEmailReportsPage({ initialReports, reportTypes, endpoints, trans }) {
    const [reports, setReports] = useState(initialReports);
    const [errors, setErrors]   = useState({});
    const [success, setSuccess] = useState('');
    const [saving, setSaving]   = useState(false);

    function setField(type, field, value) {
        setReports(prev => ({
            ...prev,
            [type]: { ...prev[type], [field]: value },
        }));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});
        setSuccess('');
        setSaving(true);
        const res = await apiFetch(endpoints.save, { method: 'PUT', body: JSON.stringify({ reports }) });
        setSaving(false);
        if (res.ok) {
            setSuccess(trans.saved ?? 'Automatic email reports saved');
        } else {
            const data = await res.json();
            setErrors(data.errors ?? {});
        }
    }

    return (
        <div className="card card-info">
                <div className="card-header">
                    <h3 className="card-title">{trans.automatic_email_reports ?? 'Automatic Email Reports'}</h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool" data-card-widget="maximize"><i className="fas fa-expand"></i></button>
                    </div>
                </div>
                <form onSubmit={handleSubmit}>
                    <div className="card-body">
                        <p className="text-muted">{trans.automatic_email_reports_help ?? 'Configure automatic email reports below.'}</p>

                        {success && (
                            <div className="alert alert-success alert-dismissible">
                                <button type="button" className="close" onClick={() => setSuccess('')}><span>&times;</span></button>
                                {success}
                            </div>
                        )}

                        <div className="table-responsive">
                            <table className="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{trans.report ?? 'Report'}</th>
                                        <th>{trans.send_time ?? 'Send Time'}</th>
                                        <th className="text-center">{trans.enabled ?? 'Enabled'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {Object.entries(reportTypes).map(([type, label]) => (
                                        <tr key={type}>
                                            <td>{label}</td>
                                            <td>
                                                <input
                                                    type="time"
                                                    className="form-control"
                                                    value={reports[type]?.send_time ?? '08:00'}
                                                    onChange={e => setField(type, 'send_time', e.target.value)}
                                                />
                                                {errors[`reports.${type}.send_time`] && (
                                                    <span className="text-danger">{errors[`reports.${type}.send_time`][0]}</span>
                                                )}
                                            </td>
                                            <td className="text-center align-middle">
                                                <input
                                                    type="checkbox"
                                                    className="form-check-input"
                                                    checked={reports[type]?.enabled ?? false}
                                                    onChange={e => setField(type, 'enabled', e.target.checked)}
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div className="card-footer">
                        <button type="submit" className="btn btn-info btn-flat" disabled={saving}>
                            <i className="fas fa-save fa-lg mr-1"></i>
                            {saving ? (trans.saving ?? 'Saving…') : (trans.update ?? 'Update')}
                        </button>
                    </div>
                </form>
            </div>
    );
}
