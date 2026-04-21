import React, { useState, useEffect, useRef } from 'react';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function assignSides(messages) {
    // Reproduit la logique Blade : alternance de côté à chaque changement d'utilisateur
    let i = 1;
    let lastId = null;

    return messages.map((msg) => {
        const uid = msg.user_id ?? 0;
        if (uid !== lastId) {
            i++;
            lastId = uid;
        }
        return { ...msg, side: i % 2 === 1 ? 'right' : 'left' };
    });
}

export default function ChatLive({ endpoints, trans }) {
    const t = (key, fallback) => trans?.[key] ?? fallback;

    const [messages, setMessages] = useState([]);
    const [label, setLabel]       = useState('');
    const [error, setError]       = useState('');
    const [loading, setLoading]   = useState(false);
    const messagesEndRef           = useRef(null);

    useEffect(() => {
        fetchMessages();
    }, []);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    async function fetchMessages() {
        try {
            const res = await fetch(endpoints.list, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
            });
            if (!res.ok) throw new Error(res.statusText);
            const data = await res.json();
            setMessages(assignSides(data));
        } catch {
            // silently ignore fetch errors on init
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        if (!label.trim()) {
            setError(t('required', 'Ce champ est requis.'));
            return;
        }
        setError('');
        setLoading(true);

        try {
            const res = await fetch(endpoints.store, {
                method:  'POST',
                headers: {
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({
                    label:        label.trim(),
                    related_id:   endpoints.relatedId,
                    related_type: endpoints.relatedType,
                }),
            });

            if (!res.ok) {
                const body = await res.json().catch(() => ({}));
                setError(body?.message ?? 'Erreur lors de l\'envoi.');
                return;
            }

            const newMessage = await res.json();
            setMessages((prev) => assignSides([...prev, newMessage]));
            setLabel('');
        } catch {
            setError('Erreur réseau.');
        } finally {
            setLoading(false);
        }
    }

    const withSides = assignSides(messages);

    return (
        <div className="card card-secondary">
            <div className="card-header">
                <h3 className="card-title">Direct Chat</h3>
            </div>

            <div className="card-body p-0">
                <div className="direct-chat-messages" style={{ height: '250px', overflowY: 'auto', padding: '10px' }}>
                    {withSides.length === 0 ? (
                        <div>
                            <div className="direct-chat-infos clearfix">
                                <span className="direct-chat-name float-left">system</span>
                                <span className="direct-chat-timestamp float-right">-</span>
                            </div>
                            <div className="direct-chat-text">
                                {t('no_data_trans_key', 'Aucun message.')}
                            </div>
                        </div>
                    ) : (
                        withSides.map((msg) => (
                            <div key={msg.id} className={`direct-chat-msg ${msg.side}`}>
                                <div className="direct-chat-infos clearfix">
                                    <span className={`direct-chat-name float-${msg.side}`}>
                                        {msg.user_name}
                                    </span>
                                    <span className={`direct-chat-timestamp float-${msg.side === 'left' ? 'right' : 'left'}`}>
                                        {msg.created_at}
                                    </span>
                                </div>
                                <div className="direct-chat-text">
                                    {msg.label}
                                </div>
                            </div>
                        ))
                    )}
                    <div ref={messagesEndRef} />
                </div>
            </div>

            <div className="card-footer">
                <form onSubmit={handleSubmit}>
                    <div className="input-group">
                        <input
                            type="text"
                            value={label}
                            onChange={(e) => setLabel(e.target.value)}
                            placeholder="Chat ..."
                            className="form-control"
                            disabled={loading}
                        />
                        <span className="input-group-append">
                            <button type="submit" className="btn btn-danger" disabled={loading}>
                                {t('add_trans_key', 'Ajouter')}
                            </button>
                        </span>
                    </div>
                    {error && <span className="text-danger">{error}</span>}
                </form>
            </div>
        </div>
    );
}
