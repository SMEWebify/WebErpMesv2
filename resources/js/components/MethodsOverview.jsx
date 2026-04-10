import React, { useState } from 'react';

// ---------------------------------------------------------------------------
// Primitives
// ---------------------------------------------------------------------------

function ProgressBar({ value }) {
    const pct = Math.min(Math.max(value ?? 0, 0), 100);
    return (
        <div className="progress progress-sm" style={{ minWidth: 60 }}>
            <div
                className="progress-bar bg-teal progress-bar-striped progress-bar-animated"
                role="progressbar"
                style={{ width: `${pct}%` }}
            >
                <span>{pct}%</span>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// ServiceCard
// ---------------------------------------------------------------------------

function ServiceCard({ service, t }) {
    return (
        <div className="col-md-4 mb-3">
            <div className="border rounded p-3 h-100">
                <div className="d-flex justify-content-between align-items-start">
                    <div>
                        <div className="font-weight-bold">{service.label}</div>
                        <small className="text-muted">{service.code}</small>
                    </div>
                    <span
                        className="badge text-white"
                        style={{ backgroundColor: service.color ?? '#6c757d' }}
                    >
                        &nbsp;
                    </span>
                </div>
                <div className="mt-2">
                    <div>
                        <strong>{t('ressource_trans_key')}</strong> : {service.ressources_count}
                    </div>
                    <div>
                        <strong>{t('in_progress_trans_key')}</strong> : {service.tasks_in_progress_count}
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// TaskCard
// ---------------------------------------------------------------------------

function TaskCard({ task, t }) {
    return (
        <div className="border rounded p-2 mb-2">
            <div className="d-flex justify-content-between align-items-start">
                <div>
                    <div className="font-weight-bold">
                        <a
                            href={`/production/Task/Statu/Id/${task.id}`}
                            className="btn btn-xs btn-success mr-1"
                        >
                            {t('view_trans_key')}
                        </a>
                        {task.code} — {task.label}
                    </div>
                    {task.Products && (
                        <small className="text-muted">{task.Products.label}</small>
                    )}
                </div>
                <span className="badge badge-info">
                    {task.status?.title ?? t('in_progress_trans_key')}
                </span>
            </div>
            <div className="mt-2">
                <ProgressBar value={task.progress} />
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// ResourceCard
// ---------------------------------------------------------------------------

function ResourceCard({ resource, t }) {
    return (
        <div className="col-lg-6">
            <div className="border rounded p-3 mb-3 h-100">
                <div className="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 className="mb-1">{resource.label}</h5>
                        <small className="text-muted">
                            {t('service_trans_key')} :{' '}
                            {resource.service?.label ?? t('no_data_trans_key')}
                        </small>
                    </div>
                    <span className="badge badge-light">
                        {t('location_trans_key')} : {resource.locations?.length ?? 0}
                    </span>
                </div>

                {/* Locations */}
                <div className="mt-3">
                    <strong>{t('location_in_workshop_trans_key')}</strong>
                    <div className="d-flex flex-wrap mt-2">
                        {(resource.locations ?? []).length > 0 ? (
                            resource.locations.map((loc) => (
                                <span
                                    key={loc.id}
                                    className="badge badge-pill text-white mr-2 mb-2"
                                    style={{ backgroundColor: loc.color ?? '#6c757d' }}
                                >
                                    {loc.label}
                                </span>
                            ))
                        ) : (
                            <span className="text-muted">{t('no_data_trans_key')}</span>
                        )}
                    </div>
                </div>

                {/* Tasks in progress */}
                <div className="mt-3">
                    <strong>
                        {t('tasks_trans_key')} — {t('in_progress_trans_key')}
                    </strong>
                    <div className="mt-2">
                        {(resource.tasks ?? []).length > 0 ? (
                            resource.tasks.map((task) => (
                                <TaskCard key={task.id} task={task} t={t} />
                            ))
                        ) : (
                            <p className="text-muted mb-0">{t('no_task_trans_key')}</p>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// SectionPanel
// ---------------------------------------------------------------------------

function SectionPanel({ section, t }) {
    const [collapsed, setCollapsed] = useState(false);

    return (
        <div className="col-12 mb-3">
            <div className="card card-primary card-outline">
                <div className="card-header d-flex justify-content-between align-items-center">
                    <h3 className="card-title mb-0">{section.label}</h3>
                    <button
                        type="button"
                        className="btn btn-sm btn-outline-secondary"
                        onClick={() => setCollapsed((c) => !c)}
                        title={collapsed ? 'Développer' : 'Réduire'}
                    >
                        <i className={`fas fa-${collapsed ? 'plus' : 'minus'}`} />
                    </button>
                </div>
                {!collapsed && (
                    <div className="card-body">
                        <div className="row">
                            {(section.Ressources ?? []).length > 0 ? (
                                section.Ressources.map((resource) => (
                                    <ResourceCard key={resource.id} resource={resource} t={t} />
                                ))
                            ) : (
                                <div className="col-12">
                                    <p className="text-muted">{t('no_data_trans_key')}</p>
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// MethodsOverview
// ---------------------------------------------------------------------------

export default function MethodsOverview({ sections, services, trans }) {
    const t = (key) => trans?.[key] ?? key;

    const [serviceFilter, setServiceFilter] = useState('');
    const [search, setSearch] = useState('');

    const filteredSections = sections.map((section) => ({
        ...section,
        Ressources: (section.Ressources ?? []).filter((resource) => {
            const matchService =
                !serviceFilter ||
                String(resource.methods_services_id) === String(serviceFilter);
            const matchSearch =
                !search ||
                resource.label.toLowerCase().includes(search.toLowerCase());
            return matchService && matchSearch;
        }),
    }));

    const totalInProgress = services.reduce(
        (sum, s) => sum + (s.tasks_in_progress_count ?? 0),
        0
    );

    return (
        <div>
            {/* ── Services summary ── */}
            <div className="row">
                <div className="col-12">
                    <div className="card card-info card-outline">
                        <div className="card-header">
                            <h3 className="card-title">
                                {t('service_trans_key')}
                                <span className="badge badge-info ml-2">{totalInProgress} {t('in_progress_trans_key')}</span>
                            </h3>
                        </div>
                        <div className="card-body">
                            <div className="row">
                                {services.length > 0 ? (
                                    services.map((service) => (
                                        <ServiceCard key={service.id} service={service} t={t} />
                                    ))
                                ) : (
                                    <div className="col-12 text-muted">{t('no_data_trans_key')}</div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* ── Toolbar ── */}
            <div className="row mb-3">
                <div className="col-12">
                    <div className="d-flex flex-wrap align-items-center" style={{ gap: '0.5rem' }}>
                        <select
                            className="form-control form-control-sm flex-shrink-0"
                            style={{ width: 180 }}
                            value={serviceFilter}
                            onChange={(e) => setServiceFilter(e.target.value)}
                        >
                            <option value="">— {t('service_trans_key')} —</option>
                            {services.map((s) => (
                                <option key={s.id} value={s.id}>
                                    {s.label}
                                </option>
                            ))}
                        </select>

                        <input
                            type="text"
                            className="form-control form-control-sm flex-shrink-0"
                            style={{ width: 200 }}
                            placeholder={`⌕ ${t('ressource_trans_key')}`}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />

                        {(serviceFilter || search) && (
                            <button
                                type="button"
                                className="btn btn-sm btn-outline-secondary"
                                onClick={() => { setServiceFilter(''); setSearch(''); }}
                            >
                                <i className="fas fa-times mr-1" />
                                Reset
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {/* ── Sections ── */}
            <div className="row">
                {filteredSections.length > 0 ? (
                    filteredSections.map((section) => (
                        <SectionPanel key={section.id} section={section} t={t} />
                    ))
                ) : (
                    <div className="col-12">
                        <p className="text-muted">{t('no_section_trans_key')}</p>
                    </div>
                )}
            </div>
        </div>
    );
}
