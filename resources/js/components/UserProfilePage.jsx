import React, { useState } from 'react';

// ─────────────────────────────────────────────────────────────────────────────
// HTTP helper
// ─────────────────────────────────────────────────────────────────────────────

function apiFetch(url, options = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    return fetch(url, {
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        ...options,
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Alert banner
// ─────────────────────────────────────────────────────────────────────────────

function Alert({ type, message, onDismiss }) {
    if (!message) return null;
    return (
        <div className={`alert alert-${type} alert-dismissible`}>
            <button type="button" className="close" onClick={onDismiss}>
                <span>&times;</span>
            </button>
            {message}
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Account section (name + email)
// ─────────────────────────────────────────────────────────────────────────────

function AccountCard({ initial, updateUrl, trans }) {
    const [form, setForm]       = useState({ name: initial.name ?? '', email: initial.email ?? '' });
    const [errors, setErrors]   = useState({});
    const [success, setSuccess] = useState('');
    const [saving, setSaving]   = useState(false);

    const set = (field) => (e) => setForm(f => ({ ...f, [field]: e.target.value }));

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});
        setSuccess('');
        setSaving(true);
        const res = await apiFetch(updateUrl, { method: 'PUT', body: JSON.stringify(form) });
        setSaving(false);
        if (res.ok) {
            setSuccess(trans.success_account ?? 'Profile updated successfully');
        } else {
            const data = await res.json();
            setErrors(data.errors ?? {});
        }
    }

    return (
        <div className="card card-primary">
            <div className="card-header">
                <h3 className="card-title">{trans.about_setup ?? 'Account'}</h3>
                <div className="card-tools">
                    <button type="button" className="btn btn-tool" data-card-widget="maximize"><i className="fas fa-expand"></i></button>
                </div>
            </div>
            <form onSubmit={handleSubmit}>
                <div className="card-body">
                    <Alert type="success" message={success} onDismiss={() => setSuccess('')} />
                    <div className="form-group">
                        <label>{trans.name ?? 'Name'}</label>
                        <input type="text" className="form-control" value={form.name} onChange={set('name')} />
                        {errors.name && <span className="text-danger">{errors.name[0]}</span>}
                    </div>
                    <div className="form-group">
                        <label>{trans.email ?? 'Email'}</label>
                        <div className="input-group">
                            <div className="input-group-prepend">
                                <span className="input-group-text"><i className="fas fa-envelope"></i></span>
                            </div>
                            <input type="email" className="form-control" value={form.email} onChange={set('email')} />
                        </div>
                        {errors.email && <span className="text-danger">{errors.email[0]}</span>}
                    </div>
                </div>
                <div className="card-footer">
                    <button type="submit" className="btn btn-info btn-flat" disabled={saving}>
                        <i className="fas fa-save fa-lg mr-1"></i>
                        {saving ? (trans.saving ?? 'Saving…') : (trans.update ?? 'Update')}
                    </button>
                </div>
            </form>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Personal information section
// ─────────────────────────────────────────────────────────────────────────────

function InformationCard({ initial, updateUrl, trans }) {
    const fields = [
        'personnal_phone_number', 'born_date', 'desc', 'nationality',
        'gender', 'marital_status', 'ssn_num', 'nic_num',
        'driving_license', 'driving_license_exp_date',
        'address1', 'address2', 'city', 'country',
        'province', 'postal_code', 'home_phone', 'mobile_phone',
        'private_email', 'custom1', 'custom2', 'custom3', 'custom4',
    ];
    const init = {};
    fields.forEach(f => { init[f] = initial[f] ?? ''; });

    const [form, setForm]       = useState(init);
    const [errors, setErrors]   = useState({});
    const [success, setSuccess] = useState('');
    const [saving, setSaving]   = useState(false);

    const set = (field) => (e) => setForm(f => ({ ...f, [field]: e.target.value }));

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});
        setSuccess('');
        setSaving(true);
        const res = await apiFetch(updateUrl, { method: 'PUT', body: JSON.stringify(form) });
        setSaving(false);
        if (res.ok) {
            setSuccess(trans.success_information ?? 'Information updated successfully');
        } else {
            const data = await res.json();
            setErrors(data.errors ?? {});
        }
    }

    return (
        <div className="card card-warning">
            <div className="card-header">
                <h3 className="card-title">{trans.personnal_information ?? 'Personal Information'}</h3>
                <div className="card-tools">
                    <button type="button" className="btn btn-tool" data-card-widget="maximize"><i className="fas fa-expand"></i></button>
                </div>
            </div>
            <form onSubmit={handleSubmit}>
                <div className="card-body">
                    <Alert type="success" message={success} onDismiss={() => setSuccess('')} />

                    <div className="row">
                        <div className="form-group col-md-3">
                            <label>{trans.personnal_phone ?? 'Personal Phone'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-phone"></i></span></div>
                                <input type="text" className="form-control" value={form.personnal_phone_number} onChange={set('personnal_phone_number')} />
                            </div>
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.personnal_email ?? 'Personal Email'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-envelope"></i></span></div>
                                <input type="text" className="form-control" value={form.private_email} onChange={set('private_email')} />
                            </div>
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.born_date ?? 'Date of Birth'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="far fa-calendar-alt"></i></span></div>
                                <input type="date" className="form-control" value={form.born_date} onChange={set('born_date')} />
                            </div>
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.nationality ?? 'Nationality'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-flag"></i></span></div>
                                <input type="text" className="form-control" value={form.nationality} onChange={set('nationality')} />
                            </div>
                        </div>
                    </div>

                    <hr />

                    <div className="row">
                        <div className="form-group col-md-3">
                            <label>{trans.gender ?? 'Gender'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-tags"></i></span></div>
                                <select className="form-control" value={form.gender} onChange={set('gender')}>
                                    <option value="">{trans.select_gender ?? '— Select —'}</option>
                                    <option value="1">{trans.male ?? 'Male'}</option>
                                    <option value="2">{trans.female ?? 'Female'}</option>
                                    <option value="3">{trans.other ?? 'Other'}</option>
                                </select>
                            </div>
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.marital_status ?? 'Marital Status'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="fas fa-tags"></i></span></div>
                                <select className="form-control" value={form.marital_status} onChange={set('marital_status')}>
                                    <option value="">{trans.select_marital_status ?? '— Select —'}</option>
                                    <option value="1">{trans.married ?? 'Married'}</option>
                                    <option value="2">{trans.single ?? 'Single'}</option>
                                    <option value="3">{trans.divorced ?? 'Divorced'}</option>
                                    <option value="4">{trans.widowed ?? 'Widowed'}</option>
                                    <option value="5">{trans.other ?? 'Other'}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <hr />

                    <div className="row">
                        <div className="form-group col-md-3">
                            <label>{trans.driving_license ?? 'Driving License'}</label>
                            <input type="text" className="form-control" value={form.driving_license} onChange={set('driving_license')} />
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.driving_license_exp_date ?? 'License Expiry'}</label>
                            <div className="input-group">
                                <div className="input-group-prepend"><span className="input-group-text"><i className="far fa-calendar-alt"></i></span></div>
                                <input type="date" className="form-control" value={form.driving_license_exp_date} onChange={set('driving_license_exp_date')} />
                            </div>
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.ssn_num ?? 'SSN'}</label>
                            <input type="text" className="form-control" value={form.ssn_num} onChange={set('ssn_num')} />
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.nic_num ?? 'NIC'}</label>
                            <input type="text" className="form-control" value={form.nic_num} onChange={set('nic_num')} />
                        </div>
                    </div>

                    <hr />
                    <div className="row"><label>{trans.adress_section ?? 'Address'}</label></div>
                    <hr />

                    <div className="row">
                        <div className="form-group col-md-6">
                            <label>{trans.adress ?? 'Address'} 1</label>
                            <input type="text" className="form-control" value={form.address1} onChange={set('address1')} />
                        </div>
                        <div className="form-group col-md-6">
                            <label>{trans.adress ?? 'Address'} 2</label>
                            <input type="text" className="form-control" value={form.address2} onChange={set('address2')} />
                        </div>
                    </div>
                    <div className="row">
                        <div className="form-group col-md-3">
                            <label>{trans.city ?? 'City'}</label>
                            <input type="text" className="form-control" value={form.city} onChange={set('city')} />
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.postal_code ?? 'Postal Code'}</label>
                            <input type="text" className="form-control" value={form.postal_code} onChange={set('postal_code')} />
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.province ?? 'Province'}</label>
                            <input type="text" className="form-control" value={form.province} onChange={set('province')} />
                        </div>
                        <div className="form-group col-md-3">
                            <label>{trans.country ?? 'Country'}</label>
                            <input type="text" className="form-control" value={form.country} onChange={set('country')} />
                        </div>
                    </div>

                    <hr />
                    <div className="row"><label>{trans.custom_section ?? 'Custom Fields'}</label></div>
                    <hr />

                    <div className="row">
                        {['custom1', 'custom2', 'custom3', 'custom4'].map((f, i) => (
                            <div key={f} className="form-group col-md-3">
                                <label>{trans.custom ?? 'Custom'} {i + 1}</label>
                                <input type="text" className="form-control" value={form[f]} onChange={set(f)} />
                            </div>
                        ))}
                    </div>

                    <hr />
                    <div className="row">
                        <div className="col-12">
                            <label>{trans.about_you ?? 'About you'}</label>
                            <textarea className="form-control" rows="3" value={form.desc} onChange={set('desc')} placeholder="..."></textarea>
                        </div>
                    </div>
                </div>
                <div className="card-footer">
                    <button type="submit" className="btn btn-info btn-flat" disabled={saving}>
                        <i className="fas fa-save fa-lg mr-1"></i>
                        {saving ? (trans.saving ?? 'Saving…') : (trans.update ?? 'Update')}
                    </button>
                </div>
            </form>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Root
// ─────────────────────────────────────────────────────────────────────────────

export default function UserProfilePage({ initial, endpoints, trans }) {
    return (
        <>
            <AccountCard     initial={initial} updateUrl={endpoints.updateAccount}     trans={trans} />
            <InformationCard initial={initial} updateUrl={endpoints.updateInformation} trans={trans} />
        </>
    );
}
