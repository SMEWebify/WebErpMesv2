import React, { useState, useEffect, useCallback } from 'react';

const PRIORITY_STYLES = { 1: 'danger', 2: 'warning', 3: 'success' };

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function api(url, method = 'GET', body = null) {
    const res = await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: body ? JSON.stringify(body) : null,
    });
    return res.json();
}

function TaskCard({ task, users, priorities, columns, endpoints, onRefresh }) {
    const [comment, setComment] = useState('');
    const [saving, setSaving]   = useState(false);

    const handleAssign = async (field, value) => {
        await api(endpoints.assign, 'PATCH', { task_id: task.id, [field]: value || null });
        onRefresh();
    };

    const handlePriority = async (value) => {
        await api(endpoints.priority, 'PATCH', { task_id: task.id, priority: parseInt(value) });
        onRefresh();
    };

    const handleMove = async (statusId) => {
        setSaving(true);
        await api(endpoints.move, 'PATCH', { task_id: task.id, status_id: statusId });
        onRefresh();
        setSaving(false);
    };

    const handleComment = async () => {
        if (!comment.trim()) return;
        setSaving(true);
        await api(endpoints.comment, 'POST', { task_id: task.id, comment });
        setComment('');
        onRefresh();
        setSaving(false);
    };

    return (
        <div className="card border mb-3 shadow-sm">
            <div className="card-body p-3">
                <div className="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h5 className="card-title mb-1">#{task.id} - {task.label}</h5>
                        <span className={`badge badge-pill badge-${PRIORITY_STYLES[task.priority] ?? 'secondary'}`}>
                            Priority : {task.priority_label}
                        </span>
                    </div>
                    <div className="text-right">
                        {task.due_date ? (
                            <>
                                <span className="badge badge-info">{task.due_date.split('-').reverse().join('/')}</span>
                                {task.is_overdue && <div><span className="badge badge-danger">Overdue</span></div>}
                                {task.is_today  && <div><span className="badge badge-warning">Due today</span></div>}
                            </>
                        ) : (
                            <small className="text-muted">No due date</small>
                        )}
                    </div>
                </div>

                <p className="text-muted mb-2">Status : {task.status_title ?? 'No status'}</p>

                <div className="form-group mb-2">
                    <label className="mb-1">Primary assignee</label>
                    <select className="form-control form-control-sm" value={task.user_id ?? ''} onChange={e => handleAssign('user_id', e.target.value)}>
                        <option value="">Unassigned</option>
                        {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                    </select>
                </div>

                <div className="form-group mb-2">
                    <label className="mb-1">Secondary assignee</label>
                    <select className="form-control form-control-sm" value={task.secondary_user_id ?? ''} onChange={e => handleAssign('secondary_user_id', e.target.value)}>
                        <option value="">Unassigned</option>
                        {users.map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                    </select>
                </div>

                <div className="form-group mb-2">
                    <label className="mb-1">Priority level</label>
                    <select className="form-control form-control-sm" value={task.priority} onChange={e => handlePriority(e.target.value)}>
                        {Object.entries(priorities).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                    </select>
                </div>

                <div className="mb-2">
                    <label className="mb-1 d-block">Recent comments</label>
                    {task.comments.length > 0 ? (
                        <ul className="list-unstyled small mb-2">
                            {task.comments.map((c, i) => (
                                <li key={i} className="mb-1">
                                    <strong>{c.user}</strong>{' '}
                                    <span className="text-muted">{c.created_at}</span>
                                    <div>{c.comment}</div>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-muted small mb-2">No comments yet.</p>
                    )}

                    <div className="form-group mb-0">
                        <label className="mb-1">Add a comment</label>
                        <textarea className="form-control form-control-sm" rows={2} value={comment} onChange={e => setComment(e.target.value)} />
                        <div className="d-flex justify-content-between align-items-center mt-2">
                            <button className="btn btn-sm btn-outline-primary" disabled={saving} onClick={handleComment}>
                                Add comment
                            </button>
                            <div className="btn-group btn-group-sm">
                                {Object.entries(columns).map(([key, col]) =>
                                    col.default_status && key !== task._column ? (
                                        <button key={key} className="btn btn-outline-secondary" disabled={saving} onClick={() => handleMove(col.default_status)}>
                                            {col.title}
                                        </button>
                                    ) : null
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function GtdBoard({ endpoints }) {
    const [columns, setColumns]     = useState({});
    const [users, setUsers]         = useState([]);
    const [priorities, setPriorities] = useState({});
    const [loading, setLoading]     = useState(true);

    const load = useCallback(async () => {
        setLoading(true);
        const data = await api(endpoints.board);
        setColumns(data.columns ?? {});
        setUsers(data.users ?? []);
        setPriorities(data.priorities ?? {});
        setLoading(false);
    }, [endpoints.board]);

    useEffect(() => { load(); }, [load]);

    if (loading) return <div className="text-center p-4"><i className="fas fa-spinner fa-spin fa-2x"></i></div>;

    return (
        <div className="row">
            {Object.entries(columns).map(([colKey, col]) => (
                <div key={colKey} className="col-xl-4 col-lg-6 mb-4 d-flex">
                    <div className="card w-100 shadow-sm">
                        <div className="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <span>{col.title}</span>
                            <span className="badge badge-light">{col.tasks.length}</span>
                        </div>
                        <div className="card-body">
                            {col.tasks.length === 0 ? (
                                <p className="text-muted mb-0">No tasks in this column.</p>
                            ) : col.tasks.map(task => (
                                <TaskCard
                                    key={task.id}
                                    task={{ ...task, _column: colKey }}
                                    users={users}
                                    priorities={priorities}
                                    columns={columns}
                                    endpoints={endpoints}
                                    onRefresh={load}
                                />
                            ))}
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}
