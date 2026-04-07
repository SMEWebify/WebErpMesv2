import React, { useState, useMemo } from 'react';

export default function DocumentTable({ initialDocuments, translations }) {
    const documents = useMemo(
        () =>
            initialDocuments.map((doc) => ({
                ...doc,
                hashtags: Array.isArray(doc.hashtags) ? doc.hashtags : [],
                linked_entities: Array.isArray(doc.linked_entities) ? doc.linked_entities : [],
            })),
        [initialDocuments]
    );

    const [filters, setFilters] = useState({
        type: '',
        search: '',
        startDate: '',
        endDate: '',
        hashtag: '',
        uploader: '',
    });

    const [columns, setColumns] = useState([
        { key: 'name', label: translations.name, sortable: true },
        { key: 'type', label: translations.type, sortable: true },
        { key: 'formatted_size', label: translations.size, sortable: true },
        { key: 'created_at', label: translations.uploaded_at, sortable: true },
        { key: 'updated_at', label: translations.updated_at, sortable: true },
        { key: 'hashtags', label: translations.hashtags, sortable: true },
        { key: 'uploaded_by', label: translations.uploaded_by, sortable: true },
        { key: 'linked_entities', label: translations.linked_entities, sortable: false },
    ]);

    const [sortState, setSortState] = useState({ key: 'created_at', direction: 'desc' });
    const [dragIndex, setDragIndex] = useState(null);

    const availableTypes = useMemo(() => {
        const types = new Set(documents.map((doc) => doc.type).filter(Boolean));
        return Array.from(types).sort();
    }, [documents]);

    const availableHashtags = useMemo(() => {
        const hashtags = new Set();
        documents.forEach((doc) => doc.hashtags.forEach((tag) => hashtags.add(`#${tag}`)));
        return Array.from(hashtags).sort((a, b) => a.localeCompare(b));
    }, [documents]);

    const availableUploaders = useMemo(() => {
        const uploaders = new Set(documents.map((doc) => doc.uploaded_by).filter(Boolean));
        return Array.from(uploaders).sort((a, b) => a.localeCompare(b));
    }, [documents]);

    const filteredDocuments = useMemo(() => {
        return documents.filter((doc) => {
            if (filters.type && doc.type !== filters.type) return false;
            if (filters.uploader && doc.uploaded_by !== filters.uploader) return false;

            if (filters.search) {
                const haystack = [doc.name, doc.original_file_name, doc.comment]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();
                if (!haystack.includes(filters.search.toLowerCase())) return false;
            }

            if (filters.hashtag) {
                const normalized = filters.hashtag.replace(/^#/, '').toLowerCase();
                if (!doc.hashtags.some((tag) => tag.toLowerCase().includes(normalized))) return false;
            }

            if (filters.startDate && doc.created_at) {
                if (new Date(doc.created_at) < new Date(filters.startDate)) return false;
            }

            if (filters.endDate && doc.created_at) {
                const end = new Date(filters.endDate);
                end.setHours(23, 59, 59, 999);
                if (new Date(doc.created_at) > end) return false;
            }

            return true;
        });
    }, [documents, filters]);

    const sortedDocuments = useMemo(() => {
        const docs = [...filteredDocuments];
        const { key, direction } = sortState;

        docs.sort((a, b) => {
            const aVal = getSortableValue(a, key);
            const bVal = getSortableValue(b, key);

            if (aVal === bVal) return 0;
            if (aVal == null) return direction === 'asc' ? 1 : -1;
            if (bVal == null) return direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return direction === 'asc' ? 1 : -1;
            if (aVal < bVal) return direction === 'asc' ? -1 : 1;
            return 0;
        });

        return docs;
    }, [filteredDocuments, sortState]);

    function getSortableValue(doc, key) {
        switch (key) {
            case 'formatted_size':
                return doc.size ?? 0;
            case 'hashtags':
                return doc.hashtags.join(' ').toLowerCase();
            case 'created_at':
            case 'updated_at':
                return doc[key] ? new Date(doc[key]).getTime() : 0;
            default: {
                const v = doc[key];
                if (v == null) return '';
                return typeof v === 'string' ? v.toLowerCase() : v;
            }
        }
    }

    function toggleSort(columnKey) {
        setSortState((prev) =>
            prev.key === columnKey
                ? { key: columnKey, direction: prev.direction === 'asc' ? 'desc' : 'asc' }
                : { key: columnKey, direction: 'asc' }
        );
    }

    function onDragStart(index) {
        setDragIndex(index);
    }

    function onDrop(index) {
        if (dragIndex === null || dragIndex === index) {
            setDragIndex(null);
            return;
        }
        const updated = [...columns];
        const [moved] = updated.splice(dragIndex, 1);
        updated.splice(index, 0, moved);
        setColumns(updated);
        setDragIndex(null);
    }

    function resetFilters() {
        setFilters({ type: '', search: '', startDate: '', endDate: '', hashtag: '', uploader: '' });
    }

    function formatDate(isoString) {
        if (!isoString) return '';
        return new Date(isoString).toLocaleString();
    }

    function renderCell(doc, column) {
        switch (column.key) {
            case 'name':
                return (
                    <>
                        <span className="fw-semibold">{doc.name}</span>
                        {doc.original_file_name && doc.original_file_name !== doc.name && (
                            <small className="d-block text-muted">{doc.original_file_name}</small>
                        )}
                    </>
                );
            case 'type':
                return doc.type || '—';
            case 'formatted_size':
                return doc.formatted_size;
            case 'created_at':
                return (
                    <>
                        <span className="d-block">{doc.created_at_human || '—'}</span>
                        {doc.created_at && (
                            <small className="text-muted">{formatDate(doc.created_at)}</small>
                        )}
                    </>
                );
            case 'updated_at':
                return (
                    <>
                        <span className="d-block">{doc.updated_at_human || '—'}</span>
                        {doc.updated_at && (
                            <small className="text-muted">{formatDate(doc.updated_at)}</small>
                        )}
                    </>
                );
            case 'hashtags':
                return doc.hashtags.length ? (
                    doc.hashtags.map((tag) => (
                        <span key={`${doc.id}-${tag}`} className="badge bg-secondary me-1 mb-1">
                            #{tag}
                        </span>
                    ))
                ) : (
                    '—'
                );
            case 'uploaded_by':
                return doc.uploaded_by || '—';
            case 'linked_entities':
                return (
                    <ul
                        className="list-unstyled mb-0"
                        style={{ maxHeight: '120px', overflowY: 'auto' }}
                    >
                        {doc.linked_entities.length ? (
                            doc.linked_entities.map((entity, i) => (
                                <li key={`${doc.id}-entity-${i}`}>
                                    <i className="fas fa-link me-2 text-muted"></i>
                                    {entity}
                                </li>
                            ))
                        ) : (
                            <li className="text-muted">—</li>
                        )}
                    </ul>
                );
            default:
                return doc[column.key] ?? '—';
        }
    }

    return (
        <div style={{ minHeight: '420px' }}>
            <div className="p-3 border-bottom bg-light">
                <div className="row g-3">
                    <div className="col-md-3">
                        <label className="form-label fw-semibold">
                            {translations.filters?.typeLabel}
                        </label>
                        <select
                            className="form-select"
                            value={filters.type}
                            onChange={(e) => setFilters((f) => ({ ...f, type: e.target.value }))}
                        >
                            <option value="">{translations.filters?.typePlaceholder}</option>
                            {availableTypes.map((type) => (
                                <option key={type} value={type}>
                                    {type}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="col-md-3">
                        <label className="form-label fw-semibold">{translations.name}</label>
                        <input
                            type="search"
                            className="form-control"
                            value={filters.search}
                            onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
                            placeholder={translations.filters?.searchPlaceholder}
                        />
                    </div>

                    <div className="col-md-2">
                        <label className="form-label fw-semibold">
                            {translations.filters?.dateFrom}
                        </label>
                        <input
                            type="date"
                            className="form-control"
                            value={filters.startDate}
                            onChange={(e) =>
                                setFilters((f) => ({ ...f, startDate: e.target.value }))
                            }
                        />
                    </div>

                    <div className="col-md-2">
                        <label className="form-label fw-semibold">
                            {translations.filters?.dateTo}
                        </label>
                        <input
                            type="date"
                            className="form-control"
                            value={filters.endDate}
                            onChange={(e) =>
                                setFilters((f) => ({ ...f, endDate: e.target.value }))
                            }
                        />
                    </div>

                    <div className="col-md-2">
                        <label className="form-label fw-semibold">{translations.hashtags}</label>
                        <input
                            type="text"
                            className="form-control"
                            list="document-hashtags"
                            value={filters.hashtag}
                            onChange={(e) =>
                                setFilters((f) => ({ ...f, hashtag: e.target.value }))
                            }
                            placeholder={translations.filters?.hashtagPlaceholder}
                        />
                        <datalist id="document-hashtags">
                            {availableHashtags.map((tag) => (
                                <option key={tag} value={tag}>
                                    {tag}
                                </option>
                            ))}
                        </datalist>
                    </div>

                    <div className="col-md-2">
                        <label className="form-label fw-semibold">
                            {translations.filters?.uploaderLabel}
                        </label>
                        <select
                            className="form-select"
                            value={filters.uploader}
                            onChange={(e) =>
                                setFilters((f) => ({ ...f, uploader: e.target.value }))
                            }
                        >
                            <option value="">--</option>
                            {availableUploaders.map((u) => (
                                <option key={u} value={u}>
                                    {u}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="col-12 d-flex justify-content-end align-items-end">
                        <button
                            className="btn btn-outline-secondary"
                            type="button"
                            onClick={resetFilters}
                        >
                            <i className="fas fa-undo me-2"></i>
                            {translations.filters?.reset}
                        </button>
                    </div>
                </div>
            </div>

            <div className="table-responsive">
                <table className="table table-hover table-striped mb-0">
                    <thead className="table-light">
                        <tr>
                            {columns.map((column, index) => (
                                <th
                                    key={column.key}
                                    scope="col"
                                    className="text-nowrap"
                                    style={{
                                        cursor: column.sortable ? 'pointer' : undefined,
                                        userSelect: column.sortable ? 'none' : undefined,
                                        backgroundColor:
                                            sortState.key === column.key
                                                ? 'rgba(13, 110, 253, 0.08)'
                                                : undefined,
                                        transition: 'background-color 0.2s ease-in-out',
                                    }}
                                    draggable
                                    onDragStart={() => onDragStart(index)}
                                    onDragOver={(e) => e.preventDefault()}
                                    onDrop={() => onDrop(index)}
                                    onClick={() => column.sortable && toggleSort(column.key)}
                                >
                                    <span>{column.label}</span>
                                    {column.sortable && (
                                        <span
                                            className="ms-1"
                                            style={{ color: '#6c757d', fontSize: '0.85rem' }}
                                        >
                                            {sortState.key === column.key ? (
                                                <i
                                                    className={`fas fa-arrow-${sortState.direction === 'asc' ? 'up' : 'down'}`}
                                                ></i>
                                            ) : (
                                                <i className="fas fa-sort"></i>
                                            )}
                                        </span>
                                    )}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {sortedDocuments.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={columns.length}
                                    className="text-center py-5 text-muted"
                                >
                                    <i className="fas fa-folder-open fa-2x mb-3 d-block"></i>
                                    {translations.filters?.noResult}
                                </td>
                            </tr>
                        ) : (
                            sortedDocuments.map((doc) => (
                                <tr key={doc.id}>
                                    {columns.map((column) => (
                                        <td
                                            key={`${doc.id}-${column.key}`}
                                            className={`align-top${column.key === 'type' ? ' text-nowrap' : ''}`}
                                        >
                                            {renderCell(doc, column)}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
