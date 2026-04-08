import React, { useState } from 'react';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

// A textarea field with an optional "show on PDF" toggle
function FieldGroup({ label, name, value, onChange, showPdfName, showPdfValue, onToggle }) {
    return (
        <div className="card card-secondary card-outline mb-3">
            <div className="card-body py-2">
                <div className="form-group mb-2">
                    <label htmlFor={name}>{label}</label>
                    <textarea
                        className="form-control"
                        id={name}
                        name={name}
                        rows={3}
                        value={value}
                        onChange={e => onChange(name, e.target.value)}
                    />
                </div>
                {showPdfName && (
                    <div className="d-flex align-items-center">
                        <div className="custom-control custom-switch">
                            <input
                                type="checkbox"
                                className="custom-control-input"
                                id={showPdfName}
                                checked={showPdfValue === 1}
                                onChange={e => onToggle(showPdfName, e.target.checked ? 1 : 2)}
                            />
                            <label className="custom-control-label" htmlFor={showPdfName}>
                                PDF
                            </label>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

function CostFieldGroup({ label, name, value, onChange, costName, costValue, showPdfName, showPdfValue, onToggle, currency }) {
    return (
        <div className="card card-secondary card-outline mb-3">
            <div className="card-body py-2">
                <div className="form-group mb-2">
                    <label htmlFor={name}>{label}</label>
                    <textarea
                        className="form-control"
                        id={name}
                        name={name}
                        rows={3}
                        value={value}
                        onChange={e => onChange(name, e.target.value)}
                    />
                </div>
                <div className="form-group mb-2">
                    <label htmlFor={costName}>
                        {costName.replace(/_/g, ' ')} ({currency})
                    </label>
                    <input
                        type="number"
                        step="0.01"
                        className="form-control"
                        id={costName}
                        name={costName}
                        value={costValue}
                        onChange={e => onChange(costName, e.target.value)}
                    />
                </div>
                <div className="custom-control custom-switch">
                    <input
                        type="checkbox"
                        className="custom-control-input"
                        id={showPdfName}
                        checked={showPdfValue === 1}
                        onChange={e => onToggle(showPdfName, e.target.checked ? 1 : 2)}
                    />
                    <label className="custom-control-label" htmlFor={showPdfName}>
                        PDF
                    </label>
                </div>
            </div>
        </div>
    );
}

export default function ConstructionSitePage({ quoteId, saveUrl, currency, initialData }) {
    const init = initialData ?? {};

    const [fields, setFields] = useState({
        client_requirements:               init.client_requirements               ?? '',
        show_client_requirements_on_pdf:   init.show_client_requirements_on_pdf   ?? 2,
        layout_plan:                       init.layout_plan                       ?? '',
        show_layout_on_pdf:                init.show_layout_on_pdf                ?? 2,
        materials_finishes:                init.materials_finishes                ?? '',
        show_materials_on_pdf:             init.show_materials_on_pdf             ?? 2,
        logistics:                         init.logistics                         ?? '',
        logistics_cost:                    init.logistics_cost                    ?? '',
        show_logistics_on_pdf:             init.show_logistics_on_pdf             ?? 2,
        coordination_with_contractors:     init.coordination_with_contractors     ?? '',
        contractors_cost:                  init.contractors_cost                  ?? '',
        show_contractors_on_pdf:           init.show_contractors_on_pdf           ?? 2,
        waste_management:                  init.waste_management                  ?? '',
        waste_management_cost:             init.waste_management_cost             ?? '',
        show_waste_on_pdf:                 init.show_waste_on_pdf                 ?? 2,
        taxes_and_fees:                    init.taxes_and_fees                    ?? '',
        taxes_cost:                        init.taxes_cost                        ?? '',
        show_taxes_on_pdf:                 init.show_taxes_on_pdf                 ?? 2,
        options_variants:                  init.options_variants                  ?? '',
        show_options_on_pdf:               init.show_options_on_pdf               ?? 2,
        insurance_liability:               init.insurance_liability               ?? '',
        insurance_cost:                    init.insurance_cost                    ?? '',
        show_insurance_on_pdf:             init.show_insurance_on_pdf             ?? 2,
        work_start_date:                   init.work_start_date                   ?? '',
        work_end_date:                     init.work_end_date                     ?? '',
        contingency_days:                  init.contingency_days                  ?? '',
    });

    const [saving, setSaving] = useState(false);
    const [flash, setFlash]   = useState(null); // { type: 'success'|'danger', msg }

    function handleChange(name, value) {
        setFields(prev => ({ ...prev, [name]: value }));
    }

    function handleToggle(name, value) {
        setFields(prev => ({ ...prev, [name]: value }));
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);
        setFlash(null);

        const body = new FormData();
        body.append('_token', csrfToken());

        Object.entries(fields).forEach(([key, val]) => {
            // Boolean toggles: send 1 when checked, omit when unchecked (server checks has())
            if (key.startsWith('show_')) {
                if (val === 1) body.append(key, '1');
            } else {
                body.append(key, val ?? '');
            }
        });

        try {
            const res = await fetch(saveUrl, { method: 'POST', body });
            if (res.ok || res.redirected) {
                setFlash({ type: 'success', msg: 'Enregistré avec succès.' });
            } else {
                setFlash({ type: 'danger', msg: `Erreur ${res.status}` });
            }
        } catch {
            setFlash({ type: 'danger', msg: 'Erreur réseau.' });
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            {flash && (
                <div className={`alert alert-${flash.type} alert-dismissible`}>
                    <button type="button" className="close" onClick={() => setFlash(null)}>
                        <span>&times;</span>
                    </button>
                    {flash.msg}
                </div>
            )}

            <div className="row">
                <div className="col-md-6">
                    <FieldGroup
                        label="Exigences du client"
                        name="client_requirements"
                        value={fields.client_requirements}
                        onChange={handleChange}
                        showPdfName="show_client_requirements_on_pdf"
                        showPdfValue={fields.show_client_requirements_on_pdf}
                        onToggle={handleToggle}
                    />

                    <FieldGroup
                        label="Plan de mise en page"
                        name="layout_plan"
                        value={fields.layout_plan}
                        onChange={handleChange}
                        showPdfName="show_layout_on_pdf"
                        showPdfValue={fields.show_layout_on_pdf}
                        onToggle={handleToggle}
                    />

                    <FieldGroup
                        label="Matériaux et finitions"
                        name="materials_finishes"
                        value={fields.materials_finishes}
                        onChange={handleChange}
                        showPdfName="show_materials_on_pdf"
                        showPdfValue={fields.show_materials_on_pdf}
                        onToggle={handleToggle}
                    />

                    <CostFieldGroup
                        label="Logistique"
                        name="logistics"
                        value={fields.logistics}
                        costName="logistics_cost"
                        costValue={fields.logistics_cost}
                        showPdfName="show_logistics_on_pdf"
                        showPdfValue={fields.show_logistics_on_pdf}
                        onChange={handleChange}
                        onToggle={handleToggle}
                        currency={currency}
                    />

                    <CostFieldGroup
                        label="Coordination avec les entrepreneurs"
                        name="coordination_with_contractors"
                        value={fields.coordination_with_contractors}
                        costName="contractors_cost"
                        costValue={fields.contractors_cost}
                        showPdfName="show_contractors_on_pdf"
                        showPdfValue={fields.show_contractors_on_pdf}
                        onChange={handleChange}
                        onToggle={handleToggle}
                        currency={currency}
                    />
                </div>

                <div className="col-md-6">
                    <CostFieldGroup
                        label="Gestion des déchets"
                        name="waste_management"
                        value={fields.waste_management}
                        costName="waste_management_cost"
                        costValue={fields.waste_management_cost}
                        showPdfName="show_waste_on_pdf"
                        showPdfValue={fields.show_waste_on_pdf}
                        onChange={handleChange}
                        onToggle={handleToggle}
                        currency={currency}
                    />

                    <CostFieldGroup
                        label="Taxes et frais additionnels"
                        name="taxes_and_fees"
                        value={fields.taxes_and_fees}
                        costName="taxes_cost"
                        costValue={fields.taxes_cost}
                        showPdfName="show_taxes_on_pdf"
                        showPdfValue={fields.show_taxes_on_pdf}
                        onChange={handleChange}
                        onToggle={handleToggle}
                        currency={currency}
                    />

                    <FieldGroup
                        label="Options / variantes"
                        name="options_variants"
                        value={fields.options_variants}
                        onChange={handleChange}
                        showPdfName="show_options_on_pdf"
                        showPdfValue={fields.show_options_on_pdf}
                        onToggle={handleToggle}
                    />

                    <CostFieldGroup
                        label="Assurance / responsabilité"
                        name="insurance_liability"
                        value={fields.insurance_liability}
                        costName="insurance_cost"
                        costValue={fields.insurance_cost}
                        showPdfName="show_insurance_on_pdf"
                        showPdfValue={fields.show_insurance_on_pdf}
                        onChange={handleChange}
                        onToggle={handleToggle}
                        currency={currency}
                    />

                    <div className="card card-secondary card-outline mb-3">
                        <div className="card-body py-2">
                            <div className="form-group mb-2">
                                <label htmlFor="work_start_date">Date de début des travaux</label>
                                <input
                                    type="date"
                                    className="form-control"
                                    id="work_start_date"
                                    value={fields.work_start_date}
                                    onChange={e => handleChange('work_start_date', e.target.value)}
                                />
                            </div>
                            <div className="form-group mb-2">
                                <label htmlFor="work_end_date">Date de fin des travaux</label>
                                <input
                                    type="date"
                                    className="form-control"
                                    id="work_end_date"
                                    value={fields.work_end_date}
                                    onChange={e => handleChange('work_end_date', e.target.value)}
                                />
                            </div>
                            <div className="form-group mb-0">
                                <label htmlFor="contingency_days">Marge de manœuvre (jours)</label>
                                <input
                                    type="number"
                                    className="form-control"
                                    id="contingency_days"
                                    value={fields.contingency_days}
                                    onChange={e => handleChange('contingency_days', e.target.value)}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" className="btn btn-primary" disabled={saving}>
                {saving ? 'Enregistrement…' : 'Enregistrer'}
            </button>
        </form>
    );
}
