import React, { useState } from 'react';
import { formatQty } from '../utils';

const TYP_MOVE_LABELS = {
    1:  'Inventaire',
    2:  'Allocation tâche',
    3:  'Réception commande achat',
    4:  'Mouvements inter-stocks',
    5:  'Réception stock manuelle',
    6:  'Dispatching stock manuel',
    7:  'Réservation',
    8:  'Annulation réservation',
    9:  'Livraison partielle',
    10: 'En production',
    11: 'Réservation composant production',
    12: 'Entrée composant fabriqué',
    13: 'Inventaire direct',
    14: 'Entrée transfert',
    15: 'Écart inventaire (sortie)',
};

function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export default function StockDetailPage({ initial, endpoints }) {
    const [stockMove, setStockMove] = useState(initial);
    const [form, setForm] = useState({
        x_size:       initial.x_size       ?? '',
        y_size:       initial.y_size       ?? '',
        z_size:       initial.z_size       ?? '',
        surface_perc: initial.surface_perc ?? '',
        tracability:  initial.tracability  ?? '',
    });
    const [saving,    setSaving]    = useState(false);
    const [saveMsg,   setSaveMsg]   = useState(null);
    const [uploading, setUploading] = useState(false);

    function handleChange(e) {
        const { name, value } = e.target;
        setForm(prev => ({ ...prev, [name]: value }));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setSaveMsg(null);
        try {
            const res = await fetch(endpoints.update, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify(form),
            });
            if (!res.ok) throw new Error('Erreur serveur');
            setSaveMsg({ type: 'success', text: 'Stock mis à jour avec succès.' });
            // Reflect tracability change in the trace URL
            setStockMove(prev => ({ ...prev, tracability: form.tracability }));
        } catch (err) {
            setSaveMsg({ type: 'danger', text: err.message });
        } finally {
            setSaving(false);
        }
    }

    async function handlePhotoUpload(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        setUploading(true);
        const formData = new FormData();
        formData.append('file', file);
        formData.append('stock_move_id', stockMove.id);
        formData.append('_token', getCsrf());
        try {
            await fetch(endpoints.photo_store, { method: 'POST', body: formData });
            window.location.reload();
        } catch {
            setUploading(false);
        }
    }

    const typMoveLabel = TYP_MOVE_LABELS[stockMove.typ_move] ?? `Type ${stockMove.typ_move}`;

    return (
        <div>
            {/* Trace button */}
            {stockMove.trace_url && (
                <a href={stockMove.trace_url} className="btn btn-primary mb-3">
                    Trace
                </a>
            )}

            {/* ── Details table ── */}
            <div className="card card-primary">
                <div className="card-header">
                    <h3 className="card-title">
                        Stock — {stockMove.product_code}
                    </h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool" data-card-widget="maximize">
                            <i className="fas fa-expand" />
                        </button>
                    </div>
                </div>
                <div className="card-body p-0">
                    <table className="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Date/Heure</th>
                                <th>Qté</th>
                                <th>Commande</th>
                                <th>Tâche</th>
                                <th>BL Achat</th>
                                <th>Type</th>
                                <th>Prix</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{stockMove.user_name}</td>
                                <td>{stockMove.date}</td>
                                <td>{formatQty(stockMove.qty)}</td>
                                <td>
                                    {stockMove.order?.url ? (
                                        <a href={stockMove.order.url} className="btn btn-primary btn-sm">
                                            <i className="fas fa-folder mr-1" />
                                            {stockMove.order.code}
                                        </a>
                                    ) : stockMove.order?.code ?? null}
                                </td>
                                <td>
                                    {stockMove.task?.url ? (
                                        <a href={stockMove.task.url} className="btn btn-sm btn-success">
                                            Voir
                                        </a>
                                    ) : stockMove.task ? `#${stockMove.task.id}` : null}
                                </td>
                                <td>
                                    {stockMove.purchase_receipt?.url ? (
                                        <a href={stockMove.purchase_receipt.url} className="btn btn-primary btn-sm">
                                            <i className="fas fa-folder mr-1" />
                                            {stockMove.purchase_receipt.code}
                                        </a>
                                    ) : stockMove.purchase_receipt?.code ?? null}
                                </td>
                                <td>{typMoveLabel}</td>
                                <td>{stockMove.formatted_price}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {/* ── Edit form ── */}
            <div className="card card-warning">
                <div className="card-header">
                    <h3 className="card-title">Stock — Détails</h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool" data-card-widget="maximize">
                            <i className="fas fa-expand" />
                        </button>
                    </div>
                </div>
                <div className="card-body">
                    {saveMsg && (
                        <div className={`alert alert-${saveMsg.type} alert-dismissible`}>
                            <button type="button" className="close" onClick={() => setSaveMsg(null)}>
                                <span>&times;</span>
                            </button>
                            {saveMsg.text}
                        </div>
                    )}
                    <form onSubmit={handleSubmit}>
                        <div className="row">
                            <div className="form-group col-md-4">
                                <label>X</label>
                                <div className="input-group">
                                    <div className="input-group-prepend">
                                        <span className="input-group-text">
                                            <i className="fas fa-ruler-combined" />
                                        </span>
                                    </div>
                                    <input
                                        type="number"
                                        className="form-control"
                                        name="x_size"
                                        value={form.x_size}
                                        onChange={handleChange}
                                        step=".001"
                                        placeholder="X"
                                    />
                                </div>
                            </div>
                            <div className="form-group col-md-4">
                                <label>Y</label>
                                <div className="input-group">
                                    <div className="input-group-prepend">
                                        <span className="input-group-text">
                                            <i className="fas fa-ruler-combined" />
                                        </span>
                                    </div>
                                    <input
                                        type="number"
                                        className="form-control"
                                        name="y_size"
                                        value={form.y_size}
                                        onChange={handleChange}
                                        step=".001"
                                        placeholder="Y"
                                    />
                                </div>
                            </div>
                            <div className="form-group col-md-4">
                                <label>Z</label>
                                <div className="input-group">
                                    <div className="input-group-prepend">
                                        <span className="input-group-text">
                                            <i className="fas fa-ruler-combined" />
                                        </span>
                                    </div>
                                    <input
                                        type="number"
                                        className="form-control"
                                        name="z_size"
                                        value={form.z_size}
                                        onChange={handleChange}
                                        step=".001"
                                        placeholder="Z"
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="row">
                            <div className="form-group col-md-4">
                                <label>M²</label>
                                <div className="input-group">
                                    <div className="input-group-prepend">
                                        <span className="input-group-text">
                                            <i className="fas fa-ruler-combined" />
                                        </span>
                                    </div>
                                    <input
                                        type="number"
                                        className="form-control"
                                        name="surface_perc"
                                        value={form.surface_perc}
                                        onChange={handleChange}
                                        step=".001"
                                        placeholder="M²"
                                    />
                                </div>
                            </div>
                            <div className="form-group col-md-4">
                                <label>Traçabilité</label>
                                <div className="input-group">
                                    <div className="input-group-prepend">
                                        <span className="input-group-text">
                                            <i className="fas fa-barcode" />
                                        </span>
                                    </div>
                                    <input
                                        type="text"
                                        className="form-control"
                                        name="tracability"
                                        value={form.tracability}
                                        onChange={handleChange}
                                        placeholder="Traçabilité"
                                    />
                                </div>
                            </div>
                        </div>
                        <div className="card-footer px-0 pb-0">
                            <button type="submit" className="btn btn-info btn-flat" disabled={saving}>
                                <i className={`fas fa-lg mr-1 ${saving ? 'fa-spinner fa-spin' : 'fa-save'}`} />
                                {saving ? 'Enregistrement…' : 'Mettre à jour'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {/* ── Barcode ── */}
            <div className="card card-secondary">
                <div className="card-header">
                    <h3 className="card-title">Code-barres</h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool" data-card-widget="maximize">
                            <i className="fas fa-expand" />
                        </button>
                    </div>
                </div>
                <div className="card-body">
                    <img
                        src={`data:image/jpeg;base64,${stockMove.barcode_base64}`}
                        alt="barcode"
                    />
                </div>
            </div>

            {/* ── Photo upload ── */}
            <div className="row">
                <div className="col-md-12">
                    <div className="card card-info">
                        <div className="card-header">
                            <h3 className="card-title">Photos</h3>
                            <div className="card-tools">
                                <button type="button" className="btn btn-tool" data-card-widget="maximize">
                                    <i className="fas fa-expand" />
                                </button>
                            </div>
                        </div>
                        <div className="card-body">
                            <div className="input-group">
                                <div className="custom-file">
                                    <input
                                        type="file"
                                        accept="image/*"
                                        capture="camera"
                                        className="custom-file-input"
                                        id="chooseFile"
                                        onChange={handlePhotoUpload}
                                        disabled={uploading}
                                    />
                                    <label className="custom-file-label" htmlFor="chooseFile">
                                        {uploading ? 'Téléversement…' : 'Prendre une photo'}
                                    </label>
                                </div>
                                <div className="input-group-append">
                                    <span className="input-group-text">
                                        {uploading
                                            ? <i className="fas fa-spinner fa-spin" />
                                            : <i className="fas fa-camera" />}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* ── Photos gallery ── */}
            {stockMove.photos.length > 0 && (
                <div className="row">
                    {stockMove.photos.map(photo => (
                        <div key={photo.name} className="col-md-4 mb-4">
                            <div className="card">
                                <img
                                    src={`/photo/${photo.name}`}
                                    className="card-img-top"
                                    alt={photo.original_file_name}
                                />
                                <div className="card-body">
                                    <p className="card-text">{photo.original_file_name}</p>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
