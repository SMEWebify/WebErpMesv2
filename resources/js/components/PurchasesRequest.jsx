import React, { useState, useEffect, useRef } from 'react';
import { apiFetchWithStatus as apiFetch } from '../lib/http';
import { SortIcon } from './table';
import { SupplierQualificationBadge, sendWithSupplierOverride, supplierOptionLabel } from '../lib/supplierQualification.jsx';

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export default function PurchasesRequest({
    lastPurchaseCode,
    lastQuotationCode,
    suppliers,
    supplierQualification = null,
    endpoints,
    trans,
}) {
    const [documentType,       setDocumentType]       = useState('PU');
    const [filterCompanyId,    setFilterCompanyId]    = useState('');
    const [selectedCompanies,  setSelectedCompanies]  = useState([]);
    const [companyId,          setCompanyId]          = useState('');
    const [code,               setCode]               = useState(lastPurchaseCode ?? '');
    const [label,              setLabel]              = useState(lastPurchaseCode ?? '');

    const [tasks,              setTasks]              = useState([]);
    const [selections,         setSelections]         = useState({}); // { [taskId]: true }
    const [sortField,          setSortField]          = useState('id');
    const [sortAsc,            setSortAsc]            = useState(true);
    const [loading,            setLoading]            = useState(false);
    const [submitting,         setSubmitting]         = useState(false);
    const [errors,             setErrors]             = useState({});

    const multiSelectRef = useRef(null);

    // Update code/label when document type changes
    useEffect(() => {
        if (documentType === 'PU') {
            setCode(lastPurchaseCode ?? '');
            setLabel(lastPurchaseCode ?? '');
        } else {
            setCode(lastQuotationCode ?? '');
            setLabel(lastQuotationCode ?? '');
        }
        setErrors({});
    }, [documentType]);

    // Fetch tasks whenever filter supplier or sort changes
    useEffect(() => {
        setLoading(true);
        const params = new URLSearchParams({ sort: sortField, dir: sortAsc ? 'asc' : 'desc' });
        if (filterCompanyId) params.set('company_id', filterCompanyId);

        apiFetch(`${endpoints.tasks}?${params}`)
            .then(data => {
                setTasks(data.tasks);
                setSelections({});
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [filterCompanyId, sortField, sortAsc]);

    // Sync multi-select native element → state
    useEffect(() => {
        const el = multiSelectRef.current;
        if (!el) return;
        function handleChange() {
            const values = Array.from(el.selectedOptions).map(o => parseInt(o.value, 10));
            setSelectedCompanies(values);
        }
        el.addEventListener('change', handleChange);
        return () => el.removeEventListener('change', handleChange);
    }, [documentType]);

    // ---------------------------------------------------------------------------
    // Sort
    // ---------------------------------------------------------------------------

    const sortedTasks = [...tasks].sort((a, b) => {
        let va = a[sortField] ?? '';
        let vb = b[sortField] ?? '';
        if (typeof va === 'string') va = va.toLowerCase();
        if (typeof vb === 'string') vb = vb.toLowerCase();
        if (va < vb) return sortAsc ? -1 : 1;
        if (va > vb) return sortAsc ? 1 : -1;
        return 0;
    });

    function handleSort(field) {
        if (sortField === field) {
            setSortAsc(v => !v);
        } else {
            setSortField(field);
            setSortAsc(true);
        }
    }

    // ---------------------------------------------------------------------------
    // Selection helpers
    // ---------------------------------------------------------------------------

    function handleCheck(taskId, checked) {
        setSelections(s => {
            const next = { ...s };
            if (checked) next[taskId] = true;
            else delete next[taskId];
            return next;
        });
    }

    // ---------------------------------------------------------------------------
    // Submit
    // ---------------------------------------------------------------------------

    async function handleSubmit(e) {
        e.preventDefault();
        setErrors({});

        const taskIds = Object.keys(selections).map(Number);

        const body = {
            document_type: documentType,
            code,
            label,
            task_ids: taskIds,
        };

        if (documentType === 'PU') {
            body.companies_id = companyId ? Number(companyId) : null;
        } else {
            body.selected_companies = selectedCompanies;
        }

        setSubmitting(true);
        try {
            // Commande à un fournisseur non qualifié : fenêtre de passe-droit puis renvoi
            const result = await sendWithSupplierOverride(endpoints.store, body);
            if (result.ok) {
                window.location.href = result.data.redirect;
                return;
            }
            if (result.status === 422 && result.data?.errors) {
                setErrors(result.data.errors);
            } else if (result.data?.message) {
                setErrors({ general: result.data.message });
            } else {
                setErrors({ general: 'An error occurred. Please try again.' });
            }
        } catch {
            setErrors({ general: 'An error occurred. Please try again.' });
        }
        setSubmitting(false);
    }

    const selectedSupplier = suppliers.find(c => String(c.id) === String(companyId));

    function fieldError(key) {
        const e = errors[key];
        if (!e) return null;
        return <span className="text-danger">{Array.isArray(e) ? e[0] : e}</span>;
    }

    // ---------------------------------------------------------------------------
    // Render
    // ---------------------------------------------------------------------------

    return (
        <div className="card mb-3">
            {errors.general && (
                <div className="alert alert-danger m-3">{errors.general}</div>
            )}

            <form onSubmit={handleSubmit}>
                <div className="card-body">
                    <div className="row gx-2">

                        {/* Document type */}
                        <div className="mb-3 col-md-3">
                            <label>{trans.document_type}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-tags" /></span>
                                <select
                                    className="form-select"
                                    value={documentType}
                                    onChange={e => setDocumentType(e.target.value)}
                                >
                                    <option value="">{trans.select_document}</option>
                                    <option value="PU">{trans.purchase_order}</option>
                                    <option value="PQ">{trans.purchase_quotation}</option>
                                </select>
                            </div>
                            {fieldError('document_type')}
                        </div>

                        {/* Filter by supplier (for table) */}
                        <div className="mb-3 col-md-3">
                            <label>{trans.sort_supplier}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-building" /></span>
                                <select
                                    className="form-select"
                                    value={filterCompanyId}
                                    onChange={e => setFilterCompanyId(e.target.value)}
                                >
                                    <option value="">{suppliers.length ? trans.select_company : trans.no_company}</option>
                                    {suppliers.map(c => (
                                        <option key={c.id} value={c.id}>{c.label}</option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* PU: single supplier */}
                        {documentType === 'PU' && (
                            <div className="mb-3 col-md-3">
                                <label>{trans.purchase_order} — Fournisseur</label>
                                <div className="input-group">
                                    <span className="input-group-text"><i className="fas fa-industry" /></span>
                                    <select
                                        className="form-select"
                                        value={companyId}
                                        onChange={e => setCompanyId(e.target.value)}
                                    >
                                        <option value="">{suppliers.length ? trans.select_company : trans.no_company}</option>
                                        {suppliers.map(c => (
                                            <option key={c.id} value={c.id}>{supplierOptionLabel(c.label, c.qualification, supplierQualification)}</option>
                                        ))}
                                    </select>
                                </div>
                                {selectedSupplier && (
                                    <div className="mt-1">
                                        <SupplierQualificationBadge status={selectedSupplier.qualification} config={supplierQualification} />
                                    </div>
                                )}
                                {fieldError('companies_id')}
                            </div>
                        )}

                        {/* PQ: multi-select suppliers */}
                        {documentType === 'PQ' && (
                            <div className="mb-3 col-md-3">
                                <label>{trans.select_suppliers}</label>
                                <div className="input-group">
                                    <span className="input-group-text"><i className="fas fa-industry" /></span>
                                    <select
                                        className="form-select"
                                        multiple
                                        ref={multiSelectRef}
                                        style={{ minHeight: '80px' }}
                                    >
                                        {suppliers.map(c => (
                                            <option key={c.id} value={c.id}>{c.label}</option>
                                        ))}
                                    </select>
                                </div>
                                {fieldError('selected_companies')}
                            </div>
                        )}

                        {/* Code */}
                        <div className="mb-3 col-md-3">
                            <label>{trans.external_id}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-external-link-square-alt" /></span>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={code}
                                    onChange={e => setCode(e.target.value)}
                                    placeholder={trans.external_id}
                                />
                            </div>
                            {fieldError('code')}
                        </div>

                        {/* Label */}
                        <div className="mb-3 col-md-3">
                            <label>{trans.label}</label>
                            <div className="input-group">
                                <span className="input-group-text"><i className="fas fa-tags" /></span>
                                <input
                                    type="text"
                                    className="form-control"
                                    value={label}
                                    onChange={e => setLabel(e.target.value)}
                                    placeholder={trans.label}
                                />
                            </div>
                            {fieldError('label')}
                        </div>

                        {/* Submit */}
                        <div className="mb-3 col-md-2 d-flex align-items-end">
                            <button type="submit" className="btn btn-success w-100" disabled={submitting}>
                                {submitting
                                    ? <><i className="fas fa-spinner fa-spin me-1" />{trans.new_purchase_doc}</>
                                    : trans.new_purchase_doc}
                            </button>
                        </div>
                    </div>

                    {/* CSV export row */}
                    <div className="row gx-2">
                        <div className="mb-3 col-md-4">
                            <label>{trans.sheet_metal_need}</label>
                            <a
                                href={endpoints.exportCsv}
                                className="btn btn-outline-info w-100"
                            >
                                <i className="fas fa-file-csv me-1" />
                                {trans.export_csv}
                            </a>
                            <small className="form-text text-muted">{trans.sheet_metal_hint}</small>
                        </div>
                    </div>
                </div>
            </form>

            {/* Tasks table */}
            <div className="table-responsive p-0">
                {loading ? (
                    <div className="text-center p-4">
                        <i className="fas fa-spinner fa-spin me-2" />Loading...
                    </div>
                ) : (
                    <table className="table table-hover">
                        <thead>
                            <tr>
                                <th>{trans.order}</th>
                                <th>{trans.qty}</th>
                                <th>{trans.order_label}</th>
                                <th>
                                    <button type="button" className="btn btn-secondary btn-sm" onClick={() => handleSort('label')}>
                                        {trans.task_label} <SortIcon field="label" sortField={sortField} sortAsc={sortAsc} />
                                    </button>
                                </th>
                                <th>{trans.product}</th>
                                <th>{trans.qty}</th>
                                <th>{trans.service}</th>
                                <th>{trans.action}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {sortedTasks.length === 0 ? (
                                <tr>
                                    <td colSpan="8" className="text-center text-muted py-3">
                                        {trans.no_data}
                                    </td>
                                </tr>
                            ) : sortedTasks.map(task => (
                                <tr key={task.id}>
                                    <td>
                                        {task.order_url
                                            ? <a href={task.order_url} className="btn btn-outline-secondary btn-sm">{task.order_code}</a>
                                            : <span>{trans.generic}</span>}
                                    </td>
                                    <td>
                                        {task.order_qty != null ? `${task.order_qty} x ${task.qty_required ?? ''}` : ''}
                                    </td>
                                    <td>{task.order_label ?? ''}</td>
                                    <td>
                                        <a href={task.task_url} className="btn btn-sm btn-success me-1">{trans.view}</a>
                                        #{task.id} - {task.label}
                                        {task.component_label ? ` - ${task.component_label}` : ''}
                                    </td>
                                    <td>
                                        {task.component_url && (
                                            <a href={task.component_url} className="btn btn-sm btn-info">
                                                <i className="fas fa-eye" />
                                            </a>
                                        )}
                                    </td>
                                    <td>{task.qty_required != null ? Number(task.qty_required).toLocaleString('fr-FR', { maximumFractionDigits: 0 }) : ''}</td>
                                    <td style={task.service_color ? { backgroundColor: task.service_color } : {}}>
                                        {task.service_label ?? ''}
                                    </td>
                                    <td>
                                        <div className="form-check ">
                                            <input
                                                className="form-check-input"
                                                type="checkbox"
                                                id={`task-${task.id}`}
                                                checked={!!selections[task.id]}
                                                onChange={e => handleCheck(task.id, e.target.checked)}
                                            />
                                            <label className="form-check-label" htmlFor={`task-${task.id}`}>
                                                {trans.add_to_document}
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>{trans.order}</th>
                                <th>{trans.qty}</th>
                                <th>{trans.order_label}</th>
                                <th>{trans.task_label}</th>
                                <th>{trans.product}</th>
                                <th>{trans.qty}</th>
                                <th>{trans.service}</th>
                                <th>{trans.action}</th>
                            </tr>
                        </tfoot>
                    </table>
                )}
            </div>
        </div>
    );
}
