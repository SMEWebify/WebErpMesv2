<?php

return [
    'options' => [

        //DATA BASE ACCOUTING
        'TABLE_ERP_TRANSPORT' => 'ac_delivery',
        'TABLE_ERP_CONDI_REG' => 'ac_payment_condition',
        'TABLE_ERP_MODE_REG' => 'ac_payment_method',
        'TABLE_ERP_TVA' => 'ac_VAT',
        'TABLE_ERP_IMPUT_COMPTA' => 'ac_accounting_allocation',
        'TABLE_ERP_IMPUT_COMPTA_LIGNE' => 'ac_accounting_allocation_lines',
        'TABLE_ERP_IMPUT_COMPTA_PRESTATION' => 'ac_accounting_allocation_services',
        'TABLE_ERP_ECHEANCIER_TYPE' => 'ac_timeline_paiement',
        'TABLE_ERP_ECHEANCIER_TYPE_LIGNE' => 'ac_timeline_paiement_lines',

        //DATA
        'TABLE_ERP_ATTACHED_DOCUMENT' => 'attached_document',
        //DATA BASE COMPANIES
        'TABLE_ERP_COMPANES' => 'companies',
        'TABLE_ERP_ADRESSE' => 'companies_addresses',
        'TABLE_ERP_CONTACT' => 'companies_contact',
        //DATA BASE COMPANY
        'TABLE_ERP_ACTIVITY_SECTOR' => 'company_activity_sector',
        'TABLE_ERP_COMPANY' => 'company_setting',
        'TABLE_ERP_RIGHTS' => 'company_rights',
        'TABLE_ERP_EMPLOYEES' => 'company_users',
        'TABLE_ERP_EMAIL' => 'company_email_type',
        'TABLE_ERP_INFO_GENERAL' => 'company_timeline', 
        'TABLE_ERP_NUM_DOC' => 'company_document_numbering',

        //DATA BASE QUOTE
        'TABLE_ERP_QUOTE' => 'quote',
        'TABLE_ERP_QUOTE_LIGNE' => 'quote_lines',
        'TABLE_ERP_QUOTE_SUB_ASSEMBLY' => 'quote_sub_assembly',
        //DATA BASE ORDER
        'TABLE_ERP_ORDER' => 'orders',
        'TABLE_ERP_ORDER_LIGNE' => 'orders_lines',
        'TABLE_ERP_ORDER_SUB_ASSEMBLY' => 'order_sub_assembly',
        //DATA BASE ORDER ACKNOWLEGMENT
        'TABLE_ERP_ORDER_ACKNOWLEGMENT' => 'order_acknowledgment',
        'TABLE_ERP_ORDER_ACKNOWLEGMENT_LINES' => 'order_acknowledgment_lines',
        //DATA BASE ORDER DELERERY NOTE
        'TABLE_ERP_ORDER_DELIVERY_NOTE' => 'order_delivery_note',
        'TABLE_ERP_ORDER_DELIVERY_NOTE_LINES' => 'order_delivery_note_lines',
        //DATA BASE ORDER INVOICE
        'TABLE_ERP_ORDER_INVOICE' => 'order_invoice',
        'TABLE_ERP_ORDER_INVOICE_LINES' => 'order_invoice_lines',
        //DATA BASE ORDER RETURN
        'TABLE_ERP_ORDER_RETURN' => 'orders_delivery_return',
        'TABLE_ERP_ORDER_RETURN_LINES' => 'orders_delivery_return_lines',

        // DATA PURCHASE REQUEST
        'TABLE_ERP_PURCHASE_REQUEST' => 'purchase_request',
        'TABLE_ERP_PURCHASE_REQUEST_LINES' => 'purchase_request_lines',
        // DATA PURCHASE ORDER
        'TABLE_ERP_PURCHASE_ORDER' => 'purchase_order',
        'TABLE_ERP_PURCHASE_ORDER_LINES' => 'purchase_order_lines',
        // DATA PURCHASE DELIVERY
        'TABLE_ERP_PURCHASE_DELIVERY_NOTE' => 'purchase_delivery_note',
        'TABLE_ERP_PURCHASE_DELIVERY_NOTE_LINES' => 'purchase_delivery_note_lines',


        //DATA BASE QUALITY
        'TABLE_ERP_QL_ACTION' => 'ql_action',
        'TABLE_ERP_QL_APP_MESURE' => 'ql_appareil_mesure',
        'TABLE_ERP_QL_CAUSES' => 'ql_causes',
        'TABLE_ERP_QL_CORRECTIONS' => 'ql_corrections',
        'TABLE_ERP_DEFAUT' => 'ql_defaut',
        'TABLE_ERP_DEROGATION' => 'ql_derogation',
        'TABLE_ERP_NFC' => 'ql_nfc',


        //DATA BASE STOCK
        'TABLE_ERP_STOCK' => 'stock',
        'TABLE_ERP_STOCK_LOCATION' => 'stock_location',

        //DATA BASE STUDY
        'TABLE_ERP_UNIT' => 'study_unit',
        'TABLE_ERP_SOUS_FAMILLE' => 'study_sub_familly',
        'TABLE_ERP_STANDARD_ARTICLE' => 'study_standard_article',
        'TABLE_ERP_STANDARD_SUB_ASSEMBLY' => 'study_standard_sub_assembly',

        //DATA BASE METHODS
        'TABLE_ERP_SECTION' => 'methods_section',
        'TABLE_ERP_RESSOURCE' => 'methods_resource',
        'TABLE_ERP_STOCK_ZONE' => 'methods_stock_zone',
        'TABLE_ERP_PRESTATION' => 'methods_services',

        //DATA BASE TIME
        'TABLE_ERP_DAILY_HOURLY_MODEL' => 'time_daily_hourly_model',
        'TABLE_ERP_DAILY_HOURLY_MODEL_LINES' => 'time_daily_hourly_model_line',
        'TABLE_ERP_TYPE_ABS' => 'time_absence_type',
        'TABLE_ERP_FERIER' => 'time_bank_holiday',
        'TABLE_ERP_EVENT_MACHINE' => 'time_event_machine',
        'TABLE_ERP_EVENT_IMPRODUC_TIME' => 'time_improductive_activity',
        'TABLE_ERP_ABS_HISTORY' => 'time_absence_history',

        //DATA TASK
        'TABLE_ERP_TASK' => 'task',
        'TABLE_ERP_TASK_REMAINING_TIME' => 'order_remaining_time',

        //TOOL
        'TABLE_ERP_TOOL' => 'tool',


        //FOLDER
        'PICTURE_FOLDER' => 'images/',
        'PROFIL_FOLDER' => 'Profils/',
        'RESSOURCES_FOLDER' => 'Ressources/',
        'QUALITY_DEVICES_FOLDER' => 'Quality/',
        'STUDY_ARTICLE_FOLDER' => 'Articles/',
        'COMPANIES_FOLDER' => 'Clients/',
        'COMPANY_FOLDER' => 'Company/',

    ]
];