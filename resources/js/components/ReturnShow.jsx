import React, { useState } from 'react';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function api(url, body = null) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify(body ?? {}),
    });
    return res.json();
}

function Alert({ type, msg, onClose }) {
    return (
        <div className={`alert alert-${type} alert-dismissible fade show`}>
            {msg}
            <button type="button" className="close" onClick={onClose}><span>&times;</span></button>
        </div>
    );
}

export default function ReturnShow({ initial, endpoints, trans }) {
    const [data, setData]                   = useState(initial);
    const [diagnosisNotes, setDiagnosisNotes]     = useState(initial.diagnosis);
    const [customerReport, setCustomerReport]     = useState(initial.customer_report);
    const [closureNotes, setClosureNotes]         = useState(initial.resolution_notes);
    const [alert, setAlert]                 = useState(null);
    const [saving, setSaving]               = useState(false);

    const showAlert = (type, msg) => {
        setAlert({ type, msg });
        setTimeout(() => setAlert(null), 4000);
    };

    const reload = async () => {
        // Reload by re-fetching from the same page as JSON is not available;
        // just update local state from the response message and reflect statu changes.
    };

    const handleDiagnose = async (e) => {
        e.preventDefault();
        if (!diagnosisNotes.trim()) return;
        setSaving(true);
        const res = await api(endpoints.diagnose, { diagnosis: diagnosisNotes, customer_report: customerReport });
        setSaving(false);
        if (res.message) {
            showAlert('success', res.message);
            setData(prev => ({ ...prev, diagnosis: diagnosisNotes, customer_report: customerReport, statu: Math.max(prev.statu, 2) }));
        }
    };

    const handleReopen = async () => {
        setSaving(true);
        const res = await api(endpoints.reopen);
        setSaving(false);
        showAlert(res.message?.includes('no') ? 'warning' : 'success', res.message);
        if (!res.message?.includes('no')) {
            setData(prev => ({ ...prev, statu: 3 }));
        }
    };

    const handleClose = async (e) => {
        e.preventDefault();
        setSaving(true);
        const res = await api(endpoints.close, { resolution_notes: closureNotes });
        setSaving(false);
        if (res.message) {
            showAlert('success', res.message);
            setData(prev => ({ ...prev, statu: 4, resolution_notes: closureNotes }));
        }
    };

    return (
        <>
            {alert && <Alert type={alert.type} msg={alert.msg} onClose={() => setAlert(null)} />}

            {/* Infos générales */}
            <div className="card card-primary">
                <div className="card-header"><h3 className="card-title"><i className="fas fa-undo mr-2"></i>{data.code}</h3></div>
                <div className="card-body">
                    <dl className="row mb-0">
                        <dt className="col-sm-3">{trans.label}</dt>
                        <dd className="col-sm-9">{data.label}</dd>

                        <dt className="col-sm-3">{trans.delivery}</dt>
                        <dd className="col-sm-9">
                            {data.delivery
                                ? <a href={endpoints.delivery.replace('__ID__', data.delivery.id)} className="btn btn-outline-secondary btn-sm">{data.delivery.code}</a>
                                : <span className="text-muted">{trans.no_data}</span>}
                        </dd>

                        <dt className="col-sm-3">{trans.non_conformity}</dt>
                        <dd className="col-sm-9">
                            {data.non_conformity
                                ? <a href={endpoints.nc} className="btn btn-outline-secondary btn-sm">{data.non_conformity.code}</a>
                                : <span className="text-muted">{trans.no_data}</span>}
                        </dd>

                        <dt className="col-sm-3">{trans.status}</dt>
                        <dd className="col-sm-9"><span className="badge badge-info">{data.status_label}</span></dd>

                        <dt className="col-sm-3">{trans.created_at}</dt>
                        <dd className="col-sm-9">{data.created_at}</dd>
                    </dl>
                </div>
            </div>

            {/* Lignes */}
            <div className="card card-info">
                <div className="card-header"><h3 className="card-title">{trans.lines}</h3></div>
                <div className="card-body">
                    <div className="table-responsive">
                        <table className="table table-striped">
                            <thead>
                                <tr>
                                    <th>{trans.delivery_line}</th>
                                    <th>{trans.task}</th>
                                    <th>{trans.issue}</th>
                                    <th>{trans.action}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.status}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {data.lines.length === 0 ? (
                                    <tr><td colSpan={6} className="text-center">{trans.no_data}</td></tr>
                                ) : data.lines.map(line => (
                                    <tr key={line.id}>
                                        <td>{line.delivery_line_label ?? '-'}</td>
                                        <td>
                                            {line.original_task_id ?? '-'}
                                            {line.rework_task_id && <span className="badge badge-success ml-2">{line.rework_task_id}</span>}
                                        </td>
                                        <td>{line.issue_description}</td>
                                        <td>{line.rework_instructions}</td>
                                        <td>{line.qty}</td>
                                        <td>
                                            {line.rework_task_id
                                                ? <span className="badge badge-success">{trans.in_rework}</span>
                                                : <span className="badge badge-secondary">{trans.received}</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Diagnostic + Actions */}
            <div className="row">
                <div className="col-md-6">
                    <div className="card card-warning">
                        <div className="card-header"><h3 className="card-title">{trans.diagnosis}</h3></div>
                        <div className="card-body">
                            <form onSubmit={handleDiagnose}>
                                <div className="form-group">
                                    <label>{trans.diagnosis}</label>
                                    <textarea className="form-control" rows={4} value={diagnosisNotes} onChange={e => setDiagnosisNotes(e.target.value)} />
                                </div>
                                <div className="form-group">
                                    <label>{trans.customer_report}</label>
                                    <textarea className="form-control" rows={3} value={customerReport} onChange={e => setCustomerReport(e.target.value)} />
                                </div>
                                <button type="submit" className="btn btn-success" disabled={saving}>
                                    <i className="fas fa-save"></i> {trans.save}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div className="col-md-6">
                    <div className="card card-secondary">
                        <div className="card-header"><h3 className="card-title">{trans.actions}</h3></div>
                        <div className="card-body">
                            <div className="mb-3">
                                <button className="btn btn-outline-secondary" onClick={handleReopen} disabled={saving || data.statu >= 4}>
                                    <i className="fas fa-undo"></i> {trans.reopen_tasks}
                                </button>
                            </div>
                            <form onSubmit={handleClose}>
                                <div className="form-group">
                                    <label>{trans.closure_comment}</label>
                                    <textarea className="form-control" rows={3} value={closureNotes} onChange={e => setClosureNotes(e.target.value)} />
                                </div>
                                <button type="submit" className="btn btn-success" disabled={saving || data.statu === 4}>
                                    <i className="fas fa-check"></i> {trans.close_return}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
