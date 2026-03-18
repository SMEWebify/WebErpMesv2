import './bootstrap';

import { createApp, h } from 'vue';
import Whiteboard from './components/Whiteboard.vue';
import { createRoot } from 'react-dom/client';
import React from 'react';
import KanbanBoard from './components/KanbanBoard.jsx';
import DocumentTable from './components/DocumentTable.jsx';
import NestingPage from './components/NestingPage.jsx';
import 'livewire-sortable';

function mountKanbanBoard() {
    const element = document.getElementById('card');
    if (!element) return;

    const initialData = element.dataset.initialData
        ? JSON.parse(element.dataset.initialData)
        : [];

    createRoot(element).render(
        React.createElement(KanbanBoard, { initialData })
    );
}

function mountDocumentTable() {
    const element = document.getElementById('document-table-app');
    if (!element) return;

    const initialDocuments = element.dataset.documents
        ? JSON.parse(element.dataset.documents)
        : [];
    const translations = element.dataset.translations
        ? JSON.parse(element.dataset.translations)
        : {};

    createRoot(element).render(
        React.createElement(DocumentTable, { initialDocuments, translations })
    );
}

function parseJsonAttribute(value) {
    if (!value) return null;
    try {
        return JSON.parse(value);
    } catch (error) {
        console.warn('Unable to parse JSON attribute', value, error);
        return null;
    }
}

function mountWhiteboard() {
    const element = document.getElementById('whiteboard-app');
    if (!element) return;

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
        createApp(Whiteboard, props).mount(element);
        return;
    }

    const whiteboardApp = createApp({
        render() {
            return h('whiteboard', props);
        },
    });
    whiteboardApp.component('whiteboard', Whiteboard);
    whiteboardApp.mount(element);
}

function mountNestingPage() {
    const element = document.getElementById('nesting-app');
    if (!element) return;

    createRoot(element).render(React.createElement(NestingPage));
}

mountKanbanBoard();
mountDocumentTable();
mountWhiteboard();
mountNestingPage();
