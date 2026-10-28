import React, { useEffect, useState } from 'react';
import { MissingOperationsTable } from '../../lib/requiredOperations.jsx';

/*
 * Avertissement du devis (non bloquant) : lignes article dont la gamme ne contient
 * pas les opérations exigées par la catégorie d'affaire, et passe-droits déjà
 * accordés à la conversion. Rien n'est affiché quand tout est en ordre.
 *
 * `refreshKey` change quand les lignes ou leur gamme peuvent avoir changé : la
 * liste est alors relue (avec un court délai, pour ne pas relire à chaque frappe).
 */
export default function RequiredOperationsBanner({ endpoint, refreshKey }) {
    const [data, setData] = useState(null);

    useEffect(() => {
        if (!endpoint) return undefined;
        let cancelled = false;
        const timer = setTimeout(() => {
            fetch(endpoint, { headers: { Accept: 'application/json' } })
                .then(r => (r.ok ? r.json() : null))
                .then(json => { if (!cancelled) setData(json); })
                .catch(() => {});
        }, 300);
        return () => { cancelled = true; clearTimeout(timer); };
    }, [endpoint, refreshKey]);

    if (!data) return null;
    const missing   = data.missing ?? [];
    const overrides = data.overrides ?? [];
    if (missing.length === 0 && overrides.length === 0) return null;

    const texts = data.texts ?? {};

    return (
        <div className="mb-3">
            {missing.length > 0 && (
                <div className="alert alert-warning mb-2" role="alert">
                    <div className="fw-bold mb-1">
                        <i className="fas fa-exclamation-triangle me-1" />{texts.title}
                    </div>
                    <p className="small mb-2">{texts.intro}</p>
                    <div className="table-responsive bg-body rounded">
                        <MissingOperationsTable missing={missing} texts={texts} />
                    </div>
                </div>
            )}
            {overrides.length > 0 && (
                <div className="callout callout-danger small mb-0 py-2">
                    <div className="fw-bold mb-1"><i className="fas fa-user-shield me-1" />{texts.overrides}</div>
                    <ul className="mb-0 ps-3">
                        {overrides.map(o => (
                            <li key={o.id}>
                                {o.order_url ? <a href={o.order_url} className="me-1">{o.order_code}</a> : null}
                                {(texts.override_entry ?? ':date — :user : :reason')
                                    .replace(':date', o.created_at ?? '')
                                    .replace(':user', o.user ?? '—')
                                    .replace(':reason', o.reason ?? '')}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
