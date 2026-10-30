<?php

/*
 * Permission descriptions, shown under the technical name in Your company →
 * Roles and permissions. Key = permission name (spatie). A permission missing
 * from this list (created by hand) is shown without a description.
 */
return [
    'descriptions' => [
        // Module menus
        'companies-menu' => 'Companies menu: customers, suppliers, contacts and addresses.',
        'leads-menu' => 'Leads menu.',
        'opportunities-menu' => 'Opportunities menu; also shows opportunity sections in the affair summary.',
        'quotes-menu' => 'Quotes menu; also shows quotes, amounts and blockers in the affair summary.',
        'orders-menu' => 'Orders menu; also shows orders in the affair summary.',
        'order-confirmations-menu' => 'Order confirmations entry of the Orders menu.',
        'scheduling-menu' => 'Scheduling menu: load planning, task list and status, Kanban, Gantt, nesting.',
        'deliverys-menu' => 'Delivery notes menu; also shows delivery notes in the affair summary.',
        'returns-menu' => 'Returns entry (Delivery notes and Quality menus).',
        'invoices-menu' => 'Invoices menu: invoices, proformas, credit notes, reminders (read).',
        'products-menu' => 'Read the product catalogue: list, product sheet, routing, bill of materials, documents.',
        'products-edit' => 'Edit the product catalogue: create, update, duplicate, routing, bill of materials, suppliers, prices, import, product documents.',
        'products-merge' => 'Merge two products (also requires products-edit).',
        'stock-lot-serial-management' => 'Stock: locations, moves, inventories, shortages, batches (heat, material certificate) and serial numbers.',
        'purchases-menu' => 'Purchases menu: requests, requests for quotation, orders, receipts, supplier invoices.',
        'quality-menu' => 'Quality menu: dashboard, actions, derogations, non-conformities, inspection plans, FMEA, processes.',
        'audit-menu' => 'Internal audits (planning and execution).',
        'settings-time-menu' => 'Times menu: time overview and shifts.',
        'methods-menu' => 'Methods menu: work centres, resources, units, affair categories, loss reasons…',
        'accounting-menu' => 'Accounting menu: payment methods, VAT, allocations, delivery methods; invoice and FEC exports.',
        'human-resources-menu' => 'Human resources: employees, expense reports, attendance, leave balances, payroll export, absence approval, confidential employee file.',
        'osh-menu' => 'Health and safety menu: incidents, risks, trainings, skills matrix, compliance; manage training types.',
        'documents-menu' => 'Documents menu.',
        'reports-menu' => 'Reports menu (dashboard, accounting reports).',
        'spreadsheet-menu' => 'Spreadsheets menu.',
        'your-company-menu' => 'Administration: company settings, roles and permissions, users, imports / exports, integrations.',
        'asset_manager' => 'Maintenance (CMMS) module: dashboard, equipment, maintenance plans, work orders.',
        'asset-menu' => 'Assets entry of the Accounting and Maintenance menus.',

        // Action permissions
        'accounting-manage' => 'VAT regimes and natures, VAT matrices, accounting period locking.',
        'credit-limit-override' => 'Change a company\'s credit limit and create an order or delivery note beyond it (reason required).',
        'invoice-reminders-send' => 'Send or record invoice reminders and set up reminder levels.',
        'inspection-plans-edit' => 'Create and balloon inspection plans; sign a first article report as "checked".',
        'inspection-plans-approve' => 'Approve an inspection plan; approve (and lock) a first article report.',
        'required-operations-override' => 'Convert a quote into an order although the routing lacks an operation required by the affair category (reason required).',
        'audit-access-log' => 'View and export the access log (who opened which record).',
        'quality-derogation-validate' => 'Set a derogation to "Validated" and edit a validated derogation.',
        'production-release' => 'Grant or cancel the production release of order lines.',
        'purchase-quality-clauses-manage' => 'Manage the purchase order quality clauses and their rules.',
        'task-release' => 'Sign off a finished phase (work centres with "Release sign-off required").',
        'dossier-validate' => 'Validate, revoke, require or waive the manufacturing dossier of an order line (unblocks delivery and invoicing).',
        'reception-document-check' => 'Sign off the document conformity (certificates) of receipt lines.',
        'unqualified-supplier-override' => 'Create or validate a purchase order with an unqualified supplier (reason required).',
        'contract-review-approve' => 'Sign off (approve or reject) an order\'s contract review and its amendments; cancel an amendment.',
        'order-price-recalculate' => 'Enable the calculated price of an order line past preparation (reason required).',
        'affair-risk-close' => 'Close a critical risk of an affair risk register.',
    ],
];
