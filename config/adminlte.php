<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => env('APP_COMMERCIAL', false) ? 'Nest2Prod ERP' : 'WEB ERP MES',
    'title_prefix' => '',
    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => true,

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '',
    'logo_img' => env('APP_COMMERCIAL', false) ? 'img/nest2prod-logo.png' : 'vendor/adminlte/dist/img/simple-logo -R.PNG',
    'logo_img_class' => 'brand-image  elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => env('APP_COMMERCIAL', false) ? 'Nest2Prod ERP' : 'WEM',

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => true,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => true,
    'usermenu_desc' => true,
    'usermenu_profile_url' => true,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => true,
    'layout_fixed_footer' => true,
    'layout_dark_mode' => false,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline',
    'classes_auth_header' => 'bg-gradient-info',
    'classes_auth_body' => '',
    'classes_auth_footer' => 'text-center',
    'classes_auth_icon' => 'fa-lg text-info',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => ' elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => true,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => 'home',
    'logout_url' => 'logout',
    'login_url' => '/',
    'register_url' => 'register',
    'password_reset_url' => 'forgot/password',
    'password_email_url' => 'password/email',
    'profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Mix
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Mix option for the admin panel.
    |
    | For detailed instructions you can look the laravel mix section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'enabled_laravel_mix' => false,
    'laravel_mix_css_path' => 'css/app.css',
    'laravel_mix_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'menu' => [
        // Navbar items:
        [
            'type'          => 'navbar-search',
            'text'          => 'search_trans_key',
            'topnav'        => true,
            'url'           => 'navbar/search',
            'method'        => 'post',
            'input_name'    => 'searchVal' ,
        ],
        [
            'text'            => 'whiteboard_trans_key',
            'url'             => 'collaboration/whiteboards',
            'topnav_right'    => true,
        ],
        [
            'text'            => 'Iframe mode',
            'url'             => 'iframe-mode',
            'topnav_right'    => true,
        ],
        [
            'text'            => 'users_trans_key',
            'url'             => 'users',
            'topnav_right'    => true,
        ],
        [
            'type'         => 'fullscreen-widget',
            'topnav_right' => true,
        ],
        [
            'type'         => 'darkmode-widget',
            'topnav_right' => true, // Or "topnav => true" to place on the left.
        ],
        [
            'type'         => 'navbar-notification',
            'id'           => 'my-notification',
            'icon'         => 'fas fa-bell',
            'route'        => 'notifications.show',
            'topnav_right' => true,
            'dropdown_mode'   => true,
            'dropdown_flabel' => 'All notifications',
            'update_cfg'   => [
                'route'  => ['notifications.get', ['param' => 'val']],
                'period' => 30,
            ],
        ],
        [
            'text' => 'language_trans_key',
            'topnav_right' => true,
            'icon' => 'flag-icon flag-icon-gb',
            'submenu' => [
                [
                    'text'=>'english_trans_key',
                    'icon' => 'flag-icon flag-icon-gb',
                    'url'=> 'en',
                ],
                [
                    'text'=>'french_trans_key',
                    'icon' => 'flag-icon flag-icon-fr',
                    'url'=> 'fr',
                ],
                [
                    'text'=>'vietnamese_trans_key',
                    'icon' => 'flag-icon flag-icon-vi',
                    'url'=> 'vi',
                ],
                [
                    'text'=>'Spanish',
                    'icon' => 'flag-icon flag-icon-es',
                    'url'=> 'es',
                ],
                [
                    'text'=>'Arabic',
                    'icon' => 'flag-icon flag-icon-ar',
                    'url'=> 'ar',
                ],
                [
                    'text' =>'Chinese',
                    'icon' => 'flag-icon flag-icon-zh',
                    'url'=> 'zh',
                ]

                
            ]
        ],
        // Sidebar items:
        /*[
            'type'      => 'sidebar-menu-search',
            'text'      => 'search',
            'url'       => 'sidebar/search' ,
            'method'    => 'post' ,
            'input_name'=> 'searchVal' ,
        ],*/
        [
            'text'        => 'dashboard_trans_key',
            'url'         => 'dashboard',
            'icon'        => 'fas fa-tachometer-alt',
            'icon_color' => 'warning',
        ],
        [
            'text'    => 'companies_trans_key',
            'icon'    => 'far fa-building',
            'url'     => 'companies',
            'icon_color' => 'info',
            'can'     => ['companies-menu'],
        ],
        [
            'text'        => 'leads_trans_key',
            'url'         => 'leads',
            'icon'        => 'fas fa-globe',
            'icon_color' => 'primary',
            'can'         => ['leads-menu'],
        ],
        [
            'text'        => 'opportunities_trans_key',
            'url'         => 'opportunities',
            'icon'        => 'fa fa-tags',
            'icon_color' => 'danger',
            'can'         => ['opportunities-menu'],
        ],
        [
            'text'    => 'quote_trans_key',
            'icon'    => 'fas fa-calculator',
            'icon_color' => 'teal',
            'can'  => ['quotes-menu'],
            'submenu' => array_merge(
                [
                    [
                        'text' => 'quotes_list_trans_key',
                        'url'  => 'quotes',
                    ],
                    [
                        'text' => 'quotes_lines_list_trans_key',
                        'url'  => 'quotes/lines',
                    ],
                ],
                env('NEST4QUOTE_ENABLED', false) ? [
                    [
                        'text'   => 'Nest4Quote',
                        'url'    => 'https://nest4quote.com/',
                        'target' => '_blank',
                        'icon'   => 'fas fa-external-link-alt',
                    ],
                ] : []
            )
        ],
        [
            'text'    => 'orders_trans_key',
            'icon'    => 'fas fa-shopping-cart',
            'url'  => 'orders',
            'icon_color' => 'warning',
            'can'  => ['orders-menu'],
            'submenu' => [
                [
                    'key'  => 'orders_lines_list',
                    'text' => 'orders_lines_list_trans_key',
                    'url'  => 'orders/lines',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'order_calendar_trans_key',
                    'url'  => 'production/calendar/orders',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'pre_orders_trans_key',
                    'url'  => 'pre-orders',
                    'icon_color' => 'primary',
                    'enabled' => (bool) config('pre_orders.menu_enabled', false),
                ],
            ]
        ],
        [
            'text'    => 'scheduling_trans_key',
            'icon' => 'fas fa-calendar-alt',
            'icon_color' => 'info',
            'can'  => ['scheduling-menu'],
            'submenu' => array_merge(
                [
                    [
                        'text' => 'load_planning_trans_key',
                        'url'  => 'production/load-planning',
                        'icon_color' => 'warning',
                    ],
                    [
                        'text' => 'tasks_list_trans_key',
                        'url'  => 'production/Task',
                        'icon_color' => 'info',
                    ],
                    [
                        'text' => 'methods_overview_trans_key',
                        'url'  => 'methods/overview',
                        'icon_color' => 'success',
                    ],
                    [
                        'text' => 'tasks_calendar_trans_key',
                        'url'  => 'production/calendar/tasks',
                        'icon_color' => 'primary',
                    ],
                    [
                        'text' => 'tasks_statu_trans_key',
                        'url'  => 'production/Task/Statu',
                        'icon_color' => 'danger',
                    ],
                    [
                        'text' => 'workflow_trans_key',
                        'url'  => 'production/kanban',
                        'icon_color' => 'teal',
                    ],
                    [
                        'text' => 'gantt_trans_key',
                        'url'  => 'production/gantt',
                        'icon_color' => 'orange',
                        'label'       => 'Beta',
                        'label_color' => 'danger',
                    ],
                ],
                env('NESTING_ENABLED', false) ? [
                    [
                        'text' => 'nesting_trans_key',
                        'url'  => 'nesting',
                        'icon_color' => 'purple',
                    ],
                ] : []
            ),
        ],
        [
            'key'  => 'delivery_notes',
            'text'    => 'delivery_notes_trans_key',
            'icon'    => 'fas fa-receipt',
            'icon_color' => 'primary',
            'can'  => ['deliverys-menu'],
            'submenu' => [
                [
                    'text' => 'deliverys_notes_list_trans_key',
                    'url'  => 'deliverys',
                ],
                [
                    'text' => 'returns_trans_key',
                    'url'  => 'returns',
                    'icon_color' => 'warning',
                    'can'  => ['returns-menu'],
                ],
            ]
        ],
        [
            'key'  => 'invoices',
            'text'    => 'invoices_trans_key',
            'icon'    => 'fas fa-calculator',
            'icon_color' => 'danger',
            'can'  => ['invoices-menu'],
            'submenu' => [
                [
                    'text' => 'invoices_list_trans_key',
                    'url'  => 'invoices',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'proformas_trans_key',
                    'url'  => 'proformas',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'credit_notes_trans_key',
                    'url'  => 'credit-notes',
                    'icon_color' => 'primary',
                ],
            ]
        ],
        [
            'text'    => 'product_trans_key',
            'icon'    => 'fas fa-barcode',
            'icon_color' => 'teal',
            'can'  => ['products-menu'],
            'submenu' => [
                [
                    'text' => 'product_list_trans_key',
                    'url'  => 'products',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'serial_numbers_trans_key',
                    'url'  => 'products/serial-numbers',
                    'icon_color' => 'info',
                    'can'  => ['stock-lot-serial-management'],
                ],
                [
                    'text' => 'batches_trans_key',
                    'url'  => 'products/batches',
                    'icon_color' => 'info', // choose color consistent with design
                    'can'  => ['stock-lot-serial-management'],
                ],
                [
                    'text' => 'stock_trans_key',
                    'url'  => 'products/Stock',
                    'icon_color' => 'primary',
                    'can'  => ['stock-lot-serial-management'],
                ],
                /*[
                    'text' => 'inventory_trans_key',
                    'url'  => '#',
                ],*/
            ],
        ],
        [
            'text'    => 'purchase_trans_key',
            'icon'    => 'fas fa-cash-register',
            'icon_color' => 'orange',
            'can'  => ['purchases-menu'],
            'submenu' => [
                [
                    'text' => 'purchase_request_trans_key',
                    'url'  => 'purchases/request',
                    'label_color' => 'warning',
                ],
                [
                    'key'  => 'requests_for_quotation',
                    'text' => 'requests_for_quotation_list_trans_key',
                    'url'  => 'purchases/quotation',
                ],
                [
                    'text' => 'purchase_list_trans_key',
                    'url'  => 'purchases',
                ],
                [
                    'key'  => 'po_receipt',
                    'text' => 'po_receipt_trans_key',
                    'url'  => 'purchases/receipt',
                ],
                [
                    'key'  => 'invoice_supplier',
                    'text' => 'invoice_supplier_trans_key',
                    'url'  => 'purchases/invoice',
                ],
            ],
        ],
        [
            'text' => 'quality_trans_key',
            'icon'    => 'fas fa-ruler-combined',
            'icon_color' => 'info',
            'can'  => ['quality-menu'],
            'submenu' => [
                [
                    'text' => 'dashboard_trans_key',
                    'url'  => 'quality',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'action_trans_key',
                    'url'  => 'quality/action',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'derogations_trans_key',
                    'url'  => 'quality/derogation',
                    'icon_color' => 'primary',
                ],
                [
                    'text' => 'non_conformities_trans_key',
                    'url'  => 'quality/nonConformitie',
                    'icon_color' => 'danger',
                ],
                [
                    'text' => 'inspection_trans_key',
                    'url'  => 'quality/inspection-projects',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'process_diagrams_trans_key',
                    'url'  => 'quality/process-diagrams',
                    'icon_color' => 'purple',
                ],
                [
                    'text' => 'amdec_trans_key',
                    'url'  => 'quality/amdec',
                    'icon_color' => 'teal',
                ],
                [
                    'text' => 'internal_audits_trans_key',
                    'url'  => 'audit',
                    'icon' => 'fas fa-clipboard-check',
                    'icon_color' => 'success',
                    'can'  => ['audit-menu'],
                ],
                [
                    'text' => 'returns_trans_key',
                    'url'  => 'returns',
                    'icon_color' => 'warning',
                    'can'  => ['returns-menu'],
                ],
            ],
        ],
        ['header' => 'settings_trans_key'],
        [
            'text' => 'settings_time_trans_key',
            'icon'    => 'fas fa-user-clock',
            'url'  => 'times',
            'can'  => ['settings-time-menu'],
        ],
        [
            'text' => 'methods_trans_key',
            'icon'   => 'fas fa-cogs',
            'url'  => 'methods',
            'can'  => ['methods-menu'],
            'submenu' => [
                [
                    'text' => 'methods_services_trans_key',
                    'url'  => 'methods/service',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'methods_ressources_trans_key',
                    'url'  => 'methods/ressources',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'methods_sections_trans_key',
                    'url'  => 'methods/section',
                    'icon_color' => 'primary',
                ],
                [
                    'text' => 'methods_locations_trans_key',
                    'url'  => 'methods/location',
                    'icon_color' => 'danger',
                ],
                [
                    'text' => 'methods_units_trans_key',
                    'url'  => 'methods/unit',
                    'icon_color' => 'teal',
                ],
                [
                    'text' => 'methods_familys_trans_key',
                    'url'  => 'methods/family',
                    'icon_color' => 'orange',
                ],
                [
                    'text' => 'methods_tools_trans_key',
                    'url'  => 'methods/tool',
                ],
                [
                    'text' => 'methods_standard_bom_trans_key',
                    'url'  => 'methods/standard-nomenclature',
                    'icon_color' => 'purple',
                ],
            ],
        ],
        [
            'text' => 'accounting_trans_key',
            'icon' => 'fas fa-piggy-bank',
            'can'  => ['accounting-menu'],
            'submenu' => [
                [
                    'text' => 'payment_conditions_trans_key',
                    'url'  => 'accounting/payment-conditions',
                    'icon_color' => 'primary',
                ],
                [
                    'text' => 'payment_methods_trans_key',
                    'url'  => 'accounting/payment-methods',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'vat_trans_key',
                    'url'  => 'accounting/vats',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'accounting_allocations_trans_key',
                    'url'  => 'accounting/allocations',
                    'icon_color' => 'teal',
                ],
                [
                    'text' => 'delevery_method_trans_key',
                    'url'  => 'accounting/deliveries',
                    'icon_color' => 'danger',
                ],
                [
                    'text' => 'assets_trans_key',
                    'url'  => 'accounting/assets',
                    'icon_color' => 'purple',
                    'can'  => ['asset-menu'],
                ],
            ],
        ],
        [
            'text' => 'GMAO',
            'icon' => 'fas fa-tools',
            'can'  => ['asset_manager'],
            'submenu' => [
                [
                    'text' => 'Dashboard',
                    'url'  => 'gmao/dashboard',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'assets_trans_key',
                    'url'  => 'assets',
                    'icon_color' => 'info',
                    'can'  => ['asset-menu'],
                ],
                [
                    'text' => 'Work orders',
                    'url'  => 'gmao/work-orders',
                    'icon_color' => 'primary',
                ],
            ],
        ],
        [
            'text' => 'human_resources_trans_key',
            'icon' => 'fas fa-users',
            'url'  => '',
            'can'  => ['human-resources-menu'],
            'submenu' => [
                [
                    'text' => 'expenses_trans_key',
                    'url'  => 'human-resources',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'users_list_trans_key',
                    'url'  => 'human-resources/users',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'attendance_report_trans_key',
                    'url'  => 'human-resources/attendance',
                    'icon_color' => 'primary',
                ],
            ],
        ],
        [
            'text' => 'documents_trans_key',
            'icon' => 'fas fa-folder-open',
            'icon_color' => 'cyan',
            'url'  => 'documents',
            'can'  => ['documents-menu'],
        ],
        [
            'text' => 'osh_trans_key',
            'icon'    => 'fas fa-hard-hat',
            'can'  => ['osh-menu'],
            'submenu' => [
                [
                    'text' => 'incidents_trans_key',
                    'url'  => 'osh/incidents',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'risques_trans_key',
                    'url'  => 'osh/risks',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'formations_trans_key',
                    'url'  => 'osh/trainings',
                    'icon_color' => 'primary',
                ],
                [
                    'text' => 'conformites_trans_key',
                    'url'  => 'osh/conformities',
                    'icon_color' => 'success',
                ],
            ],
        ],

        [
            'text' => 'Tableurs',
            'icon' => 'nav-icon fas fa-table',
            'url'  => 'spreadsheet',
            'active' => ['spreadsheet*'],
            'can'  => ['spreadsheet-menu'],
        ],
        [
            'text' => 'Reports',
            'icon' => 'fas fa-chart-pie',
            'icon_color' => 'purple',
            'url'  => 'reports',
            'can'  => ['reports-menu'],
            'submenu' => [
                [
                    'text' => 'dashboard_trans_key',
                    'url'  => 'reports',
                    'icon_color' => 'purple',
                ],
                [
                    'text' => 'Accounting',
                    'url'  => 'reports/accounting',
                    'icon_color' => 'cyan',
                ],
            ],
        ],
        [
            'text' => 'your_company_trans_key',
            'icon'    => 'fas fa-industry',
            'can'  => ['your-company-menu'],
            'submenu' => [
                [
                    'text' => 'factory_settings_trans_key',
                    'url'  => 'admin/factory',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'roles_and_permissions_trans_key',
                    'url'  => 'admin/roles-permissions',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'import_export_trans_key',
                    'url'  => 'admin/imports-exports',
                    'icon_color' => 'primary',
                ],
                [
                    'text' => 'estimated_budget_trans_key',
                    'url'  => 'admin/estimated-budgets-settings',
                    'icon_color' => 'danger',
                ],
                [
                    'text' => 'workflow_settings_trans_key',
                    'url'  => 'admin/kanban-settings',
                    'icon_color' => 'orange',
                ],
                [
                    'text' => 'template_mail_trans_key',
                    'url'  => 'admin/emails/templates',
                    'icon_color' => 'secondary',
                ],
                [
                    'text' => 'energy_consumption_trans_key',
                    'url'  => 'energy-consumptions',
                    'icon_color' => 'success',
                ],
                [
                    'text' => 'logs_view_trans_key',
                    'url'  => 'admin/logs-view',
                    'icon_color' => 'success',
                ],
                [
                    'text'       => 'Tokens API',
                    'url'        => 'admin/api-tokens',
                    'icon'       => 'fas fa-key',
                    'icon_color' => 'primary',
                ],
            ],
        ],
        ['header' => env('APP_COMMERCIAL', false) ? 'NEST2PROD' : 'W.E.M.'],
        [
            'text' => 'licence_trans_key',
            'url'  => 'licence',
            'icon' => 'nav-icon fas fa-file-contract',
            'icon_color' => 'info',
        ],
        [
            'text' => 'RGPD Policy',
            'url'  => 'rgpd-policy',
            'icon' => 'nav-icon fas fa-cloud',
            'icon_color' => 'info',
        ],
        [
            'text' => 'release_note_trans_key',
            'url'  => 'https://github.com/SMEWebify/WebErpMesv2/releases',
            'icon' => 'nav-icon fas fa-file-contract',
            'icon_color' => 'info',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'ThemeNest2Prod' => [
            'active' => env('WEBERP_COLOR_THEME', 'nest2prod') === 'nest2prod',
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'css/theme-nest2prod.css',
                ],
            ],
        ],
        'Datatables' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'vendor/datatables/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'DatatablesPlugins' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables-plugins/buttons/js/dataTables.buttons.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables-plugins/buttons/js/buttons.bootstrap4.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables-plugins/buttons/js/buttons.html5.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables-plugins/buttons/js/buttons.print.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables-plugins/jszip/jszip.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables-plugins/pdfmake/pdfmake.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/datatables-plugins/pdfmake/vfs_fonts.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'vendor/datatables-plugins/buttons/css/buttons.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
        'active' => true,
        'files' => [
            [
                'type' => 'js',
                'asset' => true,
                'location' => 'vendor/select2/js/select2.full.min.js',
            ],
            [
                'type' => 'css',
                'asset' => true,
                'location' => 'vendor/select2/css/select2.min.css',
            ],
            [
                'type' => 'css',
                'asset' => true,
                'location' => 'vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css',
            ],
        ],
    ],
        'Chartjs' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@8',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
        'BootstrapSwitch' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/bootstrap-switch/js/bootstrap-switch.min.js',
                ],
            ],
        ],
        'BsCustomFileInput' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js',
                ],
            ],
        ],
        'FlagIconCss' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => '//cdn.jsdelivr.net/gh/lipis/flag-icons@6.6.6/css/flag-icons.min.css',
                    ],
            ],
        ],
        'Dropzone' => [
            'active' => false,
            'files' => [
                [
                'type' => 'css',
                'asset' => false,
                'location' => '//unpkg.com/dropzone@5/dist/min/dropzone.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//unpkg.com/dropzone@5/dist/min/dropzone.min.js',
                ],
            ],
        ],
        'Summernote' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/summernote/summernote-bs4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'vendor/summernote/summernote-bs4.min.css',
                ],
            ],
        ],
        'KrajeeFileinput' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'vendor/krajee-fileinput/css/fileinput.min.css',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => 'vendor/krajee-fileinput/themes/explorer-fa5/theme.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/krajee-fileinput/js/fileinput.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/krajee-fileinput/themes/fa5/theme.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/krajee-fileinput/themes/explorer-fa5/theme.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => 'vendor/krajee-fileinput/js/locales/es.js',
                ],
            ],
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    */

    'livewire' => false,
];
