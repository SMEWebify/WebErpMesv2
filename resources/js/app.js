import './bootstrap';

import { createApp, h } from 'vue';
import Whiteboard from './components/Whiteboard.vue';
import { createRoot } from 'react-dom/client';
import React from 'react';
import KanbanBoard from './components/KanbanBoard.jsx';
import DocumentTable from './components/DocumentTable.jsx';
import NestingPage from './components/NestingPage.jsx';
import QuotesIndex from './components/QuotesIndex.jsx';
import CompaniesIndex from './components/CompaniesIndex.jsx';
import OrdersIndex from './components/OrdersIndex.jsx';
import DeliverysIndex from './components/DeliverysIndex.jsx';
import InvoicesIndex from './components/InvoicesIndex.jsx';
import ProductsIndex from './components/ProductsIndex.jsx';
import QuoteLinesIndex from './components/QuoteLinesIndex.jsx';
import OrderLinesIndex from './components/OrderLinesIndex.jsx';
import SetupWizard from './components/SetupWizard.jsx';
import CompanyDashboard from './components/CompanyDashboard.jsx';
import CompanyForm from './components/CompanyForm.jsx';
import CompanyAddresses from './components/CompanyAddresses.jsx';
import CompanyContacts from './components/CompanyContacts.jsx';
// livewire-sortable ne doit être chargé que sur les pages qui embarquent Livewire
document.addEventListener('livewire:init', () => {
    import('livewire-sortable');
});

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

function mountQuotesIndex() {
    const element = document.getElementById('quotes-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(QuotesIndex, {
            kpi:           parse('kpi')          ?? {},
            chartData:     parse('chart')        ?? {},
            topCustomers:  parse('topCustomers') ?? [],
            quotesByUser:  parse('quotesByUser') ?? {},
            endpoints:     parse('endpoints')    ?? {},
            trans:         parse('trans')        ?? {},
            companieId:    parse('companieId')   ?? null,
        })
    );
}

function mountOrdersIndex() {
    const element = document.getElementById('orders-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(OrdersIndex, {
            kpi:          parse('kpi')          ?? {},
            chartData:    parse('chart')        ?? {},
            topCustomers: parse('topCustomers') ?? [],
            endpoints:    parse('endpoints')    ?? {},
            trans:        parse('trans')        ?? {},
            companieId:   parse('companieId')   ?? null,
        })
    );
}

function mountQuoteLinesIndex() {
    const element = document.getElementById('quote-lines-index-app');
    if (!element) return;
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };
    createRoot(element).render(
        React.createElement(QuoteLinesIndex, {
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountOrderLinesIndex() {
    const element = document.getElementById('order-lines-index-app');
    if (!element) return;
    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };
    createRoot(element).render(
        React.createElement(OrderLinesIndex, {
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountDeliverysIndex() {
    const element = document.getElementById('deliverys-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(DeliverysIndex, {
            kpi:        parse('kpi')        ?? {},
            chartData:  parse('chart')      ?? {},
            endpoints:  parse('endpoints')  ?? {},
            trans:      parse('trans')      ?? {},
            companieId: parse('companieId') ?? null,
        })
    );
}

function mountInvoicesIndex() {
    const element = document.getElementById('invoices-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(InvoicesIndex, {
            kpi:        parse('kpi')        ?? {},
            chartData:  parse('chart')      ?? {},
            topClients: parse('topClients') ?? [],
            endpoints:  parse('endpoints')  ?? {},
            trans:      parse('trans')      ?? {},
            companieId: element.dataset.companieId ? parseInt(element.dataset.companieId, 10) : null,
        })
    );
}

function mountProductsIndex() {
    const element = document.getElementById('products-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(ProductsIndex, {
            kpi:       parse('kpi')       ?? {},
            chartData: parse('chart')     ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountCompaniesIndex() {
    const element = document.getElementById('companies-index-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompaniesIndex, {
            kpi:       parse('kpi')       ?? {},
            chartData: parse('chart')     ?? {},
            endpoints: parse('endpoints') ?? {},
            trans:     parse('trans')     ?? {},
        })
    );
}

function mountCompanyForm() {
    const element = document.getElementById('company-form-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompanyForm, {
            company:  parse('company')  ?? {},
            users:    parse('users')    ?? [],
            endpoint: element.dataset.endpoint ?? '',
            trans:    parse('trans')    ?? {},
        })
    );
}

function mountCompanyDashboard() {
    const element = document.getElementById('company-dashboard-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompanyDashboard, {
            kpi:    parse('kpi')    ?? {},
            charts: parse('charts') ?? {},
            trans:  parse('trans')  ?? {},
        })
    );
}

function mountCompanyAddresses() {
    const element = document.getElementById('company-addresses-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompanyAddresses, {
            initialAddresses: parse('addresses') ?? [],
            storeUrl:         element.dataset.storeUrl      ?? '',
            updateBaseUrl:    element.dataset.updateBaseUrl ?? '',
            companieId:       element.dataset.companieId ? parseInt(element.dataset.companieId, 10) : null,
            trans:            parse('trans') ?? {},
        })
    );
}

function mountCompanyContacts() {
    const element = document.getElementById('company-contacts-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(CompanyContacts, {
            initialContacts: parse('contacts') ?? [],
            storeUrl:        element.dataset.storeUrl      ?? '',
            updateBaseUrl:   element.dataset.updateBaseUrl ?? '',
            companieId:      element.dataset.companieId ? parseInt(element.dataset.companieId, 10) : null,
            trans:           parse('trans') ?? {},
        })
    );
}

function mountSetupWizard() {
    const element = document.getElementById('setup-wizard-app');
    if (!element) return;

    const parse = (attr) => {
        try { return JSON.parse(element.dataset[attr] ?? 'null'); } catch { return null; }
    };

    createRoot(element).render(
        React.createElement(SetupWizard, {
            endpoints: parse('endpoints') ?? {},
            initial:   parse('initial')   ?? {},
        })
    );
}

mountSetupWizard();
mountCompanyDashboard();
mountCompanyForm();
mountCompanyAddresses();
mountCompanyContacts();
mountKanbanBoard();
mountDocumentTable();
mountCompaniesIndex();
mountWhiteboard();
mountNestingPage();
mountQuotesIndex();
mountOrdersIndex();
mountQuoteLinesIndex();
mountOrderLinesIndex();
mountDeliverysIndex();
mountInvoicesIndex();
mountProductsIndex();
