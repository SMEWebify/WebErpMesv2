import React, { useState, useEffect, useCallback } from 'react';
import { formatQty } from '../utils';

export default function TaskLines({ endpoints, services, statuses, resources, defaultStatusIds }) {
    const [rows, setRows]               = useState([]);
    const [loading, setLoading]         = useState(false);
    const [search, setSearch]           = useState('');
    const [serviceId, setServiceId]     = useState('');
    const [resourceId, setResourceId]   = useState('');
    const [selectedStatuses, setSelectedStatuses] = useState(defaultStatusIds.map(String));
    const [showGeneric, setShowGeneric] = useState(false);
    const [sort, setSort]               = useState('end_date');
    const [asc, setAsc]                 = useState(true);

    const load = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams();
        params.set('search', search);
        params.set('service_id', serviceId);
        params.set('resource_id', resourceId);
        params.set('show_generic', showGeneric ? '1' : '0');
        params.set('sort', sort);
        params.set('asc', asc ? '1' : '0');
        selectedStatuses.forEach(id => params.append('status_ids[]', id));

        fetch(`${endpoints.list}?${params}`, { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => { setRows(data); setLoading(false); })
            .catch(() => setLoading(false));
    }, [endpoints.list, search, serviceId, resourceId, showGeneric, sort, asc, selectedStatuses]);

    useEffect(() => { load(); }, [load]);

    const toggleStatus = (id) => {
        const sid = String(id);
        setSelectedStatuses(prev =>
            prev.includes(sid) ? prev.filter(s => s !== sid) : [...prev, sid]
        );
    };

    const handleSort = (field) => {
        if (sort === field) setAsc(prev => !prev);
        else { setSort(field); setAsc(true); }
    };

    const SortBtn = ({ field, label }) => (
        <button className="btn btn-secondary btn-sm" onClick={() => handleSort(field)}>
            {label} {sort === field ? (asc ? '↑' : '↓') : ''}
        </button>
    );

    return (
        <div className="card">
            {/* Filtres */}
            <div className="row">
                <div className="card-body col-12">
                    <div className="input-group mb-3">
                        <div className="input-group-prepend">
                            <span className="input-group-text"><i className="fas fa-search fa-fw"></i></span>
                        </div>
                        <input
                            type="text"
                            className="form-control"
                            placeholder="Rechercher une tâche..."
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                        />
                    </div>
                </div>
            </div>
            <div className="row">
                <div className="col-md-3">
                    <div className="card-body">
                        <div className="form-group">
                            <label>Service</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-list"></i></span></div>
                                <select className="form-control" value={serviceId} onChange={e => setServiceId(e.target.value)}>
                                    <option value="">-- Sélectionner --</option>
                                    {services.map(s => <option key={s.id} value={s.id}>{s.label}</option>)}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-3">
                    <div className="card-body">
                        <div className="form-group">
                            <label>Statut</label>
                            <div className="form-control overflow-auto" style={{ maxHeight: 200 }}>
                                {statuses.map(s => (
                                    <div className="form-check" key={s.id}>
                                        <input
                                            className="form-check-input"
                                            type="checkbox"
                                            id={`status-${s.id}`}
                                            checked={selectedStatuses.includes(String(s.id))}
                                            onChange={() => toggleStatus(s.id)}
                                        />
                                        <label className="form-check-label" htmlFor={`status-${s.id}`}>{s.title}</label>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-3">
                    <div className="card-body">
                        <div className="form-group">
                            <label>Ressource</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-list"></i></span></div>
                                <select className="form-control" value={resourceId} onChange={e => setResourceId(e.target.value)}>
                                    <option value="">-- Sélectionner --</option>
                                    {resources.map(r => <option key={r.id} value={r.id}>{r.label}</option>)}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-3">
                    <div className="card-body">
                        <div className="form-group">
                            <label>Tâches génériques</label>
                            <div>
                                <input
                                    type="checkbox"
                                    id="ShowGenericTask"
                                    checked={showGeneric}
                                    onChange={e => setShowGeneric(e.target.checked)}
                                    style={{ display: 'flex', alignItems: 'center' }}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Tableau */}
            <div className="table-responsive p-0">
                {loading ? (
                    <div className="text-center p-3"><i className="fas fa-spinner fa-spin"></i></div>
                ) : (
                    <table className="table table-hover">
                        <thead>
                            <tr>
                                <th>Commande</th>
                                <th>Qté</th>
                                <th>Libellé commande</th>
                                <th><SortBtn field="label" label="Tâche" /></th>
                                <th>Produit</th>
                                <th><SortBtn field="methods_services_id" label="Service" /></th>
                                <th>Ressource</th>
                                <th>Qté req.</th>
                                <th>Régl.</th>
                                <th>Tps/u</th>
                                <th>Tps total</th>
                                <th>Avancement</th>
                                <th>Statut</th>
                                <th><SortBtn field="end_date" label="Fin" /></th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan={14} className="text-center text-muted">Aucune donnée</td></tr>
                            ) : rows.map(task => (
                                <tr key={task.id}>
                                    <td>
                                        {task.order ? (
                                            <a href={endpoints.order.replace('__ID__', task.order.id)} className="btn btn-sm btn-primary">{task.order.code}</a>
                                        ) : <span className="text-muted">Générique</span>}
                                    </td>
                                    <td>{formatQty(task.qty)}</td>
                                    <td>{task.order_label}</td>
                                    <td>
                                        <a href={endpoints.task.replace('__ID__', task.id)} className="btn btn-sm btn-success">Voir</a>
                                        {' '}#{task.id} - {task.label}
                                        {task.component && <span> - {task.component.label}</span>}
                                    </td>
                                    <td>
                                        {task.component && (
                                            <a href={endpoints.product.replace('__ID__', task.component.id)} className={`btn btn-sm bg-${task.component.color_class} color-palette`}>
                                                {task.component.label}
                                            </a>
                                        )}
                                    </td>
                                    <td style={task.service?.color ? { backgroundColor: task.service.color } : {}}>
                                        {task.service && (
                                            task.service.picture_url ? (
                                                <p data-toggle="tooltip" data-html="true" title={`<img alt='Service' class='profile-user-img img-fluid img-circle' src='${task.service.picture_url}'>`}>
                                                    <span>{task.service.label}</span>
                                                </p>
                                            ) : task.service.label
                                        )}
                                    </td>
                                    <td>
                                        <ul className="list-unstyled mb-0">
                                            {task.resources.map((r, i) => (
                                                <li key={i}>
                                                    {r.picture_url ? (
                                                        <span data-toggle="tooltip" data-html="true" title={`<img alt='Ressource' class='profile-user-img' src='${r.picture_url}'>`}>{r.label}</span>
                                                    ) : r.label}
                                                </li>
                                            ))}
                                        </ul>
                                    </td>
                                    <td>{task.qty_required}</td>
                                    <td>{task.seting_time} h</td>
                                    <td>{task.unit_time} h</td>
                                    <td>{task.total_time} h</td>
                                    <td>
                                        <div className="progress">
                                            <div className="progress-bar bg-teal" style={{ width: `${task.progress}%` }}>
                                                {task.progress}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>{task.status_title}</td>
                                    <td className={`${task.end_date_class} color-palette`}>
                                        {task.end_date_class === 'bg-info' ? task.service_label : task.end_date}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Commande</th><th>Qté</th><th>Libellé commande</th><th>Tâche</th>
                                <th>Produit</th><th>Service</th><th>Ressource</th><th>Qté req.</th>
                                <th>Régl.</th><th>Tps/u</th><th>Tps total</th><th>Avancement</th>
                                <th>Statut</th><th>Fin</th>
                            </tr>
                        </tfoot>
                    </table>
                )}
            </div>
        </div>
    );
}
