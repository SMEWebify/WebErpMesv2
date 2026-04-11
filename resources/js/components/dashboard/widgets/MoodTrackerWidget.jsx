import React, { useState, useEffect, useCallback } from 'react';
import WidgetCard from '../WidgetCard.jsx';

const MOODS = [
    { key: 'happy',   emoji: '😊', color: '#28a745', labelKey: 'mood_happy'   },
    { key: 'neutral', emoji: '😐', color: '#17a2b8', labelKey: 'mood_neutral' },
    { key: 'sad',     emoji: '😢', color: '#ffc107', labelKey: 'mood_sad'     },
];

// ─── Avatar initiales ─────────────────────────────────────────────────────────

function Avatar({ name, size = 28 }) {
    const initials = name
        .split(' ')
        .map(w => w[0] ?? '')
        .slice(0, 2)
        .join('')
        .toUpperCase();

    // couleur déterministe basée sur le nom
    let hash = 0;
    for (let i = 0; i < name.length; i++) hash = (hash * 31 + name.charCodeAt(i)) >>> 0;
    const hue = hash % 360;
    const bg  = `hsl(${hue}, 55%, 48%)`;

    return (
        <div
            title={name}
            style={{
                width: size, height: size, borderRadius: '50%',
                background: bg, color: '#fff',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: size * 0.38, fontWeight: 700, flexShrink: 0,
                userSelect: 'none',
            }}
        >
            {initials}
        </div>
    );
}

// ─── MoodTrackerWidget ────────────────────────────────────────────────────────

/**
 * MoodTrackerWidget — Niko-Niko atelier
 *
 * Props:
 *   endpoint  string  — GET/POST /kpi/mood
 *   trans     object  — clés i18n passées par le dashboard
 *   editMode  bool
 *   onRemove  fn
 */
export default function MoodTrackerWidget({
    endpoint,
    trans    = {},
    editMode = false,
    onRemove,
}) {
    const [data,    setData]    = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving,  setSaving]  = useState(false);
    const [error,   setError]   = useState(null);

    const csrf = () =>
        document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ── Chargement initial ────────────────────────────────────────────────────

    const load = useCallback(() => {
        if (!endpoint) return;
        setLoading(true);
        fetch(endpoint, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            credentials: 'same-origin',
        })
            .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
            .then(d  => { setData(d); setLoading(false); })
            .catch(e => { setError(e.message); setLoading(false); });
    }, [endpoint]);

    useEffect(() => { load(); }, [load]);

    // ── Vote ──────────────────────────────────────────────────────────────────

    const selectMood = (mood) => {
        if (saving || !endpoint) return;

        // optimistic update
        setSaving(true);
        setData(prev => prev ? {
            ...prev,
            myMood: mood,
            team: prev.team.some(m => m.user_id === prev.myUserId)
                ? prev.team.map(m => m.user_id === prev.myUserId ? { ...m, mood } : m)
                : prev.team,
        } : prev);

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ mood }),
        })
            .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.json(); })
            .then(d  => { setData(d); setSaving(false); })
            .catch(e => { setError(e.message); setSaving(false); });
    };

    // ── Rendu ─────────────────────────────────────────────────────────────────

    const t = (key, fallback) => trans[key] ?? fallback;

    return (
        <WidgetCard
            title={t('mood_title', 'Niko Niko')}
            icon="fa-smile"
            theme="success"
            loading={loading}
            error={error}
            editMode={editMode}
            onRemove={onRemove}
        >
            {data && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>

                    {/* ── Mon humeur ─────────────────────────────────────── */}
                    <div>
                        <div style={{ fontSize: '0.72rem', color: '#6c757d', marginBottom: '0.35rem', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                            {t('my_mood', "Mon humeur aujourd'hui")}
                        </div>
                        <div className="d-flex" style={{ gap: '0.4rem' }}>
                            {MOODS.map(m => {
                                const active = data.myMood === m.key;
                                return (
                                    <button
                                        key={m.key}
                                        type="button"
                                        onClick={() => selectMood(m.key)}
                                        disabled={saving}
                                        style={{
                                            flex: 1,
                                            padding: '0.45rem 0.25rem',
                                            border:     `2px solid ${active ? m.color : '#dee2e6'}`,
                                            borderRadius: 10,
                                            background:   active ? m.color + '22' : '#fff',
                                            cursor:  saving ? 'wait' : 'pointer',
                                            transition: 'all 0.15s ease',
                                            textAlign: 'center',
                                            outline: 'none',
                                            boxShadow: active ? `0 0 0 3px ${m.color}33` : 'none',
                                        }}
                                        title={t(m.labelKey, m.key)}
                                    >
                                        <div style={{ fontSize: '1.6rem', lineHeight: 1 }}>{m.emoji}</div>
                                        <div style={{
                                            fontSize: '0.63rem',
                                            color: active ? m.color : '#6c757d',
                                            marginTop: 3,
                                            fontWeight: active ? 700 : 400,
                                        }}>
                                            {t(m.labelKey, m.key)}
                                        </div>
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* ── Résumé équipe ──────────────────────────────────── */}
                    {data.team && data.team.length > 0 ? (
                        <div>
                            {/* Titre + compteurs */}
                            <div className="d-flex align-items-center" style={{ marginBottom: '0.4rem', gap: '0.5rem' }}>
                                <span style={{ fontSize: '0.72rem', color: '#6c757d', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                                    {t('team_mood', 'Équipe')} ({data.team.length})
                                </span>
                                <div className="d-flex" style={{ gap: '0.3rem', marginLeft: 'auto' }}>
                                    {MOODS.map(m => (
                                        <span
                                            key={m.key}
                                            style={{
                                                padding: '1px 7px',
                                                borderRadius: 20,
                                                background: m.color + '18',
                                                border: `1px solid ${m.color}44`,
                                                fontSize: '0.75rem',
                                                fontWeight: 600,
                                                color: m.color,
                                                whiteSpace: 'nowrap',
                                            }}
                                        >
                                            {m.emoji} {data.counts?.[m.key] ?? 0}
                                        </span>
                                    ))}
                                </div>
                            </div>

                            {/* Avatars avec emoji */}
                            <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem' }}>
                                {data.team.map(member => {
                                    const mood = MOODS.find(m => m.key === member.mood);
                                    return (
                                        <div
                                            key={member.user_id}
                                            title={`${member.name} — ${t(mood?.labelKey, member.mood)}`}
                                            style={{ display: 'flex', alignItems: 'center', gap: '0.2rem' }}
                                        >
                                            <Avatar name={member.name} size={26} />
                                            <span style={{ fontSize: '0.9rem', lineHeight: 1 }}>
                                                {mood?.emoji ?? '?'}
                                            </span>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    ) : (
                        <p className="text-muted text-center small mb-0">
                            <i className="fas fa-users mr-1" />
                            {t('no_mood_yet', "Aucun vote aujourd'hui")}
                        </p>
                    )}
                </div>
            )}
        </WidgetCard>
    );
}
