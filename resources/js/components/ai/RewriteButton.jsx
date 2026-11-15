import React, { useState } from 'react';
import { rewriteText, proposalState, fillMax } from '../../lib/aiRewrite.js';

// « Réécrire sans fautes » sous un champ React : la proposition s'affiche à
// côté du texte saisi, l'utilisateur choisit de la reprendre. Rien n'est enregistré.
export default function RewriteButton({ endpoint, value, onAccept, maxLength, disabled, trans = {} }) {
    const [status, setStatus]     = useState('idle'); // idle | loading | done | error
    const [proposal, setProposal] = useState('');
    const [error, setError]       = useState(null);

    if (!endpoint) return null;

    const run = async () => {
        setStatus('loading');
        setError(null);
        try {
            setProposal(await rewriteText(endpoint, value));
            setStatus('done');
        } catch (e) {
            setError(e?.message ?? trans.failed);
            setStatus('error');
        }
    };

    const state = status === 'done' ? proposalState(value, proposal, maxLength) : null;

    return (
        <div className="mt-1">
            <button type="button" className="btn btn-sm btn-outline-secondary py-0"
                disabled={disabled || status === 'loading' || !String(value ?? '').trim()} onClick={run}>
                {status === 'loading'
                    ? <><span className="spinner-border spinner-border-sm me-1" />{trans.rewriting}</>
                    : <><i className="fas fa-spell-check me-1" />{trans.rewrite ?? 'Réécrire sans fautes'}</>}
            </button>
            {status === 'error' && <div className="small text-danger mt-1">{error}</div>}
            {state === 'unchanged' && <div className="small text-success mt-1"><i className="fas fa-check me-1" />{trans.unchanged}</div>}
            {state && state !== 'unchanged' && (
                <div className="border rounded p-2 mt-1 bg-body-tertiary">
                    <div className="small fw-bold mb-1">{trans.proposal}</div>
                    <div className="small mb-2" style={{ whiteSpace: 'pre-wrap' }}>{proposal}</div>
                    {state === 'too_long' && <div className="small text-danger mb-2">{fillMax(trans.too_long, maxLength)}</div>}
                    <button type="button" className="btn btn-sm btn-primary me-1" disabled={state === 'too_long'}
                        onClick={() => { onAccept(proposal); setStatus('idle'); }}>
                        {trans.replace ?? 'Remplacer'}
                    </button>
                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setStatus('idle')}>
                        {trans.ignore ?? 'Ignorer'}
                    </button>
                </div>
            )}
        </div>
    );
}
