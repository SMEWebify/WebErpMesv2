import React from 'react';
import { createPortal } from 'react-dom';
import { csrfToken } from '../../lib/http';

/**
 * Outils partagés par les onglets Adresses et Contacts de la fiche société
 * (CompanyAddresses, CompanyContacts) : requête POST, badges de type de
 * document par défaut, fenêtre modale.
 */
export async function apiFetch(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept':       'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw json;
    return json;
}

// ---------------------------------------------------------------------------
// Document-type badge config
// ---------------------------------------------------------------------------

export const DOC_TYPES = [
    { key: 'quote',    label: 'DEV', color: '#007bff' },
    { key: 'order',    label: 'CMD', color: '#28a745' },
    { key: 'delivery', label: 'BL',  color: '#fd7e14' },
    { key: 'invoice',  label: 'FAC', color: '#dc3545' },
];

export function DocBadge({ type, active }) {
    const cfg = DOC_TYPES.find(d => d.key === type);
    if (!cfg) return null;
    return (
        <span style={{
            display: 'inline-block',
            padding: '1px 5px',
            borderRadius: '3px',
            fontSize: '0.7rem',
            fontWeight: 700,
            marginRight: '2px',
            backgroundColor: active ? cfg.color : '#e9ecef',
            color: active ? '#fff' : '#adb5bd',
            border: `1px solid ${active ? cfg.color : '#dee2e6'}`,
        }}>
            {cfg.label}
        </span>
    );
}

// ---------------------------------------------------------------------------
// Modal overlay — rendered via portal to avoid AdminLTE overflow clipping
// ---------------------------------------------------------------------------

export function Modal({ title, icon, onClose, children }) {
    return createPortal(
        <div
            style={{
                position: 'fixed', top: 0, left: 0,
                width: '100vw', height: '100vh',
                zIndex: 9999,
                backgroundColor: 'rgba(0,0,0,0.55)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                padding: '1rem',
            }}
            onClick={e => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div style={{
                width: '760px',
                maxWidth: '100%',
                maxHeight: '90vh',
                display: 'flex',
                flexDirection: 'column',
                borderRadius: '6px',
                overflow: 'hidden',
                boxShadow: '0 10px 40px rgba(0,0,0,0.4)',
            }}>
                {/* Header */}
                <div style={{
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    padding: '0.85rem 1.25rem',
                    backgroundColor: '#17a2b8',
                    flexShrink: 0,
                }}>
                    <h5 style={{ margin: 0, color: '#fff', fontWeight: 600, fontSize: '1rem' }}>
                        <i className={`fas ${icon} mr-2`} />{title}
                    </h5>
                    <button
                        type="button"
                        onClick={onClose}
                        style={{
                            background: 'none', border: 'none', color: '#fff',
                            fontSize: '1.4rem', lineHeight: 1, cursor: 'pointer',
                            opacity: 0.8, padding: '0 4px',
                        }}
                        onMouseOver={e => e.currentTarget.style.opacity = 1}
                        onMouseOut={e => e.currentTarget.style.opacity = 0.8}
                    >
                        &times;
                    </button>
                </div>
                {/* Body */}
                <div style={{
                    backgroundColor: '#fff',
                    padding: '1.5rem',
                    overflowY: 'auto',
                    flex: 1,
                }}>
                    {children}
                </div>
            </div>
        </div>,
        document.body
    );
}
