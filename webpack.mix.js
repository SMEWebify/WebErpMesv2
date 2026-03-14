const path = require('path');
const mix = require('laravel-mix');

mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/spreadsheet.js', 'public/js')
    .vue()
    .sass('resources/sass/app.scss', 'public/css');

mix.webpackConfig({
    resolve: {
        alias: {
            '@univerjs/core':           path.resolve(__dirname, 'node_modules/@univerjs/core'),
            '@univerjs/engine-render':  path.resolve(__dirname, 'node_modules/@univerjs/engine-render'),
            '@univerjs/engine-formula': path.resolve(__dirname, 'node_modules/@univerjs/engine-formula'),
            '@univerjs/docs':           path.resolve(__dirname, 'node_modules/@univerjs/docs'),
            '@univerjs/docs-ui':        path.resolve(__dirname, 'node_modules/@univerjs/docs-ui'),
            '@univerjs/ui':             path.resolve(__dirname, 'node_modules/@univerjs/ui'),
            '@univerjs/drawing':        path.resolve(__dirname, 'node_modules/@univerjs/drawing'),
            '@wendellhu/redi':          path.resolve(__dirname, 'node_modules/@wendellhu/redi'),
            '@wendellhu/redi/react-bindings': path.resolve(__dirname, 'node_modules/@wendellhu/redi/dist/esm/react-bindings/index.js'),
            'rxjs':                     path.resolve(__dirname, 'node_modules/rxjs'),
            'react':                    path.resolve(__dirname, 'node_modules/react'),
            'react-dom':                path.resolve(__dirname, 'node_modules/react-dom'),
        },
    },
});