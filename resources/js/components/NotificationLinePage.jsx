import React, { useState, useEffect } from 'react';

function apiFetch(url, options = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    return fetch(url, {
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        ...options,
    });
}

function NotificationItem({ n, onRead, showReadAction }) {
    return (
        <div className={`d-flex align-items-start py-2 ${n.read_at ? 'text-muted' : ''}`}>
            <a href={n.route} className="flex-grow-1 text-decoration-none text-dark d-flex align-items-center">
                <i className={`${n.icon} mr-2 flex-shrink-0`} style={{ width: 16 }}></i>
                <span className="small">{n.text}</span>
            </a>
            <div className="ml-2 d-flex flex-column align-items-end flex-shrink-0">
                <span className="text-muted" style={{ fontSize: '0.75rem', whiteSpace: 'nowrap' }}>{n.time}</span>
                {!n.read_at && (
                    <span className="badge badge-primary" style={{ fontSize: '0.65rem' }}>Nouveau</span>
                )}
                {n.read_at && (
                    <span className="badge badge-secondary" style={{ fontSize: '0.65rem' }}>Lu</span>
                )}
            </div>
            {showReadAction && !n.read_at && (
                <button
                    className="btn btn-sm btn-outline-info ml-2 flex-shrink-0"
                    style={{ padding: '1px 6px', fontSize: '0.7rem' }}
                    onClick={() => onRead(n.id)}
                    title="Marquer comme lu"
                >
                    <i className="fas fa-check"></i>
                </button>
            )}
        </div>
    );
}

export default function NotificationLinePage({ initialNotifications, endpoints, trans }) {
    const [activeTab, setActiveTab]       = useState('unread');
    const [unread, setUnread]             = useState(initialNotifications);
    const [history, setHistory]           = useState([]);
    const [historyPage, setHistoryPage]   = useState(1);
    const [historyHasMore, setHistoryHasMore] = useState(true);
    const [historyLoading, setHistoryLoading] = useState(false);
    const [historyLoaded, setHistoryLoaded]   = useState(false);
    const [success, setSuccess]           = useState('');

    useEffect(() => {
        if (activeTab === 'history' && !historyLoaded) {
            loadHistory(1);
        }
    }, [activeTab]);

    async function loadHistory(page) {
        setHistoryLoading(true);
        try {
            const res = await apiFetch(`${endpoints.history}?page=${page}`);
            if (res.ok) {
                const data = await res.json();
                setHistory(prev => page === 1 ? data.notifications : [...prev, ...data.notifications]);
                setHistoryPage(data.current_page);
                setHistoryHasMore(data.has_more);
                setHistoryLoaded(true);
            }
        } finally {
            setHistoryLoading(false);
        }
    }

    async function handleRead(id) {
        const url = endpoints.readOne.replace('__ID__', id);
        const res = await apiFetch(url, { method: 'POST' });
        if (res.ok) {
            setUnread(prev => prev.filter(n => n.id !== id));
            setHistory(prev => prev.map(n => n.id === id ? { ...n, read_at: new Date().toISOString() } : n));
            showSuccess(trans.read_success ?? 'Marqué comme lu');
        }
    }

    async function handleReadAll() {
        const res = await apiFetch(endpoints.readAll, { method: 'POST' });
        if (res.ok) {
            const now = new Date().toISOString();
            setUnread([]);
            setHistory(prev => prev.map(n => ({ ...n, read_at: n.read_at ?? now })));
            showSuccess(trans.all_read_success ?? 'Toutes les notifications marquées comme lues');
        }
    }

    function showSuccess(msg) {
        setSuccess(msg);
        setTimeout(() => setSuccess(''), 3000);
    }

    const tabStyle = {
        base: {
            display: 'flex', alignItems: 'center', gap: 8,
            padding: '10px 22px', border: 'none', background: 'transparent',
            fontSize: '0.88rem', fontWeight: 500, color: '#6c757d',
            cursor: 'pointer', borderBottom: '3px solid transparent',
            transition: 'color .15s, border-color .15s',
            whiteSpace: 'nowrap',
        },
        active: { color: '#007bff', borderBottom: '3px solid #007bff' },
    };

    return (
        <div className="card shadow-sm" style={{ border: '1px solid #e3e6ea', borderRadius: 8 }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0 8px', borderBottom: '1px solid #e3e6ea', background: '#fff', borderRadius: '8px 8px 0 0' }}>
                <div style={{ display: 'flex' }}>
                    <button
                        style={{ ...tabStyle.base, ...(activeTab === 'unread' ? tabStyle.active : {}) }}
                        onClick={() => setActiveTab('unread')}
                    >
                        <i className="fas fa-bell" style={{ fontSize: '0.85rem' }}></i>
                        {trans.unread_tab ?? 'Non lues'}
                        {unread.length > 0 && (
                            <span style={{ background: '#dc3545', color: '#fff', borderRadius: 10, fontSize: '0.7rem', padding: '1px 7px', fontWeight: 600 }}>
                                {unread.length}
                            </span>
                        )}
                    </button>
                    <button
                        style={{ ...tabStyle.base, ...(activeTab === 'history' ? tabStyle.active : {}) }}
                        onClick={() => setActiveTab('history')}
                    >
                        <i className="fas fa-history" style={{ fontSize: '0.85rem' }}></i>
                        {trans.history_tab ?? 'Historique'}
                    </button>
                </div>
            </div>

            <div className="card-body">
                {success && (
                    <div className="alert alert-success alert-dismissible py-2 mb-2">
                        <button type="button" className="close" onClick={() => setSuccess('')}><span>&times;</span></button>
                        {success}
                    </div>
                )}

                {activeTab === 'unread' && (
                    <>
                        {unread.length === 0 ? (
                            <div className="text-center text-muted py-3">
                                <i className="fas fa-check-circle fa-2x mb-2 d-block"></i>
                                {trans.no_data ?? 'Aucune notification non lue'}
                            </div>
                        ) : (
                            unread.map((n, i) => (
                                <div key={n.id}>
                                    <NotificationItem n={n} onRead={handleRead} showReadAction={true} />
                                    {i < unread.length - 1 && <hr className="my-1" />}
                                </div>
                            ))
                        )}
                    </>
                )}

                {activeTab === 'history' && (
                    <>
                        {!historyLoaded && historyLoading && (
                            <div className="text-center text-muted py-3">
                                <i className="fas fa-spinner fa-spin mr-1"></i>
                                {trans.loading ?? 'Chargement…'}
                            </div>
                        )}
                        {historyLoaded && history.length === 0 && (
                            <div className="text-center text-muted py-3">
                                <i className="fas fa-inbox fa-2x mb-2 d-block"></i>
                                {trans.history_empty ?? 'Aucune notification'}
                            </div>
                        )}
                        {history.map((n, i) => (
                            <div key={n.id}>
                                <NotificationItem n={n} onRead={handleRead} showReadAction={true} />
                                {i < history.length - 1 && <hr className="my-1" />}
                            </div>
                        ))}
                        {historyLoaded && historyHasMore && (
                            <div className="text-center mt-3">
                                <button
                                    className="btn btn-outline-secondary btn-sm"
                                    onClick={() => loadHistory(historyPage + 1)}
                                    disabled={historyLoading}
                                >
                                    {historyLoading
                                        ? <><i className="fas fa-spinner fa-spin mr-1"></i>{trans.loading ?? 'Chargement…'}</>
                                        : <><i className="fas fa-chevron-down mr-1"></i>{trans.load_more ?? 'Charger plus'}</>
                                    }
                                </button>
                            </div>
                        )}
                    </>
                )}
            </div>

            {activeTab === 'unread' && unread.length > 0 && (
                <div className="card-footer">
                    <button className="btn btn-info btn-sm" onClick={handleReadAll}>
                        <i className="fas fa-check-double mr-1"></i>
                        {trans.all_read ?? 'Tout marquer comme lu'}
                    </button>
                </div>
            )}
        </div>
    );
}
