import React, { useState, useEffect, useCallback } from 'react';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        headers: {
            'Accept':       'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            ...options.headers,
        },
        ...options,
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({ message: res.statusText }));
        throw err;
    }
    return res.json();
}

function buildUrl(template, id) {
    return template.replace('__ID__', id);
}

const AMOUNTS = [1,2,3,4,5,6,7,8,9,10,11,12];
const YEARS   = [2021,2022,2023,2024,2025,2026,2027,2028,2029,2030];

function emptyForm() {
    const f = { year: '' };
    AMOUNTS.forEach(i => { f[`amount${i}`] = ''; });
    return f;
}

function calcTotal(row) {
    return AMOUNTS.reduce((s, i) => s + (parseFloat(row[`amount${i}`]) || 0), 0);
}

function formatNum(val) {
    return Number(val).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

// ---------------------------------------------------------------------------
// Alert
// ---------------------------------------------------------------------------

function Alert({ type, message, onClose }) {
    if (!message) return null;
    return (
        <div className={`alert alert-${type} alert-dismissible`}>
            <button type="button" className="close" onClick={onClose}>
                <span>&times;</span>
            </button>
            {message}
        </div>
    );
}

// ---------------------------------------------------------------------------
// BudgetForm
// ---------------------------------------------------------------------------

function BudgetForm({ form, setForm, editId, onSubmit, onCancel, loading, errors, trans }) {
    const months = trans.months ?? [];
    const currency = trans.currency ?? '€';
    const isEdit = editId !== null;

    function handleChange(e) {
        setForm(f => ({ ...f, [e.target.name]: e.target.value }));
    }

    // Render 3 rows of 4 months each (like the original Blade), with year + submit in row 1
    const rows = [
        [1, 2, 3, 4],
        [5, 6, 7, 8],
        [9, 10, 11, 12],
    ];

    return (
        <div className="card">
            <div className="card-header">
                <h3 className="card-title">
                    <i className="fas fa-chart-line mr-2" />
                    {isEdit ? trans.update : trans.submit} — {trans.title}
                </h3>
            </div>
            <div className="card-body">
                <form onSubmit={onSubmit}>
                    {/* Row 1: year + months 1-4 + submit */}
                    <div className="row">
                        <div className="col-md-2">
                            <div className="form-group">
                                <label>{trans.year}</label>
                                <div className="input-group">
                                    <div className="input-group-prepend">
                                        <span className="input-group-text"><i className="fas fa-calendar" /></span>
                                    </div>
                                    <select
                                        name="year"
                                        className={`form-control${errors.year ? ' is-invalid' : ''}`}
                                        value={form.year}
                                        onChange={handleChange}
                                    >
                                        <option value="">{trans.select_year}</option>
                                        {YEARS.map(y => (
                                            <option key={y} value={y}>{y}</option>
                                        ))}
                                    </select>
                                    {errors.year && <div className="invalid-feedback">{errors.year[0]}</div>}
                                </div>
                            </div>
                        </div>
                        {rows[0].map(i => (
                            <div key={i} className="col-md-2">
                                <div className="form-group">
                                    <label>{months[i - 1] ?? `M${i}`}</label>
                                    <div className="input-group">
                                        <div className="input-group-prepend">
                                            <span className="input-group-text">{currency}</span>
                                        </div>
                                        <input
                                            type="number"
                                            step="0.001"
                                            name={`amount${i}`}
                                            className={`form-control${errors[`amount${i}`] ? ' is-invalid' : ''}`}
                                            placeholder={months[i - 1] ?? `M${i}`}
                                            value={form[`amount${i}`]}
                                            onChange={handleChange}
                                        />
                                        {errors[`amount${i}`] && <div className="invalid-feedback">{errors[`amount${i}`][0]}</div>}
                                    </div>
                                </div>
                            </div>
                        ))}
                        <div className="col-md-2 d-flex align-items-end">
                            <div className="form-group w-100">
                                <button
                                    type="submit"
                                    className={`btn btn-block ${isEdit ? 'btn-info' : 'btn-danger'}`}
                                    disabled={loading}
                                >
                                    {loading
                                        ? <i className="fas fa-spinner fa-spin" />
                                        : <><i className={`fas fa-save mr-1`} />{isEdit ? trans.update : trans.submit}</>
                                    }
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Row 2: months 5-8 + refresh/cancel */}
                    <div className="row">
                        <div className="col-md-2" /> {/* spacer under year */}
                        {rows[1].map(i => (
                            <div key={i} className="col-md-2">
                                <div className="form-group">
                                    <label>{months[i - 1] ?? `M${i}`}</label>
                                    <div className="input-group">
                                        <div className="input-group-prepend">
                                            <span className="input-group-text">{currency}</span>
                                        </div>
                                        <input
                                            type="number"
                                            step="0.001"
                                            name={`amount${i}`}
                                            className={`form-control${errors[`amount${i}`] ? ' is-invalid' : ''}`}
                                            placeholder={months[i - 1] ?? `M${i}`}
                                            value={form[`amount${i}`]}
                                            onChange={handleChange}
                                        />
                                        {errors[`amount${i}`] && <div className="invalid-feedback">{errors[`amount${i}`][0]}</div>}
                                    </div>
                                </div>
                            </div>
                        ))}
                        {isEdit && (
                            <div className="col-md-2 d-flex align-items-end">
                                <div className="form-group w-100">
                                    <button type="button" className="btn btn-secondary btn-block" onClick={onCancel}>
                                        <i className="fas fa-times mr-1" />{trans.cancel}
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Row 3: months 9-12 */}
                    <div className="row">
                        <div className="col-md-2" />
                        {rows[2].map(i => (
                            <div key={i} className="col-md-2">
                                <div className="form-group">
                                    <label>{months[i - 1] ?? `M${i}`}</label>
                                    <div className="input-group">
                                        <div className="input-group-prepend">
                                            <span className="input-group-text">{currency}</span>
                                        </div>
                                        <input
                                            type="number"
                                            step="0.001"
                                            name={`amount${i}`}
                                            className={`form-control${errors[`amount${i}`] ? ' is-invalid' : ''}`}
                                            placeholder={months[i - 1] ?? `M${i}`}
                                            value={form[`amount${i}`]}
                                            onChange={handleChange}
                                        />
                                        {errors[`amount${i}`] && <div className="invalid-feedback">{errors[`amount${i}`][0]}</div>}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </form>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// SortTh
// ---------------------------------------------------------------------------

function SortTh({ field, sortField, sortAsc, onSort, children }) {
    const active = sortField === field;
    return (
        <th style={{ cursor: 'pointer', whiteSpace: 'nowrap' }} onClick={() => onSort(field)}>
            {children}
            {' '}
            {active
                ? <i className={`fas fa-sort-${sortAsc ? 'up' : 'down'}`} />
                : <i className="fas fa-sort text-muted" />
            }
        </th>
    );
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

function Pagination({ meta, onPageChange }) {
    if (!meta || meta.last_page <= 1) return null;
    const pages = [];
    for (let p = 1; p <= meta.last_page; p++) pages.push(p);
    return (
        <nav>
            <ul className="pagination pagination-sm m-0 float-right">
                {pages.map(p => (
                    <li key={p} className={`page-item${meta.current_page === p ? ' active' : ''}`}>
                        <button className="page-link" onClick={() => onPageChange(p)}>{p}</button>
                    </li>
                ))}
            </ul>
        </nav>
    );
}

// ---------------------------------------------------------------------------
// BudgetTable
// ---------------------------------------------------------------------------

function BudgetTable({ budgets, meta, sortField, sortAsc, onSort, onEdit, onDelete, page, onPageChange, search, onSearch, loading, trans }) {
    const months  = trans.months ?? [];
    const currency = trans.currency ?? '€';

    return (
        <div className="card">
            <div className="card-body">
                {/* Search */}
                <div className="row mb-3">
                    <div className="col-md-4">
                        <div className="input-group input-group-sm">
                            <div className="input-group-prepend">
                                <span className="input-group-text"><i className="fas fa-search" /></span>
                            </div>
                            <input
                                type="text"
                                className="form-control"
                                placeholder={trans.search}
                                value={search}
                                onChange={e => onSearch(e.target.value)}
                            />
                        </div>
                    </div>
                    {loading && (
                        <div className="col-auto d-flex align-items-center">
                            <i className="fas fa-spinner fa-spin text-muted" />
                        </div>
                    )}
                </div>

                <div className="table-responsive p-0">
                    <table className="table table-hover table-sm">
                        <thead>
                            <tr>
                                <SortTh field="year" sortField={sortField} sortAsc={sortAsc} onSort={onSort}>
                                    {trans.year}
                                </SortTh>
                                {months.map((m, i) => <th key={i}>{m}</th>)}
                                <th>{trans.total}</th>
                                <th>{trans.action}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {budgets.length === 0 && (
                                <tr>
                                    <td colSpan={15} className="text-center text-muted">{trans.no_data}</td>
                                </tr>
                            )}
                            {budgets.map(b => {
                                const total = calcTotal(b);
                                return (
                                    <tr key={b.id}>
                                        <td><strong>{b.year}</strong></td>
                                        {AMOUNTS.map(i => (
                                            <td key={i}>{formatNum(b[`amount${i}`])}</td>
                                        ))}
                                        <td>
                                            <strong>{formatNum(total)} {currency}</strong>
                                        </td>
                                        <td>
                                            <div className="btn-group btn-group-sm">
                                                <button className="btn btn-warning" onClick={() => onEdit(b)} title={trans.update}>
                                                    <i className="fa fa-edit" />
                                                </button>
                                                <button className="btn btn-danger" onClick={() => onDelete(b.id)} title="Supprimer">
                                                    <i className="fa fa-trash" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>{trans.year}</th>
                                {months.map((m, i) => <th key={i}>{m}</th>)}
                                <th>{trans.total}</th>
                                <th>{trans.action}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <Pagination meta={meta} onPageChange={onPageChange} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Root
// ---------------------------------------------------------------------------

export default function EstimatedBudgetsIndex({ endpoints, trans }) {
    const [budgets, setBudgets]     = useState([]);
    const [meta, setMeta]           = useState(null);
    const [page, setPage]           = useState(1);
    const [search, setSearch]       = useState('');
    const [sortField, setSortField] = useState('year');
    const [sortAsc, setSortAsc]     = useState(true);
    const [loadingList, setLoadingList] = useState(false);

    const [form, setForm]           = useState(emptyForm());
    const [editId, setEditId]       = useState(null);
    const [formLoading, setFormLoading] = useState(false);
    const [formErrors, setFormErrors]   = useState({});
    const [alert, setAlert]         = useState({ type: 'success', message: '' });

    // -----------------------------------------------------------------------
    // Fetch list
    // -----------------------------------------------------------------------

    const fetchList = useCallback(() => {
        setLoadingList(true);
        const params = new URLSearchParams({
            search,
            sort: sortField,
            asc: sortAsc ? '1' : '0',
            page,
        });
        apiFetch(`${endpoints.list}?${params}`)
            .then(data => {
                setBudgets(data.data ?? []);
                setMeta(data);
            })
            .catch(() => {})
            .finally(() => setLoadingList(false));
    }, [search, sortField, sortAsc, page, endpoints.list]);

    useEffect(() => { fetchList(); }, [fetchList]);

    // -----------------------------------------------------------------------
    // Search debounce
    // -----------------------------------------------------------------------

    const [searchRaw, setSearchRaw] = useState('');

    useEffect(() => {
        const t = setTimeout(() => {
            setSearch(searchRaw);
            setPage(1);
        }, 300);
        return () => clearTimeout(t);
    }, [searchRaw]);

    // -----------------------------------------------------------------------
    // Sort
    // -----------------------------------------------------------------------

    function handleSort(field) {
        if (sortField === field) {
            setSortAsc(a => !a);
        } else {
            setSortField(field);
            setSortAsc(true);
        }
        setPage(1);
    }

    // -----------------------------------------------------------------------
    // Form submit (create or update)
    // -----------------------------------------------------------------------

    async function handleSubmit(e) {
        e.preventDefault();
        setFormErrors({});
        setFormLoading(true);

        try {
            if (editId !== null) {
                const url = buildUrl(endpoints.update, editId);
                await apiFetch(url, { method: 'PUT', body: JSON.stringify(form) });
                setAlert({ type: 'success', message: trans.update + ' ✓' });
            } else {
                await apiFetch(endpoints.store, { method: 'POST', body: JSON.stringify(form) });
                setAlert({ type: 'success', message: trans.submit + ' ✓' });
            }
            setForm(emptyForm());
            setEditId(null);
            fetchList();
        } catch (err) {
            if (err.errors) {
                setFormErrors(err.errors);
            } else {
                setAlert({ type: 'danger', message: err.message ?? 'Error' });
            }
        } finally {
            setFormLoading(false);
        }
    }

    // -----------------------------------------------------------------------
    // Edit
    // -----------------------------------------------------------------------

    function handleEdit(budget) {
        setEditId(budget.id);
        const f = { year: String(budget.year) };
        AMOUNTS.forEach(i => { f[`amount${i}`] = String(budget[`amount${i}`] ?? ''); });
        setForm(f);
        setFormErrors({});
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function handleCancel() {
        setEditId(null);
        setForm(emptyForm());
        setFormErrors({});
    }

    // -----------------------------------------------------------------------
    // Delete
    // -----------------------------------------------------------------------

    async function handleDelete(id) {
        if (!window.confirm('Supprimer cette ligne ?')) return;
        try {
            const url = buildUrl(endpoints.destroy, id);
            await apiFetch(url, { method: 'DELETE' });
            setAlert({ type: 'success', message: 'Ligne supprimée.' });
            fetchList();
        } catch (err) {
            setAlert({ type: 'danger', message: err.message ?? 'Error' });
        }
    }

    // -----------------------------------------------------------------------
    // Render
    // -----------------------------------------------------------------------

    return (
        <div>
            <Alert
                type={alert.type}
                message={alert.message}
                onClose={() => setAlert({ ...alert, message: '' })}
            />

            <BudgetForm
                form={form}
                setForm={setForm}
                editId={editId}
                onSubmit={handleSubmit}
                onCancel={handleCancel}
                loading={formLoading}
                errors={formErrors}
                trans={trans}
            />

            <BudgetTable
                budgets={budgets}
                meta={meta}
                sortField={sortField}
                sortAsc={sortAsc}
                onSort={handleSort}
                onEdit={handleEdit}
                onDelete={handleDelete}
                page={page}
                onPageChange={setPage}
                search={searchRaw}
                onSearch={setSearchRaw}
                loading={loadingList}
                trans={trans}
            />
        </div>
    );
}
