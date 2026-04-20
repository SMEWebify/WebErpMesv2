import React, { useState, useEffect, useCallback } from 'react';

const JOURNAL_CODES = ['ACHAT', 'VENT'];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export default function FecExportLines({ endpoints, trans, initialStartDate, initialEndDate }) {
    const [rows, setRows]             = useState([]);
    const [selected, setSelected]     = useState({});
    const [loading, setLoading]       = useState(false);
    const [journalCodes, setJournalCodes] = useState(['ACHAT', 'VENT']);
    const [startDate, setStartDate]   = useState(initialStartDate ?? '');
    const [endDate, setEndDate]       = useState(initialEndDate ?? '');

    const load = useCallback(() => {
        setLoading(true);
        const params = new URLSearchParams();
        journalCodes.forEach(c => params.append('journal_codes[]', c));
        if (startDate) params.set('start_date', startDate);
        if (endDate)   params.set('end_date', endDate);

        fetch(`${endpoints.list}?${params}`, { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => { setRows(data); setSelected({}); })
            .finally(() => setLoading(false));
    }, [endpoints.list, journalCodes, startDate, endDate]);

    useEffect(() => { load(); }, []);

    const toggleJournal = (code) => {
        setJournalCodes(prev =>
            prev.includes(code) ? prev.filter(c => c !== code) : [...prev, code]
        );
    };

    const toggleRow = (id) => setSelected(prev => ({ ...prev, [id]: !prev[id] }));

    const selectedIds = Object.entries(selected).filter(([, v]) => v).map(([k]) => k);

    const handleExport = (ext) => {
        if (selectedIds.length === 0) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = endpoints.export.replace('__EXT__', ext);

        const csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = csrfToken();
        form.appendChild(csrf);

        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setRows(prev => prev.filter(r => !selected[r.id]));
        setSelected({});
    };

    return (
        <div className="card">
            <div className="card-body">
                {/* Boutons export */}
                <div className="row mb-2">
                    <div className="btn-group ml-3">
                        <button className="btn btn-info" disabled={selectedIds.length === 0} onClick={() => handleExport('csv')}>CSV</button>
                    </div>
                    <div className="btn-group ml-1">
                        <button className="btn btn-danger" disabled={selectedIds.length === 0} onClick={() => handleExport('xlsx')}>XLS</button>
                    </div>
                    <div className="btn-group ml-1">
                        <button className="btn btn-primary" disabled={selectedIds.length === 0} onClick={() => handleExport('pdf')}>PDF</button>
                    </div>
                </div>

                {/* Filtres */}
                <div className="row mt-3">
                    <div className="form-group col-2">
                        <label>Journaux</label>
                        {JOURNAL_CODES.map(code => (
                            <div className="form-check" key={code}>
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={journalCodes.includes(code)}
                                    onChange={() => toggleJournal(code)}
                                    id={`journal_${code}`}
                                />
                                <label className="form-check-label" htmlFor={`journal_${code}`}>{code}</label>
                            </div>
                        ))}
                    </div>
                    <div className="form-group col-2">
                        <label>{trans.start_date} :</label>
                        <input type="date" className="form-control" value={startDate} onChange={e => setStartDate(e.target.value)} />
                    </div>
                    <div className="form-group col-2">
                        <label>{trans.end_date} :</label>
                        <input type="date" className="form-control" value={endDate} onChange={e => setEndDate(e.target.value)} />
                    </div>
                    <div className="form-group col-2 align-self-end">
                        <button className="btn btn-danger" onClick={load} disabled={loading}>
                            {loading ? <i className="fas fa-spinner fa-spin"></i> : trans.submit}
                        </button>
                    </div>
                </div>

                {/* Tableau */}
                <div className="table-responsive p-0">
                    <table className="table table-hover">
                        <tbody>
                            {rows.length === 0 && !loading ? (
                                <tr><td colSpan={18} className="text-center">{trans.no_data}</td></tr>
                            ) : rows.map(row => (
                                <tr key={row.id}>
                                    <td>{row.journal_code}</td>
                                    <td>{row.journal_label}</td>
                                    <td>{row.sequence_number}</td>
                                    <td>{row.accounting_date}</td>
                                    <td>{row.account_number}</td>
                                    <td>{row.account_label}</td>
                                    <td>{row.justification_reference}</td>
                                    <td>{row.justification_date}</td>
                                    <td>{row.auxiliary_account_number}</td>
                                    <td>{row.auxiliary_account_label}</td>
                                    <td>{row.document_reference}</td>
                                    <td>{row.document_date}</td>
                                    <td>{row.entry_label}</td>
                                    <td>{row.formatted_debit}</td>
                                    <td>{row.formatted_credit}</td>
                                    <td>{row.entry_lettering}</td>
                                    <td>{row.currency_code}</td>
                                    <td>
                                        <div className="custom-control custom-checkbox">
                                            <input
                                                className="custom-control-input"
                                                type="checkbox"
                                                id={`fec_${row.id}`}
                                                checked={!!selected[row.id]}
                                                onChange={() => toggleRow(row.id)}
                                            />
                                            <label className="custom-control-label" htmlFor={`fec_${row.id}`}>
                                                {trans.add_export}
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}
