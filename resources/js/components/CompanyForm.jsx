import React, { useState } from 'react';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, body) {
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
// Toggle Switch (Bootstrap 4 custom-switch)
// ---------------------------------------------------------------------------

function Toggle({ id, checked, onChange, label }) {
    return (
        <div className="custom-control custom-switch">
            <input
                type="checkbox"
                className="custom-control-input"
                id={id}
                checked={!!checked}
                onChange={e => onChange(e.target.checked)}
            />
            <label className="custom-control-label" htmlFor={id}>{label}</label>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Field components
// ---------------------------------------------------------------------------

function Field({ label, children, error }) {
    return (
        <div className="form-group">
            {label && <label className="mb-1">{label}</label>}
            {children}
            {error && <span className="text-danger small">{error}</span>}
        </div>
    );
}

function InputGroup({ icon, children }) {
    return (
        <div className="input-group">
            <div className="input-group-prepend">
                <span className="input-group-text"><i className={`fas ${icon}`} /></span>
            </div>
            {children}
        </div>
    );
}

// ---------------------------------------------------------------------------
// Section Cards
// ---------------------------------------------------------------------------

function Card({ title, theme, children }) {
    return (
        <div className={`card card-${theme ?? 'primary'}`}>
            {title && (
                <div className="card-header">
                    <h3 className="card-title">{title}</h3>
                </div>
            )}
            <div className="card-body">{children}</div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function CompanyForm({ company: initial, users, endpoint, trans }) {
    const [form, setForm]       = useState({ ...initial });
    const [errors, setErrors]   = useState({});
    const [saving, setSaving]   = useState(false);
    const [success, setSuccess] = useState(null);
    const [warning, setWarning] = useState(null);

    const set = (field) => (value) => {
        setForm(f => ({ ...f, [field]: value }));
        setErrors(e => { const n = { ...e }; delete n[field]; return n; });
    };

    const setVal = (field) => (e) => set(field)(e.target.value);

    const normalizeForm = (data) => {
        const nullableFields = [
            'civility', 'last_name', 'website', 'fbsite', 'twittersite', 'lkdsite',
            'siren', 'naf_code', 'intra_community_vat',
            'discount', 'tolerance_days',
            'account_general_customer', 'account_auxiliary_customer',
            'account_general_supplier', 'account_auxiliary_supplier',
            'latitude', 'longitude', 'comment', 'barcode_value',
        ];
        const result = { ...data };
        nullableFields.forEach(f => {
            if (result[f] === '' || result[f] === undefined) result[f] = null;
        });
        // Ces champs sont validés comme nullable|string côté Laravel :
        // s'ils arrivent de PHP comme integer, on les force en string.
        ['account_general_customer', 'account_auxiliary_customer',
         'account_general_supplier', 'account_auxiliary_supplier'].forEach(f => {
            if (result[f] !== null) result[f] = String(result[f]);
        });
        return result;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setSuccess(null);
        setWarning(null);
        setErrors({});
        try {
            const res = await apiFetch(endpoint, normalizeForm(form));
            setSuccess(trans.save_success);
            if (res.warning) setWarning(res.warning);
        } catch (err) {
            if (err.errors) setErrors(err.errors);
            else setSuccess(null);
        } finally {
            setSaving(false);
        }
    };

    const isIndividual = parseInt(form.client_type, 10) === 2;

    return (
        <form onSubmit={handleSubmit}>

            {/* Feedback */}
            {success && (
                <div className="alert alert-success alert-dismissible">
                    <button type="button" className="close" onClick={() => setSuccess(null)}>
                        <span>&times;</span>
                    </button>
                    <i className="fas fa-check-circle mr-1" />{success}
                </div>
            )}
            {warning && (
                <div className="alert alert-warning alert-dismissible">
                    <button type="button" className="close" onClick={() => setWarning(null)}>
                        <span>&times;</span>
                    </button>
                    <i className="fas fa-exclamation-triangle mr-1" />{warning}
                </div>
            )}

            {/* General info */}
            <Card title={trans.general_information} theme="primary">
                <div className="row">
                    <div className="col-md-1">
                        <div className="text-muted">
                            <small>{trans.external_id}</small>
                            <b className="d-block">{form.code}</b>
                        </div>
                    </div>

                    <div className="col-md-2 d-flex align-items-center">
                        <Toggle
                            id="active"
                            checked={form.active}
                            onChange={set('active')}
                            label={trans.active}
                        />
                    </div>

                    {!isIndividual ? (
                        <div className="col-md-4">
                            <Field label={trans.name_company} error={errors.label?.[0]}>
                                <InputGroup icon="fa-building">
                                    <input
                                        type="text"
                                        className={`form-control ${errors.label ? 'is-invalid' : ''}`}
                                        value={form.label ?? ''}
                                        onChange={setVal('label')}
                                        placeholder={trans.name_company}
                                    />
                                </InputGroup>
                            </Field>
                        </div>
                    ) : (
                        <>
                            <div className="col-md-2">
                                <Field label={trans.civility} error={errors.civility?.[0]}>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={form.civility ?? ''}
                                        onChange={setVal('civility')}
                                        placeholder={trans.civility}
                                    />
                                </Field>
                            </div>
                            <div className="col-md-2">
                                <Field label={trans.first_name} error={errors.label?.[0]}>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={form.label ?? ''}
                                        onChange={setVal('label')}
                                        placeholder={trans.first_name}
                                    />
                                </Field>
                            </div>
                            <div className="col-md-2">
                                <Field label={trans.contact_name} error={errors.last_name?.[0]}>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={form.last_name ?? ''}
                                        onChange={setVal('last_name')}
                                        placeholder={trans.contact_name}
                                    />
                                </Field>
                            </div>
                        </>
                    )}

                    <div className="col-md-3">
                        <Field label={trans.user_management} error={errors.user_id?.[0]}>
                            <InputGroup icon="fa-user">
                                <select
                                    className={`form-control ${errors.user_id ? 'is-invalid' : ''}`}
                                    value={form.user_id ?? ''}
                                    onChange={setVal('user_id')}
                                >
                                    <option value="">{trans.select_user}</option>
                                    {users.map(u => (
                                        <option key={u.id} value={u.id}>{u.name}</option>
                                    ))}
                                </select>
                            </InputGroup>
                        </Field>
                    </div>
                </div>
            </Card>

            {/* Social links */}
            <Card title={trans.site_link} theme="secondary">
                <div className="row">
                    {[
                        { field: 'website',     icon: 'fa-globe',           placeholder: 'Website' },
                        { field: 'fbsite',      icon: 'fa-facebook-square', placeholder: 'Facebook' },
                        { field: 'twittersite', icon: 'fa-twitter-square',  placeholder: 'X / Twitter' },
                        { field: 'lkdsite',     icon: 'fa-linkedin',        placeholder: 'LinkedIn' },
                    ].map(({ field, icon, placeholder }) => (
                        <div key={field} className="col-md-3">
                            <div className="form-group">
                                <InputGroup icon={icon}>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={form[field] ?? ''}
                                        onChange={setVal(field)}
                                        placeholder={placeholder}
                                    />
                                </InputGroup>
                                {form[field] && (
                                    <a href={form[field]} target="_blank" rel="noopener noreferrer" className="text-muted small mt-1 d-inline-block">
                                        <i className={`fab ${icon} fa-lg`} />
                                    </a>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </Card>

            {/* Administrative info */}
            <Card title={trans.administrative_information} theme="warning">
                <div className="row">
                    <div className="col-md-4">
                        <Field error={errors.siren?.[0]}>
                            <input
                                type="text"
                                className="form-control"
                                value={form.siren ?? ''}
                                onChange={setVal('siren')}
                                placeholder={trans.reg_number}
                                disabled={isIndividual}
                            />
                        </Field>
                    </div>
                    <div className="col-md-4">
                        <Field error={errors.naf_code?.[0]}>
                            <input
                                type="text"
                                className="form-control"
                                value={form.naf_code ?? ''}
                                onChange={setVal('naf_code')}
                                placeholder={trans.naf_code}
                                disabled={isIndividual}
                            />
                        </Field>
                    </div>
                    <div className="col-md-4">
                        <Field error={errors.intra_community_vat?.[0]}>
                            <input
                                type="text"
                                className="form-control"
                                value={form.intra_community_vat ?? ''}
                                onChange={setVal('intra_community_vat')}
                                placeholder={trans.vat_number}
                                disabled={isIndividual}
                            />
                        </Field>
                    </div>
                </div>
            </Card>

            {/* Customer status */}
            <div className="card card-outline card-success">
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-3">
                            <Field label={trans.status_client} error={errors.statu_customer?.[0]}>
                                <InputGroup icon="fa-user-check">
                                    <select
                                        className="form-control"
                                        value={form.statu_customer ?? ''}
                                        onChange={setVal('statu_customer')}
                                    >
                                        <option value="">{trans.select_status}</option>
                                        <option value="1">{trans.inactive}</option>
                                        <option value="2">{trans.active}</option>
                                        <option value="3">{trans.prospect}</option>
                                    </select>
                                </InputGroup>
                            </Field>
                        </div>
                        <div className="col-md-3">
                            <Field label={`${trans.discount} :`} error={errors.discount?.[0]}>
                                <InputGroup icon="fa-percentage">
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.discount ?? ''}
                                        onChange={setVal('discount')}
                                        placeholder={trans.discount}
                                    />
                                </InputGroup>
                            </Field>
                        </div>
                        <div className="col-md-3">
                            <Field label={trans.general_account} error={errors.account_general_customer?.[0]}>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.account_general_customer ?? ''}
                                    onChange={setVal('account_general_customer')}
                                    placeholder={trans.general_account}
                                />
                            </Field>
                        </div>
                        <div className="col-md-3">
                            <Field label={trans.auxiliary_account} error={errors.account_auxiliary_customer?.[0]}>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.account_auxiliary_customer ?? ''}
                                    onChange={setVal('account_auxiliary_customer')}
                                    placeholder={trans.auxiliary_account}
                                />
                            </Field>
                        </div>
                    </div>
                </div>
            </div>

            {/* Supplier status */}
            <div className="card card-outline card-purple">
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-3">
                            <Field label={trans.status_supplier} error={errors.statu_supplier?.[0]}>
                                <InputGroup icon="fa-truck">
                                    <select
                                        className="form-control"
                                        value={form.statu_supplier ?? ''}
                                        onChange={setVal('statu_supplier')}
                                    >
                                        <option value="">{trans.select_status}</option>
                                        <option value="1">{trans.inactive}</option>
                                        <option value="2">{trans.active}</option>
                                    </select>
                                </InputGroup>
                            </Field>
                        </div>
                        <div className="col-md-3">
                            <Field label={trans.reception_control} error={errors.recept_controle?.[0]}>
                                <select
                                    className="form-control"
                                    value={form.recept_controle ?? ''}
                                    onChange={setVal('recept_controle')}
                                >
                                    <option value="">{trans.select_control}</option>
                                    <option value="1">{trans.yes}</option>
                                    <option value="0">{trans.no}</option>
                                </select>
                            </Field>
                        </div>
                        <div className="col-md-3">
                            <Field label={trans.general_account} error={errors.account_general_supplier?.[0]}>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.account_general_supplier ?? ''}
                                    onChange={setVal('account_general_supplier')}
                                    placeholder={trans.general_account}
                                />
                            </Field>
                        </div>
                        <div className="col-md-3">
                            <Field label={trans.auxiliary_account} error={errors.account_auxiliary_supplier?.[0]}>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.account_auxiliary_supplier ?? ''}
                                    onChange={setVal('account_auxiliary_supplier')}
                                    placeholder={trans.auxiliary_account}
                                />
                            </Field>
                        </div>
                    </div>
                </div>
            </div>

            {/* Geolocation / logistics */}
            <div className="card card-outline card-dark">
                <div className="card-body">
                    <div className="row">
                        <div className="col-md-2">
                            <Field label={trans.latitude} error={errors.latitude?.[0]}>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.latitude ?? ''}
                                    onChange={setVal('latitude')}
                                    placeholder={trans.latitude}
                                />
                            </Field>
                        </div>
                        <div className="col-md-2">
                            <Field label={trans.longitude} error={errors.longitude?.[0]}>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={form.longitude ?? ''}
                                    onChange={setVal('longitude')}
                                    placeholder={trans.longitude}
                                />
                            </Field>
                        </div>
                        <div className="col-md-3">
                            <Field label={trans.delivery_constraint} error={errors.delivery_constraint?.[0]}>
                                <InputGroup icon="fa-exclamation">
                                    <select
                                        className="form-control"
                                        value={form.delivery_constraint ?? '1'}
                                        onChange={setVal('delivery_constraint')}
                                    >
                                        <option value="1">{trans.no_constraints}</option>
                                        <option value="2">{trans.no_tolerance}</option>
                                        <option value="3">{trans.tolerance_in_days}</option>
                                    </select>
                                </InputGroup>
                            </Field>
                        </div>
                        {parseInt(form.delivery_constraint, 10) === 3 && (
                            <div className="col-md-2">
                                <Field label={trans.tolerance_days} error={errors.tolerance_days?.[0]}>
                                    <input
                                        type="number"
                                        className="form-control"
                                        value={form.tolerance_days ?? ''}
                                        onChange={setVal('tolerance_days')}
                                        placeholder={trans.tolerance_days}
                                    />
                                </Field>
                            </div>
                        )}
                        <div className="col-md-2 d-flex align-items-center">
                            <Toggle
                                id="quoted_delivery_note"
                                checked={form.quoted_delivery_note}
                                onChange={set('quoted_delivery_note')}
                                label={trans.quoted_delivery_note}
                            />
                        </div>
                    </div>
                </div>
            </div>

            {/* Comment */}
            <div className="form-group">
                <label className="text-primary">{trans.comment}</label>
                <textarea
                    className="form-control"
                    rows={4}
                    value={form.comment ?? ''}
                    onChange={setVal('comment')}
                    placeholder={trans.comment}
                />
            </div>

            {/* Barcode */}
            <Card title="BARCODE" theme="orange">
                <Field error={errors.barcode_value?.[0]}>
                    <input
                        type="text"
                        className="form-control"
                        value={form.barcode_value ?? ''}
                        onChange={setVal('barcode_value')}
                    />
                </Field>
            </Card>

            {/* Submit */}
            <div className="card-footer px-0">
                <button type="submit" className="btn btn-info btn-flat" disabled={saving}>
                    <i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-save'} mr-1`} />
                    {saving ? trans.saving : trans.update}
                </button>
            </div>

        </form>
    );
}
