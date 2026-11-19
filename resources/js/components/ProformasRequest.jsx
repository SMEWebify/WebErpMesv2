import React, { useState, useEffect } from 'react';

// Textes de l'écran (resources/lang/*/billing_ui.php), posés par le composant principal
// avant le rendu de ses enfants ; repli sur le texte français.
let L = {};

// ── Mode "depuis commande existante" ────────────────────────────────────────
function FromOrderForm({ users, companies, endpoints, initialCode, userId }) {
    const [code,         setCode]         = useState(initialCode ?? '');
    const [label,        setLabel]        = useState('');
    const [companyId,    setCompanyId]    = useState('');
    const [addressId,    setAddressId]    = useState('');
    const [contactId,    setContactId]    = useState('');
    const [selectedUser, setSelectedUser] = useState(userId ?? '');
    const [addresses,    setAddresses]    = useState([]);
    const [contacts,     setContacts]     = useState([]);
    const [lines,        setLines]        = useState([]);
    const [selected,     setSelected]     = useState([]);
    const [loading,      setLoading]      = useState(false);
    const [submitting,   setSubmitting]   = useState(false);
    const [error,        setError]        = useState('');

    useEffect(() => {
        if (!companyId) { setLines([]); setAddresses([]); setContacts([]); return; }
        setLoading(true);
        fetch(`${endpoints.lines}?company_id=${companyId}`)
            .then(r => r.json())
            .then(d => {
                setLines(d.lines ?? []);
                setAddresses(d.addresses ?? []);
                setContacts(d.contacts ?? []);
                setAddressId(d.addresses?.[0]?.id ?? '');
                setContactId(d.contacts?.[0]?.id ?? '');
            })
            .finally(() => setLoading(false));
    }, [companyId]);

    function toggleLine(id) {
        setSelected(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);
    }

    function handleSubmit(e) {
        e.preventDefault();
        setError('');
        if (selected.length === 0) { setError((L.select_one_line ?? 'Sélectionnez au moins une ligne.')); return; }
        setSubmitting(true);
        fetch(endpoints.store, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({ code, label, companies_id: +companyId, companies_addresses_id: +addressId, companies_contacts_id: +contactId, user_id: +selectedUser, lines: selected }),
        })
            .then(r => r.json())
            .then(d => { if (d.redirect) window.location.href = d.redirect; })
            .catch(() => setError((L.error_create ?? 'Erreur lors de la création.')))
            .finally(() => setSubmitting(false));
    }

    return (
        <form onSubmit={handleSubmit}>
            {error && <div className="alert alert-danger">{error}</div>}
            <CommonHeader code={code} setCode={setCode} label={label} setLabel={setLabel}
                selectedUser={selectedUser} setSelectedUser={setSelectedUser} users={users}
                companyId={companyId} setCompanyId={setCompanyId} companies={companies}
                addressId={addressId} setAddressId={setAddressId} addresses={addresses}
                contactId={contactId} setContactId={setContactId} contacts={contacts} />

            {companyId && (
                <LineTable lines={lines} selected={selected} toggleLine={toggleLine} loading={loading}
                    emptyMsg="Aucune ligne de commande trouvée pour ce client." />
            )}

            <SubmitFooter submitting={submitting} count={selected.length} label={L.create_proforma ?? 'Créer la proforma'} />
        </form>
    );
}

// ── Mode "depuis devis" ─────────────────────────────────────────────────────
function FromQuoteForm({ users, companies, endpoints, initialCode, userId }) {
    const [code,         setCode]         = useState(initialCode ?? '');
    const [label,        setLabel]        = useState('');
    const [companyId,    setCompanyId]    = useState('');
    const [addressId,    setAddressId]    = useState('');
    const [contactId,    setContactId]    = useState('');
    const [selectedUser, setSelectedUser] = useState(userId ?? '');
    const [addresses,    setAddresses]    = useState([]);
    const [contacts,     setContacts]     = useState([]);
    const [quotes,       setQuotes]       = useState([]);
    const [quoteId,      setQuoteId]      = useState('');
    const [lines,        setLines]        = useState([]);
    const [selected,     setSelected]     = useState([]);
    const [loading,      setLoading]      = useState(false);
    const [submitting,   setSubmitting]   = useState(false);
    const [error,        setError]        = useState('');

    useEffect(() => {
        if (!companyId) { setQuotes([]); setLines([]); setAddresses([]); setContacts([]); return; }
        setLoading(true);
        Promise.all([
            fetch(`${endpoints.quotes}?company_id=${companyId}`).then(r => r.json()),
            fetch(`${endpoints.lines}?company_id=${companyId}`).then(r => r.json()),
        ]).then(([qData, lData]) => {
            setQuotes(qData.quotes ?? []);
            setAddresses(lData.addresses ?? []);
            setContacts(lData.contacts ?? []);
            setAddressId(lData.addresses?.[0]?.id ?? '');
            setContactId(lData.contacts?.[0]?.id ?? '');
        }).finally(() => setLoading(false));
    }, [companyId]);

    useEffect(() => {
        if (!quoteId) { setLines([]); setSelected([]); return; }
        const q = quotes.find(x => x.id === +quoteId);
        setLines(q?.lines ?? []);
        setSelected([]);
        if (q) setLabel(q.label ?? '');
    }, [quoteId, quotes]);

    function toggleLine(id) {
        setSelected(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);
    }

    function handleSubmit(e) {
        e.preventDefault();
        setError('');
        if (!quoteId) { setError((L.select_quote ?? 'Sélectionnez un devis.')); return; }
        if (selected.length === 0) { setError((L.select_one_line ?? 'Sélectionnez au moins une ligne.')); return; }
        setSubmitting(true);
        fetch(endpoints.storeFromQuote, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
            body: JSON.stringify({ code, label, quote_id: +quoteId, companies_id: +companyId, companies_addresses_id: +addressId, companies_contacts_id: +contactId, user_id: +selectedUser, lines: selected }),
        })
            .then(r => r.json())
            .then(d => { if (d.redirect) window.location.href = d.redirect; })
            .catch(() => setError((L.error_create ?? 'Erreur lors de la création.')))
            .finally(() => setSubmitting(false));
    }

    return (
        <form onSubmit={handleSubmit}>
            {error && <div className="alert alert-danger">{error}</div>}
            <CommonHeader code={code} setCode={setCode} label={label} setLabel={setLabel}
                selectedUser={selectedUser} setSelectedUser={setSelectedUser} users={users}
                companyId={companyId} setCompanyId={setCompanyId} companies={companies}
                addressId={addressId} setAddressId={setAddressId} addresses={addresses}
                contactId={contactId} setContactId={setContactId} contacts={contacts} />

            {companyId && (
                <div className="mb-3">
                    <label>{L.source_quote ?? 'Devis source'}</label>
                    {loading
                        ? <p><i className="fas fa-spinner fa-spin" /> {L.loading ?? 'Chargement…'}</p>
                        : (
                            <select className="form-select" value={quoteId} onChange={e => setQuoteId(e.target.value)} required>
                                <option value="">{L.choose_quote ?? '-- Choisir un devis --'}</option>
                                {quotes.map(q => <option key={q.id} value={q.id}>{q.code} — {q.label}</option>)}
                            </select>
                        )
                    }
                </div>
            )}

            {quoteId && (
                <LineTable lines={lines} selected={selected} toggleLine={toggleLine} loading={false}
                    emptyMsg="Ce devis n'a pas de lignes." />
            )}

            <SubmitFooter submitting={submitting} count={selected.length} label={L.create_proforma_draft_order ?? 'Créer la proforma (+ commande brouillon)'} />
        </form>
    );
}

// ── Sous-composants partagés ────────────────────────────────────────────────
function CommonHeader({ code, setCode, label, setLabel, selectedUser, setSelectedUser, users,
    companyId, setCompanyId, companies, addressId, setAddressId, addresses,
    contactId, setContactId, contacts }) {
    return (
        <>
            <div className="row">
                <div className="col-md-3 mb-3">
                    <label>{L.proforma_code ?? 'Code proforma'}</label>
                    <input className="form-control" value={code} onChange={e => setCode(e.target.value)} required />
                </div>
                <div className="col-md-5 mb-3">
                    <label>{L.label ?? 'Libellé'}</label>
                    <input className="form-control" value={label} onChange={e => setLabel(e.target.value)} required />
                </div>
                <div className="col-md-4 mb-3">
                    <label>{L.owner ?? 'Responsable'}</label>
                    <select className="form-select" value={selectedUser} onChange={e => setSelectedUser(e.target.value)} required>
                        <option value="">{L.choose_dashes ?? '-- Choisir --'}</option>
                        {(users ?? []).map(u => <option key={u.id} value={u.id}>{u.name}</option>)}
                    </select>
                </div>
            </div>
            <div className="row">
                <div className="col-md-4 mb-3">
                    <label>{L.customer ?? 'Client'}</label>
                    <select className="form-select" value={companyId} onChange={e => setCompanyId(e.target.value)} required>
                        <option value="">{L.choose_customer ?? '-- Choisir un client --'}</option>
                        {(companies ?? []).map(c => <option key={c.id} value={c.id}>{c.label}</option>)}
                    </select>
                </div>
                <div className="col-md-4 mb-3">
                    <label>{L.address ?? 'Adresse'}</label>
                    <select className="form-select" value={addressId} onChange={e => setAddressId(e.target.value)} required>
                        <option value="">{L.address_placeholder ?? '-- Adresse --'}</option>
                        {addresses.map(a => <option key={a.id} value={a.id}>{a.label}</option>)}
                    </select>
                </div>
                <div className="col-md-4 mb-3">
                    <label>{L.contact ?? 'Contact'}</label>
                    <select className="form-select" value={contactId} onChange={e => setContactId(e.target.value)} required>
                        <option value="">{L.contact_placeholder ?? '-- Contact --'}</option>
                        {contacts.map(c => <option key={c.id} value={c.id}>{c.first_name} {c.name}</option>)}
                    </select>
                </div>
            </div>
        </>
    );
}

function LineTable({ lines, selected, toggleLine, loading, emptyMsg }) {
    if (loading) return <p><i className="fas fa-spinner fa-spin" /> {L.loading ?? 'Chargement…'}</p>;
    if (lines.length === 0) return <p className="text-muted">{emptyMsg}</p>;
    return (
        <table className="table table-sm table-hover mt-3">
            <thead>
                <tr>
                    <th></th><th>{L.code ?? 'Code'}</th><th>{L.designation ?? 'Désignation'}</th><th>{L.qty ?? 'Qté'}</th><th>{L.unit_price_ht ?? 'PU HT'}</th><th>{L.discount ?? 'Remise'}</th>
                </tr>
            </thead>
            <tbody>
                {lines.map(l => (
                    <tr key={l.id} onClick={() => toggleLine(l.id)} style={{ cursor: 'pointer' }}
                        className={selected.includes(l.id) ? 'table-success' : ''}>
                        <td><input type="checkbox" checked={selected.includes(l.id)} onChange={() => toggleLine(l.id)} /></td>
                        <td>{l.code}</td>
                        <td>{l.label}</td>
                        <td>{l.qty}</td>
                        <td>{Number(l.selling_price).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })}</td>
                        <td>{l.discount} %</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

function SubmitFooter({ submitting, count, label }) {
    return (
        <div className="card-footer">
            <button type="submit" className="btn btn-success" disabled={submitting || count === 0}>
                {submitting ? <><i className="fas fa-spinner fa-spin me-1" />{L.creating ?? 'Création…'}</> : <><i className="fas fa-file-invoice me-1" />{label}</>}
            </button>
            <span className="ms-3 text-muted">{count} {L.lines_selected ?? 'ligne(s) sélectionnée(s)'}</span>
        </div>
    );
}

// ── Composant principal ─────────────────────────────────────────────────────
export default function ProformasRequest({ initialCode, userId, users, companies, endpoints, trans = {} }) {
    L = trans?.ui ?? {};
    const [mode, setMode] = useState('quote'); // 'quote' | 'order'

    return (
        <div className="card card-primary mb-3">
            <div className="card-header">
                <h3 className="card-title">{L.new_proforma_title ?? 'Nouvelle facture proforma'}</h3>
            </div>
            <div className="card-body">
                <div className="mb-4">
                    <div className="btn-group" role="group">
                        <button type="button"
                            className={`btn ${mode === 'quote' ? 'btn-primary' : 'btn-outline-primary'}`}
                            onClick={() => setMode('quote')}>
                            <i className="fas fa-file-alt me-1" /> {L.from_quote ?? 'Depuis un devis'}
                            <small className="d-block text-muted" style={{fontSize:'0.75em'}}>{L.from_quote_help ?? 'Crée une commande brouillon (statu 0)'}</small>
                        </button>
                        <button type="button"
                            className={`btn ${mode === 'order' ? 'btn-primary' : 'btn-outline-primary'}`}
                            onClick={() => setMode('order')}>
                            <i className="fas fa-shopping-cart me-1" /> {L.from_order ?? 'Depuis une commande existante'}
                        </button>
                    </div>
                </div>

                {mode === 'quote'
                    ? <FromQuoteForm initialCode={initialCode} userId={userId} users={users}
                        companies={companies} endpoints={endpoints} />
                    : <FromOrderForm initialCode={initialCode} userId={userId} users={users}
                        companies={companies} endpoints={endpoints} />
                }
            </div>
        </div>
    );
}
