import React, { useState, useRef, useEffect, useCallback } from 'react';

// ─── Utils ─────────────────────────────────────────────────────────────────────

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function sendMessage(message, history) {
    const res = await fetch('/ai/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ message, history }),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.error || `HTTP ${res.status}`);
    return json;
}

// ─── Markdown basique ──────────────────────────────────────────────────────────
// Transforme **gras**, *italique*, [texte](url), listes en HTML simple.
function renderMarkdown(text) {
    if (!text) return '';
    return text
        // URLs brutes → liens cliquables
        .replace(/\bhttps?:\/\/[^\s)\]]+/g, url => `<a href="${url}" target="_blank" rel="noopener" style="color:#3b82f6;text-decoration:underline">${url}</a>`)
        // [texte](url)
        .replace(/\[([^\]]+)\]\((https?:\/\/[^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener" style="color:#3b82f6;text-decoration:underline">$1</a>')
        // **gras**
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        // *italique*
        .replace(/\*([^*]+)\*/g, '<em>$1</em>')
        // - liste
        .replace(/^- (.+)$/gm, '<li style="margin-left:12px;list-style:disc">$1</li>')
        // sauts de ligne
        .replace(/\n/g, '<br>');
}

// ─── Composant Message ─────────────────────────────────────────────────────────

function Message({ role, content, isLoading }) {
    const isUser = role === 'user';

    const bubbleStyle = {
        maxWidth: '85%',
        padding: '8px 12px',
        borderRadius: isUser ? '16px 16px 4px 16px' : '16px 16px 16px 4px',
        background: isUser ? '#2563eb' : '#f3f4f6',
        color: isUser ? '#ffffff' : '#111827',
        fontSize: '13px',
        lineHeight: '1.5',
        alignSelf: isUser ? 'flex-end' : 'flex-start',
        wordBreak: 'break-word',
    };

    if (isLoading) {
        return (
            <div style={{ display: 'flex', justifyContent: 'flex-start' }}>
                <div style={bubbleStyle}>
                    <span style={{ display: 'inline-flex', gap: '3px', alignItems: 'center' }}>
                        {[0, 1, 2].map(i => (
                            <span key={i} style={{
                                width: 6, height: 6, borderRadius: '50%',
                                background: '#9ca3af',
                                display: 'inline-block',
                                animation: `ai-bounce 1.2s ease-in-out ${i * 0.2}s infinite`,
                            }} />
                        ))}
                    </span>
                </div>
            </div>
        );
    }

    return (
        <div style={{ display: 'flex', justifyContent: isUser ? 'flex-end' : 'flex-start' }}>
            <div
                style={bubbleStyle}
                dangerouslySetInnerHTML={isUser ? undefined : { __html: renderMarkdown(content) }}
            >
                {isUser ? content : undefined}
            </div>
        </div>
    );
}

// ─── Traductions ───────────────────────────────────────────────────────────────

const TRANS = {
    fr: {
        title:        'Assistant ERP',
        subtitle:     'Commandes · Stock · Factures · Devis',
        welcome:      'Bonjour ! Je suis votre assistant ERP. Posez-moi une question ou choisissez une catégorie ci-dessous.',
        reset:        'Conversation réinitialisée. Comment puis-je vous aider ?',
        placeholder:  'Posez votre question…',
        micTitle:     'Parler',
        micStop:      "Arrêter l'écoute",
        newChat:      'Nouvelle conversation',
        noMic:        "La reconnaissance vocale n'est pas supportée par votre navigateur.",
        journalBtn:   'Journal de la veille',
        journalMsg:   'Génère le journal de la veille',
    },
    en: {
        title:        'ERP Assistant',
        subtitle:     'Orders · Stock · Invoices · Quotes',
        welcome:      'Hello! I am your ERP assistant. Ask me a question or choose a category below.',
        reset:        'Conversation reset. How can I help you?',
        placeholder:  'Ask your question…',
        micTitle:     'Speak',
        micStop:      'Stop listening',
        newChat:      'New conversation',
        noMic:        'Speech recognition is not supported by your browser.',
        journalBtn:   "Yesterday's journal",
        journalMsg:   'Generate the daily journal',
    },
};

function t(locale, key) {
    return (TRANS[locale] ?? TRANS.fr)[key] ?? TRANS.fr[key];
}

// ─── Catalogue de suggestions à 2 niveaux ─────────────────────────────────────

const SUGGESTION_CATEGORIES = {
    fr: [
        {
            id: 'journal',
            label: '📰 Journal',
            suggestions: ['Journal de la veille', 'Journal du jour', 'Journal du 2025-01-15'],
        },
        {
            id: 'commande',
            label: '📦 Commande',
            suggestions: ['Commandes en cours', 'Dernières commandes', 'Commandes en retard', 'Commandes terminées ce mois'],
        },
        {
            id: 'devis',
            label: '📄 Devis',
            suggestions: ['Devis en cours', 'Devis envoyés en attente', 'Devis acceptés', 'Devis expirés'],
        },
        {
            id: 'facture',
            label: '💶 Facture',
            suggestions: ['Factures impayées', 'Factures en retard', 'Factures envoyées ce mois'],
        },
        {
            id: 'stock',
            label: '🏭 Stock',
            suggestions: ['Stock tôle inox', 'Stock aluminium', 'Stock acier', 'Produits en rupture de stock'],
        },
    ],
    en: [
        {
            id: 'journal',
            label: '📰 Journal',
            suggestions: ["Yesterday's journal", "Today's journal", 'Journal for 2025-01-15'],
        },
        {
            id: 'order',
            label: '📦 Orders',
            suggestions: ['Current orders', 'Latest orders', 'Late orders', 'Orders completed this month'],
        },
        {
            id: 'quote',
            label: '📄 Quotes',
            suggestions: ['Ongoing quotes', 'Sent quotes pending', 'Accepted quotes', 'Expired quotes'],
        },
        {
            id: 'invoice',
            label: '💶 Invoices',
            suggestions: ['Unpaid invoices', 'Overdue invoices', 'Invoices sent this month'],
        },
        {
            id: 'stock',
            label: '🏭 Stock',
            suggestions: ['Stainless steel sheet stock', 'Aluminium stock', 'Steel stock', 'Out of stock products'],
        },
    ],
};

// ─── Composant SuggestionPanel ─────────────────────────────────────────────────

function SuggestionPanel({ onSelect, locale }) {
    const [activeCategory, setActiveCategory] = useState(null);

    const categories = SUGGESTION_CATEGORIES[locale] ?? SUGGESTION_CATEGORIES.fr;
    const cat = categories.find(c => c.id === activeCategory);

    const btnBase = {
        padding: '5px 12px',
        borderRadius: 20,
        border: '1px solid #d1d5db',
        background: 'white',
        fontSize: 12,
        cursor: 'pointer',
        whiteSpace: 'nowrap',
        transition: 'all 0.15s',
    };

    return (
        <div style={{ padding: '8px 12px 4px', background: '#fafafa', borderTop: '1px solid #f0f0f0' }}>
            {/* Niveau 1 — catégories */}
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, marginBottom: cat ? 6 : 0 }}>
                {categories.map(c => (
                    <button
                        key={c.id}
                        onClick={() => setActiveCategory(activeCategory === c.id ? null : c.id)}
                        style={{
                            ...btnBase,
                            background: activeCategory === c.id ? '#2563eb' : 'white',
                            color:      activeCategory === c.id ? '#fff'     : '#374151',
                            border:     activeCategory === c.id ? '1px solid #2563eb' : '1px solid #d1d5db',
                            fontWeight: activeCategory === c.id ? 600 : 400,
                        }}
                    >{c.label}</button>
                ))}
            </div>

            {/* Niveau 2 — suggestions de la catégorie active */}
            {cat && (
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: 5 }}>
                    {cat.suggestions.map(s => (
                        <button
                            key={s}
                            onClick={() => { setActiveCategory(null); onSelect(s); }}
                            style={{
                                ...btnBase,
                                color: '#2563eb',
                                border: '1px solid #bfdbfe',
                                background: '#eff6ff',
                                fontSize: 11,
                            }}
                        >{s}</button>
                    ))}
                </div>
            )}
        </div>
    );
}

// ─── Composant principal ───────────────────────────────────────────────────────

export default function ChatWidget({ locale = 'fr' }) {
    const [open, setOpen] = useState(false);
    const [input, setInput] = useState('');
    const [messages, setMessages] = useState([
        { role: 'assistant', content: t(locale, 'welcome') },
    ]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [isListening, setIsListening] = useState(false);

    const messagesEndRef = useRef(null);
    const inputRef       = useRef(null);
    const recognitionRef = useRef(null);

    // Scroll en bas à chaque nouveau message
    useEffect(() => {
        if (open) {
            messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages, loading, open]);

    // Focus input à l'ouverture
    useEffect(() => {
        if (open) {
            setTimeout(() => inputRef.current?.focus(), 100);
        }
    }, [open]);

    // Construit l'historique à envoyer (sans le message system initial)
    const buildHistory = useCallback(() => {
        return messages
            .slice(1) // exclut le message de bienvenue
            .map(m => ({ role: m.role, content: m.content }));
    }, [messages]);

    const submit = useCallback(async (text) => {
        const trimmed = (text || input).trim();
        if (!trimmed || loading) return;

        setInput('');
        setError(null);
        setMessages(prev => [...prev, { role: 'user', content: trimmed }]);
        setLoading(true);

        try {
            const history = buildHistory();
            // Ajoute le message courant à l'historique avant envoi
            history.push({ role: 'user', content: trimmed });

            const data = await sendMessage(trimmed, history.slice(0, -1));

            setMessages(prev => [...prev, { role: 'assistant', content: data.message }]);
        } catch (err) {
            setError(err.message || 'Une erreur est survenue.');
        } finally {
            setLoading(false);
        }
    }, [input, loading, buildHistory]);

    const handleKeyDown = useCallback((e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            submit();
        }
    }, [submit]);

    // Reconnaissance vocale (Web Speech API)
    const toggleListening = useCallback(() => {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            alert(t(locale, 'noMic'));
            return;
        }

        if (isListening) {
            recognitionRef.current?.stop();
            setIsListening(false);
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        const recognition = new SpeechRecognition();
        recognition.lang = locale === 'en' ? 'en-GB' : `${locale}-${locale.toUpperCase()}`;
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        recognition.onstart  = () => setIsListening(true);
        recognition.onend    = () => setIsListening(false);
        recognition.onerror  = () => setIsListening(false);
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            setInput(transcript);
            // Auto-submit après reconnaissance vocale
            setTimeout(() => submit(transcript), 100);
        };

        recognitionRef.current = recognition;
        recognition.start();
    }, [isListening, submit]);

    const clearChat = useCallback(() => {
        setMessages([{ role: 'assistant', content: t(locale, 'reset') }]);
        setError(null);
    }, [locale]);

    // ─── Render ───────────────────────────────────────────────────────────────

    return (
        <>
            {/* Animation CSS pour les points de chargement */}
            <style>{`
                @keyframes ai-bounce {
                    0%, 60%, 100% { transform: translateY(0); }
                    30% { transform: translateY(-6px); }
                }
                #ai-chat-widget * { box-sizing: border-box; }
                #ai-chat-widget textarea:focus { outline: none; }
            `}</style>

            <div id="ai-chat-widget" style={{ position: 'fixed', bottom: 24, right: 24, zIndex: 9999, fontFamily: 'system-ui, sans-serif' }}>

                {/* Fenêtre de chat */}
                {open && (
                    <div style={{
                        position: 'absolute',
                        bottom: 64,
                        right: 0,
                        width: 360,
                        maxHeight: 520,
                        background: '#ffffff',
                        borderRadius: 16,
                        boxShadow: '0 20px 60px rgba(0,0,0,0.18)',
                        display: 'flex',
                        flexDirection: 'column',
                        overflow: 'hidden',
                        border: '1px solid #e5e7eb',
                    }}>
                        {/* Header */}
                        <div style={{
                            background: 'linear-gradient(135deg, #1e40af 0%, #2563eb 100%)',
                            padding: '12px 16px',
                            display: 'flex',
                            alignItems: 'center',
                            gap: 10,
                        }}>
                            <div style={{
                                width: 32, height: 32, borderRadius: '50%',
                                background: 'rgba(255,255,255,0.2)',
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                                fontSize: 16,
                            }}>🤖</div>
                            <div style={{ flex: 1 }}>
                                <div style={{ color: '#fff', fontWeight: 600, fontSize: 14 }}>{t(locale, 'title')}</div>
                                <div style={{ color: 'rgba(255,255,255,0.7)', fontSize: 11 }}>{t(locale, 'subtitle')}</div>
                            </div>
                            <button
                                onClick={() => submit(t(locale, 'journalMsg'))}
                                title={t(locale, 'journalBtn')}
                                disabled={loading}
                                style={{ background: 'none', border: 'none', color: 'rgba(255,255,255,0.7)', cursor: loading ? 'not-allowed' : 'pointer', fontSize: 15, padding: 4 }}
                            >📰</button>
                            <button
                                onClick={clearChat}
                                title={t(locale, 'newChat')}
                                style={{ background: 'none', border: 'none', color: 'rgba(255,255,255,0.7)', cursor: 'pointer', fontSize: 16, padding: 4 }}
                            >↺</button>
                            <button
                                onClick={() => setOpen(false)}
                                style={{ background: 'none', border: 'none', color: 'rgba(255,255,255,0.7)', cursor: 'pointer', fontSize: 18, padding: 4 }}
                            >✕</button>
                        </div>

                        {/* Messages */}
                        <div style={{
                            flex: 1,
                            overflowY: 'auto',
                            padding: '12px 12px 4px',
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 8,
                            background: '#fafafa',
                        }}>
                            {messages.map((msg, idx) => (
                                <Message key={idx} role={msg.role} content={msg.content} />
                            ))}
                            {loading && <Message role="assistant" isLoading />}
                            {error && (
                                <div style={{
                                    background: '#fef2f2',
                                    border: '1px solid #fca5a5',
                                    borderRadius: 8,
                                    padding: '8px 12px',
                                    fontSize: 12,
                                    color: '#dc2626',
                                }}>
                                    {error}
                                </div>
                            )}
                            <div ref={messagesEndRef} />
                        </div>

                        {/* Suggestions à 2 niveaux — toujours visible */}
                        {!loading && (
                            <SuggestionPanel onSelect={(s) => submit(s)} locale={locale} />
                        )}

                        {/* Input */}
                        <div style={{
                            padding: '10px 12px',
                            borderTop: '1px solid #e5e7eb',
                            display: 'flex',
                            gap: 8,
                            alignItems: 'flex-end',
                            background: '#fff',
                        }}>
                            <textarea
                                ref={inputRef}
                                value={input}
                                onChange={e => setInput(e.target.value)}
                                onKeyDown={handleKeyDown}
                                placeholder={t(locale, 'placeholder')}
                                rows={1}
                                disabled={loading}
                                style={{
                                    flex: 1,
                                    resize: 'none',
                                    border: '1px solid #d1d5db',
                                    borderRadius: 8,
                                    padding: '7px 10px',
                                    fontSize: 13,
                                    fontFamily: 'inherit',
                                    lineHeight: 1.4,
                                    maxHeight: 80,
                                    background: loading ? '#f9fafb' : '#fff',
                                }}
                                onInput={e => {
                                    e.target.style.height = 'auto';
                                    e.target.style.height = Math.min(e.target.scrollHeight, 80) + 'px';
                                }}
                            />
                            {/* Bouton micro */}
                            <button
                                onClick={toggleListening}
                                title={isListening ? t(locale, 'micStop') : t(locale, 'micTitle')}
                                style={{
                                    width: 34, height: 34,
                                    borderRadius: '50%',
                                    border: 'none',
                                    background: isListening ? '#dc2626' : '#f3f4f6',
                                    color: isListening ? '#fff' : '#6b7280',
                                    cursor: 'pointer',
                                    fontSize: 16,
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    flexShrink: 0,
                                    animation: isListening ? 'ai-bounce 1s ease-in-out infinite' : 'none',
                                }}
                            >🎤</button>
                            {/* Bouton envoyer */}
                            <button
                                onClick={() => submit()}
                                disabled={loading || !input.trim()}
                                style={{
                                    width: 34, height: 34,
                                    borderRadius: '50%',
                                    border: 'none',
                                    background: loading || !input.trim() ? '#e5e7eb' : '#2563eb',
                                    color: loading || !input.trim() ? '#9ca3af' : '#fff',
                                    cursor: loading || !input.trim() ? 'not-allowed' : 'pointer',
                                    fontSize: 16,
                                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                                    flexShrink: 0,
                                }}
                            >↑</button>
                        </div>
                    </div>
                )}

                {/* Bouton flottant */}
                <button
                    onClick={() => setOpen(o => !o)}
                    title={t(locale, 'title')}
                    style={{
                        width: 52,
                        height: 52,
                        borderRadius: '50%',
                        border: 'none',
                        background: open
                            ? '#1e40af'
                            : 'linear-gradient(135deg, #1e40af 0%, #2563eb 100%)',
                        color: '#fff',
                        cursor: 'pointer',
                        fontSize: 22,
                        boxShadow: '0 4px 20px rgba(37,99,235,0.5)',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        transition: 'transform 0.2s',
                    }}
                    onMouseEnter={e => e.currentTarget.style.transform = 'scale(1.08)'}
                    onMouseLeave={e => e.currentTarget.style.transform = 'scale(1)'}
                >
                    {open ? '✕' : '🤖'}
                </button>
            </div>
        </>
    );
}
