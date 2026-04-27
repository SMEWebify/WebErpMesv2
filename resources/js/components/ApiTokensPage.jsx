import React, { useState } from 'react';

const DURATION_LABELS = {
    week:     '1 semaine',
    month:    '1 mois',
    infinite: 'Infini',
};

function formatDate(iso) {
    if (!iso) return <span className="badge badge-secondary">Infini</span>;
    const d = new Date(iso);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function formatLastUsed(iso) {
    if (!iso) return <span className="text-muted">—</span>;
    const d = new Date(iso);
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function isExpired(iso) {
    if (!iso) return false;
    return new Date(iso) < new Date();
}

export default function ApiTokensPage({ initialTokens, endpoints }) {
    const [tokens, setTokens] = useState(initialTokens);
    const [name, setName] = useState('');
    const [duration, setDuration] = useState('infinite');
    const [creating, setCreating] = useState(false);
    const [newToken, setNewToken] = useState(null);
    const [copied, setCopied] = useState(false);
    const [revoking, setRevoking] = useState(null);
    const [error, setError] = useState(null);

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    async function handleCreate(e) {
        e.preventDefault();
        setCreating(true);
        setError(null);
        try {
            const res = await fetch(endpoints.store, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ name, duration }),
            });
            if (!res.ok) {
                const data = await res.json();
                setError(data.message ?? 'Erreur lors de la création');
                return;
            }
            const data = await res.json();
            setNewToken(data.plain_token);
            setTokens(prev => [data.token, ...prev]);
            setName('');
            setDuration('infinite');
        } catch {
            setError('Erreur réseau');
        } finally {
            setCreating(false);
        }
    }

    async function handleRevoke(id) {
        if (!confirm('Révoquer ce token ? Les intégrations utilisant ce token seront immédiatement coupées.')) return;
        setRevoking(id);
        try {
            const res = await fetch(endpoints.destroy.replace(':id', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
            });
            if (res.ok) {
                setTokens(prev => prev.filter(t => t.id !== id));
            }
        } finally {
            setRevoking(null);
        }
    }

    function handleCopy() {
        navigator.clipboard.writeText(newToken).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    }

    return (
        <div>
            {/* New token revealed */}
            {newToken && (
                <div className="alert alert-success">
                    <strong><i className="fas fa-key mr-1" />Token créé — copiez-le maintenant, il ne sera plus affiché.</strong>
                    <div className="input-group mt-2">
                        <input
                            type="text"
                            className="form-control font-monospace"
                            readOnly
                            value={newToken}
                            style={{ fontFamily: 'monospace', fontSize: '0.85rem' }}
                        />
                        <div className="input-group-append">
                            <button className="btn btn-outline-success" onClick={handleCopy}>
                                <i className={`fas ${copied ? 'fa-check' : 'fa-copy'}`} />
                                {copied ? ' Copié !' : ' Copier'}
                            </button>
                        </div>
                    </div>
                    <div className="mt-2">
                        <button className="btn btn-sm btn-success" onClick={() => setNewToken(null)}>
                            <i className="fas fa-check mr-1" />J'ai copié le token
                        </button>
                        <small className="text-muted ml-2">Ce token ne sera plus jamais affiché.</small>
                    </div>
                </div>
            )}

            <div className="row">
                {/* Create form */}
                <div className="col-md-4">
                    <div className="card card-primary card-outline">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-plus-circle mr-1" />Nouveau token</h3>
                        </div>
                        <div className="card-body">
                            <form onSubmit={handleCreate}>
                                <div className="form-group">
                                    <label>Nom <span className="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        placeholder="Ex: ERP Sage X3, Script export..."
                                        value={name}
                                        onChange={e => setName(e.target.value)}
                                        required
                                        maxLength={100}
                                    />
                                    <small className="text-muted">Identifie l'intégration qui utilise ce token.</small>
                                </div>
                                <div className="form-group">
                                    <label>Durée de validité</label>
                                    <select
                                        className="form-control"
                                        value={duration}
                                        onChange={e => setDuration(e.target.value)}
                                    >
                                        <option value="week">1 semaine</option>
                                        <option value="month">1 mois</option>
                                        <option value="infinite">Infini</option>
                                    </select>
                                </div>
                                {error && <div className="alert alert-danger py-2">{error}</div>}
                                <button type="submit" className="btn btn-primary btn-block" disabled={creating}>
                                    {creating
                                        ? <><i className="fas fa-spinner fa-spin mr-1" />Création...</>
                                        : <><i className="fas fa-key mr-1" />Générer le token</>
                                    }
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="card card-secondary card-outline">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-info-circle mr-1" />Utilisation</h3>
                        </div>
                        <div className="card-body" style={{ fontSize: '0.875rem' }}>
                            <p>Ajoutez le token dans le header HTTP de vos requêtes :</p>
                            <code style={{ display: 'block', background: '#f4f4f4', padding: '8px', borderRadius: '4px', fontSize: '0.8rem' }}>
                                Authorization: Bearer {'{'}{'{'}token{'}'}{'}'}
                            </code>
                            <hr />
                            <p className="mb-1"><strong>Endpoints disponibles :</strong></p>
                            <ul className="mb-0 pl-3">
                                <li><code>GET /api/clients</code></li>
                                <li><code>POST /api/quote</code></li>
                                <li><code>PUT /api/quote/{'{'}{'{'}id{'}'}{'}'}  </code></li>
                            </ul>
                        </div>
                    </div>
                </div>

                {/* Token list */}
                <div className="col-md-8">
                    <div className="card">
                        <div className="card-header">
                            <h3 className="card-title"><i className="fas fa-list mr-1" />Tokens actifs ({tokens.length})</h3>
                        </div>
                        <div className="card-body p-0">
                            {tokens.length === 0 ? (
                                <div className="text-center text-muted py-4">
                                    <i className="fas fa-key fa-2x mb-2" style={{ display: 'block' }} />
                                    Aucun token créé
                                </div>
                            ) : (
                                <table className="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Créé le</th>
                                            <th>Expire le</th>
                                            <th>Dernière utilisation</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {tokens.map(token => (
                                            <tr key={token.id} className={isExpired(token.expires_at) ? 'table-warning' : ''}>
                                                <td>
                                                    <strong>{token.name}</strong>
                                                    {token.user && (
                                                        <small className="d-block text-muted">{token.user}</small>
                                                    )}
                                                    {isExpired(token.expires_at) && (
                                                        <span className="badge badge-warning ml-1">Expiré</span>
                                                    )}
                                                </td>
                                                <td>{formatDate(token.created_at)}</td>
                                                <td>{formatDate(token.expires_at)}</td>
                                                <td>{formatLastUsed(token.last_used_at)}</td>
                                                <td className="text-right">
                                                    <button
                                                        className="btn btn-sm btn-outline-danger"
                                                        onClick={() => handleRevoke(token.id)}
                                                        disabled={revoking === token.id}
                                                        title="Révoquer ce token"
                                                    >
                                                        {revoking === token.id
                                                            ? <i className="fas fa-spinner fa-spin" />
                                                            : <i className="fas fa-trash" />
                                                        }
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
