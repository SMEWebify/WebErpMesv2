@extends('adminlte::page')

@section('title', __('general_content.licence_trans_key'))

@section('content_header')
<h1>{{ __('general_content.licence_trans_key') }}</h1>
@stop

@section('content')
<div class="float-end"><img src="/{{ config('branding.logo_img') }}" alt="{{ config('branding.logo_alt') }}" class="brand-image shadow"></div>
<div class="content">
    <h3>GNU Lesser General Public License v3.0 or later</h3>
    <p>
        <strong>{{ config('branding.app_name_full') }}</strong> (WEM - Web ERP MES)<br/>
        Copyright (C) 2021-2026 Kévin Niglaut and contributors<br/>
        <br/>
        This program is free software: you can redistribute it and/or modify it
        under the terms of the GNU Lesser General Public License as published by
        the Free Software Foundation, either version 3 of the License, or (at your
        option) any later version.<br/>
        <br/>
        This program is distributed in the hope that it will be useful, but WITHOUT
        ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
        FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
        for more details.<br/>
        <br/>
        The full texts are distributed with the software: the
        <a href="https://www.gnu.org/licenses/lgpl-3.0.html" target="_blank" rel="noopener">GNU Lesser General Public License</a>
        in the <code>LICENSE</code> file, and the
        <a href="https://www.gnu.org/licenses/gpl-3.0.html" target="_blank" rel="noopener">GNU General Public License</a>
        it supplements in the <code>COPYING</code> file.
    </p>

    <p>
        The Nest2Prod name and logo are not covered by this license; see the
        <code>TRADEMARKS.md</code> file.
    </p>

    <hr/>

    <h3>Licensing history</h3>
    <p>
        Releases up to and including v2.1.0 were published under the MIT License.
        Those releases remain available under the MIT License. Starting with
        v2.2.0, the project as a whole is distributed under the GNU LGPL v3 or
        later.<br/>
        <br/>
        Contributions made while the project was under the MIT License remain
        covered by the following notice, which is kept as the MIT License requires:
    </p>
    <pre style="white-space: pre-wrap;">
MIT License

Copyright (c) 2022 SMEWebify

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
    </pre>

    <hr/>

    <h3>THIRD PARTY LICENSES</h3>
    <p>
        This product includes software developed by third parties.<br/>
        The following libraries are used under their respective licenses.
    </p>

    <h4>PHP libraries (Composer)</h4>
    <pre style="white-space: pre-wrap;">
------------------------------------------------------------
MIT License
------------------------------------------------------------

almasaeed2010/adminlte
barryvdh/laravel-dompdf
brick/math
carbonphp/carbon-doctrine-types
composer/*
dflydev/dot-access-data
directorytree/ldaprecord
directorytree/ldaprecord-laravel
doctrine/dbal
doctrine/deprecations
doctrine/inflector
doctrine/instantiator
doctrine/lexer
doctrine/sql-formatter
dominikb/composer-license-checker
dragonbe/vies
dragonmantank/cron-expression
egulias/email-validator
fakerphp/faker
filp/whoops
fruitcake/php-cors
goetas-webservices/xsd2php-runtime
graham-campbell/result-type
guzzlehttp/guzzle
guzzlehttp/promises
guzzlehttp/psr7
guzzlehttp/uri-template
horstoeko/mimedb
horstoeko/stringmanagement
horstoeko/zugferd
intervention/gif
intervention/image
jaybizzle/crawler-detect
jenssegers/agent
jeroennoten/laravel-adminlte
jms/metadata
jms/serializer
justinrainbow/json-schema
laravel/breeze
laravel/framework
laravel/prompts
laravel/pulse
laravel/sail
laravel/sanctum
laravel/sentinel
laravel/serializable-closure
laravel/telescope
laravel/tinker
laravel/ui
laravolt/avatar
league/flysystem
league/flysystem-local
league/mime-type-detection
league/uri
league/uri-interfaces
livewire/livewire
maatwebsite/excel
maennchen/zipstream-php
markbaker/complex
markbaker/matrix
masterminds/html5
mcamara/laravel-localization
mobiledetect/mobiledetectlib
monolog/monolog
myclabs/deep-copy
nesbot/carbon
nunomaduro/collision
nunomaduro/termwind
phpoffice/phpspreadsheet
phpstan/phpdoc-parser
predis/predis
protonemedia/laravel-cross-eloquent-search
psr/*
psy/psysh
pusher/pusher-php-server
ralouphie/getallheaders
ramsey/collection
ramsey/uuid
react/promise
sabberworm/php-css-parser
seld/jsonlint
seld/phar-utils
seld/signal-handler
setasign/fpdf
setasign/fpdi
spatie/backtrace
spatie/db-dumper
spatie/error-solutions
spatie/flare-client-php
spatie/ignition
spatie/laravel-activitylog
spatie/laravel-backup
spatie/laravel-ignition
spatie/laravel-package-tools
spatie/laravel-permission
spatie/laravel-signal-aware-command
spatie/temporary-directory
staabm/side-effects-detector
symfony/*
thecodingmachine/safe
voku/portable-ascii
webklex/laravel-pdfmerger

------------------------------------------------------------
BSD-3-Clause License
------------------------------------------------------------

hamcrest/hamcrest-php
league/commonmark
league/config
marc-mabe/php-enum
mockery/mockery
nette/schema (BSD-3-Clause, GPL-2.0-only or GPL-3.0-only)
nette/utils (BSD-3-Clause, GPL-2.0-only or GPL-3.0-only)
nikic/php-parser
phar-io/manifest
phar-io/version
phpunit/*
sebastian/*
theseer/tokenizer
tijsverkoyen/css-to-inline-styles
vlucas/phpdotenv

------------------------------------------------------------
Apache License 2.0
------------------------------------------------------------

phpoption/phpoption

------------------------------------------------------------
LGPL Licenses
------------------------------------------------------------

dompdf/dompdf (LGPL-2.1)
dompdf/php-font-lib (LGPL-2.1-or-later)
dompdf/php-svg-lib (LGPL-3.0-or-later)
ezyang/htmlpurifier (LGPL-2.1-or-later)
milon/barcode (LGPL-3.0)
smalot/pdfparser (LGPL-3.0)
    </pre>

    <h4>JavaScript libraries (npm)</h4>
    <pre style="white-space: pre-wrap;">
------------------------------------------------------------
MIT License
------------------------------------------------------------

@dnd-kit/core
@dnd-kit/sortable
@dnd-kit/utilities
@popperjs/core
admin-lte
dxf-parser
flag-icon-css
frappe-gantt
jspdf
react
react-dom
react-grid-layout
reactflow
three

------------------------------------------------------------
Apache License 2.0
------------------------------------------------------------

@univerjs/*
rxjs

------------------------------------------------------------
LGPL Licenses
------------------------------------------------------------

occt-import-js (LGPL-2.1)

------------------------------------------------------------
Other
------------------------------------------------------------

@fortawesome/fontawesome-free (icons CC-BY-4.0, fonts OFL-1.1, code MIT)

------------------------------------------------------------

Full license texts for the above libraries can be found in the
vendor and node_modules directories of this distribution or in the
respective upstream repositories.
    </pre>
</div>
@stop

@section('css')
@stop

@section('js')
@stop
