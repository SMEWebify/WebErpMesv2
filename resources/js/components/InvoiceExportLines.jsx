import React, { useState, useEffect, useRef } from 'react';
import { formatQty } from '../utils';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

export default function InvoiceExportLines({ endpoints, trans }) {
    const [rows, setRows]         = useState([]);
    const [selected, setSelected] = useState({});
    const [loading, setLoading]   = useState(true);
    const [exporting, setExporting] = useState(false);
    const formRef = useRef(null);

    useEffect(() => {
        fetch(endpoints.list, { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => { setRows(data); setLoading(false); })
            .catch(() => setLoading(false));
    }, []);

    const toggle = (id) => setSelected(prev => ({ ...prev, [id]: !prev[id] }));

    const selectedIds = Object.entries(selected).filter(([, v]) => v).map(([k]) => k);

    const handleExport = (ext) => {
        if (selectedIds.length === 0) return;
        setExporting(true);

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

        // Mark as exported locally
        setRows(prev => prev.filter(r => !selected[r.id]));
        setSelected({});
        setExporting(false);
    };

    if (loading) return <div className="text-center p-3"><i className="fas fa-spinner fa-spin"></i></div>;

    return (
        <div className="card">
            <div className="card-body">
                <div className="row mb-3">
                    <div className="btn-group">
                        <button
                            className="btn btn-info"
                            type="button"
                            disabled={exporting || selectedIds.length === 0}
                            onClick={() => handleExport('csv')}
                        >
                            CSV
                        </button>
                    </div>
                    <div className="btn-group ml-1">
                        <button
                            className="btn btn-danger"
                            type="button"
                            disabled={exporting || selectedIds.length === 0}
                            onClick={() => handleExport('xlsx')}
                        >
                            XLS
                        </button>
                    </div>
                </div>

                <div className="table-responsive p-0">
                    <table className="table table-hover">
                        <thead>
                            <tr>
                                <th>{trans.order}</th>
                                <th>{trans.invoice}</th>
                                <th>{trans.customer}</th>
                                <th>{trans.external_id}</th>
                                <th>{trans.description}</th>
                                <th>{trans.qty}</th>
                                <th>{trans.unit}</th>
                                <th>{trans.price}</th>
                                <th>{trans.discount}</th>
                                <th>{trans.vat}</th>
                                <th>{trans.select_to_export}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length === 0 ? (
                                <tr><td colSpan={11} className="text-center">{trans.no_data}</td></tr>
                            ) : rows.map(row => (
                                <tr key={row.id}>
                                    <td>
                                        {row.order_id ? (
                                            <a href={endpoints.order.replace('__ID__', row.order_id)} className="btn btn-sm btn-primary">
                                                {row.order_code}
                                            </a>
                                        ) : row.order_code}
                                    </td>
                                    <td>{row.invoice_code}</td>
                                    <td>
                                        {row.companie_id ? (
                                            <a href={endpoints.companie.replace('__ID__', row.companie_id)} className="btn btn-sm btn-secondary">
                                                {row.companie_label}
                                            </a>
                                        ) : row.companie_label}
                                    </td>
                                    <td>{row.line_code}</td>
                                    <td>{row.line_label}</td>
                                    <td>{formatQty(row.qty)}</td>
                                    <td>{row.unit}</td>
                                    <td>{row.formatted_price}</td>
                                    <td>{row.discount} %</td>
                                    <td>{row.vat_rate} %</td>
                                    <td>
                                        <div className="custom-control custom-checkbox">
                                            <input
                                                className="custom-control-input"
                                                type="checkbox"
                                                id={`invoice_line_${row.id}`}
                                                checked={!!selected[row.id]}
                                                onChange={() => toggle(row.id)}
                                            />
                                            <label className="custom-control-label" htmlFor={`invoice_line_${row.id}`}>
                                                {trans.add_export}
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>{trans.order}</th>
                                <th>{trans.invoice}</th>
                                <th>{trans.customer}</th>
                                <th>{trans.external_id}</th>
                                <th>{trans.description}</th>
                                <th>{trans.qty}</th>
                                <th>{trans.unit}</th>
                                <th>{trans.price}</th>
                                <th>{trans.discount}</th>
                                <th>{trans.vat}</th>
                                <th>{trans.select_to_export}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    );
}
