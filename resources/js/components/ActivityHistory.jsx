import React, { useEffect, useState } from 'react';

/**
 * Historique des modifications d'un enregistrement (journal activitylog), lu sur
 * `url` qui renvoie { data: [...] } au format de App\Support\ActivityHistory :
 * une entrée par modification, avec ancien et nouveau libellé de chaque champ.
 */
export function ActivityHistoryList({ entries, trans }) {
    const t = k => trans?.[k] ?? k;

    if (!entries.length) {
        return <p className="text-body-secondary small mb-0">{t('history_empty')}</p>;
    }

    return (
        <ul className="list-group list-group-flush small border rounded">
            {entries.map(entry => (
                <li key={entry.id} className="list-group-item">
                    <div className="d-flex justify-content-between">
                        <span><strong>{entry.causer}</strong> · {entry.event}</span>
                        <span className="text-body-secondary">{entry.at}</span>
                    </div>
                    {entry.changes.map(change => (
                        <div key={change.field} className="text-body-secondary">
                            {change.label} :{' '}
                            {change.old !== null && <><del>{change.old}</del>{' → '}</>}
                            <span className="text-body">{change.new ?? '—'}</span>
                        </div>
                    ))}
                </li>
            ))}
        </ul>
    );
}

export default function ActivityHistory({ url, trans }) {
    const [entries, setEntries] = useState(null);
    const [error, setError] = useState(false);
    const t = k => trans?.[k] ?? k;

    useEffect(() => {
        let cancelled = false;
        setEntries(null);
        setError(false);

        fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => (res.ok ? res.json() : Promise.reject(res)))
            .then(json => { if (!cancelled) setEntries(json.data ?? []); })
            .catch(() => { if (!cancelled) setError(true); });

        return () => { cancelled = true; };
    }, [url]);

    return (
        <div>
            <h6>{t('history')}</h6>
            {error && <p className="text-danger small mb-0">{t('history_error')}</p>}
            {!error && entries === null && <p className="text-body-secondary small mb-0">{t('history_loading')}</p>}
            {!error && entries !== null && <ActivityHistoryList entries={entries} trans={trans} />}
        </div>
    );
}
