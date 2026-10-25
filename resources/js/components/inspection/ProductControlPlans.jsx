import React, { useEffect, useState } from 'react';
import { apiFetch } from '../../lib/http';
import PlanThumbnail from './PlanThumbnail.jsx';

const url = (template, id) => template.replace('__ID__', id);
const STATUS_BADGE = { draft: 'text-bg-warning', approved: 'text-bg-success', obsolete: 'text-bg-secondary' };

/**
 * Onglet « Plans de contrôle » de la fiche article : toutes les révisions, et
 * l'entrée vers le bulleur pour l'indice courant.
 */
export default function ProductControlPlans({ productId, endpoints, trans, canEdit = false }) {
    const [data, setData]   = useState(null);
    const [error, setError] = useState(null);
    const [busy, setBusy]   = useState(false);

    useEffect(() => {
        apiFetch(url(endpoints.productPlans, productId))
            .then(setData)
            .catch(err => setError(err?.message ?? trans.load_error));
    }, [productId]);

    if (error) return <div className="alert alert-danger">{error}</div>;
    if (!data) return <div className="text-center py-3"><i className="fas fa-spinner fa-spin text-secondary" /></div>;

    const currentRevision = data.product_revision || null;
    const hasCurrent = data.plans.some(p => p.status !== 'obsolete' && (p.revision ?? null) === currentRevision);

    const create = () => {
        setBusy(true);
        apiFetch(endpoints.store, { method: 'POST', body: JSON.stringify({ products_id: productId, revision: currentRevision }) })
            .then(res => { window.location.href = url(endpoints.bubbler, res.plan.id); })
            .catch(err => { setError(err?.message ?? trans.save_error); setBusy(false); });
    };

    return (
        <div>
            <div className="d-flex align-items-center mb-2">
                <p className="text-secondary small mb-0 flex-grow-1">{trans.product_tab_help}</p>
                {canEdit && !hasCurrent && (
                    <button type="button" className="btn btn-sm btn-primary" disabled={busy} onClick={create}>
                        <i className="fas fa-pen-ruler me-1" />
                        {trans.create_plan} {currentRevision ? `(${trans.revision} ${currentRevision})` : ''}
                    </button>
                )}
            </div>
            {data.plans.length === 0 ? (
                <p className="text-secondary">{trans.no_plan_yet}</p>
            ) : (
                <div className="table-responsive">
                    <table className="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{trans.plan}</th>
                                <th>{trans.revision}</th>
                                <th>{trans.status}</th>
                                <th>{trans.characteristics}</th>
                                <th>{trans.approved_on}</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {data.plans.map(p => (
                                <tr key={p.id}>
                                    <td><PlanThumbnail url={p.thumbnail_url} file={p.file} title={p.file?.name ?? ''} /></td>
                                    <td>{p.revision ?? '—'}</td>
                                    <td><span className={`badge ${STATUS_BADGE[p.status]}`}>{trans[`status_${p.status}`]}</span></td>
                                    <td>
                                        {p.points_count ?? 0}
                                        {p.critical_count > 0 && <span className="badge text-bg-warning ms-2">{p.critical_count} {trans.critical}</span>}
                                    </td>
                                    <td>{p.approved_at ? new Date(p.approved_at).toLocaleDateString(trans.locale || undefined) : '—'}</td>
                                    <td className="text-end">
                                        <a className="btn btn-sm btn-outline-primary" href={p.bubbler_url}>
                                            <i className={`fas ${p.status === 'draft' && canEdit ? 'fa-pen-ruler' : 'fa-eye'} me-1`} />
                                            {p.status === 'draft' && canEdit ? trans.open_bubbler : trans.view}
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
