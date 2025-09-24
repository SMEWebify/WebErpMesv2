// Importez bootstrap.js pour charger les dépendances de Laravel
import './bootstrap';

import { createApp, h } from 'vue';
import KanbanBoard from './components/KanbanBoard.vue';
import DocumentTable from './components/DocumentTable.vue';
import Whiteboard from './components/Whiteboard.vue';
import 'livewire-sortable';

function mountKanbanBoard() {
    const element = document.getElementById('card');


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

    if (!element) {
        return;
    }

    const kanbanApp = createApp({});
    kanbanApp.component('kanban-board', KanbanBoard);
    kanbanApp.mount(element);
}

function parseJsonAttribute(value) {
    if (!value) {
        return null;
    }

    try {
        return JSON.parse(value);
    } catch (error) {
        console.warn('Unable to parse JSON attribute', value, error);
        return null;
    }
}

function mountWhiteboard() {
    const element = document.getElementById('whiteboard-app');

    if (!element) {
        return;
    }

    const props = {};

    if (element.hasAttribute('data-initial-whiteboard-id')) {
        const id = element.getAttribute('data-initial-whiteboard-id');
        if (id !== null && id !== '' && id !== 'null' && id !== 'undefined') {
            const numericId = Number(id);
            if (!Number.isNaN(numericId)) {
                props.initialWhiteboardId = numericId;
            }
        }
    }

    if (element.dataset.initialWhiteboard) {
        props.initialWhiteboard = parseJsonAttribute(element.dataset.initialWhiteboard);
    }

    if (element.dataset.initialSnapshots) {
        props.initialSnapshots = parseJsonAttribute(element.dataset.initialSnapshots) || [];
    }

    if (element.dataset.initialFiles) {
        props.initialFiles = parseJsonAttribute(element.dataset.initialFiles) || [];
    }

    if (element.dataset.endpoints) {
        props.endpoints = parseJsonAttribute(element.dataset.endpoints) || {};
    }

    if (element.children.length === 0) {
        const whiteboardApp = createApp(Whiteboard, props);
        whiteboardApp.mount(element);
        return;
    }

    const whiteboardApp = createApp({
        render() {
            return h('whiteboard', props);
        }
    });

    whiteboardApp.component('whiteboard', Whiteboard);
    whiteboardApp.mount(element);
}

mountKanbanBoard();
mountWhiteboard();

