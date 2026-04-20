import React, { useState, useRef } from 'react';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

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
    const json = await res.json();
    if (!res.ok) {
        const msg = json.message
            ?? (json.errors ? Object.values(json.errors).flat().join(' ') : 'Erreur serveur');
        throw new Error(msg);
    }
    return json;
}

async function apiFetchForm(url, formData) {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept':       'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: formData,
    });
    const json = await res.json();
    if (!res.ok) {
        const msg = json.message
            ?? (json.errors ? Object.values(json.errors).flat().join(' ') : 'Erreur serveur');
        throw new Error(msg);
    }
    return json;
}

// ---------------------------------------------------------------------------
// Steps metadata
// ---------------------------------------------------------------------------

const STEPS = [
    { id: 1, title: 'Société',             icon: 'fa-building' },
    { id: 2, title: 'TVA',                 icon: 'fa-percent' },
    { id: 3, title: 'Cond. paiement',      icon: 'fa-calendar-alt' },
    { id: 4, title: 'Moyen paiement',      icon: 'fa-credit-card' },
    { id: 5, title: 'Livraison',           icon: 'fa-truck' },
    { id: 6, title: 'Unité',               icon: 'fa-ruler' },
    { id: 7, title: 'Rôle & Droits',       icon: 'fa-user-shield' },
    { id: 8, title: 'Budget prévisionnel', icon: 'fa-chart-bar' },
];

// ---------------------------------------------------------------------------
// Field / Input helpers
// ---------------------------------------------------------------------------

function Field({ label, required, children, hint }) {
    return (
        <div className="form-group mb-3">
            <label className="font-weight-bold mb-1">
                {label}{required && <span className="text-danger ml-1">*</span>}
            </label>
            {children}
            {hint && <small className="text-muted">{hint}</small>}
        </div>
    );
}

function Input({ value, onChange, placeholder, type = 'text', min }) {
    return (
        <input
            type={type}
            className="form-control"
            value={value}
            min={min}
            onChange={e => onChange(e.target.value)}
            placeholder={placeholder}
        />
    );
}

// ---------------------------------------------------------------------------
// Step forms
// ---------------------------------------------------------------------------

function LogoUpload({ preview, onFile, onRemove }) {
    const inputRef = useRef(null);
    const [drag, setDrag]  = useState(false);

    function handleChange(e) {
        const file = e.target.files?.[0];
        if (file) onFile(file);
    }

    function handleDrop(e) {
        e.preventDefault();
        setDrag(false);
        const file = e.dataTransfer.files?.[0];
        if (file && file.type.startsWith('image/')) onFile(file);
    }

    return (
        <div className="form-group mb-4">
            <label className="font-weight-bold mb-2">
                Logo de l&apos;entreprise <small className="text-muted font-weight-normal">(optionnel — PNG, JPG, SVG, max 2 Mo)</small>
            </label>
            <div
                onClick={() => !preview && inputRef.current?.click()}
                onDragOver={e => { e.preventDefault(); setDrag(true); }}
                onDragLeave={() => setDrag(false)}
                onDrop={handleDrop}
                className={`border rounded d-flex align-items-center justify-content-center ${drag ? 'border-primary bg-light' : 'border-dashed'}`}
                style={{
                    minHeight: 110,
                    cursor: preview ? 'default' : 'pointer',
                    borderStyle: drag ? 'solid' : 'dashed',
                    borderColor: drag ? '#007bff' : '#ced4da',
                    background: drag ? '#e8f0fe' : '#f8f9fa',
                    transition: 'all 0.2s',
                    position: 'relative',
                }}
            >
                {preview ? (
                    <>
                        <img
                            src={preview}
                            alt="Logo"
                            style={{ maxHeight: 90, maxWidth: '100%', objectFit: 'contain', padding: 8 }}
                        />
                        <button
                            type="button"
                            className="btn btn-sm btn-danger"
                            style={{ position: 'absolute', top: 6, right: 6 }}
                            onClick={e => { e.stopPropagation(); onRemove(); }}
                            title="Supprimer le logo"
                        >
                            <i className="fas fa-times" />
                        </button>
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            style={{ position: 'absolute', bottom: 6, right: 6, fontSize: '0.75rem' }}
                            onClick={e => { e.stopPropagation(); inputRef.current?.click(); }}
                        >
                            Changer
                        </button>
                    </>
                ) : (
                    <div className="text-center text-muted py-2">
                        <i className="fas fa-image fa-2x mb-2 d-block" style={{ color: '#adb5bd' }} />
                        <span style={{ fontSize: '0.85rem' }}>Cliquez ou déposez votre logo ici</span>
                    </div>
                )}
            </div>
            <input
                ref={inputRef}
                type="file"
                accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp"
                style={{ display: 'none' }}
                onChange={handleChange}
            />
        </div>
    );
}

function StepCompany({ data, onChange, logoPreview, onLogoFile, onLogoRemove }) {
    const f = key => val => onChange({ ...data, [key]: val });
    return (
        <>
            <LogoUpload preview={logoPreview} onFile={onLogoFile} onRemove={onLogoRemove} />
            <Field label="Nom de la société" required>
                <Input value={data.name}    onChange={f('name')}    placeholder="Ex : Dupont Industrie" />
            </Field>
            <div className="row">
                <div className="col-md-8">
                    <Field label="Adresse">
                        <Input value={data.address} onChange={f('address')} placeholder="15 rue des Acacias" />
                    </Field>
                </div>
                <div className="col-md-4">
                    <Field label="Code postal">
                        <Input value={data.zipcode} onChange={f('zipcode')} placeholder="75001" />
                    </Field>
                </div>
            </div>
            <div className="row">
                <div className="col-md-6">
                    <Field label="Ville">
                        <Input value={data.city}  onChange={f('city')}  placeholder="Paris" />
                    </Field>
                </div>
                <div className="col-md-6">
                    <Field label="Téléphone">
                        <Input value={data.phone} onChange={f('phone')} placeholder="+33 1 23 45 67 89" />
                    </Field>
                </div>
            </div>
            <div className="row">
                <div className="col-md-6">
                    <Field label="Email">
                        <Input value={data.mail}    onChange={f('mail')}    type="email" placeholder="contact@societe.fr" />
                    </Field>
                </div>
                <div className="col-md-3">
                    <Field label="SIREN">
                        <Input value={data.siren}   onChange={f('siren')}   placeholder="123 456 789" />
                    </Field>
                </div>
                <div className="col-md-3">
                    <Field label="N° TVA intracom.">
                        <Input value={data.vat_num} onChange={f('vat_num')} placeholder="FR12345678901" />
                    </Field>
                </div>
            </div>
        </>
    );
}

function StepVat({ data, onChange }) {
    const f = key => val => onChange({ ...data, [key]: val });
    return (
        <>
            <p className="text-muted mb-3">
                TVA appliquée par défaut sur vos devis et commandes.
            </p>
            <div className="row">
                <div className="col-md-3">
                    <Field label="Code" required>
                        <Input value={data.code}  onChange={f('code')}  placeholder="TVA20" />
                    </Field>
                </div>
                <div className="col-md-6">
                    <Field label="Libellé" required>
                        <Input value={data.label} onChange={f('label')} placeholder="TVA 20%" />
                    </Field>
                </div>
                <div className="col-md-3">
                    <Field label="Taux (%)" required>
                        <Input value={data.rate}  onChange={f('rate')}  type="number" placeholder="20" />
                    </Field>
                </div>
            </div>
        </>
    );
}

function StepPaymentCondition({ data, onChange }) {
    const f = key => val => onChange({ ...data, [key]: val });
    return (
        <>
            <p className="text-muted mb-3">Condition de paiement par défaut.</p>
            <div className="row">
                <div className="col-md-3">
                    <Field label="Code" required>
                        <Input value={data.code}  onChange={f('code')}  placeholder="NET30" />
                    </Field>
                </div>
                <div className="col-md-9">
                    <Field label="Libellé" required>
                        <Input value={data.label} onChange={f('label')} placeholder="Net 30 jours" />
                    </Field>
                </div>
            </div>
            <div className="row">
                <div className="col-md-4">
                    <Field label="Nombre de mois" required>
                        <Input value={data.months} onChange={f('months')} type="number" placeholder="0" />
                    </Field>
                </div>
                <div className="col-md-4">
                    <Field label="Nombre de jours" required>
                        <Input value={data.days}   onChange={f('days')}   type="number" placeholder="30" />
                    </Field>
                </div>
                <div className="col-md-4">
                    <Field label="Fin de mois">
                        <div className="custom-control custom-switch mt-2">
                            <input
                                type="checkbox"
                                className="custom-control-input"
                                id="month_end"
                                checked={data.month_end === 1}
                                onChange={e => onChange({ ...data, month_end: e.target.checked ? 1 : 0 })}
                            />
                            <label className="custom-control-label" htmlFor="month_end">Fin de mois</label>
                        </div>
                    </Field>
                </div>
            </div>
        </>
    );
}

function StepPaymentMethod({ data, onChange }) {
    const f = key => val => onChange({ ...data, [key]: val });
    return (
        <>
            <p className="text-muted mb-3">Moyen de paiement par défaut.</p>
            <div className="row">
                <div className="col-md-3">
                    <Field label="Code" required>
                        <Input value={data.code}  onChange={f('code')}  placeholder="VIR" />
                    </Field>
                </div>
                <div className="col-md-9">
                    <Field label="Libellé" required>
                        <Input value={data.label} onChange={f('label')} placeholder="Virement bancaire" />
                    </Field>
                </div>
            </div>
        </>
    );
}

function StepDelivery({ data, onChange }) {
    const f = key => val => onChange({ ...data, [key]: val });
    return (
        <>
            <p className="text-muted mb-3">Mode de livraison par défaut.</p>
            <div className="row">
                <div className="col-md-3">
                    <Field label="Code" required>
                        <Input value={data.code}  onChange={f('code')}  placeholder="EXW" />
                    </Field>
                </div>
                <div className="col-md-9">
                    <Field label="Libellé" required>
                        <Input value={data.label} onChange={f('label')} placeholder="Ex Works — départ usine" />
                    </Field>
                </div>
            </div>
        </>
    );
}

const UNIT_TYPES = [
    { value: 1, label: 'Masse' },
    { value: 2, label: 'Longueur' },
    { value: 3, label: 'Surface' },
    { value: 4, label: 'Volume' },
    { value: 5, label: 'Autre' },
];

function StepUnit({ data, onChange }) {
    const f = key => val => onChange({ ...data, [key]: val });
    return (
        <>
            <p className="text-muted mb-3">Unité de mesure par défaut sur les lignes devis/commande.</p>
            <div className="row">
                <div className="col-md-3">
                    <Field label="Code" required>
                        <Input value={data.code}  onChange={f('code')}  placeholder="U" />
                    </Field>
                </div>
                <div className="col-md-5">
                    <Field label="Libellé" required>
                        <Input value={data.label} onChange={f('label')} placeholder="Unité" />
                    </Field>
                </div>
                <div className="col-md-4">
                    <Field label="Type" required>
                        <select
                            className="form-control"
                            value={data.type}
                            onChange={e => onChange({ ...data, type: parseInt(e.target.value, 10) })}
                        >
                            {UNIT_TYPES.map(t => (
                                <option key={t.value} value={t.value}>{t.label}</option>
                            ))}
                        </select>
                    </Field>
                </div>
            </div>
        </>
    );
}

function StepRole({ data, onChange, existingRoles, availablePermissions }) {
    const hasRoles = existingRoles.length > 0;
    const allNames = availablePermissions.map(p => p.name);
    const allChecked = allNames.length > 0 && allNames.every(n => data.permissions.includes(n));

    function togglePermission(name) {
        const next = data.permissions.includes(name)
            ? data.permissions.filter(n => n !== name)
            : [...data.permissions, name];
        onChange({ ...data, permissions: next });
    }

    function toggleAll() {
        onChange({ ...data, permissions: allChecked ? [] : allNames });
    }

    return (
        <>
            {/* Rôle */}
            {hasRoles ? (
                <>
                    <Field label="Sélectionner un rôle existant ou en créer un" required>
                        <select
                            className="form-control"
                            value={data.role_id ?? ''}
                            onChange={e => onChange({
                                ...data,
                                role_id: e.target.value ? parseInt(e.target.value, 10) : null,
                                role_name: '',
                            })}
                        >
                            <option value="">— Créer un nouveau rôle —</option>
                            {existingRoles.map(r => (
                                <option key={r.id} value={r.id}>{r.name}</option>
                            ))}
                        </select>
                    </Field>
                    {!data.role_id && (
                        <Field label="Nom du nouveau rôle" required>
                            <Input
                                value={data.role_name}
                                onChange={val => onChange({ ...data, role_name: val })}
                                placeholder="Ex : Administrateur"
                            />
                        </Field>
                    )}
                </>
            ) : (
                <Field label="Nom du rôle à créer" required>
                    <Input
                        value={data.role_name}
                        onChange={val => onChange({ ...data, role_name: val, role_id: null })}
                        placeholder="Ex : Administrateur"
                    />
                </Field>
            )}

            {/* Permissions */}
            {availablePermissions.length > 0 && (
                <div className="mt-3">
                    <div className="d-flex justify-content-between align-items-center mb-2">
                        <label className="font-weight-bold mb-0">
                            Permissions attribuées à ce rôle
                        </label>
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            onClick={toggleAll}
                        >
                            {allChecked ? 'Tout décocher' : 'Tout cocher'}
                        </button>
                    </div>
                    <div
                        className="border rounded p-3"
                        style={{ maxHeight: 220, overflowY: 'auto', background: '#f8f9fa' }}
                    >
                        <div className="row">
                            {availablePermissions.map(p => (
                                <div key={p.name} className="col-md-6 mb-1">
                                    <div className="custom-control custom-checkbox">
                                        <input
                                            type="checkbox"
                                            className="custom-control-input"
                                            id={`perm-${p.name}`}
                                            checked={data.permissions.includes(p.name)}
                                            onChange={() => togglePermission(p.name)}
                                        />
                                        <label
                                            className="custom-control-label"
                                            htmlFor={`perm-${p.name}`}
                                            style={{ fontSize: '0.85rem' }}
                                        >
                                            {p.label}
                                        </label>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                    <small className="text-muted">
                        {data.permissions.length} / {availablePermissions.length} permission(s) sélectionnée(s)
                    </small>
                </div>
            )}
        </>
    );
}

const MONTHS = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre',
];

function StepEstimatedBudget({ data, onChange }) {
    const f = key => val => onChange({ ...data, [key]: val });
    return (
        <>
            <p className="text-muted mb-3">
                Budget prévisionnel mensuel pour l&apos;année en cours — utilisé dans les graphiques du tableau de bord.
            </p>
            <div className="row mb-3">
                <div className="col-md-3">
                    <Field label="Année" required>
                        <Input value={data.year} onChange={f('year')} type="number" placeholder={String(new Date().getFullYear())} />
                    </Field>
                </div>
            </div>
            <div className="row">
                {MONTHS.map((month, i) => {
                    const key = `amount${i + 1}`;
                    return (
                        <div key={key} className="col-6 col-md-3 mb-2">
                            <label className="mb-1" style={{ fontSize: '0.8rem', fontWeight: 600 }}>
                                {month}
                            </label>
                            <div className="input-group input-group-sm">
                                <input
                                    type="number"
                                    className="form-control"
                                    min="0"
                                    step="0.01"
                                    value={data[key]}
                                    onChange={e => onChange({ ...data, [key]: e.target.value })}
                                    placeholder="0"
                                />
                                <div className="input-group-append">
                                    <span className="input-group-text">€</span>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </>
    );
}

// ---------------------------------------------------------------------------
// Donut progress (remplace le rond bleu devant le titre)
// ---------------------------------------------------------------------------

function StepDonut({ current, total }) {
    const size = 44;
    const cx   = size / 2;
    const cy   = size / 2;
    const r    = 17;
    const circ = 2 * Math.PI * r;
    const offset = circ * (1 - current / total);

    return (
        <svg
            width={size}
            height={size}
            viewBox={`0 0 ${size} ${size}`}
            style={{ flexShrink: 0, marginRight: 12 }}
            aria-hidden="true"
        >
            {/* Piste de fond */}
            <circle cx={cx} cy={cy} r={r} fill="none" stroke="#e9ecef" strokeWidth="4" />
            {/* Arc de progression — démarre à 12h */}
            <circle
                cx={cx} cy={cy} r={r}
                fill="none"
                stroke="#007bff"
                strokeWidth="4"
                strokeLinecap="round"
                strokeDasharray={circ}
                strokeDashoffset={offset}
                transform={`rotate(-90 ${cx} ${cy})`}
                style={{ transition: 'stroke-dashoffset 0.45s ease' }}
            />
            {/* Numéro d'étape au centre */}
            <text
                x={cx} y={cy}
                textAnchor="middle"
                dominantBaseline="central"
                fontSize="13"
                fontWeight="700"
                fill="#1a1a2e"
            >
                {current}
            </text>
        </svg>
    );
}

// ---------------------------------------------------------------------------
// Progress bar
// ---------------------------------------------------------------------------

function ProgressBar({ currentStep, total }) {
    const pct = Math.round(((currentStep - 1) / (total - 1)) * 100);
    return (
        <div className="mb-4">
            <div className="d-flex justify-content-between mb-1">
                {STEPS.map(s => (
                    <div
                        key={s.id}
                        className="text-center flex-fill"
                        style={{ fontSize: '0.65rem', opacity: s.id <= currentStep ? 1 : 0.4 }}
                    >
                        <div
                            className="mx-auto d-flex align-items-center justify-content-center rounded-circle mb-1"
                            style={{
                                width: 26, height: 26,
                                background: s.id < currentStep ? '#28a745' : s.id === currentStep ? '#007bff' : '#dee2e6',
                                color: s.id <= currentStep ? '#fff' : '#6c757d',
                                fontWeight: 'bold',
                                fontSize: '0.7rem',
                            }}
                        >
                            {s.id < currentStep
                                ? <i className="fas fa-check" style={{ fontSize: '0.6rem' }} />
                                : s.id}
                        </div>
                        <span className="d-none d-lg-block" style={{ lineHeight: 1.2 }}>{s.title}</span>
                    </div>
                ))}
            </div>
            <div className="progress" style={{ height: 4 }}>
                <div
                    className="progress-bar bg-primary"
                    style={{ width: `${pct}%`, transition: 'width 0.3s ease' }}
                />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Defaults
// ---------------------------------------------------------------------------

const DEFAULT_COMPANY  = { name: '', address: '', city: '', zipcode: '', phone: '', mail: '', siren: '', vat_num: '' };
const DEFAULT_VAT      = { code: 'TVA20', label: 'TVA 20%', rate: '20' };
const DEFAULT_PC       = { code: 'NET30', label: 'Net 30 jours', months: 0, days: 30, month_end: 0 };
const DEFAULT_PM       = { code: 'VIR',   label: 'Virement bancaire' };
const DEFAULT_DEL      = { code: 'EXW',   label: 'Ex Works — départ usine' };
const DEFAULT_UNIT     = { code: 'U',     label: 'Unité', type: 5 };
const DEFAULT_BUDGET   = {
    year: new Date().getFullYear(),
    amount1: 0, amount2: 0, amount3: 0, amount4: 0,
    amount5: 0, amount6: 0, amount7: 0, amount8: 0,
    amount9: 0, amount10: 0, amount11: 0, amount12: 0,
};

// ---------------------------------------------------------------------------
// Main wizard
// ---------------------------------------------------------------------------

export default function SetupWizard({ endpoints, initial }) {
    const [step, setStep]       = useState(initial.firstStep ?? 1);
    const [loading, setLoading] = useState(false);
    const [error, setError]     = useState(null);

    const availablePermissions = initial.availablePermissions ?? [];

    const [logoFile,    setLogoFile]    = useState(null);
    const [logoPreview, setLogoPreview] = useState(initial.company?.logo_url ?? null);

    function handleLogoFile(file) {
        setLogoFile(file);
        setLogoPreview(URL.createObjectURL(file));
    }

    function handleLogoRemove() {
        setLogoFile(null);
        setLogoPreview(null);
    }

    const [company, setCompany] = useState({ ...DEFAULT_COMPANY,  ...initial.company });
    const [vat,     setVat]     = useState({ ...DEFAULT_VAT,      ...initial.vat });
    const [pc,      setPc]      = useState({ ...DEFAULT_PC,       ...initial.paymentCondition });
    const [pm,      setPm]      = useState({ ...DEFAULT_PM,       ...initial.paymentMethod });
    const [del,     setDel]     = useState({ ...DEFAULT_DEL,      ...initial.delivery });
    const [unit,    setUnit]    = useState({ ...DEFAULT_UNIT,      ...initial.unit });
    const [role,    setRole]    = useState({
        // Si l'user a déjà un rôle → le pré-sélectionner,
        // sinon pré-sélectionner le premier rôle existant s'il y en a un
        role_id:     initial.userRoleId ?? (initial.roles?.length > 0 ? initial.roles[0].id : null),
        role_name:   (initial.roles?.length > 0) ? '' : 'Administrateur',
        permissions: initial.userPermissions ?? availablePermissions.map(p => p.name),
    });
    const [budget, setBudget]   = useState({
        ...DEFAULT_BUDGET,
        ...(initial.estimatedBudget ?? {}),
        year: initial.currentYear ?? new Date().getFullYear(),
    });

    function getPayload() {
        switch (step) {
            case 1: return company;
            case 2: return vat;
            case 3: return pc;
            case 4: return pm;
            case 5: return del;
            case 6: return unit;
            case 7: return role;
            case 8: return budget;
        }
    }

    function validate() {
        switch (step) {
            case 1:
                if (!company.name?.trim()) return 'Le nom de la société est obligatoire.';
                break;
            case 2:
                if (!vat.code?.trim() || !vat.label?.trim()) return 'Code et libellé sont obligatoires.';
                if (isNaN(parseFloat(vat.rate))) return 'Le taux TVA doit être un nombre.';
                break;
            case 3:
                if (!pc.code?.trim() || !pc.label?.trim()) return 'Code et libellé sont obligatoires.';
                break;
            case 4:
                if (!pm.code?.trim() || !pm.label?.trim()) return 'Code et libellé sont obligatoires.';
                break;
            case 5:
                if (!del.code?.trim() || !del.label?.trim()) return 'Code et libellé sont obligatoires.';
                break;
            case 6:
                if (!unit.code?.trim() || !unit.label?.trim()) return 'Code et libellé sont obligatoires.';
                break;
            case 7:
                if (!role.role_id && !role.role_name?.trim()) return 'Sélectionnez ou créez un rôle.';
                break;
            case 8:
                if (!budget.year || isNaN(parseInt(budget.year))) return 'L\'année est obligatoire.';
                break;
        }
        return null;
    }

    async function handleNext() {
        const err = validate();
        if (err) { setError(err); return; }

        setLoading(true);
        setError(null);
        try {
            if (step === 1) {
                const fd = new FormData();
                Object.entries(company).forEach(([k, v]) => {
                    if (k !== 'logo_url') fd.append(k, v ?? '');
                });
                if (logoFile) fd.append('logo', logoFile);
                await apiFetchForm(endpoints.step1, fd);
            } else {
                await apiFetch(endpoints[`step${step}`], getPayload());
            }
            if (step >= STEPS.length) {
                window.location.href = initial.dashboardUrl;
            } else {
                setStep(s => s + 1);
            }
        } catch (e) {
            setError(e.message);
        } finally {
            setLoading(false);
        }
    }

    function handlePrev() {
        setError(null);
        setStep(s => s - 1);
    }

    const isLast = step === STEPS.length;

    return (
        <div
            className="min-vh-100 d-flex align-items-center justify-content-center py-5"
            style={{ background: 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)' }}
        >
            <div className="container" style={{ maxWidth: 760 }}>

                {/* Header */}
                <div className="text-center mb-4">
                    <h2 className="text-white font-weight-bold mb-1">
                        <i className="fas fa-cogs mr-2" />
                        Configuration initiale
                    </h2>
                    <p className="text-white-50">
                        Étape {step} sur {STEPS.length} — {STEPS[step - 1].title}
                    </p>
                </div>

                {/* Card */}
                <div className="card shadow-lg border-0" style={{ borderRadius: 12 }}>
                    <div className="card-body p-4 p-md-5">

                        <ProgressBar currentStep={step} total={STEPS.length} />

                        {/* Step title */}
                        <h4 className="mb-4 d-flex align-items-center" style={{ color: '#1a1a2e' }}>
                            <StepDonut current={step} total={STEPS.length} />
                            {STEPS[step - 1].title}
                        </h4>

                        {/* Error */}
                        {error && (
                            <div className="alert alert-danger d-flex align-items-center" role="alert">
                                <i className="fas fa-exclamation-circle mr-2" />
                                {error}
                            </div>
                        )}

                        {/* Step content */}
                        {step === 1 && (
                            <StepCompany
                                data={company}
                                onChange={setCompany}
                                logoPreview={logoPreview}
                                onLogoFile={handleLogoFile}
                                onLogoRemove={handleLogoRemove}
                            />
                        )}
                        {step === 2 && <StepVat              data={vat}     onChange={setVat} />}
                        {step === 3 && <StepPaymentCondition data={pc}      onChange={setPc} />}
                        {step === 4 && <StepPaymentMethod    data={pm}      onChange={setPm} />}
                        {step === 5 && <StepDelivery         data={del}     onChange={setDel} />}
                        {step === 6 && <StepUnit             data={unit}    onChange={setUnit} />}
                        {step === 7 && (
                            <StepRole
                                data={role}
                                onChange={setRole}
                                existingRoles={initial.roles ?? []}
                                availablePermissions={availablePermissions}
                            />
                        )}
                        {step === 8 && <StepEstimatedBudget data={budget}  onChange={setBudget} />}

                        {/* Navigation */}
                        <div className="d-flex justify-content-between mt-4 pt-3 border-top">
                            <button
                                className="btn btn-outline-secondary"
                                onClick={handlePrev}
                                disabled={step === 1 || loading}
                            >
                                <i className="fas fa-arrow-left mr-1" /> Précédent
                            </button>
                            <button
                                className="btn btn-primary px-4"
                                onClick={handleNext}
                                disabled={loading}
                            >
                                {loading && (
                                    <span className="spinner-border spinner-border-sm mr-2" role="status" />
                                )}
                                {isLast
                                    ? <><i className="fas fa-check mr-1" /> Terminer</>
                                    : <>Suivant <i className="fas fa-arrow-right ml-1" /></>}
                            </button>
                        </div>
                    </div>
                </div>

                <p className="text-center text-white-50 mt-3" style={{ fontSize: '0.8rem' }}>
                    Ces paramètres sont modifiables à tout moment dans les réglages.
                </p>
            </div>
        </div>
    );
}
