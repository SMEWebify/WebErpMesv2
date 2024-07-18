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

    'title' => 'WEB ERP MES',
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

    'logo' => 'v1.10',
    'logo_img' => 'vendor/adminlte/dist/img/simple-logo.PNG',
    'logo_img_class' => 'brand-image  elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'WEM',

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
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
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
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
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
            'can'         => ['leads-menu'],
        ],
        [
            'text'        => 'opportunities_trans_key',
            'url'         => 'opportunities',
            'icon'        => 'fa fa-tags',
            'can'         => ['opportunities-menu'],
        ],
        [
            'text'    => 'quote_trans_key',
            'icon'    => 'fas fa-calculator',
            'can'  => ['quotes-menu'],
            'submenu' => [
                [
                    'text' => 'quotes_list_trans_key',
                    'url'  => 'quotes',
                ],
                [
                    'text' => 'quotes_lines_list_trans_key',
                    'url'  => 'quotes/lines',
                ],
            ]
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
                ],
                [
                    'text' => 'order_calendar_trans_key',
                    'url'  => 'production/calendar/orders',
                    'icon_color' => 'warning',
                ],
            ]
        ],
        [
            'text'    => 'scheduling_trans_key',
            'icon' => 'fas fa-calendar-alt',
            'can'  => ['scheduling-menu'],
            'submenu' => [
                [
                    'text' => 'load_planning_trans_key',
                    'url'  => 'production/load-planning',
                    'icon_color' => 'success',
                ],
                [
                    'text' => 'tasks_list_trans_key',
                    'url'  => 'production/Task',
                    'icon_color' => 'primary',
                ],
                [
                    'text' => 'tasks_calendar_trans_key',
                    'url'  => 'production/calendar/tasks',
                    'icon_color' => 'primary',
                ],
                [
                    'text' => 'tasks_statu_trans_key',
                    'url'  => 'production/Task/Statu',
                    'icon_color' => 'primary',
                ],
                [
                    'text' => 'workflow_trans_key',
                    'url'  => 'production/kanban',
                    'icon_color' => 'warning',
                ],
                [
                    'text' => 'gantt_trans_key',
                    'url'  => 'production/gantt',
                    'icon_color' => 'info',
                    'label'       => 'Beta',
                    'label_color' => 'danger',
                ],
                
            ]
        ],
        [
            'key'  => 'delivery_notes',
            'text'    => 'delivery_notes_trans_key',
            'icon'    => 'fas fa-receipt',
            'icon_color' => 'purple',
            'can'  => ['deliverys-menu'],
            'submenu' => [
                [
                    'text' => 'deliverys_notes_list_trans_key',
                    'url'  => 'deliverys',
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
                ],
                [
                    'text' => 'export_invoices_lines_list_trans_key',
                    'url'  => 'invoices/export',
                ],
                
                [
                    'text' => 'credit_notes_trans_key',
                    'url'  => 'credit-notes',
                ],
            ]
        ],
        ['header' => 'others_trans_key'],
        [
            'text'    => 'product_trans_key',
            'icon'    => 'fas fa-barcode',
            'can'  => ['products-menu'],
            'submenu' => [
                [
                    'text' => 'product_list_trans_key',
                    'url'  => 'products',
                ],
                [
                    'text' => 'serial_numbers_trans_key',
                    'url'  => 'products/serial-numbers',
                ],
                [
                    'text' => 'stock_trans_key',
                    'url'  => 'products/Stock',
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
            'icon_color' => 'primary',
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
                ],
                [
                    'text' => 'action_trans_key',
                    'url'  => 'quality/action',
                ],
                [
                    'text' => 'derogations_trans_key',
                    'url'  => 'quality/derogation',
                ],
                [
                    'text' => 'non_conformities_trans_key',
                    'url'  => 'quality/nonConformitie',
                ],
                [
                    'text' => 'amdec_trans_key',
                    'url'  => 'quality/amdec',
                ],
            ],
        ],
        ['header' => 'settings_trans_key'],
        [
            'text' => 'Setting times',
            'icon'    => 'fas fa-user-clock',
            'url'  => 'times',
            'can'  => ['settings-time-menu'],
        ],
        [
            'text' => 'methods_trans_key',
            'icon'   => 'fas fa-cogs',
            'url'  => 'methods',
            'can'  => ['methods-menu'],
        ],
        [
            'text' => 'accounting_trans_key',
            'icon' => 'fas fa-piggy-bank',
            'url'  => 'accounting',
            'can'  => ['accounting-menu'],
        ],
        [
            'text' => 'human_resources_trans_key',
            'icon' => 'fas fa-users',
            'url'  => 'human-resources',
            'can'  => ['human-resources-menu'],
        ],
        [
            'text' => 'your_company_trans_key',
            'icon'    => 'fas fa-industry',
            'can'  => ['your-company-menu'],
            'submenu' => [
                [
                    'text' => 'factory_settings_trans_key',
                    'url'  => 'admin/factory',
                ],
                [
                    'text' => 'roles_and_permissions_trans_key',
                    'url'  => 'admin/roles-permissions',
                ],
                [
                    'text' => 'import_export_trans_key',
                    'url'  => 'admin/imports-exports',
                ],
                [
                    'text' => 'logs_view_trans_key',
                    'url'  => 'admin/logs-view',
                ],
            ],
        ],
        ['header' => 'W.E.M.'],
        [
            'text' => 'licence_trans_key',
            'url'  => 'licence',
            'icon' => 'nav-icon fas fa-file-contract',
        ],
        [
            'text' => 'RGPD Policy',
            'url'  => 'rgpd-policy',
            'icon' => 'nav-icon fas fa-cloud',
        ],
        [
            'text' => 'release_note_trans_key',
            'url'  => 'https://github.com/SMEWebify/WebErpMesv2/releases',
            'icon' => 'nav-icon fas fa-file-contract',
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
        'dhtmlxGantt ' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => '//cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.css',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => '//cdn.dhtmlx.com/gantt/edge/dhtmlxgantt.js',
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

    'livewire' => true,
];
