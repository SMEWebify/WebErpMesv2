<?php

/*
|--------------------------------------------------------------------------
| Laravel AdminLTE Configuration
|--------------------------------------------------------------------------
|
| The options below are grouped by topic, and every group is introduced by
| the comment block right above it. The order of the groups is: the basics
| (title, logos, urls), the assets, the layout and the styling, the
| authentication views, the menu, the plugins and finally the integrations.
|
| Note the options added for AdminLTE v4 live inside a nested array of their
| own (like 'color_mode' or 'lockscreen'), while the older ones keep their
| flat name (like 'sidebar_nav_pills'). Renaming the flat ones would break
| every configuration file already published into an application, so they
| are kept as they are.
|
| The full documentation is available here:
| https://jeroennoten.github.io/Laravel-AdminLTE/
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel. The prefix and
    | the postfix are placed around the title of every page, and a view can
    | override any of the three through the section of the same name.
    |
    | For detailed instructions you can look the title section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
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
    | Here you can activate the favicon. Enable 'use_ico_only' to link the
    | 'public/favicons/favicon.ico' file alone, or 'use_full_favicon' to also
    | link the whole set of touch icons, png sizes and the web app manifest
    | that the same folder is expected to provide.
    |
    | For detailed instructions you can look the favicon section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'use_ico_only' => false,
    'use_full_favicon' => true,

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel. The 'logo' option holds
    | the brand text (html is allowed) and 'logo_img' the image placed next to
    | it, relative to the public folder.
    |
    | The optional 'logo_img_xl' option enables the AdminLTE logo switch: the
    | 'logo_img' one is then only shown while the sidebar is collapsed, and
    | this larger image replaces it while the sidebar is expanded.
    |
    | For detailed instructions you can look the logo section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'logo' => '',
    'logo_img' => env('APP_COMMERCIAL', false) ? 'img/nest2prod_logo_seul.png' : 'img/simple-logo -R.PNG',
    'logo_img_class' => 'brand-image',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs opacity-75',
    'logo_img_alt' => env('APP_COMMERCIAL', false) ? 'Nest2Prod ERP' : 'WEM',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/assets/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    | Any other value falls back to the 'fullscreen' mode.
    |
    | The 'img' options configure the default preloader content, which a view
    | can replace altogether through its 'preloader' section. The 'effect' one
    | accepts the legacy 'animation__shake', 'animation__wobble',
    | 'animation__flipInX', 'animation__fadeIn', 'animation__fadeOut' and
    | 'animation__spin' tokens; any other value emits no animation. Note the
    | animation is always suppressed for the visitors who ask for reduced
    | motion.
    |
    | For detailed instructions you can look the preloader section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => env('APP_COMMERCIAL', false) ? 'img/nest2prod_logo_seul.png' : 'img/simple-logo -R.PNG',
            'alt' => env('APP_COMMERCIAL', false) ? 'Nest2Prod ERP' : 'WEM',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu, the dropdown of the
    | navbar that holds the data of the authenticated user. When it is
    | disabled, a plain logout link is rendered instead.
    |
    | The 'usermenu_header' option adds the colored header of the dropdown, and
    | 'usermenu_header_class' holds its classes (a legacy 'bg-{color}' value is
    | translated to the Bootstrap 5.3 'text-bg-{color}' helper).
    |
    | The 'usermenu_image', 'usermenu_desc' and 'usermenu_profile_url' options
    | read their value from the authenticated user model, so they require it to
    | provide, respectively, an 'adminlte_image()', an 'adminlte_desc()' and an
    | 'adminlte_profile_url()' method. A missing method is ignored.
    |
    | For detailed instructions you can look the user menu section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
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
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel. Every option
    | below holds a plain url by default, or the name of a route when the
    | 'use_route_url' option is enabled. A route name that can not be resolved
    | falls back to a plain url, so a missing route does not break the panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'use_route_url' => false,
    'dashboard_url' => 'home',
    'logout_url' => 'logout',

    // The HTTP method spoofed on the logout form. Set it to 'GET' when your
    // logout route is not a POST one, or leave it as null to post the form.

    'logout_method' => null,
    'login_url' => '/',
    'register_url' => env('REGISTRATION_ENABLED', false) ? 'register' : false,
    'password_reset_url' => 'forgot/password',
    'password_email_url' => 'password/email',
    'profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Assets (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | Here you can configure how the base assets of the admin panel are served.
    | AdminLTE v4 bundles Bootstrap 5.3 into its own stylesheet, but requires
    | some external resources at runtime (the Bootstrap JavaScript bundle, the
    | Bootstrap Icons font, the OverlayScrollbars plugin and the web font).
    |
    | The 'mode' option supports the next values:
    | 'local' => Serve the assets published into the public folder. When a file
    |            is not published (the third party assets require an extra step,
    |            see the artisan console commands section of the docs), the CDN
    |            location is used as a fallback when 'cdn_fallback' is enabled.
    | 'cdn'   => Always serve the assets from the configured CDN locations.
    |
    | Any other value falls back to the 'local' mode.
    |
    | Note the RTL variant of a stylesheet is picked automatically when the RTL
    | mode is active (see the 'rtl' section below).
    |
    | For detailed instructions you can look the assets section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/other.html
    |
    */

    'assets' => [

        'mode' => 'local',
        'cdn_fallback' => true,

        // The AdminLTE version used on the CDN locations below. When left as
        // null, the version installed by composer is detected and used, so
        // the assets served from the CDN always match the local ones.

        'adminlte_version' => null,

        // Load the optional extended color palette (adminlte-colors.css). It
        // provides the .bg-*, .text-bg-*, .card-* and .callout-* classes for
        // the extended set of AdminLTE colors (navy, olive, sky, ...).

        'extended_colors' => true,

        // Load the v3 color aliases (adminlte-colors-v3.css) instead of the
        // v4 palette. Useful when your markup still uses the old color names.

        'extended_colors_v3_aliases' => false,

        // Palette tuning. Both options are provided by the palette
        // stylesheets, so they require the extended colors to be enabled.
        //
        // The 'primary' option remaps the primary color of the whole template
        // to any other color of the enabled palette (for example 'teal' or
        // 'navy'). Set it to null to keep the default blue. A color the
        // enabled palette does not provide is ignored.
        //
        // The 'contrast' option applies the WCAG AA correction of the palette.
        // Only the v3 alias stylesheet ships that correction, since some of
        // its colors miss the 4.5:1 ratio. Use null to apply it automatically
        // on that palette, 'aa' to declare it in any case, or false to always
        // disable it.

        'palette' => [
            'primary' => null,
            'contrast' => null,
        ],

        // The third party resources required by the template. Set any of them
        // to false when you provide the resource on your own (for example,
        // through your Laravel asset bundling setup).

        'bootstrap_js' => true,
        'bootstrap_icons' => true,
        'overlayscrollbars' => true,

        // Paths (relative to the public folder) of the published assets.

        'local' => [
            'adminlte_css' => 'vendor/adminlte/dist/css/adminlte.min.css',
            'adminlte_rtl_css' => 'vendor/adminlte/dist/css/adminlte.rtl.min.css',
            'adminlte_js' => 'vendor/adminlte/dist/js/adminlte.min.js',
            'colors_css' => 'vendor/adminlte/dist/css/adminlte-colors.min.css',
            'colors_rtl_css' => 'vendor/adminlte/dist/css/adminlte-colors.rtl.min.css',
            'colors_v3_css' => 'vendor/adminlte/dist/css/adminlte-colors-v3.min.css',
            'colors_v3_rtl_css' => 'vendor/adminlte/dist/css/adminlte-colors-v3.rtl.min.css',
            'bootstrap_js' => 'vendor/bootstrap/js/bootstrap.bundle.min.js',
            'bootstrap_icons_css' => 'vendor/bootstrap-icons/font/bootstrap-icons.min.css',
            'overlayscrollbars_css' => 'vendor/overlayscrollbars/styles/overlayscrollbars.min.css',
            'overlayscrollbars_js' => 'vendor/overlayscrollbars/browser/overlayscrollbars.browser.es6.min.js',
            'fonts_css' => 'vendor/fonts/source-sans-3/index.css',
        ],

        // Locations used on the 'cdn' mode and as fallback of missing assets.
        // The AdminLTE files follow the release composer installed, the third
        // party ones carry the version the 'package.json' of this package
        // resolves to, so both delivery modes serve the same files. Bump them
        // together with that file.

        'cdn' => [
            'adminlte_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte.min.css',
            'adminlte_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte.rtl.min.css',
            'adminlte_js' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/js/adminlte.min.js',
            'colors_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors.min.css',
            'colors_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors.rtl.min.css',
            'colors_v3_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors-v3.min.css',
            'colors_v3_rtl_css' => 'https://cdn.jsdelivr.net/npm/admin-lte@{version}/dist/css/adminlte-colors-v3.rtl.min.css',
            'bootstrap_js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js',
            'bootstrap_icons_css' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
            'overlayscrollbars_css' => 'https://cdn.jsdelivr.net/npm/overlayscrollbars@2.16.0/styles/overlayscrollbars.min.css',
            'overlayscrollbars_js' => 'https://cdn.jsdelivr.net/npm/overlayscrollbars@2.16.0/browser/overlayscrollbars.browser.es6.min.js',
            'fonts_css' => 'https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.3.0/index.css',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of the external web font used by the
    | AdminLTE v4 template (Source Sans 3, served from a CDN). Disabling the
    | web fonts may be useful if your admin panel internet access is
    | restricted somehow, or when you do not want the browsers of your
    | visitors to reach a third party host. The font source can be changed on
    | the 'assets' section above (the 'fonts_css' locations).
    |
    | For detailed instructions you can look the google fonts section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/basic_configuration.html
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | Note the AdminLTE stylesheet and script are then expected to be part of
    | your own bundle, so they are not linked by the layout anymore. Any other
    | value behaves like 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/other.html
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'resources/css/app.css',
    'laravel_js_path' => 'resources/js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Color Mode (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | AdminLTE v4 replaces the old 'dark mode' class by the Bootstrap 5.3
    | native 'data-bs-theme' attribute, which is set on the <html> element.
    |
    | The 'default' option supports the next values:
    | 'light' => Always start on light mode.
    | 'dark'  => Always start on dark mode.
    | 'auto'  => Follow the operating system preference of the visitor.
    |
    | Any other value falls back to the 'auto' mode.
    |
    | When 'remember' is enabled, the AdminLTE color mode widget stores the
    | visitor choice on the browser local storage (the AdminLTE v4 default
    | behavior) and the navbar widget offers the three modes above. Disable it
    | to always start with the configured default, in which case the navbar
    | widget becomes a two states toggle that persists the choice on the
    | server through the routes below.
    |
    | The 'no_flash_script' option adds a tiny inline script on the head of the
    | document to apply the resolved color mode before the first paint, and
    | thus avoid a flash of the incorrect theme.
    |
    | For detailed instructions you can look the color mode section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'color_mode' => [
        'enabled' => true,
        'default' => 'light',
        'remember' => false,
        'no_flash_script' => true,

        // Register the color mode routes of the package (the server side dark
        // mode toggle). Set it to false when your application provides its own
        // endpoint, or when the color mode is fully client side.

        'routes' => true,

        // The color used for the 'theme-color' meta tags. These are used by
        // some browsers to colorize parts of their user interface.

        'theme_color' => [
            'light' => '#0d6efd',
            'dark' => '#1a1a1a',
        ],
    ],

    // The legacy alias of the 'color_mode.routes' option above. Set it to true
    // to unregister the color mode routes of the package.

    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | RTL Mode (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | Here you can enable the right-to-left (RTL) support of AdminLTE v4. When
    | the RTL mode is active, the 'dir="rtl"' attribute is added to the <html>
    | element and the RTL variant of the stylesheets is loaded.
    |
    | The 'enabled' option supports the next values:
    | true  => Always use the RTL mode.
    | false => Never use the RTL mode.
    | null  => Enable the RTL mode only when the current application locale is
    |          included on the 'locales' array (auto detection).
    |
    | For detailed instructions you can look the RTL section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'rtl' => [
        'enabled' => null,
        'locales' => [
            'ar', 'arc', 'ckb', 'dv', 'fa', 'ha', 'he', 'khw', 'ks', 'ps',
            'sd', 'ug', 'ur', 'uz-AF', 'yi',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Print Mode (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | AdminLTE tunes the printed output of the panel through the
    | 'data-lte-print' attribute of the <html> element. Here you can list the
    | tokens to declare on it, either as an array or as a space separated
    | string.
    |
    | The bundled stylesheet provides the next token:
    | 'plain' => Do not print the url after every external link, and drop the
    |            border printed around the buttons.
    |
    | The 'app' token is accepted as well, but only the AdminLTE releases whose
    | stylesheet provides it react to it (the bundled one always prints the
    | header, the sidebar and the footer).
    |
    | Leave the array empty (the default) and no attribute is emitted at all,
    | so the AdminLTE print styles apply as they ship. Any other token is
    | dropped.
    |
    | For detailed instructions you can look the layout section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'print' => [],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | The 'layout_topnav' option removes the sidebar and moves the whole
    | navigation into the top navbar. The 'layout_fixed_sidebar' option keeps
    | the sidebar in place while the content scrolls, and it is ignored on the
    | topnav layout. The 'layout_fixed_navbar' and 'layout_fixed_footer' ones
    | do the same for the navbar and the footer; note AdminLTE v4 has no
    | responsive fixed modes anymore, so an array value is read as enabled
    | whenever any of its entries is true.
    |
    | The 'layout_compact' option reduces the spacing of the layout elements.
    |
    | For detailed instructions you can look the layout section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'layout_topnav' => null,
    'layout_fixed_sidebar' => true,
    'layout_fixed_navbar' => true,
    'layout_fixed_footer' => true,
    'layout_compact' => false,

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel. Every
    | option holds the extra classes added to one element of the layout, and
    | they are all appended to the classes the template requires.
    |
    | Note the 'classes_topnav_nav' option is the place of the Bootstrap
    | 'navbar-expand*' class of the navbar. Removing it collapses the navbar
    | items into a single column on every viewport.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'classes_body' => 'bg-body-tertiary',
    'classes_brand' => '',
    'classes_brand_text' => 'fw-light',
    'classes_wrapper' => '',
    'classes_content_wrapper' => '',
    'classes_footer' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_content_top_area' => '',
    'classes_content_bottom_area' => '',
    'classes_sidebar' => 'bg-body shadow',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'bg-body',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container-fluid',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    // The 'data-bs-theme' value applied to the main sidebar. Use 'dark' for
    // the classic AdminLTE dark sidebar, 'light' for a light one, or null to
    // inherit the color mode of the page. Any other value is read as 'dark'.

    'sidebar_theme' => null,

    // Behavior of the sidebar. The mini mode keeps the icons of a collapsed
    // sidebar visible, 'sidebar_collapse' starts the panel with the sidebar
    // already collapsed, and 'sidebar_without_hover' stops a collapsed
    // sidebar from expanding again on mouse hover.

    'sidebar_mini' => true,
    'sidebar_collapse' => false,
    'sidebar_without_hover' => false,

    // Persist the collapsed state of the sidebar on the browser, so it
    // survives a page load (the AdminLTE push menu plugin takes care of it).

    'sidebar_collapse_remember' => true,

    // The breakpoint where the sidebar turns into an overlay. AdminLTE only
    // provides the 'sm', 'md', 'lg', 'xl' and 'xxl' stylesheets, so any other
    // value falls back to 'lg'. The 'sidebar_breakpoint' option is the same
    // setting expressed as a viewport width in pixels: only the widths of
    // those breakpoints are honored (576, 768, 992, 1200 and 1400, or the
    // '.98' upper bound of their media query), any other width is ignored.
    // Leave it as null to just use the option above.

    'sidebar_expand' => 'lg',
    'sidebar_breakpoint' => null,

    // The OverlayScrollbars instance of the sidebar. The 'theme' is a class
    // name of that plugin, 'auto_hide' accepts its 'never', 'scroll', 'leave'
    // and 'move' tokens, and 'click_scroll' jumps to the clicked position of
    // the track. Any extra option of the plugin can be declared on the
    // 'sidebar_scrollbar_options' array, which is merged into (and thus wins
    // over) the three above. At or below the 'disable_below' viewport width
    // no instance is created at all, so touch scrolling is left alone.

    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'leave',
    'sidebar_scrollbar_click_scroll' => true,
    'sidebar_scrollbar_options' => [],
    'sidebar_scrollbar_disable_below' => 992,

    // The navigation menu of the sidebar. The 'aria_label' option names it for
    // the assistive technologies (null uses the translated default), the
    // 'compact', 'indent' and 'pills' options are the AdminLTE menu variants,
    // 'accordion' keeps at most one submenu open at a time and
    // 'animation_speed' is the duration, in milliseconds, of the submenu
    // animation (a value that is not a number falls back to 300).

    'sidebar_nav_aria_label' => null,
    'sidebar_nav_compact' => false,
    'sidebar_nav_indent' => true,
    'sidebar_nav_pills' => false,
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Right Sidebar (Offcanvas)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar of the admin panel. Note the old
    | AdminLTE v3 control sidebar was removed on AdminLTE v4, so the right
    | sidebar is now built on top of the Bootstrap 5 offcanvas component.
    |
    | Enable 'right_sidebar' to add its toggler to the navbar and to render the
    | panel, whose content comes from the 'right_sidebar' section of your view.
    | The 'placement' option accepts the Bootstrap 'start', 'end', 'top' and
    | 'bottom' values (any other one falls back to 'end'), the 'theme' one
    | accepts 'light' and 'dark' (null inherits the color mode of the page),
    | 'backdrop' dims the rest of the page while the panel is open and
    | 'scroll' keeps the page scrollable meanwhile. A null 'title' falls back
    | to the title of the panel, kept for the assistive technologies only.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'bi bi-gear',
    'right_sidebar_theme' => null,
    'right_sidebar_title' => null,
    'right_sidebar_placement' => 'end',
    'right_sidebar_backdrop' => true,
    'right_sidebar_scroll' => false,
    'right_sidebar_classes' => '',

    /*
    |--------------------------------------------------------------------------
    | CSS Variables (AdminLTE v4)
    |--------------------------------------------------------------------------
    |
    | The AdminLTE v4 theming is driven by the Bootstrap 5.3 and the AdminLTE
    | custom properties, so overriding them is enough for most brandings and
    | needs no stylesheet of your own. The declarations are emitted on an
    | inline <style> block of the document head.
    |
    | Only well formed custom property names ('--name') and values that can
    | not break out of the declaration are accepted, any other entry is
    | silently dropped.
    |
    | For detailed instructions you can look the css variables section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    // The custom properties applied on the whole document. For example:
    // 'css_variables' => ['--bs-primary' => '#6f42c1'],

    'css_variables' => [],

    // The selector of the block above. Only ':root' and 'body' are accepted,
    // any other value falls back to ':root'.

    'css_variables_scope' => ':root',

    // The custom properties applied on the sidebar element only. AdminLTE
    // redeclares the sidebar properties under a color mode selector, so these
    // are emitted with a matching specificity. For example:
    // 'css_variables_sidebar' => ['--lte-sidebar-bg' => '#1f2d3d'],

    'css_variables_sidebar' => [],

    /*
    |--------------------------------------------------------------------------
    | Authentication Views
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    | The 'classes_auth_*' options hold the extra classes of the card of those
    | views and of its parts, and the social links below add an extra block of
    | buttons at the bottom of the login and register cards.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/layout_and_styling.html
    |
    */

    'classes_auth_card' => 'card-outline card-info',
    'classes_auth_header' => 'text-bg-info bg-gradient',
    'classes_auth_body' => '',
    'classes_auth_footer' => 'text-center',
    'classes_auth_icon' => 'fa-lg text-info',
    'classes_auth_btn' => 'btn-primary',

    // The social login buttons of the authentication views. Every entry
    // accepts an 'url', a 'text', an 'icon' and a 'theme'. Leave the array
    // empty (the default) and no block is rendered at all. The separator is
    // the text placed above the block: null uses the translated default and
    // an empty string drops it.
    //
    // 'auth_social_links' => [
    //     [
    //         'url' => '/auth/facebook',
    //         'text' => 'Sign in using Facebook',
    //         'icon' => 'bi bi-facebook',
    //         'theme' => 'primary',
    //     ],
    // ],

    'auth_social_links' => [],
    'auth_social_links_separator' => null,

    /*
    |--------------------------------------------------------------------------
    | Lockscreen
    |--------------------------------------------------------------------------
    |
    | The AdminLTE lockscreen keeps the visitor authenticated but locks the
    | panel behind their password. Enable it to register the package routes,
    | and add the 'RedirectIfLocked' middleware to protect your own routes.
    |
    | The 'guard' option selects the authentication guard whose user provider
    | verifies the password (null uses the default guard). The 'throttle'
    | options limit the unlock attempts per user and ip, set 'max_attempts' to
    | zero to disable the limit. The 'except' option lists extra paths that
    | stay reachable while the panel is locked.
    |
    | For detailed instructions you can look the lockscreen section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/overview/authentication_views.html
    |
    */

    'lockscreen' => [
        'enabled' => false,
        'routes' => true,
        'guard' => null,

        'throttle' => [
            'max_attempts' => 5,
            'decay_seconds' => 60,
        ],

        'except' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/menu.html
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
            'classes'         => 'wem-hide-mobile',
        ],
        [
            'text'            => 'Iframe mode',
            'url'             => 'iframe-mode',
            'topnav_right'    => true,
            'classes'         => 'wem-hide-mobile',
        ],
        [
            'text'            => 'users_trans_key',
            'url'             => 'users',
            'topnav_right'    => true,
            'classes'         => 'wem-hide-mobile',
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
            'classes' => 'wem-hide-mobile',
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
                    'text' => 'order_confirmations_trans_key',
                    'url'  => 'order-confirmations',
                    'icon_color' => 'info',
                    'can'  => ['order-confirmations-menu'],
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
                        'icon_color' => 'violet',
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
                [
                    'text' => 'invoice_reminders_trans_key',
                    'url'  => 'invoices/reminders',
                    'icon_color' => 'danger',
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
                [
                    'text' => 'stock_shortages_trans_key',
                    'url'  => 'products/Stock/shortages',
                    'icon_color' => 'danger',
                    'can'  => ['stock-lot-serial-management'],
                ],
                [
                    'text' => 'inventories_trans_key',
                    'url'  => 'products/inventories',
                    'icon_color' => 'success',
                    'can'  => ['stock-lot-serial-management'],
                ],
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
                [
                    'text' => 'purchase_quality_clauses_trans_key',
                    'url'  => 'purchases/quality-clauses',
                    'can'  => ['purchase-quality-clauses-manage'],
                    'icon_color' => 'teal',
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
                    // Plans qualité (bullage) ; l'ancien écran reste lié depuis la page.
                    'text' => 'inspection_trans_key',
                    'url'  => 'quality/plans',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'process_diagrams_trans_key',
                    'url'  => 'quality/process-diagrams',
                    'icon_color' => 'violet',
                ],
                [
                    'text' => 'amdec_trans_key',
                    'url'  => 'quality/amdec',
                    'icon_color' => 'teal',
                ],
                [
                    'text' => 'internal_audits_trans_key',
                    'url'  => 'audit',
                    // Routes hors du groupe de langue : /fr/audit n'existe pas.
                    'localized' => false,
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
            'submenu' => [
                [
                    'text' => 'times_overview_trans_key',
                    'url'  => 'times',
                    'icon_color' => 'info',
                ],
                [
                    'text' => 'times_shifts_trans_key',
                    'url'  => 'times/shift',
                    'icon_color' => 'success',
                ],
            ],
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
                    'icon_color' => 'violet',
                ],
                [
                    'text' => 'operation_transition_delays_trans_key',
                    'url'  => 'methods/operation-transition-delay',
                    'icon_color' => 'teal',
                ],
                [
                    'text' => 'affair_categories_trans_key',
                    'url'  => 'methods/affair-category',
                    'icon_color' => 'indigo',
                ],
                [
                    'text' => 'loss_reasons_trans_key',
                    'url'  => 'methods/loss-reason',
                    'icon_color' => 'red',
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
                    'icon_color' => 'violet',
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
                    'text' => 'dashboard_trans_key',
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
                    'text' => 'gmao_maintenance_plans_trans_key',
                    'url'  => 'gmao/maintenance-plans',
                    'icon_color' => 'success',
                ],
                [
                    'text' => 'gmao_work_orders_trans_key',
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
                [
                    'text' => 'leave_balances_trans_key',
                    'url'  => 'human-resources/leave/balances',
                    'icon_color' => 'success',
                ],
                [
                    'text' => 'payroll_export_trans_key',
                    'url'  => 'human-resources/payroll/export',
                    'icon_color' => 'danger',
                ],
            ],
        ],
        [
            'text' => 'documents_trans_key',
            'icon' => 'fas fa-folder-open',
            'icon_color' => 'info',
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
                    'text' => 'skills_matrix_trans_key',
                    'url'  => 'osh/trainings/skills-matrix',
                    'icon_color' => 'teal',
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
            'icon_color' => 'violet',
            'url'  => 'reports',
            'can'  => ['reports-menu'],
            'submenu' => [
                [
                    'text' => 'dashboard_trans_key',
                    'url'  => 'reports',
                    'icon_color' => 'violet',
                ],
                [
                    'text' => 'Accounting',
                    'url'  => 'reports/accounting',
                    'icon_color' => 'info',
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
                    'text' => 'time_unit_settings_trans_key',
                    'url'  => 'admin/time-unit-settings',
                    'icon_color' => 'teal',
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
                    'text'       => 'mail_config_menu_trans_key',
                    'url'        => 'admin/integrations/mail',
                    'icon'       => 'fas fa-envelope',
                    'icon_color' => 'primary',
                ],
                [
                    'text'       => 'email_logs_trans_key',
                    'url'        => 'admin/email-logs',
                    'icon'       => 'fas fa-history',
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
                    'text'       => 'access_log_trans_key',
                    'url'        => 'admin/access-log',
                    'icon'       => 'fas fa-user-shield',
                    'icon_color' => 'secondary',
                    'can'        => ['audit-access-log'],
                ],
                [
                    'text'       => 'Tokens API',
                    'url'        => 'admin/api-tokens',
                    'icon'       => 'fas fa-key',
                    'icon_color' => 'primary',
                ],
                [
                    'text'       => 'Intégrations',
                    'url'        => 'admin/integrations',
                    'icon'       => 'fas fa-exchange-alt',
                    'icon_color' => 'info',
                ],
            ],
        ],
        ['header' => env('APP_COMMERCIAL', false) ? 'NEST2PROD' : 'W.E.M.'],
        [
            'text' => 'guides_trans_key',
            'url'  => 'guides',
            'icon' => 'nav-icon fas fa-book-open',
            'icon_color' => 'info',
        ],
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
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/menu.html
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        App\Menu\Filters\LocalizedHrefFilter::class,
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
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/plugins.html
    |
    */

    'plugins' => [
        // AdminLTE v4 utilise Bootstrap Icons ; l'application garde Font Awesome
        // (menu, vues, composants React).
        'FontAwesome' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/fontawesome-free/css/all.min.css',
                ],
            ],
        ],
        // Ajustements du shell AdminLTE 4 propres à l'application.
        'WemShell' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'css/wem-shell.css',
                ],
            ],
        ],
        'ThemeNest2Prod' => [
            // Valeur par défaut ; ajustée par utilisateur par App\Support\UiTheme::apply().
            'active' => env('WEBERP_COLOR_THEME', 'nest2prod') === 'nest2prod',
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'css/theme-nest2prod.css',
                ],
            ],
        ],
        // Mode "pro" : chargé en permanence, tout est scopé sous body.theme-pro
        // et ne s'applique donc que si l'utilisateur a choisi ce mode.
        'ThemePro' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'css/theme-pro.css',
                ],
            ],
        ],
        // Ajustements globaux sous 768 px (marges, onglets, champs, modales).
        'Mobile' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'css/mobile.css',
                ],
            ],
        ],
        // Barres d'onglets en tête de carte : ce qui ne tient pas passe dans « Plus ▾ ».
        'TabsOverflow' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'css/tabs-overflow.css',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'js/tabs-overflow.js',
                ],
            ],
        ],
        // <x-adminlte-select-bs> (sélecteurs société, adresse, contact...) :
        // Tom Select, en JavaScript natif. Remplace Select2, dernière dépendance
        // à jQuery, qui n'est plus chargé.
        'TomSelect' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/tom-select/js/tom-select.complete.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/tom-select/css/tom-select.bootstrap5.min.css',
                ],
            ],
        ],
        // <x-adminlte-text-editor> (commentaires article / lead / opportunité,
        // e-mails) : Quill 2, remplaçant de Summernote en AdminLTE 4. Chargé
        // seulement par les vues qui posent @section('plugins.Quill', true).
        'Quill' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/quill/js/quill.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/quill/css/quill.snow.css',
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
    | The 'default_tab' options add a tab that is always open, the 'buttons'
    | ones pick the controls of the tab bar, and the 'options' ones tune the
    | behavior: 'loading_screen' is the duration in milliseconds of the
    | loading overlay of a tab (zero disables it), 'auto_show_new_tab' focuses
    | a tab as soon as it is created and 'use_navbar_items' also turns the
    | links of the top navbar into tabs.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/iframe_mode.html
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
    | Here we can enable the Livewire support. When enabled, the Livewire
    | styles and scripts are added to the layout.
    |
    | For detailed instructions you can look the livewire here:
    | https://jeroennoten.github.io/Laravel-AdminLTE/sections/configuration/other.html
    |
    */

    'livewire' => false,

    /*
    |--------------------------------------------------------------------------
    | Single Page Navigation
    |--------------------------------------------------------------------------
    |
    | AdminLTE re-initializes its plugins on the 'turbo:load' event of Turbo
    | Drive, but it knows nothing about Livewire. So, after a 'wire:navigate'
    | visit the sidebar, the treeview and the card tools would stay dead.
    |
    | With this option enabled, the package bridges the Livewire navigation
    | event to the AdminLTE lifecycle. Disable it when your application takes
    | care of that on its own.
    |
    */

    'spa_navigation' => true,
];
