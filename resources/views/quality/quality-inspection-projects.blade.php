@extends('adminlte::page')

@section('title', 'Inspection - Projets')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Inspections - Projets</h1>
        <a class="btn btn-primary" href="{{ route('quality') }}">Retour à la qualité</a>
    </div>
@stop

@section('content')
    <x-InfocalloutComponent note="Cet écran pilote les inspections via les contrôleurs Inspection (projets, points de contrôle, documents, sessions)." />

    <div class="row">
        <div class="col-lg-4">
            <x-adminlte-card title="Créer un projet d'inspection" theme="primary" icon="fas fa-clipboard-check" maximizable>
                <form id="inspection-project-create-form">
                    @csrf
                    <div class="form-group">
                        <label for="project_title">Titre</label>
                        <input class="form-control" id="project_title" name="title" type="text" required>
                    </div>
                    <div class="form-group">
                        <label for="project_company">ID société</label>
                        <input class="form-control" id="project_company" name="companies_id" type="number" required>
                    </div>
                    <div class="form-group">
                        <label for="project_order">ID commande</label>
                        <input class="form-control" id="project_order" name="orders_id" type="number">
                    </div>
                    <div class="form-group">
                        <label for="project_order_line">ID ligne commande</label>
                        <input class="form-control" id="project_order_line" name="order_lines_id" type="number">
                    </div>
                    <div class="form-group">
                        <label for="project_of">ID OF</label>
                        <input class="form-control" id="project_of" name="of_id" type="number">
                    </div>
                    <div class="form-group">
                        <label for="project_status">Statut</label>
                        <select class="form-control" id="project_status" name="status">
                            <option value="draft">Brouillon</option>
                            <option value="active">Actif</option>
                            <option value="closed">Clôturé</option>
                            <option value="archived">Archivé</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="project_quantity">Quantité prévue</label>
                        <input class="form-control" id="project_quantity" name="quantity_planned" type="number" min="0">
                    </div>
                    <div class="form-group form-check">
                        <input class="form-check-input" id="project_serial" name="serial_tracking" type="checkbox">
                        <label class="form-check-label" for="project_serial">Suivi par numéro de série</label>
                    </div>
                    <button class="btn btn-success" type="submit">Créer</button>
                </form>
            </x-adminlte-card>

            <x-adminlte-card title="Mettre à jour le projet" theme="info" icon="fas fa-edit" maximizable>
                <form id="inspection-project-update-form">
                    @csrf
                    <input type="hidden" id="project_update_id" name="id">
                    <div class="form-group">
                        <label for="project_update_title">Titre</label>
                        <input class="form-control" id="project_update_title" name="title" type="text">
                    </div>
                    <div class="form-group">
                        <label for="project_update_company">ID société</label>
                        <input class="form-control" id="project_update_company" name="companies_id" type="number">
                    </div>
                    <div class="form-group">
                        <label for="project_update_order">ID commande</label>
                        <input class="form-control" id="project_update_order" name="orders_id" type="number">
                    </div>
                    <div class="form-group">
                        <label for="project_update_order_line">ID ligne commande</label>
                        <input class="form-control" id="project_update_order_line" name="order_lines_id" type="number">
                    </div>
                    <div class="form-group">
                        <label for="project_update_of">ID OF</label>
                        <input class="form-control" id="project_update_of" name="of_id" type="number">
                    </div>
                    <div class="form-group">
                        <label for="project_update_status">Statut</label>
                        <select class="form-control" id="project_update_status" name="status">
                            <option value="draft">Brouillon</option>
                            <option value="active">Actif</option>
                            <option value="closed">Clôturé</option>
                            <option value="archived">Archivé</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="project_update_quantity">Quantité prévue</label>
                        <input class="form-control" id="project_update_quantity" name="quantity_planned" type="number" min="0">
                    </div>
                    <div class="form-group form-check">
                        <input class="form-check-input" id="project_update_serial" name="serial_tracking" type="checkbox">
                        <label class="form-check-label" for="project_update_serial">Suivi par numéro de série</label>
                    </div>
                    <button class="btn btn-info" type="submit">Mettre à jour</button>
                </form>
            </x-adminlte-card>
        </div>

        <div class="col-lg-8">
            <x-adminlte-card title="Projets d'inspection" theme="secondary" icon="fas fa-list" maximizable>
                <div class="table-responsive">
                    <table class="table table-striped" id="inspection-projects-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Titre</th>
                                <th>Statut</th>
                                <th>Créé le</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </x-adminlte-card>

            <x-adminlte-card title="Détails du projet" theme="secondary" icon="fas fa-search" maximizable>
                <div id="inspection-project-details" class="mb-3 text-muted">Sélectionnez un projet pour charger ses détails.</div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <a id="inspection-export-pdf" class="btn btn-outline-danger disabled" target="_blank">Exporter PDF</a>
                    <a id="inspection-export-xlsx" class="btn btn-outline-success disabled" target="_blank">Exporter XLSX</a>
                </div>

                <div class="row">
                    <div class="col-lg-6">
                        <h5>Documents</h5>
                        <form id="inspection-document-form" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="document_file">Fichier</label>
                                <input class="form-control" id="document_file" name="file" type="file" accept=".pdf,.png,.jpg,.jpeg" required>
                            </div>
                            <div class="form-group">
                                <label for="document_type">Type</label>
                                <select class="form-control" id="document_type" name="type">
                                    <option value="plan">Plan</option>
                                    <option value="spec">Spécification</option>
                                    <option value="photo">Photo</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="document_version">Version</label>
                                <input class="form-control" id="document_version" name="version_label" type="text">
                            </div>
                            <button class="btn btn-outline-primary" type="submit">Ajouter document</button>
                        </form>
                        <ul class="list-group mt-3" id="inspection-documents-list"></ul>
                    </div>
                    <div class="col-lg-6">
                        <h5>Sessions de mesure</h5>
                        <form id="inspection-session-form">
                            @csrf
                            <div class="form-group">
                                <label for="session_type">Type</label>
                                <select class="form-control" id="session_type" name="type">
                                    <option value="lot">Lot</option>
                                    <option value="serial">Série</option>
                                    <option value="recheck">Recontrôle</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="session_quantity">Quantité à mesurer</label>
                                <input class="form-control" id="session_quantity" name="quantity_to_measure" type="number" min="0">
                            </div>
                            <div class="form-group">
                                <label for="session_started">Date de démarrage</label>
                                <input class="form-control" id="session_started" name="started_at" type="datetime-local">
                            </div>
                            <button class="btn btn-outline-primary" type="submit">Créer session</button>
                        </form>
                        <ul class="list-group mt-3" id="inspection-sessions-list"></ul>
                    </div>
                </div>

                <hr>

                <h5>Points de contrôle</h5>
                <form id="inspection-control-point-form">
                    @csrf
                    <input type="hidden" id="control_point_id" name="id">
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="control_point_number">N°</label>
                            <input class="form-control" id="control_point_number" name="number" type="number" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="control_point_label">Libellé</label>
                            <input class="form-control" id="control_point_label" name="label" type="text" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="control_point_category">Catégorie</label>
                            <select class="form-control" id="control_point_category" name="category">
                                <option value="dimension">Dimension</option>
                                <option value="visual">Visuel</option>
                                <option value="attribute">Attribut</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="control_point_unit">Unité</label>
                            <input class="form-control" id="control_point_unit" name="unit" type="text">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="control_point_nominal">Nominal</label>
                            <input class="form-control" id="control_point_nominal" name="nominal_value" type="number" step="0.01">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="control_point_tol_min">Tol min</label>
                            <input class="form-control" id="control_point_tol_min" name="tol_min" type="number" step="0.01">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="control_point_tol_max">Tol max</label>
                            <input class="form-control" id="control_point_tol_max" name="tol_max" type="number" step="0.01">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="control_point_plan_page">Plan page</label>
                            <input class="form-control" id="control_point_plan_page" name="plan_page" type="number">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="control_point_plan_ref">Plan ref</label>
                            <input class="form-control" id="control_point_plan_ref" name="plan_ref" type="text">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="control_point_phase">Phase</label>
                            <input class="form-control" id="control_point_phase" name="phase" type="text">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="control_point_instrument">Instrument</label>
                            <input class="form-control" id="control_point_instrument" name="instrument_type" type="text">
                        </div>
                    </div>
                    <div class="form-group form-check">
                        <input class="form-check-input" id="control_point_critical" name="is_critical" type="checkbox">
                        <label class="form-check-label" for="control_point_critical">Point critique</label>
                    </div>
                    <button class="btn btn-outline-primary" type="submit">Enregistrer</button>
                    <button class="btn btn-outline-secondary" id="control_point_reset" type="button">Réinitialiser</button>
                </form>

                <div class="table-responsive mt-3">
                    <table class="table table-striped" id="inspection-control-points-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Libellé</th>
                                <th>Catégorie</th>
                                <th>Nominal</th>
                                <th>Tol.</th>
                                <th>Unité</th>
                                <th>Plan</th>
                                <th>Critique</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </x-adminlte-card>
        </div>
    </div>
@stop

@section('js')
<script>
    const routes = {
        projectsIndex: '{{ route('inspection.projects.index') }}',
        projectStore: '{{ route('inspection.projects.store') }}',
        projectShow: '{{ route('inspection.projects.show', ['id' => '__ID__']) }}',
        projectUpdate: '{{ route('inspection.projects.update', ['id' => '__ID__']) }}',
        projectDocuments: '{{ route('inspection.projects.documents.store', ['id' => '__ID__']) }}',
        projectControlPoints: '{{ route('inspection.projects.points.store', ['id' => '__ID__']) }}',
        controlPointUpdate: '{{ route('inspection.points.update', ['id' => '__ID__']) }}',
        controlPointDelete: '{{ route('inspection.points.destroy', ['id' => '__ID__']) }}',
        projectSessions: '{{ route('inspection.projects.sessions.store', ['id' => '__ID__']) }}',
        sessionSubmit: '{{ route('inspection.sessions.submit', ['id' => '__ID__']) }}',
        sessionClose: '{{ route('inspection.sessions.close', ['id' => '__ID__']) }}',
        exportPdf: '{{ route('inspection.projects.export.pdf', ['id' => '__ID__']) }}',
        exportXlsx: '{{ route('inspection.projects.export.xlsx', ['id' => '__ID__']) }}'
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const projectsTableBody = document.querySelector('#inspection-projects-table tbody');
    const detailsBox = document.getElementById('inspection-project-details');
    const documentsList = document.getElementById('inspection-documents-list');
    const sessionsList = document.getElementById('inspection-sessions-list');
    const controlPointsTableBody = document.querySelector('#inspection-control-points-table tbody');
    const exportPdfButton = document.getElementById('inspection-export-pdf');
    const exportXlsxButton = document.getElementById('inspection-export-xlsx');

    let selectedProject = null;

    const handleJsonResponse = async (response) => {
        if (!response.ok) {
            const message = await response.text();
            throw new Error(message || 'Erreur serveur');
        }
        return response.json();
    };

    const setExportLinks = (projectId) => {
        if (!projectId) {
            exportPdfButton.classList.add('disabled');
            exportXlsxButton.classList.add('disabled');
            exportPdfButton.removeAttribute('href');
            exportXlsxButton.removeAttribute('href');
            return;
        }
        exportPdfButton.classList.remove('disabled');
        exportXlsxButton.classList.remove('disabled');
        exportPdfButton.href = routes.exportPdf.replace('__ID__', projectId);
        exportXlsxButton.href = routes.exportXlsx.replace('__ID__', projectId);
    };

    const renderProjects = (projects) => {
        projectsTableBody.innerHTML = '';
        projects.forEach((project) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${project.id}</td>
                <td>${project.code ?? '-'}</td>
                <td>${project.title ?? ''}</td>
                <td>${project.status ?? ''}</td>
                <td>${project.created_at ? new Date(project.created_at).toLocaleString('fr-FR') : '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" data-action="select" data-id="${project.id}">Sélectionner</button>
                </td>
            `;
            projectsTableBody.appendChild(row);
        });
    };

    const renderDetails = (project) => {
        if (!project) {
            detailsBox.textContent = 'Sélectionnez un projet pour charger ses détails.';
            documentsList.innerHTML = '';
            sessionsList.innerHTML = '';
            controlPointsTableBody.innerHTML = '';
            setExportLinks(null);
            return;
        }

        detailsBox.innerHTML = `
            <div><strong>${project.title ?? ''}</strong> (${project.code ?? ''})</div>
            <div>Statut: ${project.status ?? ''}</div>
            <div>Quantité prévue: ${project.quantity_planned ?? '-'}</div>
            <div>Client: ${project.company?.label ?? '-'}</div>
        `;

        setExportLinks(project.id);

        documentsList.innerHTML = '';
        (project.documents ?? []).forEach((doc) => {
            const item = document.createElement('li');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <span>${doc.file_name ?? doc.file_path}</span>
                <span class="badge badge-secondary">${doc.type ?? ''}</span>
            `;
            documentsList.appendChild(item);
        });

        sessionsList.innerHTML = '';
        (project.measure_sessions ?? []).forEach((session) => {
            const item = document.createElement('li');
            item.className = 'list-group-item d-flex justify-content-between align-items-center';
            item.innerHTML = `
                <div>
                    <strong>${session.session_code}</strong>
                    <div class="text-muted">${session.type} - ${session.status}</div>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-success" data-session-action="submit" data-id="${session.id}">Soumettre</button>
                    <button class="btn btn-sm btn-outline-danger" data-session-action="close" data-id="${session.id}">Clôturer</button>
                </div>
            `;
            sessionsList.appendChild(item);
        });

        controlPointsTableBody.innerHTML = '';
        (project.control_points ?? []).forEach((point) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${point.number ?? ''}</td>
                <td>${point.label ?? ''}</td>
                <td>${point.category ?? '-'}</td>
                <td>${point.nominal_value ?? '-'}</td>
                <td>${point.tol_min ?? '-'} / ${point.tol_max ?? '-'}</td>
                <td>${point.unit ?? '-'}</td>
                <td>${point.plan_page ? 'P. ' + point.plan_page : ''} ${point.plan_ref ?? ''}</td>
                <td>${point.is_critical ? 'Oui' : 'Non'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-info" data-point-action="edit" data-id="${point.id}">Éditer</button>
                    <button class="btn btn-sm btn-outline-danger" data-point-action="delete" data-id="${point.id}">Supprimer</button>
                </td>
            `;
            controlPointsTableBody.appendChild(row);
        });
    };

    const loadProjects = async () => {
        const response = await fetch(routes.projectsIndex, {
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await handleJsonResponse(response);
        renderProjects(data.data ?? data);
    };

    const loadProjectDetails = async (projectId) => {
        const response = await fetch(routes.projectShow.replace('__ID__', projectId), {
            headers: {
                'Accept': 'application/json'
            }
        });
        selectedProject = await handleJsonResponse(response);
        renderDetails(selectedProject);
        hydrateUpdateForm(selectedProject);
    };

    const hydrateUpdateForm = (project) => {
        document.getElementById('project_update_id').value = project.id ?? '';
        document.getElementById('project_update_title').value = project.title ?? '';
        document.getElementById('project_update_company').value = project.companies_id ?? '';
        document.getElementById('project_update_order').value = project.orders_id ?? '';
        document.getElementById('project_update_order_line').value = project.order_lines_id ?? '';
        document.getElementById('project_update_of').value = project.of_id ?? '';
        document.getElementById('project_update_status').value = project.status ?? 'draft';
        document.getElementById('project_update_quantity').value = project.quantity_planned ?? '';
        document.getElementById('project_update_serial').checked = Boolean(project.serial_tracking);
    };

    document.getElementById('inspection-project-create-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(event.target);
        const payload = {
            title: formData.get('title'),
            companies_id: Number(formData.get('companies_id')),
            orders_id: formData.get('orders_id') ? Number(formData.get('orders_id')) : null,
            order_lines_id: formData.get('order_lines_id') ? Number(formData.get('order_lines_id')) : null,
            of_id: formData.get('of_id') ? Number(formData.get('of_id')) : null,
            status: formData.get('status'),
            quantity_planned: formData.get('quantity_planned') ? Number(formData.get('quantity_planned')) : null,
            serial_tracking: formData.get('serial_tracking') === 'on'
        };

        const response = await fetch(routes.projectStore, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        await handleJsonResponse(response);
        event.target.reset();
        await loadProjects();
    });

    document.getElementById('inspection-project-update-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        const projectId = document.getElementById('project_update_id').value;
        if (!projectId) {
            alert('Sélectionnez un projet.');
            return;
        }

        const formData = new FormData(event.target);
        const payload = {
            title: formData.get('title') || undefined,
            companies_id: formData.get('companies_id') ? Number(formData.get('companies_id')) : undefined,
            orders_id: formData.get('orders_id') ? Number(formData.get('orders_id')) : null,
            order_lines_id: formData.get('order_lines_id') ? Number(formData.get('order_lines_id')) : null,
            of_id: formData.get('of_id') ? Number(formData.get('of_id')) : null,
            status: formData.get('status') || undefined,
            quantity_planned: formData.get('quantity_planned') ? Number(formData.get('quantity_planned')) : null,
            serial_tracking: formData.get('serial_tracking') === 'on'
        };

        const response = await fetch(routes.projectUpdate.replace('__ID__', projectId), {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        await handleJsonResponse(response);
        await loadProjects();
        await loadProjectDetails(projectId);
    });

    document.getElementById('inspection-document-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selectedProject) {
            alert('Sélectionnez un projet.');
            return;
        }
        const formData = new FormData(event.target);
        const response = await fetch(routes.projectDocuments.replace('__ID__', selectedProject.id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        });

        await handleJsonResponse(response);
        event.target.reset();
        await loadProjectDetails(selectedProject.id);
    });

    document.getElementById('inspection-session-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selectedProject) {
            alert('Sélectionnez un projet.');
            return;
        }

        const formData = new FormData(event.target);
        const payload = {
            type: formData.get('type'),
            quantity_to_measure: formData.get('quantity_to_measure') ? Number(formData.get('quantity_to_measure')) : null,
            started_at: formData.get('started_at') || null
        };

        const response = await fetch(routes.projectSessions.replace('__ID__', selectedProject.id), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        await handleJsonResponse(response);
        event.target.reset();
        await loadProjectDetails(selectedProject.id);
    });

    document.getElementById('inspection-control-point-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!selectedProject) {
            alert('Sélectionnez un projet.');
            return;
        }

        const formData = new FormData(event.target);
        const pointId = formData.get('id');
        const payload = {
            number: Number(formData.get('number')),
            label: formData.get('label'),
            category: formData.get('category'),
            unit: formData.get('unit') || null,
            nominal_value: formData.get('nominal_value') ? Number(formData.get('nominal_value')) : null,
            tol_min: formData.get('tol_min') ? Number(formData.get('tol_min')) : null,
            tol_max: formData.get('tol_max') ? Number(formData.get('tol_max')) : null,
            plan_page: formData.get('plan_page') ? Number(formData.get('plan_page')) : null,
            plan_ref: formData.get('plan_ref') || null,
            phase: formData.get('phase') || null,
            instrument_type: formData.get('instrument_type') || null,
            is_critical: formData.get('is_critical') === 'on'
        };

        const response = await fetch(
            pointId ? routes.controlPointUpdate.replace('__ID__', pointId) : routes.projectControlPoints.replace('__ID__', selectedProject.id),
            {
                method: pointId ? 'PUT' : 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            }
        );

        await handleJsonResponse(response);
        resetControlPointForm();
        await loadProjectDetails(selectedProject.id);
    });

    document.getElementById('control_point_reset').addEventListener('click', () => {
        resetControlPointForm();
    });

    const resetControlPointForm = () => {
        document.getElementById('inspection-control-point-form').reset();
        document.getElementById('control_point_id').value = '';
    };

    projectsTableBody.addEventListener('click', async (event) => {
        const button = event.target.closest('button');
        if (!button) {
            return;
        }
        const projectId = button.dataset.id;
        await loadProjectDetails(projectId);
    });

    controlPointsTableBody.addEventListener('click', async (event) => {
        const button = event.target.closest('button');
        if (!button || !selectedProject) {
            return;
        }
        const pointId = button.dataset.id;
        if (button.dataset.pointAction === 'edit') {
            const point = selectedProject.control_points.find((item) => item.id === Number(pointId));
            if (!point) {
                return;
            }
            document.getElementById('control_point_id').value = point.id;
            document.getElementById('control_point_number').value = point.number ?? '';
            document.getElementById('control_point_label').value = point.label ?? '';
            document.getElementById('control_point_category').value = point.category ?? 'dimension';
            document.getElementById('control_point_unit').value = point.unit ?? '';
            document.getElementById('control_point_nominal').value = point.nominal_value ?? '';
            document.getElementById('control_point_tol_min').value = point.tol_min ?? '';
            document.getElementById('control_point_tol_max').value = point.tol_max ?? '';
            document.getElementById('control_point_plan_page').value = point.plan_page ?? '';
            document.getElementById('control_point_plan_ref').value = point.plan_ref ?? '';
            document.getElementById('control_point_phase').value = point.phase ?? '';
            document.getElementById('control_point_instrument').value = point.instrument_type ?? '';
            document.getElementById('control_point_critical').checked = Boolean(point.is_critical);
            return;
        }
        if (button.dataset.pointAction === 'delete') {
            if (!confirm('Supprimer ce point de contrôle ?')) {
                return;
            }
            const response = await fetch(routes.controlPointDelete.replace('__ID__', pointId), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            await handleJsonResponse(response);
            await loadProjectDetails(selectedProject.id);
        }
    });

    sessionsList.addEventListener('click', async (event) => {
        const button = event.target.closest('button');
        if (!button || !selectedProject) {
            return;
        }
        const sessionId = button.dataset.id;
        const action = button.dataset.sessionAction;
        const route = action === 'submit' ? routes.sessionSubmit : routes.sessionClose;
        const response = await fetch(route.replace('__ID__', sessionId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        await handleJsonResponse(response);
        await loadProjectDetails(selectedProject.id);
    });

    loadProjects().catch((error) => {
        console.error(error);
        detailsBox.textContent = 'Impossible de charger les projets.';
    });
</script>
@stop
