import React, { useState, useEffect, useCallback } from 'react';
import { csrfToken, apiFetchWithStatus as apiFetch } from '../lib/http';
import { QUALIFIED, SupplierQualificationBadge, showsBadge, supplierOptionLabel } from '../lib/supplierQualification.jsx';
import GuideHelp from './GuideHelp';
import { offerLabel, quotationLocks } from '../lib/purchaseOffers';

// Textes de l'écran (resources/lang/*/purchase_ui.php), posés par le composant principal
// avant le rendu de ses enfants ; repli sur le texte français.
let L = {};

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

const STATUS_LABELS = [
    { value: 1, get label() { return L.in_progress ?? 'En cours'; } },
    { value: 2, get label() { return L.sent ?? 'Envoyé'; } },
    { value: 3, get label() { return L.partly_received ?? 'Partiellement reçu'; } },
    { value: 4, get label() { return L.received ?? 'Reçu'; } },
    { value: 5, get label() { return L.po_partly_created ?? 'BC partiellement créé'; } },
    { value: 6, get label() { return L.po_created ?? 'BC créé'; } },
];

// ---------------------------------------------------------------------------
// Arrow Steps (inline — SPA variant, no redirect)
// ---------------------------------------------------------------------------

const AS_STYLE_ID = 'arrow-steps-react-v1';
const AS_CSS = `
.as-track{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;padding:4px 0 10px}
.as-row{display:flex;width:100%}
.as-btn{flex:1 1 0;min-width:80px;display:flex;align-items:center;justify-content:center;padding:11px 22px;font-size:12px;font-weight:500;color:#fff;background:#6c757d;border:none;outline:none;cursor:pointer;transition:background .18s,filter .14s;clip-path:polygon(14px 0,calc(100% - 14px) 0,100% 50%,calc(100% - 14px) 100%,0 100%,14px 50%);margin-left:-14px;position:relative;text-align:center;white-space:nowrap;line-height:1.3}
.as-btn:first-child{clip-path:polygon(0 0,calc(100% - 14px) 0,100% 50%,calc(100% - 14px) 100%,0 100%);border-radius:5px 0 0 5px;padding-left:14px;margin-left:0}
.as-btn:last-child{clip-path:polygon(14px 0,100% 0,100% 100%,0 100%,14px 50%);border-radius:0 5px 5px 0;padding-right:14px}
.as-btn:only-child{clip-path:none;border-radius:5px;padding:11px 14px;margin-left:0}
.as-btn.as-done{background:#198754}.as-btn.as-current{background:#0d6efd;font-weight:700;font-size:13px}
.as-btn:hover:not(:disabled):not(.as-current){filter:brightness(1.18)}.as-btn:disabled{cursor:wait;opacity:.72}
.as-btn:nth-child(1){z-index:1}.as-btn:nth-child(2){z-index:2}.as-btn:nth-child(3){z-index:3}.as-btn:nth-child(4){z-index:4}.as-btn:nth-child(5){z-index:5}.as-btn:nth-child(6){z-index:6}.as-btn:nth-child(7){z-index:7}.as-btn:nth-child(8){z-index:8}
@media(max-width:576px){.as-row{flex-direction:column;gap:5px}.as-btn,.as-btn:first-child,.as-btn:last-child,.as-btn:only-child{clip-path:none!important;border-radius:6px!important;margin-left:0!important;padding:10px 16px!important;width:100%;justify-content:flex-start;font-size:13px;z-index:auto!important}}
`;

function ArrowSteps({ statu, onChangeStatu, disabled }) {
    useEffect(() => {
        if (!document.getElementById(AS_STYLE_ID)) {
            const el = document.createElement('style');
            el.id = AS_STYLE_ID;
            el.textContent = AS_CSS;
            document.head.appendChild(el);
        }
    }, []);

    const currentIndex = STATUS_LABELS.findIndex(s => s.value === statu);

    return (
        <div className="as-track mb-2">
            <div className="as-row">
                {STATUS_LABELS.map((s, i) => (
                    <button
                        key={s.value}
                        className={`as-btn${i < currentIndex ? ' as-done' : ''}${s.value === statu ? ' as-current' : ''}`}
                        disabled={disabled}
                        onClick={e => { e.preventDefault(); if (!disabled) onChangeStatu(s.value); }}
                        title={s.label}
                    >
                        {s.label}
                    </button>
                ))}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Info Tab (left col)
// ---------------------------------------------------------------------------

function InfoForm({ quotation, suppliers, supplierQualification, addresses, contacts, affairOptions = [], endpoints, trans, onUpdated, onSupplierChange }) {
    const [label,      setLabel]      = useState(quotation.label ?? '');
    // Affaire rattachée hors tâche : « type:id » (order:12, quote:7, opportunity:3), vide = aucune
    const [affairLink, setAffairLink] = useState(quotation.affair_link ?? '');
    const [companiesId,setCompaniesId]= useState(quotation.companies_id ?? '');
    const [addressId,  setAddressId]  = useState(quotation.companies_addresses_id ?? '');
    const [contactId,  setContactId]  = useState(quotation.companies_contacts_id ?? '');
    const [delay,      setDelay]      = useState(quotation.delay ?? '');
    const [comment,    setComment]    = useState(quotation.comment ?? '');
    // Offre reçue du fournisseur (référence, indice, date, validité)
    const [offer,      setOffer]      = useState({
        supplier_offer_reference: quotation.supplier_offer_reference ?? '',
        supplier_offer_revision:  quotation.supplier_offer_revision ?? '',
        supplier_offer_date:      quotation.supplier_offer_date ?? '',
        supplier_offer_validity:  quotation.supplier_offer_validity ?? '',
    });
    const setOfferField = (field) => (e) => setOffer(prev => ({ ...prev, [field]: e.target.value }));
    const offerTrans = trans.offers ?? {};
    const locked = !!quotation.superseded;
    const [saving,     setSaving]     = useState(false);
    const [flash,      setFlash]      = useState(null);
    const [errors,     setErrors]     = useState({});

    const [localAddresses, setLocalAddresses] = useState(addresses ?? []);
    const [localContacts,  setLocalContacts]  = useState(contacts  ?? []);

    async function handleSupplierChange(e) {
        const id = e.target.value;
        setCompaniesId(id);
        setAddressId('');
        setContactId('');
        if (!id) { setLocalAddresses([]); setLocalContacts([]); return; }
        try {
            const data = await apiFetch(`${endpoints.companyData}?company_id=${id}`);
            setLocalAddresses(data.addresses);
            setLocalContacts(data.contacts);
            if (onSupplierChange) onSupplierChange(id);
        } catch {}
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});
        setSaving(true);
        try {
            await apiFetch(endpoints.update, {
                method: 'PATCH',
                body: JSON.stringify({
                    label,
                    companies_id:           companiesId || null,
                    companies_addresses_id: addressId   || null,
                    companies_contacts_id:  contactId   || null,
                    delay:   delay   || null,
                    comment: comment || null,
                    affair_link: affairLink || null,
                    supplier_offer_reference: offer.supplier_offer_reference || null,
                    supplier_offer_revision:  offer.supplier_offer_revision  || null,
                    supplier_offer_date:      offer.supplier_offer_date      || null,
                    supplier_offer_validity:  offer.supplier_offer_validity  || null,
                }),
            });
            setFlash({ type: 'success', msg: trans.update_success });
            if (onUpdated) onUpdated({ label, companies_id: companiesId, companies_addresses_id: addressId, companies_contacts_id: contactId, affair_link: affairLink, ...offer });
        } catch (err) {
            if (err.status === 422 && err.data?.errors) {
                setErrors(err.data.errors);
            } else {
                setFlash({ type: 'danger', msg: err.data?.message ?? trans.error_generic });
            }
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            {/* Sert seulement à griser le formulaire d'une offre remplacée : sans cadre ni marge */}
            <fieldset disabled={locked} className="border-0 p-0 m-0" style={{ minWidth: 0 }}>
            <div className="card card-outline card-primary mb-3">
                <div className="card-header">
                    <h3 className="card-title">{trans.informations}</h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool" data-lte-toggle="card-maximize">
                            <i className="fas fa-expand" />
                        </button>
                    </div>
                </div>
                <div className="card-body">
                    {flash && (
                        <div className={`alert alert-${flash.type} alert-dismissible`}>
                            <button type="button" onClick={() => setFlash(null)} aria-label="Close" className="btn-close float-end"></button>
                            {flash.msg}
                        </div>
                    )}

                    <div className="row">
                        <div className="mb-3 col-md-3">
                            <label className="text-success">{trans.external_id}</label>
                            <span className="ms-2">{quotation.code}</span>
                        </div>
                        <div className="mb-3 col-md-6">
                            <label>{trans.name_quote}</label>
                            <input
                                type="text"
                                className={`form-control${errors.label ? ' is-invalid' : ''}`}
                                value={label}
                                onChange={e => setLabel(e.target.value)}
                            />
                            {errors.label && <div className="invalid-feedback">{errors.label[0]}</div>}
                        </div>
                    </div>

                    <div className="row">
                        {(!quotation.companies_contacts_id || quotation.companies_contacts_id === 0) &&
                         (!quotation.companies_addresses_id || quotation.companies_addresses_id === 0) && (
                            <div className="col-12">
                                <div className="alert alert-info">{trans.update_valid}</div>
                            </div>
                        )}
                    </div>

                    <div className="row">
                        <div className="mb-3 col-md-6">
                            <label>{trans.supplier}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-building" /></span>
                                <select className="form-select" value={companiesId} onChange={handleSupplierChange}>
                                    <option value="">{trans.select_supplier}</option>
                                    {suppliers.map(s => (
                                        <option key={s.id} value={s.id}>{supplierOptionLabel(`${s.code} - ${s.label}`, s.qualification, supplierQualification)}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="row">
                        <div className="mb-3 col-md-6">
                            <label>{trans.address}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-map-marker-alt" /></span>
                                <select className="form-select" value={addressId} onChange={e => setAddressId(e.target.value)}>
                                    <option value="">{trans.select_address}</option>
                                    {localAddresses.map(a => (
                                        <option key={a.id} value={a.id}>{a.label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="mb-3 col-md-6">
                            <label>{trans.contact}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-user" /></span>
                                <select className="form-select" value={contactId} onChange={e => setContactId(e.target.value)}>
                                    <option value="">{trans.select_contact}</option>
                                    {localContacts.map(c => (
                                        <option key={c.id} value={c.id}>{c.label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="row">
                        <div className="mb-3 col-md-12">
                            <label>{trans.affair_link}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-link" /></span>
                                <select className="form-select" value={affairLink} onChange={e => setAffairLink(e.target.value)}>
                                    <option value="">{trans.affair_none}</option>
                                    {affairOptions.map(group => (
                                        <optgroup key={group.group} label={group.group}>
                                            {group.options.map(o => (
                                                <option key={o.value} value={o.value}>{o.label}</option>
                                            ))}
                                        </optgroup>
                                    ))}
                                </select>
                            </div>
                            <small className="form-text text-muted">
                                {trans.affair_help}
                                {quotation.affair && <> — <a href={quotation.affair.url}>{quotation.affair.label}</a></>}
                            </small>
                            {errors.affair_link && <div className="text-danger small">{errors.affair_link[0]}</div>}
                        </div>
                    </div>

                    <div className="row">
                        <div className="mb-3 col-md-6">
                            <label>{trans.validity_date}</label>
                            <input
                                type="date"
                                className="form-control"
                                value={delay ?? ''}
                                onChange={e => setDelay(e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="row">
                        <div className="mb-3 col-md-3">
                            <label htmlFor="supplier-offer-reference">{offerTrans.reference}</label>
                            <input
                                id="supplier-offer-reference"
                                type="text"
                                maxLength={255}
                                className={`form-control${errors.supplier_offer_reference ? ' is-invalid' : ''}`}
                                value={offer.supplier_offer_reference}
                                onChange={setOfferField('supplier_offer_reference')}
                            />
                            {errors.supplier_offer_reference && <div className="invalid-feedback">{errors.supplier_offer_reference[0]}</div>}
                        </div>
                        <div className="mb-3 col-md-3">
                            <label htmlFor="supplier-offer-revision">{offerTrans.revision}</label>
                            <input
                                id="supplier-offer-revision"
                                type="text"
                                maxLength={20}
                                className={`form-control${errors.supplier_offer_revision ? ' is-invalid' : ''}`}
                                value={offer.supplier_offer_revision}
                                onChange={setOfferField('supplier_offer_revision')}
                            />
                            {errors.supplier_offer_revision && <div className="invalid-feedback">{errors.supplier_offer_revision[0]}</div>}
                        </div>
                        <div className="mb-3 col-md-3">
                            <label htmlFor="supplier-offer-date">{offerTrans.date}</label>
                            <input
                                id="supplier-offer-date"
                                type="date"
                                className={`form-control${errors.supplier_offer_date ? ' is-invalid' : ''}`}
                                value={offer.supplier_offer_date}
                                onChange={setOfferField('supplier_offer_date')}
                            />
                            {errors.supplier_offer_date && <div className="invalid-feedback">{errors.supplier_offer_date[0]}</div>}
                        </div>
                        <div className="mb-3 col-md-3">
                            <label htmlFor="supplier-offer-validity">{offerTrans.validity}</label>
                            <input
                                id="supplier-offer-validity"
                                type="date"
                                className={`form-control${errors.supplier_offer_validity ? ' is-invalid' : ''}`}
                                value={offer.supplier_offer_validity}
                                onChange={setOfferField('supplier_offer_validity')}
                            />
                            {errors.supplier_offer_validity && <div className="invalid-feedback">{errors.supplier_offer_validity[0]}</div>}
                        </div>
                    </div>

                    <div className="row">
                        <div className="mb-3 col-md-12">
                            <label>{trans.comment}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-comment" /></span>
                                <textarea
                                    className="form-control"
                                    rows="3"
                                    name="comment"
                                    value={comment ?? ''}
                                    onChange={e => setComment(e.target.value)}
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div className="card-footer">
                    <button type="submit" className="btn btn-info rounded-0" disabled={saving}>
                        <i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-save'} me-1`} />
                        {trans.update}
                    </button>
                </div>
            </div>
            </fieldset>
        </form>
    );
}

// ---------------------------------------------------------------------------
// Options sidebar
// ---------------------------------------------------------------------------

/**
 * Nouvelle offre du fournisseur (indice suivant) : la demande et ses lignes sont
 * recopiées, celle-ci passe « remplacée ». Indice vide = indice suivant proposé.
 */
function NewOfferRevision({ endpoint, trans }) {
    const [revision, setRevision] = useState('');
    const [saving,   setSaving]   = useState(false);
    const [error,    setError]    = useState(null);

    async function handleClick() {
        setSaving(true);
        setError(null);
        try {
            const data = await apiFetch(endpoint, {
                method: 'POST',
                body: JSON.stringify({ supplier_offer_revision: revision.trim() || null }),
            });
            window.location.assign(data.redirect);
        } catch (err) {
            setError(err.data?.message ?? trans.error_generic);
            setSaving(false);
        }
    }

    return (
        <>
            <label htmlFor="new-offer-revision" className="small text-muted d-block">{trans.offers?.revision_prompt}</label>
            <div className="input-group input-group-sm">
                <input
                    id="new-offer-revision"
                    type="text"
                    className="form-control"
                    maxLength={20}
                    value={revision}
                    onChange={e => setRevision(e.target.value)}
                />
                <button type="button" className="btn btn-outline-primary" disabled={saving} onClick={handleClick}
                        title={trans.offers?.new_revision_help}>
                    <i className={`fas ${saving ? 'fa-spinner fa-spin' : 'fa-code-branch'} me-1`} />
                    {trans.offers?.new_revision}
                </button>
            </div>
            <small className="form-text text-muted">{trans.offers?.new_revision_help}</small>
            {error && <div className="text-danger small mt-1">{error}</div>}
        </>
    );
}

function OffersHistory({ offers, trans }) {
    if (!offers || offers.length === 0) return null;
    const t = trans.offers ?? {};

    return (
        <div className="card card-outline card-secondary mb-3">
            <div className="card-header">
                <h3 className="card-title">{t.history}</h3>
            </div>
            <div className="card-body p-0">
                <table className="table table-sm mb-0">
                    <tbody>
                        {offers.map(o => (
                            <tr key={o.id} className={o.current ? 'table-active' : undefined}>
                                <td>
                                    {o.current ? <strong>{o.code}</strong> : <a href={o.url}>{o.code}</a>}
                                    <div className="small text-muted">{offerLabel(o, t)}</div>
                                    {(o.date || o.validity) && (
                                        <div className="small text-muted">
                                            {o.date}{o.validity && <> → {o.validity}</>}
                                        </div>
                                    )}
                                </td>
                                <td className="text-end text-nowrap">
                                    <div>{o.total}</div>
                                    {o.superseded
                                        ? <span className="badge text-bg-warning">{t.superseded}</span>
                                        : <span className="badge text-bg-success">{t.current}</span>}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function OptionsSidebar({ quotation, offers, pdfLanguages = null, endpoints, trans }) {
    // Langue du PDF (?lang=), comme le menu d'impression des devis : utile pour un fournisseur étranger.
    const languages = pdfLanguages?.list ?? {};
    const [pdfLang, setPdfLang] = useState(pdfLanguages?.current ?? '');
    const pdfHref = quotation.pdf_url && languages[pdfLang]
        ? `${quotation.pdf_url}${quotation.pdf_url.includes('?') ? '&' : '?'}lang=${encodeURIComponent(pdfLang)}`
        : quotation.pdf_url;
    const canPdf = quotation.companies_contacts_id &&
                   quotation.companies_contacts_id !== 0 &&
                   quotation.companies_addresses_id &&
                   quotation.companies_addresses_id !== 0;

    return (
        <>
            <div className="card card-outline card-secondary mb-3">
                <div className="card-header">
                    <h3 className="card-title">{trans.options}<GuideHelp guide="achats" section="consulter-le-fournisseur-la-demande-de-prix" /></h3>
                    <div className="card-tools">
                        <button type="button" className="btn btn-tool" data-lte-toggle="card-maximize">
                            <i className="fas fa-expand" />
                        </button>
                    </div>
                </div>
                <div className="card-body p-0">
                    <table className="table table-hover mb-0">
                        <tbody>
                            <tr>
                                <td style={{ width: '50%' }}>{trans.rfq_label}</td>
                                <td>
                                    {canPdf && quotation.pdf_url ? (
                                        <div className="d-flex flex-wrap align-items-center gap-1">
                                            {Object.keys(languages).length > 1 && (
                                                <select
                                                    className="form-select form-select-sm w-auto"
                                                    value={languages[pdfLang] ? pdfLang : ''}
                                                    onChange={(e) => setPdfLang(e.target.value)}
                                                >
                                                    {!languages[pdfLang] && <option value="">{String(pdfLang).toUpperCase()}</option>}
                                                    {Object.entries(languages).map(([code, name]) => (
                                                        <option key={code} value={code}>{name}</option>
                                                    ))}
                                                </select>
                                            )}
                                            <a href={pdfHref} className="btn btn-outline-danger btn-sm" target="_blank" rel="noreferrer">
                                                <i className="fas fa-file-pdf me-1" />PDF
                                            </a>
                                        </div>
                                    ) : (
                                        <span className="text-muted small">{trans.update_valid}</span>
                                    )}
                                </td>
                            </tr>
                            {quotation.email_url && (
                                <tr>
                                    <td>{trans.email}</td>
                                    <td>
                                        <a href={quotation.email_url} className="btn btn-outline-info btn-sm">
                                            <i className="fas fa-envelope me-1" />{trans.email}
                                        </a>
                                    </td>
                                </tr>
                            )}
                            {quotation.compare_url && (
                                <tr>
                                    <td>{trans.compare_rfq}</td>
                                    <td>
                                        <a href={quotation.compare_url} className="btn btn-outline-primary btn-sm">
                                            <i className="fas fa-balance-scale me-1" />{trans.compare_rfq}
                                        </a>
                                    </td>
                                </tr>
                            )}
                            <tr>
                                <td>{trans.duplicate}</td>
                                <td>
                                    <a href={quotation.duplicate_url} className="btn btn-outline-secondary btn-sm">
                                        <i className="fas fa-copy me-1" />{trans.duplicate}
                                    </a>
                                </td>
                            </tr>
                            {quotationLocks(quotation).canRevise && endpoints.newRevision && (
                                <tr>
                                    <td colSpan={2}>
                                        <NewOfferRevision endpoint={endpoints.newRevision} trans={trans} />
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            <OffersHistory offers={offers} trans={trans} />

            {/* Email logs */}
            {quotation.email_logs && quotation.email_logs.length > 0 && (
                <div className="card card-outline card-secondary mb-3">
                    <div className="card-header">
                        <h3 className="card-title">{trans.email_history}</h3>
                    </div>
                    <div className="card-body p-0">
                        <table className="table table-sm mb-0">
                            <tbody>
                                {quotation.email_logs.map(log => (
                                    <tr key={log.id}>
                                        <td>
                                            <i className="fas fa-envelope text-muted me-1" />
                                            {log.subject}
                                        </td>
                                        <td className="text-muted small">{log.created_at}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// Line Form
// ---------------------------------------------------------------------------

function LineForm({ quotation, products, currency, endpoints, trans, onLineAdded, onLineUpdated, editingLine, onCancelEdit }) {
    const nextOrder = useCallback(() => {
        if (!quotation.lines || quotation.lines.length === 0) return 10;
        const max = Math.max(...quotation.lines.map(l => l.ordre ?? 0));
        return max + 10;
    }, [quotation.lines]);

    const [ordre,      setOrdre]      = useState(editingLine?.ordre      ?? nextOrder());
    const [productId,  setProductId]  = useState(editingLine?.product_id ?? '');
    const [code,       setCode]       = useState(editingLine?.code       ?? '');
    const [lineLabel,  setLineLabel]  = useState(editingLine?.label      ?? '');
    const [qty,        setQty]        = useState(editingLine?.qty_to_order ?? 0);
    const [unitPrice,  setUnitPrice]  = useState(editingLine?.unit_price  ?? 0);
    const [saving,     setSaving]     = useState(false);
    const [errors,     setErrors]     = useState({});

    const isEditing = !!editingLine;

    // Sync fields when editingLine changes
    useEffect(() => {
        if (editingLine) {
            setOrdre(editingLine.ordre);
            setProductId(editingLine.product_id ?? '');
            setCode(editingLine.code ?? '');
            setLineLabel(editingLine.label ?? '');
            setQty(editingLine.qty_to_order);
            setUnitPrice(editingLine.unit_price);
        } else {
            setOrdre(nextOrder());
            setProductId('');
            setCode('');
            setLineLabel('');
            setQty(0);
            setUnitPrice(0);
        }
    }, [editingLine]);

    function handleProductChange(e) {
        const id = e.target.value;
        setProductId(id);
        if (!id) { setCode(''); setLineLabel(''); setUnitPrice(0); return; }
        const product = products.find(p => p.id === Number(id));
        if (product) {
            setCode(product.code ?? '');
            setLineLabel(product.label ?? '');
            setUnitPrice(product.selling_price ?? 0);
        }
    }

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});
        setSaving(true);
        const body = {
            ordre:        Number(ordre),
            label:        lineLabel,
            qty_to_order: Number(qty),
            unit_price:   Number(unitPrice),
            product_id:   productId ? Number(productId) : null,
            code,
        };
        try {
            if (isEditing) {
                const data = await apiFetch(endpoints.updateLine.replace('__ID__', editingLine.id), {
                    method: 'PATCH',
                    body: JSON.stringify(body),
                });
                onLineUpdated(data.line);
            } else {
                const data = await apiFetch(endpoints.storeLine, {
                    method: 'POST',
                    body: JSON.stringify(body),
                });
                onLineAdded(data.line);
                setOrdre(Number(ordre) + 10);
                setProductId(''); setCode(''); setLineLabel(''); setQty(0); setUnitPrice(0);
            }
        } catch (err) {
            if (err.status === 422 && err.data?.errors) {
                setErrors(err.data.errors);
            }
        } finally {
            setSaving(false);
        }
    }

    return (
        <form onSubmit={handleSubmit}>
            <div className="row gx-2">
                {/* Sort */}
                <div className="mb-3 col-md-2">
                    <label>{trans.sort}</label>
                    <div className="input-group">
                        <span className="input-group-text"><i className="fas fa-sort-numeric-down" /></span>
                        <input
                            type="number"
                            className={`form-control${errors.ordre ? ' is-invalid' : ''}`}
                            value={ordre}
                            min="0"
                            onChange={e => setOrdre(e.target.value)}
                        />
                    </div>
                    {errors.ordre && <span className="text-danger small">{errors.ordre[0]}</span>}
                </div>

                {/* Product */}
                <div className="mb-3 col-md-2">
                    <label>{trans.product}</label>
                    <div className="input-group">
                        <span className="input-group-text text-bg-info bg-gradient"><i className="fas fa-barcode" /></span>
                        <select
                            className="form-select"
                            value={productId}
                            onChange={handleProductChange}
                        >
                            <option value="">{trans.select_product}</option>
                            {products.map(p => (
                                <option key={p.id} value={p.id}>{p.code} - {p.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                {/* Code */}
                <div className="mb-3 col-md-2">
                    <label>{trans.external_id}</label>
                    <div className="input-group">
                        <span className="input-group-text"><i className="fas fa-external-link-square-alt" /></span>
                        <input
                            type="text"
                            className={`form-control${errors.code ? ' is-invalid' : ''}`}
                            value={code}
                            onChange={e => setCode(e.target.value)}
                        />
                    </div>
                </div>

                {/* Label */}
                <div className="mb-3 col-md-2">
                    <label>{trans.description}</label>
                    <div className="input-group">
                        <span className="input-group-text"><i className="fas fa-tags" /></span>
                        <input
                            type="text"
                            className={`form-control${errors.label ? ' is-invalid' : ''}`}
                            value={lineLabel}
                            onChange={e => setLineLabel(e.target.value)}
                        />
                    </div>
                    {errors.label && <span className="text-danger small">{errors.label[0]}</span>}
                </div>

                {/* Qty */}
                <div className="mb-3 col-md-1">
                    <label>{trans.qty}</label>
                    <div className="input-group">
                        <span className="input-group-text"><i className="fas fa-times" /></span>
                        <input
                            type="number"
                            className={`form-control${errors.qty_to_order ? ' is-invalid' : ''}`}
                            value={qty}
                            min="0"
                            onChange={e => setQty(e.target.value)}
                        />
                    </div>
                    {errors.qty_to_order && <span className="text-danger small">{errors.qty_to_order[0]}</span>}
                </div>

                {/* Unit price */}
                <div className="mb-3 col-md-2">
                    <label>{trans.price}</label>
                    <div className="input-group">
                        <span className="input-group-text">{currency}</span>
                        <input
                            type="number"
                            className={`form-control${errors.unit_price ? ' is-invalid' : ''}`}
                            value={unitPrice}
                            min="0"
                            step="0.001"
                            onChange={e => setUnitPrice(e.target.value)}
                        />
                    </div>
                    {errors.unit_price && <span className="text-danger small">{errors.unit_price[0]}</span>}
                </div>

                {/* Submit */}
                <div className="mb-3 col-md-1 d-flex align-items-end">
                    <button type="submit" className="btn btn-success w-100" disabled={saving}>
                        {saving ? <i className="fas fa-spinner fa-spin" /> : trans.submit}
                    </button>
                </div>

                {isEditing && (
                    <div className="mb-3 col-md-1 d-flex align-items-end">
                        <button type="button" className="btn btn-secondary w-100" onClick={onCancelEdit}>
                            {trans.cancel}
                        </button>
                    </div>
                )}
            </div>
        </form>
    );
}

// ---------------------------------------------------------------------------
// Lines Tab
// ---------------------------------------------------------------------------

/**
 * Fournisseur de la demande de prix non qualifié (option active) : la commande qui en
 * sort sera refusée, sauf passe-droit motivé saisi ici et envoyé avec le formulaire.
 */
function OrderSupplierCheck({ status, config }) {
    if (!showsBadge(config, status) || status === QUALIFIED) return null;
    const texts = config.override_texts ?? {};

    return (
        <div className="alert alert-warning">
            <div className="mb-2">
                <SupplierQualificationBadge status={status} config={config} className="me-2" />
                {texts.blocked}
                <GuideHelp guide="fournisseurs-qualifies" section="la-commande-est-refusee-le-passe-droit" />
            </div>
            {config.can_override ? (
                <>
                    <input type="hidden" name="supplier_override" value="1" />
                    <label className="form-label fw-bold mb-1" htmlFor="supplier-override-reason">{texts.reason_label} *</label>
                    <textarea
                        id="supplier-override-reason"
                        name="supplier_override_reason"
                        className="form-control"
                        rows={2}
                        minLength={3}
                        maxLength={2000}
                        required
                    />
                    <div className="form-text">{texts.reason_help}</div>
                </>
            ) : (
                <div className="small">{texts.no_permission}</div>
            )}
        </div>
    );
}

function LinesTab({ quotation, products, currency, endpoints, trans, onLinesChange, supplierStatus = null, supplierQualification = null }) {
    const [lines,       setLines]       = useState(quotation.lines ?? []);
    const [editingLine, setEditingLine] = useState(null);

    // L'onglet est démonté quand on passe sur « Info » : sans remonter la liste au parent, il
    // repartait des lignes du chargement et les lignes ajoutées depuis disparaissaient.
    useEffect(() => {
        onLinesChange?.(lines);
    }, [lines]);

    function handleLineAdded(line) {
        setLines(prev => [...prev, line]);
    }

    function handleLineUpdated(line) {
        setLines(prev => prev.map(l => l.id === line.id ? line : l));
        setEditingLine(null);
    }

    // Prix du fournisseur retenu comme coût de l'opération du devis
    const [retaining, setRetaining] = useState(null);
    const [retainFlash, setRetainFlash] = useState(null);

    async function handleRetain(line) {
        setRetaining(line.id);
        setRetainFlash(null);
        try {
            const data = await apiFetch(endpoints.retainLine.replace('__ID__', line.id), { method: 'POST' });
            setLines(data.lines);
            setRetainFlash({ type: 'success', msg: data.message });
        } catch (err) {
            setRetainFlash({ type: 'danger', msg: err.data?.message ?? trans.error_generic });
        } finally {
            setRetaining(null);
        }
    }

    // Offre remplacée par un indice suivant : ni modification ni commande
    const { canEditLines: canEdit, canOrder } = quotationLocks(quotation);

    return (
        <div id="PurchaseQuotationLines">
            <div className="row">
                <div className="col-12">
                    <div className="card mb-3">
                        <div className="card-body">
                            {retainFlash && (
                                <div className={`alert alert-${retainFlash.type} alert-dismissible`}>
                                    <button type="button" onClick={() => setRetainFlash(null)} aria-label="Close" className="btn-close float-end"></button>
                                    {retainFlash.msg}
                                </div>
                            )}
                            {canEdit ? (
                                <LineForm
                                    quotation={{ ...quotation, lines }}
                                    products={products}
                                    currency={currency}
                                    endpoints={endpoints}
                                    trans={trans}
                                    onLineAdded={handleLineAdded}
                                    onLineUpdated={handleLineUpdated}
                                    editingLine={editingLine}
                                    onCancelEdit={() => setEditingLine(null)}
                                />
                            ) : (
                                <div className="alert alert-info">
                                    <i className="fas fa-info-circle me-1" />{trans.info_statu}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="row">
                <div className="col-12 table-responsive">
                    <form method="POST" action={endpoints.storeOrder}>
                        <input type="hidden" name="_token" value={csrfToken()} />
                        <OrderSupplierCheck status={supplierStatus} config={supplierQualification} />
                        <table className="table table-striped">
                            <thead>
                                <tr>
                                    <th>{trans.order}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.order} {trans.label}</th>
                                    <th>{trans.label}</th>
                                    <th>{trans.product}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.price}</th>
                                    <th>{trans.total_price}</th>
                                    <th>{trans.action}</th>
                                    <th>{trans.qty_accepted}</th>
                                    <th>{trans.qty_canceled}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {lines.length === 0 ? (
                                    <tr>
                                        <td colSpan="11" className="text-center text-muted py-3">{trans.no_data}</td>
                                    </tr>
                                ) : lines.map(line => (
                                    <tr key={line.id}>
                                        <td>
                                            {line.order_url ? (
                                                <a href={line.order_url} className="btn btn-outline-secondary btn-sm">
                                                    {line.order_code}
                                                </a>
                                            ) : line.quote_url ? (
                                                <a href={line.quote_url} className="btn btn-outline-info btn-sm" title={trans.quote}>
                                                    <i className="fas fa-file-signature me-1" />{line.quote_code}
                                                </a>
                                            ) : <span>{trans.generic}</span>}
                                        </td>
                                        <td>
                                            {line.order_line_qty != null
                                                ? `${line.order_line_qty} x ${line.task_qty ?? ''}`
                                                : trans.generic}
                                        </td>
                                        <td>
                                            {line.order_line_label || line.label}
                                        </td>
                                        <td>
                                            {line.task_id ? (
                                                <>
                                                    <a href={line.task_url} className="btn btn-sm btn-success me-1">{trans.view}</a>
                                                    #{line.task_id} - {line.task_label}
                                                    {line.component_label ? ` - ${line.component_label}` : ''}
                                                </>
                                            ) : line.label}
                                        </td>
                                        <td>
                                            {line.component_url && (
                                                <a href={line.component_url} className="btn btn-sm btn-info me-1">
                                                    <i className="fas fa-eye" />
                                                </a>
                                            )}
                                            {!line.component_url && line.product_url && (
                                                <a href={line.product_url} className="btn btn-sm btn-info">
                                                    <i className="fas fa-eye" />
                                                </a>
                                            )}
                                        </td>
                                        <td>{Number(line.qty_to_order).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}</td>
                                        <td>{line.formatted_unit}</td>
                                        <td>{line.formatted_total}</td>
                                        <td>
                                            {canEdit && (
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-secondary btn-sm mb-1"
                                                    onClick={() => setEditingLine(line)}
                                                >
                                                    <i className="fas fa-edit" /> {trans.edit}
                                                </button>
                                            )}
                                            {line.quote_task && (
                                                <div className="mb-1">
                                                    {line.retained && (
                                                        <span className="badge text-bg-success me-1">
                                                            <i className="fas fa-check me-1" />{trans.retained}
                                                        </span>
                                                    )}
                                                    {!line.retain_blocked ? (
                                                        <button
                                                            type="button"
                                                            className="btn btn-outline-success btn-sm"
                                                            title={trans.retain_help}
                                                            disabled={retaining === line.id}
                                                            onClick={() => handleRetain(line)}
                                                        >
                                                            <i className={`fas ${retaining === line.id ? 'fa-spinner fa-spin' : 'fa-hand-holding-usd'} me-1`} />
                                                            {trans.retain}
                                                        </button>
                                                    ) : (
                                                        <small className="text-muted d-block">{trans.retain_refused?.[line.retain_blocked]}</small>
                                                    )}
                                                    <small className="text-muted d-block">{trans.quote_not_orderable}</small>
                                                </div>
                                            )}
                                            {!line.quote_task && line.qty_to_order > line.qty_accepted && (
                                                <div className="mb-3 mb-0">
                                                    <div className="form-check ">
                                                        <input
                                                            className="form-check-input"
                                                            type="checkbox"
                                                            id={`line-${line.id}`}
                                                            value={line.id}
                                                            name="PurchaseQuotationLine[]"
                                                        />
                                                        <label className="form-check-label" htmlFor={`line-${line.id}`}>+</label>
                                                    </div>
                                                    <label htmlFor={`price-${line.id}`} className="small">{trans.proposed_purchase_price}</label>
                                                    <input
                                                        type="number"
                                                        className="form-control form-control-sm"
                                                        name={`PurchaseQuotationLinePrice[${line.id}]`}
                                                        id={`price-${line.id}`}
                                                        step="0.001"
                                                        defaultValue={line.unit_price ?? 0}
                                                    />
                                                </div>
                                            )}
                                        </td>
                                        <td>{Number(line.qty_accepted).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}</td>
                                        <td>{Number(line.canceled_qty).toLocaleString('fr-FR', { maximumFractionDigits: 0 })}</td>
                                    </tr>
                                ))}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>{trans.order}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.order} {trans.label}</th>
                                    <th>{trans.label}</th>
                                    <th>{trans.product}</th>
                                    <th>{trans.qty}</th>
                                    <th>{trans.price}</th>
                                    <th>{trans.total_price}</th>
                                    <th>
                                        {canOrder && (
                                            <button type="submit" className="btn btn-primary">
                                                {trans.new_order}
                                            </button>
                                        )}
                                    </th>
                                    <th>{trans.qty_accepted}</th>
                                    <th>{trans.qty_canceled}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function PurchasesQuotationShow({
    initialQuotation,
    offers = [],
    suppliers,
    supplierQualification = null,
    initialAddresses,
    initialContacts,
    affairOptions = [],
    currency,
    pdfLanguages = null,
    endpoints,
    trans,
}) {
    L = trans?.ui ?? {};
    const [quotation,  setQuotation]  = useState(initialQuotation);
    const [products,   setProducts]   = useState([]);
    const [activeTab,  setActiveTab]  = useState('info');
    const [statuSaving,setStatuSaving]= useState(false);

    // Fetch products for line form
    useEffect(() => {
        apiFetch(endpoints.products)
            .then(data => setProducts(data.products))
            .catch(() => {});
    }, []);

    async function handleChangeStatu(statu) {
        if (statuSaving) return;
        setStatuSaving(true);
        try {
            const data = await apiFetch(endpoints.updateStatu, {
                method: 'PATCH',
                body: JSON.stringify({ statu }),
            });
            setQuotation(prev => ({ ...prev, statu: data.statu }));
        } catch {}
        finally { setStatuSaving(false); }
    }

    function handleInfoUpdated(updated) {
        setQuotation(prev => ({ ...prev, ...updated }));
    }

    return (
        <div className="card mb-3">
            {/* Tabs header */}
            <div className="card-header p-2">
                <ul className="nav nav-pills">
                    <li className="nav-item">
                        <a
                            className={`nav-link${activeTab === 'info' ? ' active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('info'); }}
                        >
                            {trans.purchase_quotation_info}
                        </a>
                    </li>
                    <li className="nav-item">
                        <a
                            className={`nav-link${activeTab === 'lines' ? ' active' : ''}`}
                            href="#"
                            onClick={e => { e.preventDefault(); setActiveTab('lines'); }}
                        >
                            {trans.purchase_quotation_lines}
                        </a>
                    </li>
                </ul>
            </div>

            <div className="card-body">
                {quotation.superseded && (
                    <div className="alert alert-warning d-flex flex-wrap align-items-center gap-2">
                        <i className="fas fa-history" />
                        <span>{(trans.offers?.superseded_banner ?? '').replace(':code', quotation.latest?.code ?? '')}</span>
                        {quotation.latest?.url && (
                            <a href={quotation.latest.url} className="btn btn-sm btn-outline-dark ms-auto">
                                {trans.offers?.open_latest}
                            </a>
                        )}
                    </div>
                )}
                <div className="tab-content">
                    {/* Info tab */}
                    <div className={`tab-pane${activeTab === 'info' ? ' active' : ''}`} id="PurchaseQuotation">
                        <ArrowSteps
                            statu={quotation.statu}
                            onChangeStatu={handleChangeStatu}
                            disabled={statuSaving}
                        />
                        <div className="row">
                            <div className="col-md-9">
                                <InfoForm
                                    quotation={quotation}
                                    suppliers={suppliers}
                                    supplierQualification={supplierQualification}
                                    addresses={initialAddresses}
                                    contacts={initialContacts}
                                    affairOptions={affairOptions}
                                    endpoints={endpoints}
                                    trans={trans}
                                    onUpdated={handleInfoUpdated}
                                />
                            </div>
                            <div className="col-md-3">
                                <OptionsSidebar quotation={quotation} offers={offers} pdfLanguages={pdfLanguages} endpoints={endpoints} trans={trans} />
                            </div>
                        </div>
                    </div>

                    {/* Lines tab */}
                    {activeTab === 'lines' && (
                        <LinesTab
                            quotation={quotation}
                            products={products}
                            currency={currency}
                            endpoints={endpoints}
                            onLinesChange={lines => setQuotation(prev => ({ ...prev, lines }))}
                            trans={trans}
                            supplierStatus={suppliers.find(s => String(s.id) === String(quotation.companies_id))?.qualification ?? null}
                            supplierQualification={supplierQualification}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}
