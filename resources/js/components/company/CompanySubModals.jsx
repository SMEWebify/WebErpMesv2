import React, { useState, useEffect } from 'react';
import { apiFetch } from '../../lib/http';

/**
 * Création rapide d'une adresse ou d'un contact depuis la fenêtre « Nouveau
 * document » (devis, commande, achat), affichée par-dessus celle-ci.
 *
 * `storeUrl` reçoit `{ ...champs, companies_id }` et renvoie l'entité créée,
 * passée à `onCreated` avant fermeture. Les erreurs de validation (422)
 * s'affichent sous chaque champ.
 */

function useSubModalForm({ show, empty, storeUrl, companiesId, onCreated, onClose }) {
    const [form, setForm]     = useState(empty);
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    useEffect(() => { if (show) { setForm(empty); setErrors({}); } }, [show]);

    const set = (f) => (e) => setForm(prev => ({ ...prev, [f]: e.target.value }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setErrors({});
        try {
            const created = await apiFetch(storeUrl, {
                method: 'POST',
                body:   JSON.stringify({ ...form, companies_id: companiesId }),
            });
            onCreated(created);
            onClose();
        } catch (err) {
            setErrors(err.errors ?? {});
        } finally {
            setSaving(false);
        }
    };

    const fe = (f) => errors[f] ? <span className="text-danger small d-block">{errors[f][0]}</span> : null;

    return { form, set, saving, handleSubmit, fe };
}

function SubModalFrame({ title, color, onClose, onSubmit, saving, trans, children }) {
    return (
        <div className="modal fade show d-block" tabIndex="-1" style={{ background: 'rgba(0,0,0,.6)', zIndex: 1060 }}>
            <div className="modal-dialog modal-dialog-centered modal-lg">
                <div className="modal-content">
                    <div className={`modal-header bg-${color}`}>
                        <h5 className="modal-title text-white">{title}</h5>
                        <button type="button" className="close text-white" onClick={onClose}>&times;</button>
                    </div>
                    <form onSubmit={onSubmit}>
                        <div className="modal-body">
                            {children}
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary" onClick={onClose}>{trans.cancel ?? 'Annuler'}</button>
                            <button type="submit" className={`btn btn-${color}`} disabled={saving}>
                                {saving
                                    ? <><i className="fas fa-spinner fa-spin mr-1" />{trans.saving ?? 'Enregistrement…'}</>
                                    : (trans.save ?? 'Enregistrer')}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

const EMPTY_ADDRESS = { ordre: '1', label: '', adress: '', zipcode: '', city: '', country: '', number: '', mail: '' };

export function CreateAddressSubModal({ show, onClose, companiesId, storeUrl, onCreated, trans }) {
    const { form, set, saving, handleSubmit, fe } = useSubModalForm({
        show, empty: EMPTY_ADDRESS, storeUrl, companiesId, onCreated, onClose,
    });

    if (!show) return null;

    return (
        <SubModalFrame title={trans.new_address ?? 'Nouvelle adresse'} color="primary"
                       onClose={onClose} onSubmit={handleSubmit} saving={saving} trans={trans}>
            <div className="form-row">
                <div className="form-group col-md-2">
                    <label>{trans.ordre ?? 'Ordre'} *</label>
                    <input type="number" className="form-control" value={form.ordre} onChange={set('ordre')} min="1" />
                    {fe('ordre')}
                </div>
                <div className="form-group col-md-4">
                    <label>{trans.adress_label ?? 'Libellé'} *</label>
                    <input className="form-control" value={form.label} onChange={set('label')} />
                    {fe('label')}
                </div>
                <div className="form-group col-md-6">
                    <label>{trans.adress ?? 'Adresse'} *</label>
                    <input className="form-control" value={form.adress} onChange={set('adress')} />
                    {fe('adress')}
                </div>
            </div>
            <div className="form-row">
                <div className="form-group col-md-3">
                    <label>{trans.postal_code ?? 'Code postal'} *</label>
                    <input className="form-control" value={form.zipcode} onChange={set('zipcode')} />
                    {fe('zipcode')}
                </div>
                <div className="form-group col-md-4">
                    <label>{trans.city ?? 'Ville'} *</label>
                    <input className="form-control" value={form.city} onChange={set('city')} />
                    {fe('city')}
                </div>
                <div className="form-group col-md-5">
                    <label>{trans.country ?? 'Pays'} *</label>
                    <input className="form-control" value={form.country} onChange={set('country')} />
                    {fe('country')}
                </div>
            </div>
            <div className="form-row">
                <div className="form-group col-md-6">
                    <label>{trans.phone ?? 'Téléphone'}</label>
                    <input className="form-control" value={form.number} onChange={set('number')} />
                    {fe('number')}
                </div>
                <div className="form-group col-md-6">
                    <label>{trans.email ?? 'Email'}</label>
                    <input type="email" className="form-control" value={form.mail} onChange={set('mail')} />
                    {fe('mail')}
                </div>
            </div>
        </SubModalFrame>
    );
}

const EMPTY_CONTACT = { ordre: '1', civility: '', first_name: '', name: '', function: '', number: '', mobile: '', mail: '' };

// Valeurs imprimées telles quelles sur les PDF et les pages client.
const CIVILITIES = ['M.', 'Mme', 'Dr'];

export function CreateContactSubModal({ show, onClose, companiesId, storeUrl, onCreated, trans }) {
    const { form, set, saving, handleSubmit, fe } = useSubModalForm({
        show, empty: EMPTY_CONTACT, storeUrl, companiesId, onCreated, onClose,
    });

    if (!show) return null;

    return (
        <SubModalFrame title={trans.new_contact ?? 'Nouveau contact'} color="info"
                       onClose={onClose} onSubmit={handleSubmit} saving={saving} trans={trans}>
            <div className="form-row">
                <div className="form-group col-md-2">
                    <label>{trans.ordre ?? 'Ordre'} *</label>
                    <input type="number" className="form-control" value={form.ordre} onChange={set('ordre')} min="1" />
                    {fe('ordre')}
                </div>
                <div className="form-group col-md-2">
                    <label>{trans.civility ?? 'Civilité'}</label>
                    <select className="form-control" value={form.civility} onChange={set('civility')}>
                        <option value="">—</option>
                        {CIVILITIES.map(c => <option key={c} value={c}>{c}</option>)}
                    </select>
                    {fe('civility')}
                </div>
                <div className="form-group col-md-4">
                    <label>{trans.first_name ?? 'Prénom'} *</label>
                    <input className="form-control" value={form.first_name} onChange={set('first_name')} />
                    {fe('first_name')}
                </div>
                <div className="form-group col-md-4">
                    <label>{trans.name ?? 'Nom'} *</label>
                    <input className="form-control" value={form.name} onChange={set('name')} />
                    {fe('name')}
                </div>
            </div>
            <div className="form-row">
                <div className="form-group col-md-4">
                    <label>{trans.function ?? 'Fonction'}</label>
                    <input className="form-control" value={form.function} onChange={set('function')} />
                    {fe('function')}
                </div>
                <div className="form-group col-md-4">
                    <label>{trans.phone ?? 'Téléphone'}</label>
                    <input className="form-control" value={form.number} onChange={set('number')} />
                    {fe('number')}
                </div>
                <div className="form-group col-md-4">
                    <label>{trans.mobile ?? 'Mobile'}</label>
                    <input className="form-control" value={form.mobile} onChange={set('mobile')} />
                    {fe('mobile')}
                </div>
            </div>
            <div className="form-row">
                <div className="form-group col-md-6">
                    <label>{trans.email ?? 'Email'}</label>
                    <input type="email" className="form-control" value={form.mail} onChange={set('mail')} />
                    {fe('mail')}
                </div>
            </div>
        </SubModalFrame>
    );
}
