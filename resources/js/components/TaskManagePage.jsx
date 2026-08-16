import React, { useState, useEffect, useCallback, useRef } from 'react';
import { formatQty } from '../utils';

// ─────────────────────────────────────────────────────────────────────────────
// Constants
// ─────────────────────────────────────────────────────────────────────────────

const DRAWER = { TECHCUT: 'techcut', BOM: 'bom', SUBASSEMBLY: 'subassembly' };

const EMPTY_TECHCUT = {
    ordre: 10, label: '', methods_services_id: '', type: 1,
    methods_tools_id: '', seting_time: 0, unit_time: 0,
    unit_cost: 0, unit_price: 0, start_date: '', end_date: '',
    methods_units_id: '',
};
const EMPTY_BOM = {
    ordre: 10, label: '', methods_services_id: '', type: 2,
    component_id: '', qty: 1, unit_cost: 0, unit_price: 0,
    methods_units_id: '',
};
const EMPTY_SUBASSEMBLY = { ordre: 10, child_id: '', qty: 1, unit_price: 0 };

// ─────────────────────────────────────────────────────────────────────────────
// HTTP helper
// ─────────────────────────────────────────────────────────────────────────────

function apiFetch(url, options = {}) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const isFormData = options.body instanceof FormData;
    return fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            ...(options.headers ?? {}),
        },
        ...options,
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// Drawer shell (shared)
// ─────────────────────────────────────────────────────────────────────────────

function Drawer({ open, title, onClose, children, footer }) {
    // Lock body scroll when open
    useEffect(() => {
        document.body.style.overflow = open ? 'hidden' : '';
        return () => { document.body.style.overflow = ''; };
    }, [open]);

    return (
        <>
            {/* Backdrop */}
            <div
                onClick={onClose}
                style={{
                    position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)',
                    zIndex: 1049,
                    opacity: open ? 1 : 0,
                    pointerEvents: open ? 'all' : 'none',
                    transition: 'opacity 0.2s ease',
                }}
            />
            {/* Panel */}
            <div
                style={{
                    position: 'fixed', top: 0, right: 0, bottom: 0,
                    width: 500, maxWidth: '95vw',
                    background: '#fff', zIndex: 1050,
                    display: 'flex', flexDirection: 'column',
                    transform: open ? 'translateX(0)' : 'translateX(100%)',
                    transition: 'transform 0.25s ease',
                    boxShadow: '-4px 0 16px rgba(0,0,0,0.15)',
                }}
            >
                {/* Header */}
                <div style={{ padding: '14px 20px', borderBottom: '1px solid #dee2e6', display: 'flex', alignItems: 'center', justifyContent: 'space-between', background: '#343a40' }}>
                    <h6 style={{ margin: 0, color: '#fff', fontWeight: 600 }}>{title}</h6>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', color: '#adb5bd', fontSize: 20, cursor: 'pointer', lineHeight: 1 }}>×</button>
                </div>
                {/* Scrollable body */}
                <div style={{ flex: 1, overflowY: 'auto', padding: '20px' }}>
                    {children}
                </div>
                {/* Footer */}
                {footer && (
                    <div style={{ padding: '12px 20px', borderTop: '1px solid #dee2e6', display: 'flex', justifyContent: 'flex-end', gap: 8, background: '#f8f9fa' }}>
                        {footer}
                    </div>
                )}
            </div>
        </>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Inline field helpers
// ─────────────────────────────────────────────────────────────────────────────

function Field({ label, error, children }) {
    return (
        <div style={{ marginBottom: 14 }}>
            <label style={{ display: 'block', fontSize: 12, fontWeight: 600, marginBottom: 4, color: '#495057' }}>{label}</label>
            {children}
            {error && <div style={{ color: '#dc3545', fontSize: 11, marginTop: 2 }}>{error}</div>}
        </div>
    );
}

function Input({ error, ...props }) {
    return (
        <input
            className={`form-control form-control-sm${error ? ' is-invalid' : ''}`}
            {...props}
        />
    );
}

function Select({ error, children, ...props }) {
    return (
        <select className={`form-control form-control-sm${error ? ' is-invalid' : ''}`} {...props}>
            {children}
        </select>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Cost formula display
// ─────────────────────────────────────────────────────────────────────────────

function CostFormula({ setigTime, unitTime, lineQty, hourlyRate, currency }) {
    if (!hourlyRate) return null;
    const cost = ((parseFloat(setigTime) || 0) / (parseFloat(lineQty) || 1) + (parseFloat(unitTime) || 0)) * parseFloat(hourlyRate);
    return (
        <small style={{ color: '#6c757d', display: 'block', marginTop: 2 }}>
            ({setigTime || 0} h / {lineQty} + {unitTime || 0} h) × {hourlyRate} {currency}/h = <strong>{cost.toFixed(2)} {currency}</strong>
        </small>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// TechCut drawer form
// ─────────────────────────────────────────────────────────────────────────────

function TechCutForm({ editingItem, selectData, endpoints, context, lineQty, onSaved, onClose }) {
    const [form, setForm]     = useState(EMPTY_TECHCUT);
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);
    const [toolWarning, setToolWarning] = useState(false);
    const formRef = useRef();

    // service metadata (margin + hourly_rate) for live cost calc
    const selectedService = selectData.services_techcut?.find(s => s.id === parseInt(form.methods_services_id));

    useEffect(() => {
        if (editingItem) {
            setForm({
                ordre: editingItem.ordre,
                label: editingItem.label,
                methods_services_id: editingItem.methods_services_id ?? '',
                type: editingItem.type ?? 1,
                methods_tools_id: editingItem.methods_tools_id ?? '',
                seting_time: editingItem.seting_time ?? 0,
                unit_time: editingItem.unit_time ?? 0,
                unit_cost: editingItem.unit_cost ?? 0,
                unit_price: editingItem.unit_price ?? 0,
                start_date: editingItem.start_date ? editingItem.start_date.slice(0, 16) : '',
                end_date: editingItem.end_date ? editingItem.end_date.slice(0, 16) : '',
                methods_units_id: editingItem.methods_units_id ?? '',
            });
        } else {
            // auto-increment ordre from last TechCut task
            setForm({ ...EMPTY_TECHCUT });
        }
        setErrors({});
        setToolWarning(false);
    }, [editingItem]);

    const set = (field, value) => setForm(f => ({ ...f, [field]: value }));

    // Auto-fill label + recalc cost when service changes
    const handleServiceChange = (serviceId) => {
        set('methods_services_id', serviceId);
        const svc = selectData.services_techcut?.find(s => s.id === parseInt(serviceId));
        if (svc) {
            set('label', svc.label);
            set('ordre', svc.ordre || form.ordre);
            recalcCost(form.seting_time, form.unit_time, svc.hourly_rate, svc.margin);
        }
    };

    const recalcCost = (setigTime, unitTime, hourlyRate, margin) => {
        if (!hourlyRate) return;
        const cost = ((parseFloat(setigTime) || 0) / (parseFloat(lineQty) || 1) + (parseFloat(unitTime) || 0)) * parseFloat(hourlyRate);
        const price = cost * (1 + (parseFloat(margin) || 0) / 100);
        setForm(f => ({ ...f, unit_cost: Math.round(cost * 100) / 100, unit_price: Math.round(price * 100) / 100 }));
    };

    const handleTimeChange = (field, value) => {
        set(field, value);
        if (selectedService?.hourly_rate) {
            const st = field === 'seting_time' ? value : form.seting_time;
            const ut = field === 'unit_time'   ? value : form.unit_time;
            recalcCost(st, ut, selectedService.hourly_rate, selectedService.margin);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true); setErrors({}); setToolWarning(false);

        const payload = {
            ...form,
            type: selectedService?.type ?? form.type ?? 1,
            methods_services_id: form.methods_services_id ? parseInt(form.methods_services_id) : null,
            methods_tools_id: form.methods_tools_id ? parseInt(form.methods_tools_id) : null,
            methods_units_id: form.methods_units_id ? parseInt(form.methods_units_id) : null,
        };

        const isEdit = !!editingItem;
        const url = isEdit
            ? endpoints.task_update.replace('__ID__', editingItem.id)
            : endpoints.task_store;

        const res  = await apiFetch(url, { method: isEdit ? 'PUT' : 'POST', body: JSON.stringify(payload) });
        const data = await res.json();

        setSaving(false);
        if (!res.ok) {
            setErrors(data.errors ?? { _global: data.message ?? 'Erreur' });
            if (data.errors?.methods_tools_id) setToolWarning(true);
            return;
        }
        onSaved(data.task, isEdit);
        if (isEdit) { onClose(); } else { setForm(f => ({ ...EMPTY_TECHCUT, ordre: f.ordre + 10 })); }
    };

    return (
        <form id="techcut-form" ref={formRef} onSubmit={handleSubmit}>
            {errors._global && <div className="alert alert-danger py-2">{errors._global}</div>}

            <div className="form-row">
                <div className="col-3">
                    <Field label="N° ordre" error={errors.ordre?.[0]}>
                        <Input type="number" value={form.ordre} min="1" error={errors.ordre?.[0]}
                               onChange={e => set('ordre', e.target.value)} />
                    </Field>
                </div>
                <div className="col-9">
                    <Field label="Service" error={errors.methods_services_id?.[0]}>
                        <Select value={form.methods_services_id} error={errors.methods_services_id?.[0]}
                                onChange={e => handleServiceChange(e.target.value)}>
                            <option value="">— Sélectionner —</option>
                            {selectData.services_techcut?.map(s => (
                                <option key={s.id} value={s.id}>{s.code} — {s.label}</option>
                            ))}
                        </Select>
                    </Field>
                </div>
            </div>

            <Field label="Libellé" error={errors.label?.[0]}>
                <Input type="text" value={form.label} error={errors.label?.[0]}
                       onChange={e => set('label', e.target.value)} placeholder="Libellé de l'opération" />
            </Field>

            <Field label="Outil (optionnel)" error={errors.methods_tools_id?.[0]}>
                {toolWarning && (
                    <div className="alert alert-warning py-1 mb-1" style={{ fontSize: 12 }}>
                        <i className="fas fa-exclamation-triangle mr-1" />
                        Cet outil est déjà réservé sur cette période.
                    </div>
                )}
                <Select value={form.methods_tools_id} error={errors.methods_tools_id?.[0]}
                        onChange={e => { set('methods_tools_id', e.target.value); setToolWarning(false); }}>
                    <option value="">— Aucun outil —</option>
                    {selectData.tools?.map(t => (
                        <option key={t.id} value={t.id}>{t.code} — {t.label}</option>
                    ))}
                </Select>
            </Field>

            <div style={{ background: '#f8f9fa', border: '1px solid #dee2e6', borderRadius: 4, padding: '12px 14px', marginBottom: 14 }}>
                <div style={{ fontSize: 11, fontWeight: 700, color: '#6c757d', marginBottom: 8, textTransform: 'uppercase', letterSpacing: '0.5px' }}>Temps</div>
                <div className="form-row">
                    <div className="col-6">
                        <Field label="Temps de réglage (h)" error={errors.seting_time?.[0]}>
                            <Input type="number" step="0.001" min="0" value={form.seting_time} error={errors.seting_time?.[0]}
                                   onChange={e => handleTimeChange('seting_time', e.target.value)} />
                        </Field>
                    </div>
                    <div className="col-6">
                        <Field label="Temps unitaire (h)" error={errors.unit_time?.[0]}>
                            <Input type="number" step="0.001" min="0" value={form.unit_time} error={errors.unit_time?.[0]}
                                   onChange={e => handleTimeChange('unit_time', e.target.value)} />
                        </Field>
                    </div>
                </div>
                {selectedService?.hourly_rate && (
                    <CostFormula setigTime={form.seting_time} unitTime={form.unit_time}
                                 lineQty={lineQty} hourlyRate={selectedService.hourly_rate} currency={context.currency} />
                )}
            </div>

            <div className="form-row">
                <div className="col-6">
                    <Field label={`Coût unitaire (${context.currency})`} error={errors.unit_cost?.[0]}>
                        <Input type="number" step="0.01" min="0" value={form.unit_cost} error={errors.unit_cost?.[0]}
                               onChange={e => set('unit_cost', e.target.value)} />
                    </Field>
                </div>
                <div className="col-6">
                    <Field label={`Prix unitaire (${context.currency})`} error={errors.unit_price?.[0]}>
                        <Input type="number" step="0.01" min="0" value={form.unit_price} error={errors.unit_price?.[0]}
                               onChange={e => set('unit_price', e.target.value)} />
                        {selectedService?.margin > 0 && (
                            <small style={{ color: '#6c757d', fontSize: 11 }}>Marge: {selectedService.margin}%</small>
                        )}
                    </Field>
                </div>
            </div>

            {context.idType !== 'quote_lines_id' && (
                <div className="form-row">
                    <div className="col-6">
                        <Field label="Date début" error={errors.start_date?.[0]}>
                            <Input type="datetime-local" value={form.start_date} error={errors.start_date?.[0]}
                                   onChange={e => set('start_date', e.target.value)} />
                        </Field>
                    </div>
                    <div className="col-6">
                        <Field label="Date fin" error={errors.end_date?.[0]}>
                            <Input type="datetime-local" value={form.end_date} error={errors.end_date?.[0]}
                                   onChange={e => set('end_date', e.target.value)} />
                        </Field>
                    </div>
                </div>
            )}

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 8 }}>
                <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>Annuler</button>
                <button type="submit" className="btn btn-primary btn-sm" disabled={saving}>
                    {saving ? <><i className="fas fa-spinner fa-spin mr-1" />Enregistrement…</> : (editingItem ? 'Mettre à jour' : 'Ajouter l\'opération')}
                </button>
            </div>
        </form>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// BOM drawer form
// ─────────────────────────────────────────────────────────────────────────────

function BOMForm({ editingItem, selectData, endpoints, context, lineQty, onSaved, onClose }) {
    const [form, setForm]     = useState(EMPTY_BOM);
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    const selectedService = selectData.services_bom?.find(s => s.id === parseInt(form.methods_services_id));
    const selectedProduct = selectData.products_bom?.find(p => p.id === parseInt(form.component_id));

    useEffect(() => {
        if (editingItem) {
            setForm({
                ordre: editingItem.ordre,
                label: editingItem.label,
                methods_services_id: editingItem.methods_services_id ?? '',
                type: editingItem.type ?? 2,
                component_id: editingItem.component_id ?? '',
                qty: editingItem.qty ?? 1,
                unit_cost: editingItem.unit_cost ?? 0,
                unit_price: editingItem.unit_price ?? 0,
                methods_units_id: editingItem.methods_units_id ?? '',
            });
        } else {
            setForm({ ...EMPTY_BOM });
        }
        setErrors({});
    }, [editingItem]);

    const set = (field, value) => setForm(f => ({ ...f, [field]: value }));

    const handleServiceChange = (serviceId) => {
        set('methods_services_id', serviceId);
        const svc = selectData.services_bom?.find(s => s.id === parseInt(serviceId));
        if (svc) {
            set('label', svc.label);
            set('type', svc.type);
        }
    };

    const handleComponentChange = (productId) => {
        set('component_id', productId);
        const product = selectData.products_bom?.find(p => p.id === parseInt(productId));
        if (product && selectedService) {
            const cost  = product.purchased_price ?? 0;
            const price = cost * (1 + (selectedService.margin || 0) / 100);
            setForm(f => ({ ...f, component_id: productId, unit_cost: Math.round(cost * 100) / 100, unit_price: Math.round(price * 100) / 100 }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true); setErrors({});

        const payload = {
            ...form,
            type: selectedService?.type ?? form.type ?? 2,
            methods_services_id: form.methods_services_id ? parseInt(form.methods_services_id) : null,
            component_id: form.component_id ? parseInt(form.component_id) : null,
            methods_units_id: form.methods_units_id ? parseInt(form.methods_units_id) : null,
        };

        const isEdit = !!editingItem;
        const url = isEdit
            ? endpoints.task_update.replace('__ID__', editingItem.id)
            : endpoints.task_store;

        const res  = await apiFetch(url, { method: isEdit ? 'PUT' : 'POST', body: JSON.stringify(payload) });
        const data = await res.json();

        setSaving(false);
        if (!res.ok) { setErrors(data.errors ?? { _global: data.message ?? 'Erreur' }); return; }
        onSaved(data.task, isEdit);
        if (isEdit) { onClose(); } else { setForm(f => ({ ...EMPTY_BOM, ordre: f.ordre + 10 })); }
    };

    return (
        <form id="bom-form" onSubmit={handleSubmit}>
            {errors._global && <div className="alert alert-danger py-2">{errors._global}</div>}

            <div className="form-row">
                <div className="col-3">
                    <Field label="N° ordre" error={errors.ordre?.[0]}>
                        <Input type="number" value={form.ordre} min="1" error={errors.ordre?.[0]}
                               onChange={e => set('ordre', e.target.value)} />
                    </Field>
                </div>
                <div className="col-9">
                    <Field label="Service / Type matière" error={errors.methods_services_id?.[0]}>
                        <Select value={form.methods_services_id} error={errors.methods_services_id?.[0]}
                                onChange={e => handleServiceChange(e.target.value)}>
                            <option value="">— Sélectionner —</option>
                            {selectData.services_bom?.map(s => (
                                <option key={s.id} value={s.id}>{s.code} — {s.label}</option>
                            ))}
                        </Select>
                    </Field>
                </div>
            </div>

            <Field label="Libellé" error={errors.label?.[0]}>
                <Input type="text" value={form.label} error={errors.label?.[0]}
                       onChange={e => set('label', e.target.value)} placeholder="Libellé du composant" />
            </Field>

            <Field label="Composant (référence article)" error={errors.component_id?.[0]}>
                <Select value={form.component_id} error={errors.component_id?.[0]}
                        onChange={e => handleComponentChange(e.target.value)}>
                    <option value="">— Sélectionner un article —</option>
                    {selectData.products_bom?.map(p => (
                        <option key={p.id} value={p.id}>{p.code} — {p.label}</option>
                    ))}
                </Select>
                {selectedProduct && (
                    <small style={{ color: '#6c757d', fontSize: 11 }}>
                        Prix d'achat: {selectedProduct.purchased_price?.toFixed(2)} {context.currency}
                    </small>
                )}
            </Field>

            <div className="form-row">
                <div className="col-4">
                    <Field label="Quantité" error={errors.qty?.[0]}>
                        <Input type="number" step="0.001" min="0" value={form.qty} error={errors.qty?.[0]}
                               onChange={e => set('qty', e.target.value)} />
                    </Field>
                </div>
                <div className="col-4">
                    <Field label={`Coût unitaire (${context.currency})`} error={errors.unit_cost?.[0]}>
                        <Input type="number" step="0.01" min="0" value={form.unit_cost} error={errors.unit_cost?.[0]}
                               onChange={e => set('unit_cost', e.target.value)} />
                    </Field>
                </div>
                <div className="col-4">
                    <Field label={`Prix unitaire (${context.currency})`} error={errors.unit_price?.[0]}>
                        <Input type="number" step="0.01" min="0" value={form.unit_price} error={errors.unit_price?.[0]}
                               onChange={e => set('unit_price', e.target.value)} />
                        {selectedService?.margin > 0 && (
                            <small style={{ color: '#6c757d', fontSize: 11 }}>Marge: {selectedService.margin}%</small>
                        )}
                    </Field>
                </div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 8 }}>
                <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>Annuler</button>
                <button type="submit" className="btn btn-success btn-sm" disabled={saving}>
                    {saving ? <><i className="fas fa-spinner fa-spin mr-1" />Enregistrement…</> : (editingItem ? 'Mettre à jour' : 'Ajouter le composant')}
                </button>
            </div>
        </form>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// SubAssembly drawer form
// ─────────────────────────────────────────────────────────────────────────────

function SubAssemblyForm({ editingItem, selectData, endpoints, context, onSaved, onClose }) {
    const [form, setForm]     = useState(EMPTY_SUBASSEMBLY);
    const [errors, setErrors] = useState({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (editingItem) {
            setForm({
                ordre: editingItem.ordre,
                child_id: editingItem.child_id ?? '',
                qty: editingItem.qty ?? 1,
                unit_price: editingItem.unit_price ?? 0,
            });
        } else {
            setForm({ ...EMPTY_SUBASSEMBLY });
        }
        setErrors({});
    }, [editingItem]);

    const set = (field, value) => setForm(f => ({ ...f, [field]: value }));

    const handleChildChange = (productId) => {
        const product = selectData.products_subassembly?.find(p => p.id === parseInt(productId));
        setForm(f => ({
            ...f,
            child_id: productId,
            unit_price: product?.selling_price ?? f.unit_price,
        }));
    };

    const selectedChild = selectData.products_subassembly?.find(p => p.id === parseInt(form.child_id));

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true); setErrors({});

        const payload = { ...form, child_id: form.child_id ? parseInt(form.child_id) : null };

        const isEdit = !!editingItem;
        const url = isEdit
            ? endpoints.subassembly_update.replace('__ID__', editingItem.id)
            : endpoints.subassembly_store;

        const res  = await apiFetch(url, { method: isEdit ? 'PUT' : 'POST', body: JSON.stringify(payload) });
        const data = await res.json();

        setSaving(false);
        if (!res.ok) { setErrors(data.errors ?? { _global: data.message ?? 'Erreur' }); return; }
        onSaved(data.sub_assembly, isEdit);
        if (isEdit) { onClose(); } else { setForm(f => ({ ...EMPTY_SUBASSEMBLY, ordre: f.ordre + 10 })); }
    };

    return (
        <form id="subassembly-form" onSubmit={handleSubmit}>
            {errors._global && <div className="alert alert-danger py-2">{errors._global}</div>}

            <div className="form-row">
                <div className="col-3">
                    <Field label="N° ordre" error={errors.ordre?.[0]}>
                        <Input type="number" value={form.ordre} min="1" error={errors.ordre?.[0]}
                               onChange={e => set('ordre', e.target.value)} />
                    </Field>
                </div>
            </div>

            <Field label="Sous-ensemble (article de type assemblage)" error={errors.child_id?.[0]}>
                <Select value={form.child_id} error={errors.child_id?.[0]}
                        onChange={e => handleChildChange(e.target.value)}>
                    <option value="">— Sélectionner —</option>
                    {selectData.products_subassembly?.map(p => (
                        <option key={p.id} value={p.id}>{p.code} — {p.label}</option>
                    ))}
                </Select>
                {selectedChild && (
                    <small style={{ color: '#6c757d', fontSize: 11 }}>
                        Label: {selectedChild.label}
                    </small>
                )}
            </Field>

            <div className="form-row">
                <div className="col-6">
                    <Field label="Quantité" error={errors.qty?.[0]}>
                        <Input type="number" step="0.001" min="0" value={form.qty} error={errors.qty?.[0]}
                               onChange={e => set('qty', e.target.value)} />
                    </Field>
                </div>
                <div className="col-6">
                    <Field label={`Prix unitaire (${context.currency})`} error={errors.unit_price?.[0]}>
                        <Input type="number" step="0.01" min="0" value={form.unit_price} error={errors.unit_price?.[0]}
                               onChange={e => set('unit_price', e.target.value)} />
                    </Field>
                </div>
            </div>

            <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 8, marginTop: 8 }}>
                <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>Annuler</button>
                <button type="submit" className="btn btn-warning btn-sm" disabled={saving}>
                    {saving ? <><i className="fas fa-spinner fa-spin mr-1" />Enregistrement…</> : (editingItem ? 'Mettre à jour' : 'Ajouter le sous-ensemble')}
                </button>
            </div>
        </form>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// CSV Import modal (BOM)
// ─────────────────────────────────────────────────────────────────────────────

function CsvImportModal({ open, endpoint, onImported, onClose }) {
    const [file, setFile]     = useState(null);
    const [loading, setLoading] = useState(false);
    const [result, setResult]   = useState(null);

    useEffect(() => { if (!open) { setFile(null); setResult(null); } }, [open]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!file) return;
        setLoading(true); setResult(null);
        const fd = new FormData();
        fd.append('csv_file', file);
        const res  = await apiFetch(endpoint, { method: 'POST', body: fd });
        const data = await res.json();
        setLoading(false);
        setResult(data);
        if (res.ok) onImported(data.bom ?? []);
    };

    if (!open) return null;

    return (
        <div className="modal" style={{ display: 'block', background: 'rgba(0,0,0,0.5)' }} tabIndex="-1">
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content">
                    <div className="modal-header">
                        <h5 className="modal-title">Importer BOM depuis CSV</h5>
                        <button type="button" className="close" onClick={onClose}><span>×</span></button>
                    </div>
                    <form onSubmit={handleSubmit}>
                        <div className="modal-body">
                            <p style={{ fontSize: 12, color: '#6c757d' }}>
                                Colonnes attendues : <code>ordre, service_code, label, component_code, unit_code, qty, unit_cost, unit_price</code>
                            </p>
                            <input type="file" accept=".csv,.txt" className="form-control-file"
                                   onChange={e => setFile(e.target.files[0])} />
                            {result && (
                                <div className="mt-3">
                                    <div className="alert alert-success py-2">{result.success_count} ligne(s) importée(s)</div>
                                    {result.errors?.map((err, i) => (
                                        <div key={i} className="alert alert-warning py-1" style={{ fontSize: 12 }}>{err}</div>
                                    ))}
                                </div>
                            )}
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary btn-sm" onClick={onClose}>Fermer</button>
                            <button type="submit" className="btn btn-success btn-sm" disabled={loading || !file}>
                                {loading ? <><i className="fas fa-spinner fa-spin mr-1" />Import…</> : 'Importer'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Task row (TechCut + BOM share same row structure)
// ─────────────────────────────────────────────────────────────────────────────

function endDateColor(dateStr) {
    if (!dateStr) return '#6c757d';
    const d = new Date(dateStr);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate() + 1);
    if (d < today)   return '#dc3545'; // overdue
    if (d < tomorrow) return '#fd7e14'; // today
    return '#007bff'; // future
}

function ServiceBadge({ service }) {
    if (!service) return <span className="badge badge-secondary">—</span>;
    const bg = service.color || '#6c757d';
    return (
        <span className="badge" style={{ background: bg, color: '#fff', maxWidth: 100, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', display: 'inline-block' }}
              title={service.label}>
            {service.code}
        </span>
    );
}

function ProgressBar({ value }) {
    const pct = Math.min(100, Math.max(0, Math.round(value)));
    const color = pct >= 100 ? '#28a745' : pct >= 50 ? '#17a2b8' : '#ffc107';
    return (
        <div style={{ background: '#e9ecef', borderRadius: 3, height: 6, minWidth: 60 }}>
            <div style={{ width: `${pct}%`, height: '100%', background: color, borderRadius: 3 }} />
        </div>
    );
}

// Titre libre côté admin (table statuses) → mapping heuristique vers un badge Bootstrap.
function statusBadge(status) {
    if (!status) return <span className="text-muted" style={{ fontSize: 11 }}>—</span>;
    const low = (status.title || '').toLowerCase();
    let cls = 'badge-secondary';
    if (/(finish|done|terminé|supplied|fourni)/.test(low))            cls = 'badge-success';
    else if (/(progress|started|cours|outsourced|sous.?traité)/.test(low)) cls = 'badge-warning';
    else if (/(suspend|cancel|rejet)/.test(low))                       cls = 'badge-danger';
    else if (/(rfq)/.test(low))                                        cls = 'badge-info';
    return <span className={`badge ${cls}`} style={{ fontSize: 10 }} title={status.title}>{status.title}</span>;
}

function TaskRow({ task, canEdit, onEdit, onDelete, onDuplicate, currency, isBOM, showDates, statuUrl }) {
    const [menuOpen, setMenuOpen] = useState(false);

    const totalCost  = isBOM ? task.unit_cost * task.qty : task.unit_cost;
    const totalPrice = isBOM ? task.unit_price * task.qty : task.unit_price;
    const margin     = totalCost > 0 ? Math.round((totalPrice / totalCost - 1) * 100) : 0;

    return (
        <tr>
            <td style={{ color: '#6c757d', fontSize: 12 }}>{task.ordre}</td>
            <td><ServiceBadge service={task.service} /></td>
            <td style={{ maxWidth: 150 }}>
                {statuUrl ? (
                    <a href={statuUrl}
                       title={`${task.label} — écran de suivi`}
                       style={{ display: 'block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {task.label}
                        <i className="fas fa-external-link-alt ml-1" style={{ fontSize: 10, opacity: 0.6 }} />
                    </a>
                ) : (
                    <span title={task.label} style={{ display: 'block', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                        {task.label}
                    </span>
                )}
            </td>
            {isBOM ? (
                <>
                    <td>{task.component ? <code style={{ fontSize: 11 }}>{task.component.code}</code> : '—'}</td>
                    <td style={{ textAlign: 'right' }}>{formatQty(task.qty)}</td>
                </>
            ) : (
                <>
                    <td style={{ textAlign: 'right', fontSize: 12 }}>{task.tool ? task.tool.code : '—'}</td>
                    <td style={{ textAlign: 'right', fontSize: 12 }}>{task.seting_time}h</td>
                    <td style={{ textAlign: 'right', fontSize: 12 }}>{task.unit_time}h</td>
                    <td>{statusBadge(task.status)}</td>
                    <td>
                        <ProgressBar value={task.progress} />
                    </td>
                </>
            )}
            <td style={{ textAlign: 'right', fontSize: 12, color: '#6c757d' }}>
                {totalCost.toFixed(2)} {currency}
            </td>
            <td style={{ textAlign: 'right', fontSize: 12 }}>
                <span style={{ color: margin >= 0 ? '#28a745' : '#dc3545' }}>{margin}%</span>
            </td>
            <td style={{ textAlign: 'right', fontSize: 12, fontWeight: 500 }}>
                {totalPrice.toFixed(2)} {currency}
            </td>
            {!isBOM && (
                showDates && task.end_date
                    ? <td style={{ fontSize: 11, color: endDateColor(task.end_date) }}>{new Date(task.end_date).toLocaleDateString('fr-FR')}</td>
                    : <td />
            )}
            {isBOM && <td />}
            {canEdit && (
                <td style={{ textAlign: 'right', whiteSpace: 'nowrap' }}>
                    <div className="dropdown" style={{ display: 'inline-block', position: 'relative' }}>
                        <button className="btn btn-xs btn-outline-secondary" onClick={() => setMenuOpen(o => !o)}
                                style={{ padding: '1px 6px' }}>
                            <i className="fas fa-ellipsis-v" />
                        </button>
                        {menuOpen && (
                            <>
                                <div onClick={() => setMenuOpen(false)} style={{ position: 'fixed', inset: 0, zIndex: 900 }} />
                                <div className="dropdown-menu show" style={{ right: 0, left: 'auto', zIndex: 901 }}>
                                    <button className="dropdown-item" onClick={() => { setMenuOpen(false); onEdit(task); }}>
                                        <i className="fas fa-edit mr-2" />Modifier
                                    </button>
                                    <button className="dropdown-item" onClick={() => { setMenuOpen(false); onDuplicate(task.id); }}>
                                        <i className="fas fa-copy mr-2" />Dupliquer
                                    </button>
                                    <div className="dropdown-divider" />
                                    <button className="dropdown-item text-danger" onClick={() => { setMenuOpen(false); onDelete(task.id); }}>
                                        <i className="fas fa-trash mr-2" />Supprimer
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </td>
            )}
        </tr>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// SubAssembly row
// ─────────────────────────────────────────────────────────────────────────────

function SubAssemblyRow({ sa, canEdit, onEdit, onDelete, onDuplicate, currency }) {
    const [menuOpen, setMenuOpen] = useState(false);
    return (
        <tr>
            <td style={{ color: '#6c757d', fontSize: 12 }}>{sa.ordre}</td>
            <td>{sa.child ? <code style={{ fontSize: 11 }}>{sa.child.code}</code> : '—'}</td>
            <td style={{ maxWidth: 150 }}>
                <span title={sa.child?.label} style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', display: 'block' }}>
                    {sa.child?.label ?? '—'}
                </span>
            </td>
            <td style={{ textAlign: 'right' }}>{formatQty(sa.qty)}</td>
            <td style={{ textAlign: 'right', fontSize: 12 }}>{sa.unit_price?.toFixed(2)} {currency}</td>
            <td style={{ textAlign: 'right', fontSize: 12, fontWeight: 500 }}>
                {((sa.qty ?? 0) * (sa.unit_price ?? 0)).toFixed(2)} {currency}
            </td>
            {canEdit && (
                <td style={{ textAlign: 'right', whiteSpace: 'nowrap' }}>
                    <div className="dropdown" style={{ display: 'inline-block', position: 'relative' }}>
                        <button className="btn btn-xs btn-outline-secondary" onClick={() => setMenuOpen(o => !o)}
                                style={{ padding: '1px 6px' }}>
                            <i className="fas fa-ellipsis-v" />
                        </button>
                        {menuOpen && (
                            <>
                                <div onClick={() => setMenuOpen(false)} style={{ position: 'fixed', inset: 0, zIndex: 900 }} />
                                <div className="dropdown-menu show" style={{ right: 0, left: 'auto', zIndex: 901 }}>
                                    <button className="dropdown-item" onClick={() => { setMenuOpen(false); onEdit(sa); }}>
                                        <i className="fas fa-edit mr-2" />Modifier
                                    </button>
                                    <button className="dropdown-item" onClick={() => { setMenuOpen(false); onDuplicate(sa.id); }}>
                                        <i className="fas fa-copy mr-2" />Dupliquer
                                    </button>
                                    <div className="dropdown-divider" />
                                    <button className="dropdown-item text-danger" onClick={() => { setMenuOpen(false); onDelete(sa.id); }}>
                                        <i className="fas fa-trash mr-2" />Supprimer
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </td>
            )}
        </tr>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Section footer totals
// ─────────────────────────────────────────────────────────────────────────────

function SectionTotals({ items, currency, isBOM, isSubAssembly }) {
    if (isSubAssembly) {
        const total = items.reduce((s, sa) => s + (sa.qty ?? 0) * (sa.unit_price ?? 0), 0);
        return (
            <tfoot>
                <tr style={{ background: '#f8f9fa', fontWeight: 600 }}>
                    <td colSpan={5} style={{ textAlign: 'right', fontSize: 12 }}>Total sous-ensembles</td>
                    <td style={{ textAlign: 'right', fontSize: 12 }}>{total.toFixed(2)} {currency}</td>
                    <td />
                </tr>
            </tfoot>
        );
    }
    if (isBOM) {
        const totalCost  = items.reduce((s, t) => s + (t.unit_cost ?? 0) * (t.qty ?? 0), 0);
        const totalPrice = items.reduce((s, t) => s + (t.unit_price ?? 0) * (t.qty ?? 0), 0);
        const margin     = totalCost > 0 ? Math.round((totalPrice / totalCost - 1) * 100) : 0;
        return (
            <tfoot>
                <tr style={{ background: '#f8f9fa', fontWeight: 600 }}>
                    <td colSpan={5} style={{ textAlign: 'right', fontSize: 12 }}>Total nomenclature</td>
                    <td style={{ textAlign: 'right', fontSize: 12 }}>{totalCost.toFixed(2)} {currency}</td>
                    <td style={{ textAlign: 'right', fontSize: 12, color: margin >= 0 ? '#28a745' : '#dc3545' }}>{margin}%</td>
                    <td style={{ textAlign: 'right', fontSize: 12 }}>{totalPrice.toFixed(2)} {currency}</td>
                    <td /><td />
                </tr>
            </tfoot>
        );
    }
    // TechCut
    const totalSeting  = items.reduce((s, t) => s + (t.seting_time ?? 0), 0);
    const totalUnit    = items.reduce((s, t) => s + (t.unit_time ?? 0), 0);
    const totalCost    = items.reduce((s, t) => s + (t.unit_cost ?? 0), 0);
    const totalPrice   = items.reduce((s, t) => s + (t.unit_price ?? 0), 0);
    const margin       = totalCost > 0 ? Math.round((totalPrice / totalCost - 1) * 100) : 0;
    return (
        <tfoot>
            <tr style={{ background: '#f8f9fa', fontWeight: 600 }}>
                <td colSpan={3} style={{ textAlign: 'right', fontSize: 12 }}>Total gamme</td>
                <td />
                <td style={{ textAlign: 'right', fontSize: 12 }}>{totalSeting.toFixed(3)}h</td>
                <td style={{ textAlign: 'right', fontSize: 12 }}>{totalUnit.toFixed(3)}h</td>
                <td />{/* Statut */}
                <td />{/* Avance */}
                <td style={{ textAlign: 'right', fontSize: 12 }}>{totalCost.toFixed(2)} {currency}</td>
                <td style={{ textAlign: 'right', fontSize: 12, color: margin >= 0 ? '#28a745' : '#dc3545' }}>{margin}%</td>
                <td style={{ textAlign: 'right', fontSize: 12 }}>{totalPrice.toFixed(2)} {currency}</td>
                <td /><td />
            </tr>
        </tfoot>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Section card wrapper
// ─────────────────────────────────────────────────────────────────────────────

function SectionCard({ title, icon, colorClass, extra, children }) {
    return (
        <div className="card mb-3">
            <div className="card-header d-flex align-items-center justify-content-between" style={{ padding: '8px 16px' }}>
                <h6 className="mb-0">
                    <i className={`${icon} mr-2 ${colorClass}`} />{title}
                </h6>
                {extra && <div>{extra}</div>}
            </div>
            <div className="card-body p-0">
                {children}
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Fixed tab-handle buttons (one per drawer type)
// ─────────────────────────────────────────────────────────────────────────────

const TAB_DEFS = [
    { type: DRAWER.TECHCUT,    label: 'Opération',    bg: '#007bff', top: '30%' },
    { type: DRAWER.BOM,        label: 'Composant',    bg: '#28a745', top: '50%' },
    { type: DRAWER.SUBASSEMBLY,label: 'Sous-ensemble',bg: '#fd7e14', top: '70%' },
];

function DrawerTabs({ drawerOpen, drawerType, canEdit, hideSubAssembly, onOpen }) {
    if (!canEdit) return null;
    return (
        <>
            {TAB_DEFS.filter(t => !(hideSubAssembly && t.type === DRAWER.SUBASSEMBLY)).map(tab => (
                <button
                    key={tab.type}
                    type="button"
                    onClick={() => onOpen(tab.type)}
                    title={`Ajouter — ${tab.label}`}
                    style={{
                        position: 'fixed',
                        right: 0,
                        top: tab.top,
                        transform: 'translateY(-50%)',
                        width: 36,
                        padding: '14px 0',
                        background: tab.bg,
                        color: '#fff',
                        border: 'none',
                        borderRadius: '6px 0 0 6px',
                        boxShadow: '-3px 0 8px rgba(0,0,0,0.18)',
                        cursor: 'pointer',
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        gap: 6,
                        zIndex: 1049,
                        opacity: drawerOpen ? 0 : 1,
                        pointerEvents: drawerOpen ? 'none' : 'auto',
                        transition: 'opacity 0.2s',
                    }}
                >
                    <i className="fas fa-plus" style={{ fontSize: 12 }} />
                    <span style={{
                        writingMode: 'vertical-rl',
                        transform: 'rotate(180deg)',
                        fontSize: 9,
                        fontWeight: 700,
                        letterSpacing: 1,
                        textTransform: 'uppercase',
                    }}>{tab.label}</span>
                </button>
            ))}
        </>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Nomenclature templates card
// ─────────────────────────────────────────────────────────────────────────────

function NomenclatureTemplatesCard({ templates, canEdit, onApply, loading }) {
    if (!templates?.length || !canEdit) return null;
    return (
        <div className="card mb-3">
            <div className="card-header" style={{ padding: '8px 16px' }}>
                <h6 className="mb-0"><i className="fas fa-clone mr-2 text-secondary" />Modèles standards</h6>
            </div>
            <div className="card-body" style={{ padding: '10px 16px' }}>
                <div className="d-flex flex-wrap" style={{ gap: 8 }}>
                    {templates.map(tpl => (
                        <button
                            key={tpl.id}
                            className="btn btn-outline-secondary btn-sm"
                            onClick={() => onApply(tpl.id)}
                            disabled={loading}
                            title={`Copie ${tpl.task_count} opération(s) dans la gamme`}
                        >
                            {loading ? <i className="fas fa-spinner fa-spin mr-1" /> : <i className="fas fa-file-import mr-1" />}
                            {tpl.label}
                            <span className="badge badge-secondary ml-1">{tpl.task_count}</span>
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Context header (line info + custom requirements)
// ─────────────────────────────────────────────────────────────────────────────

function ContextHeader({ line, isLocked }) {
    if (!line) return null;
    return (
        <>
            {isLocked && (
                <div className="alert alert-warning alert-dismissible py-2 mb-3">
                    <i className="fas fa-lock mr-2" />
                    Document verrouillé — consultation uniquement.
                </div>
            )}
            {line.custom_requirements?.length > 0 && (
                <div className="card mb-3">
                    <div className="card-header" style={{ padding: '8px 16px' }}>
                        <h6 className="mb-0"><i className="fas fa-swatchbook mr-2 text-secondary" />Exigences client</h6>
                    </div>
                    <div className="card-body py-2">
                        <ul className="mb-0" style={{ fontSize: 13 }}>
                            {line.custom_requirements.map((req, i) => (
                                <li key={i}><strong>{req.label}:</strong> {req.value}</li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}
        </>
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Root component
// ─────────────────────────────────────────────────────────────────────────────

export default function TaskManagePage({ context, endpoints }) {
    const [line, setLine]               = useState(null);
    const [techCutTasks, setTechCutTasks] = useState([]);
    const [bomTasks, setBomTasks]         = useState([]);
    const [subAssemblies, setSubAssemblies] = useState([]);
    const [selectData, setSelectData]     = useState({});
    const [loading, setLoading]           = useState(true);
    const [error, setError]               = useState(null);

    const [drawerOpen, setDrawerOpen]   = useState(false);
    const [drawerType, setDrawerType]   = useState(null);
    const [editingItem, setEditingItem] = useState(null);

    const [csvModalOpen, setCsvModalOpen]   = useState(false);
    const [nomenclatureLoading, setNomenclatureLoading] = useState(false);

    const canEdit = context.statu === 1 || context.idType === 'nomenclature_lines_id';

    // L'écran de suivi opérateur n'existe que pour les tâches rattachées à une
    // ligne de commande : un devis n'a ni pointage ni déclaration de quantité.
    const statuUrlFor = (taskId) => (
        context.idType === 'order_lines_id' && endpoints.task_statu
            ? endpoints.task_statu.replace('__ID__', taskId)
            : null
    );

    // ── Initial data fetch ──────────────────────────────────────────────────

    useEffect(() => {
        const fetchAll = async () => {
            try {
                const [dataRes, selectRes] = await Promise.all([
                    apiFetch(endpoints.initial_data),
                    apiFetch(endpoints.select_data),
                ]);
                if (!dataRes.ok || !selectRes.ok) throw new Error('Chargement échoué');
                const [data, selects] = await Promise.all([dataRes.json(), selectRes.json()]);
                setLine(data.line);
                setTechCutTasks(data.tasks.technical_cut ?? []);
                setBomTasks(data.tasks.bom ?? []);
                setSubAssemblies(data.sub_assemblies ?? []);
                setSelectData(selects);
            } catch (e) {
                setError(e.message);
            } finally {
                setLoading(false);
            }
        };
        fetchAll();
    }, []);

    // ── Drawer helpers ──────────────────────────────────────────────────────

    const openCreate = useCallback((type) => {
        setDrawerType(type);
        setEditingItem(null);
        setDrawerOpen(true);
    }, []);

    const openEdit = useCallback((type, item) => {
        setDrawerType(type);
        setEditingItem(item);
        setDrawerOpen(true);
    }, []);

    const closeDrawer = useCallback(() => setDrawerOpen(false), []);

    // ── Task CRUD ───────────────────────────────────────────────────────────

    const handleTaskSaved = useCallback((task, isEdit) => {
        if (!task) return;
        const isTechCut = [1, 7].includes(task.type);
        const setter = isTechCut ? setTechCutTasks : setBomTasks;
        setter(prev => {
            if (isEdit) return prev.map(t => t.id === task.id ? task : t).sort((a, b) => a.ordre - b.ordre);
            return [...prev, task].sort((a, b) => a.ordre - b.ordre);
        });
    }, []);

    const handleTaskDelete = useCallback(async (id) => {
        if (!window.confirm('Supprimer cette tâche ?')) return;
        await apiFetch(endpoints.task_destroy.replace('__ID__', id), { method: 'DELETE' });
        setTechCutTasks(prev => prev.filter(t => t.id !== id));
        setBomTasks(prev => prev.filter(t => t.id !== id));
    }, [endpoints]);

    const handleTaskDuplicate = useCallback(async (id) => {
        const res  = await apiFetch(endpoints.task_duplicate.replace('__ID__', id), { method: 'POST' });
        const data = await res.json();
        if (res.ok && data.task) handleTaskSaved(data.task, false);
    }, [endpoints, handleTaskSaved]);

    // ── SubAssembly CRUD ────────────────────────────────────────────────────

    const handleSubAssemblySaved = useCallback((sa, isEdit) => {
        if (!sa) return;
        setSubAssemblies(prev => {
            if (isEdit) return prev.map(s => s.id === sa.id ? sa : s).sort((a, b) => a.ordre - b.ordre);
            return [...prev, sa].sort((a, b) => a.ordre - b.ordre);
        });
    }, []);

    const handleSubAssemblyDelete = useCallback(async (id) => {
        if (!window.confirm('Supprimer ce sous-ensemble ?')) return;
        await apiFetch(endpoints.subassembly_destroy.replace('__ID__', id), { method: 'DELETE' });
        setSubAssemblies(prev => prev.filter(s => s.id !== id));
    }, [endpoints]);

    const handleSubAssemblyDuplicate = useCallback(async (id) => {
        const res  = await apiFetch(endpoints.subassembly_duplicate.replace('__ID__', id), { method: 'POST' });
        const data = await res.json();
        if (res.ok && data.sub_assembly) handleSubAssemblySaved(data.sub_assembly, false);
    }, [endpoints, handleSubAssemblySaved]);

    // ── Apply nomenclature ──────────────────────────────────────────────────

    const handleApplyNomenclature = useCallback(async (tplId) => {
        if (!window.confirm('Ajouter toutes les opérations de ce modèle à la gamme ?')) return;
        setNomenclatureLoading(true);
        const url = endpoints.apply_nomenclature.replace('__TPL_ID__', tplId);
        const res  = await apiFetch(url, { method: 'POST' });
        const data = await res.json();
        if (res.ok) {
            setTechCutTasks(data.technical_cut ?? []);
            setBomTasks(data.bom ?? []);
        }
        setNomenclatureLoading(false);
    }, [endpoints]);

    // ── CSV import callback ─────────────────────────────────────────────────

    const handleCsvImported = useCallback((newBom) => {
        setBomTasks(newBom);
        setCsvModalOpen(false);
    }, []);

    // ── Drawer title ────────────────────────────────────────────────────────

    const drawerTitle = {
        [DRAWER.TECHCUT]:    editingItem ? 'Modifier l\'opération' : 'Nouvelle opération',
        [DRAWER.BOM]:        editingItem ? 'Modifier le composant' : 'Nouveau composant',
        [DRAWER.SUBASSEMBLY]: editingItem ? 'Modifier le sous-ensemble' : 'Nouveau sous-ensemble',
    }[drawerType] ?? '';

    // ── Render ──────────────────────────────────────────────────────────────

    if (loading) {
        return (
            <div className="text-center py-5">
                <i className="fas fa-spinner fa-spin fa-2x text-secondary" />
                <p className="mt-2 text-muted">Chargement de la gamme…</p>
            </div>
        );
    }
    if (error) {
        return <div className="alert alert-danger">{error}</div>;
    }

    const currency = context.currency || '€';

    return (
        <div>
            <ContextHeader line={line} isLocked={!canEdit} />

            <DrawerTabs
                drawerOpen={drawerOpen}
                drawerType={drawerType}
                canEdit={canEdit}
                hideSubAssembly={context.idType === 'nomenclature_lines_id'}
                onOpen={openCreate}
            />

            {/* ── Gamme opératoire (TechCut) ── */}
            <SectionCard
                title="Gamme opératoire"
                icon="fas fa-cogs"
                colorClass="text-primary"
            >
                {techCutTasks.length === 0 ? (
                    <div className="text-center text-muted py-4" style={{ fontSize: 13 }}>
                        <i className="fas fa-tools mb-2" style={{ fontSize: 24 }} /><br />
                        Aucune opération. {canEdit && 'Cliquez sur "+ Opération" pour commencer.'}
                    </div>
                ) : (
                    <div className="table-responsive">
                        <table className="table table-sm table-hover mb-0" style={{ fontSize: 13 }}>
                            <thead className="thead-light">
                                <tr>
                                    <th style={{ width: 40 }}>N°</th>
                                    <th style={{ width: 90 }}>Service</th>
                                    <th>Libellé</th>
                                    <th style={{ width: 70, textAlign: 'right' }}>Outil</th>
                                    <th style={{ width: 60, textAlign: 'right' }}>Régl.</th>
                                    <th style={{ width: 60, textAlign: 'right' }}>Unit.</th>
                                    <th style={{ width: 90 }}>Statut</th>
                                    <th style={{ width: 80 }}>Avance</th>
                                    <th style={{ width: 80, textAlign: 'right' }}>Coût</th>
                                    <th style={{ width: 60, textAlign: 'right' }}>Marge</th>
                                    <th style={{ width: 80, textAlign: 'right' }}>Prix</th>
                                    {context.idType !== 'quote_lines_id' && <th style={{ width: 80 }}>Fin prév.</th>}
                                    {context.idType === 'quote_lines_id' && <th />}
                                    {canEdit && <th style={{ width: 40 }} />}
                                </tr>
                            </thead>
                            <tbody>
                                {techCutTasks.map(task => (
                                    <TaskRow key={task.id} task={task} canEdit={canEdit} currency={currency}
                                             isBOM={false}
                                             showDates={context.idType !== 'quote_lines_id'}
                                             statuUrl={statuUrlFor(task.id)}
                                             onEdit={t => openEdit(DRAWER.TECHCUT, t)}
                                             onDelete={handleTaskDelete}
                                             onDuplicate={handleTaskDuplicate} />
                                ))}
                            </tbody>
                            <SectionTotals items={techCutTasks} currency={currency} isBOM={false} />
                        </table>
                    </div>
                )}
            </SectionCard>

            {/* ── Nomenclature matières (BOM) ── */}
            <SectionCard
                title="Nomenclature matières"
                icon="fas fa-list-alt"
                colorClass="text-success"
                extra={canEdit && (
                    <button className="btn btn-sm btn-outline-success" onClick={() => setCsvModalOpen(true)}>
                        <i className="fas fa-file-csv mr-1" />CSV
                    </button>
                )}
            >
                {bomTasks.length === 0 ? (
                    <div className="text-center text-muted py-4" style={{ fontSize: 13 }}>
                        <i className="fas fa-boxes mb-2" style={{ fontSize: 24 }} /><br />
                        Aucun composant. {canEdit && 'Ajoutez des matières ou importez un CSV.'}
                    </div>
                ) : (
                    <div className="table-responsive">
                        <table className="table table-sm table-hover mb-0" style={{ fontSize: 13 }}>
                            <thead className="thead-light">
                                <tr>
                                    <th style={{ width: 40 }}>N°</th>
                                    <th style={{ width: 90 }}>Service</th>
                                    <th>Libellé</th>
                                    <th style={{ width: 90 }}>Composant</th>
                                    <th style={{ width: 60, textAlign: 'right' }}>Qté</th>
                                    <th style={{ width: 80, textAlign: 'right' }}>Coût</th>
                                    <th style={{ width: 60, textAlign: 'right' }}>Marge</th>
                                    <th style={{ width: 80, textAlign: 'right' }}>Prix</th>
                                    <th />
                                    {canEdit && <th style={{ width: 40 }} />}
                                </tr>
                            </thead>
                            <tbody>
                                {bomTasks.map(task => (
                                    <TaskRow key={task.id} task={task} canEdit={canEdit} currency={currency}
                                             isBOM={true}
                                             statuUrl={statuUrlFor(task.id)}
                                             onEdit={t => openEdit(DRAWER.BOM, t)}
                                             onDelete={handleTaskDelete}
                                             onDuplicate={handleTaskDuplicate} />
                                ))}
                            </tbody>
                            <SectionTotals items={bomTasks} currency={currency} isBOM={true} />
                        </table>
                    </div>
                )}
            </SectionCard>

            {/* ── Sous-ensembles ── */}
            {context.idType !== 'nomenclature_lines_id' && (
                <SectionCard
                    title="Sous-ensembles"
                    icon="fas fa-cubes"
                    colorClass="text-warning"
                >
                    {subAssemblies.length === 0 ? (
                        <div className="text-center text-muted py-4" style={{ fontSize: 13 }}>
                            <i className="fas fa-cube mb-2" style={{ fontSize: 24 }} /><br />
                            Aucun sous-ensemble. {canEdit && 'Ajoutez un assemblage enfant.'}
                        </div>
                    ) : (
                        <div className="table-responsive">
                            <table className="table table-sm table-hover mb-0" style={{ fontSize: 13 }}>
                                <thead className="thead-light">
                                    <tr>
                                        <th style={{ width: 40 }}>N°</th>
                                        <th style={{ width: 90 }}>Code</th>
                                        <th>Libellé</th>
                                        <th style={{ width: 60, textAlign: 'right' }}>Qté</th>
                                        <th style={{ width: 80, textAlign: 'right' }}>P.U.</th>
                                        <th style={{ width: 80, textAlign: 'right' }}>Total</th>
                                        {canEdit && <th style={{ width: 40 }} />}
                                    </tr>
                                </thead>
                                <tbody>
                                    {subAssemblies.map(sa => (
                                        <SubAssemblyRow key={sa.id} sa={sa} canEdit={canEdit} currency={currency}
                                                        onEdit={s => openEdit(DRAWER.SUBASSEMBLY, s)}
                                                        onDelete={handleSubAssemblyDelete}
                                                        onDuplicate={handleSubAssemblyDuplicate} />
                                    ))}
                                </tbody>
                                <SectionTotals items={subAssemblies} currency={currency} isSubAssembly={true} />
                            </table>
                        </div>
                    )}
                </SectionCard>
            )}

            {/* ── Modèles standards ── */}
            <NomenclatureTemplatesCard
                templates={selectData.nomenclatures}
                canEdit={canEdit}
                onApply={handleApplyNomenclature}
                loading={nomenclatureLoading}
            />

            {/* ── Drawer ── */}
            <Drawer open={drawerOpen} title={drawerTitle} onClose={closeDrawer}>
                {drawerType === DRAWER.TECHCUT && (
                    <TechCutForm
                        editingItem={editingItem}
                        selectData={selectData}
                        endpoints={endpoints}
                        context={context}
                        lineQty={line?.qty ?? 1}
                        onSaved={handleTaskSaved}
                        onClose={closeDrawer}
                    />
                )}
                {drawerType === DRAWER.BOM && (
                    <BOMForm
                        editingItem={editingItem}
                        selectData={selectData}
                        endpoints={endpoints}
                        context={context}
                        lineQty={line?.qty ?? 1}
                        onSaved={handleTaskSaved}
                        onClose={closeDrawer}
                    />
                )}
                {drawerType === DRAWER.SUBASSEMBLY && (
                    <SubAssemblyForm
                        editingItem={editingItem}
                        selectData={selectData}
                        endpoints={endpoints}
                        context={context}
                        onSaved={handleSubAssemblySaved}
                        onClose={closeDrawer}
                    />
                )}
            </Drawer>

            {/* ── CSV Modal ── */}
            <CsvImportModal
                open={csvModalOpen}
                endpoint={endpoints.import_csv}
                onImported={handleCsvImported}
                onClose={() => setCsvModalOpen(false)}
            />
        </div>
    );
}
