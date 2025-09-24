// Importez bootstrap.js pour charger les dépendances de Laravel
import './bootstrap';

import { createApp } from 'vue';
import KanbanBoard from './components/KanbanBoard.vue';
import DocumentTable from './components/DocumentTable.vue';
import 'livewire-sortable';

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const kanbanMountPoint = document.getElementById('card');
if (kanbanMountPoint) {
    const kanbanApp = createApp({});
    kanbanApp.component('kanban-board', KanbanBoard);
    kanbanApp.mount(kanbanMountPoint);
}

const documentTableMountPoint = document.getElementById('document-table-app');
if (documentTableMountPoint) {
    const initialDocuments = documentTableMountPoint.dataset.documents
        ? JSON.parse(documentTableMountPoint.dataset.documents)
        : [];
    const translations = documentTableMountPoint.dataset.translations
        ? JSON.parse(documentTableMountPoint.dataset.translations)
        : {};

    createApp(DocumentTable, {
        initialDocuments,
        translations,
    }).mount(documentTableMountPoint);
}
