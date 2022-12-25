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

    'logo' => 'v1.0.3',
    'logo_img' => 'vendor/adminlte/dist/img/WEMLogo.png',
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

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
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
            'icon' => 'flag-icon flag-icon-uk',
            'submenu' => [
                [
                    'text'=>'english_trans_key',
                    'icon' => 'flag-icon flag-icon-uk',
                    'url'=> 'en',
                ],
                [
                    'text'=>'french_trans_key',
                    'icon' => 'flag-icon flag-icon-fr',
                    'url'=> 'fr',
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
        ],
        [
            'text'    => 'quote_trans_key',
            'icon'    => 'fas fa-calculator',
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
            'submenu' => [
                [
                    'text' => 'orders_list_trans_key',
                    'url'  => 'orders',
                ],
                [
                    'text' => 'orders_lines_list_trans_key',
                    'url'  => 'orders/lines',
                ],
            ]
        ],
        [
            'text'    => 'scheduling_trans_key',
            'icon' => 'fas fa-calendar-alt',
            'submenu' => [
                [
                    'text' => 'order_calendar_trans_key',
                    'url'  => 'production/calendar',
                ],[
                    'text'    => 'tasks_trans_key',
                    'icon_color' => 'primary',
                    'submenu' => [
                        [
                            'text' => 'tasks_statu_trans_key',
                            'url'  => 'production/Task/Statu',
                            'icon_color' => 'primary',
                        ],
                        [
                            'text' => 'tasks_list_trans_key',
                            'url'  => 'production/Task',
                            'icon_color' => 'primary',
                        ],
                    ]
                    
                ],
                [
                    'text' => 'workflow_trans_key',
                    'url'  => 'production/kanban',
                ],
                [
                    'text' => 'gantt_trans_key',
                    'url'  => 'production/gantt',
                    'label'       => 'Beta',
                    'label_color' => 'danger',
                ],
                
            ]
        ],
        [
            'text'    => 'delivery_notes_trans_key',
            'icon'    => 'fas fa-receipt',
            'submenu' => [
                [
                    'text' => 'deliverys_notes_request_trans_key',
                    'url'  => 'deliverys/request',
                ],
                [
                    'text' => 'deliverys_notes_list_trans_key',
                    'url'  => 'deliverys',
                ],
            ]
        ],
        [
            'text'    => 'invoices_trans_key',
            'icon'    => 'fas fa-calculator',
            'submenu' => [
                [
                    'text' => 'invoices_request_trans_key',
                    'url'  => 'invoices/request',
                ],
                [
                    'text' => 'invoices_list_trans_key',
                    'url'  => 'invoices',
                ],
            ]
        ],
        ['header' => 'others_trans_key'],
        [
            'text'    => 'product_trans_key',
            'icon'    => 'fas fa-barcode',
            'submenu' => [
                [
                    'text' => 'product_list_trans_key',
                    'url'  => 'products',
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
            'submenu' => [
                [
                    'text' => 'purchase_request_trans_key',
                    'url'  => 'purchases/request',
                ],
                [
                    'text' => 'requests_for_quotation_list_trans_key',
                    'url'  => 'purchases/quotation',
                ],
                [
                    'text' => 'purchase_list_trans_key',
                    'url'  => 'purchases',
                ],
                [
                    'text' => 'waiting_to_receipt_trans_key',
                    'url'  => 'purchases/waiting/receipt',
                ],
                [
                    'text' => 'po_receipt_trans_key',
                    'url'  => 'purchases/receipt',
                ],
                [
                    'text' => 'waiting_to_invoice_trans_key',
                    'url'  => 'purchases/waiting/invoice',
                ],
                [
                    'text' => 'invoice_supplier_trans_key',
                    'url'  => 'purchases/invoice',
                ],
            ],
        ],
        [
            
            'text' => 'quality_trans_key',
            'icon'    => 'fas fa-ruler-combined',
            'url'  => 'quality',
        ],
        ['header' => 'settings_trans_key'],
        /* 
        [
            'text' => 'Setting times',
            'icon'    => 'fas fa-user-clock',
            'url'  => 'times',
        ],
        */
        [
            'text' => 'methods_trans_key',
            'icon'   => 'fas fa-cogs',
            'url'  => 'methods',
        ],
        [
            'text' => 'accouting_trans_key',
            'icon'    => 'fas fa-piggy-bank',
            'url'  => 'accouting',
        ],
        [
            'text'        => 'users_trans_key',
            'url'         => 'users',
            'icon'        => 'fas fa-users',
        ],
        [
            'text' => 'your_company_trans_key',
            'url'  => 'admin/factory',
            'icon'    => 'fas fa-industry',
        ],
        ['header' => 'W.E.M.'],
        [
            'text' => 'licence_trans_key',
            'url'  => 'licence',
            'icon'    => 'nav-icon fas fa-file-contract',
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
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
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
                    'asset' => false,
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
