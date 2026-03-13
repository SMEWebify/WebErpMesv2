@extends('adminlte::page')

@section('title', __('general_content.licence_trans_key'))

@section('content_header')
<h1>{{ __('general_content.licence_trans_key') }}</h1>
@stop

@section('content')
<div class="float-right"><img src="/vendor/adminlte/dist/img/simple-logo -R.PNG" alt="WEM" class="brand-image  elevation-3"></div>
<div class="content px-20">
    <p>
        <a href="https://opensource.org/licenses/MIT">MIT license</a><br/>
        <br/>
        Copyright (c) 2022 <a href="https://github.com/SMEWebify">SMEWebify</a><br/>
        <br/>
        Permission is hereby granted, free of charge, to any person obtaining a copy
        of this software and associated documentation files (the "Software"), to deal
        in the Software without restriction, including without limitation the rights
        to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
        copies of the Software, and to permit persons to whom the Software is
        furnished to do so, subject to the following conditions:<br/>
        <br/>
        The above copyright notice and this permission notice shall be included in all
        copies or substantial portions of the Software.<br/>
        <br/>
        THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
        IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
        FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
        AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
        LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
        OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
        SOFTWARE.
    </p>

    <hr/>

    <h3>THIRD PARTY LICENSES</h3>
    <p><strong>WEM (Web ERP MES)</strong></p>
    <p>
        This product includes software developed by third parties.<br/>
        The following libraries are used under their respective licenses.
    </p>

    <pre style="white-space: pre-wrap;">
------------------------------------------------------------
MIT License
------------------------------------------------------------
The following components are licensed under the MIT License:

almasaeed2010/adminlte
barryvdh/laravel-dompdf
brick/math
carbonphp/carbon-doctrine-types
composer/ca-bundle
composer/class-map-generator
composer/composer
composer/metadata-minifier
composer/pcre
composer/semver
composer/spdx-licenses
composer/xdebug-handler
dflydev/dot-access-data
directorytree/ldaprecord
directorytree/ldaprecord-laravel
doctrine/dbal
doctrine/deprecations
doctrine/event-manager
doctrine/inflector
doctrine/instantiator
doctrine/lexer
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
laravel/sail
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
milon/barcode
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
psr/cache
psr/clock
psr/container
psr/event-dispatcher
psr/http-client
psr/http-factory
psr/http-message
psr/log
psr/simple-cache
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
spatie/error-solutions
spatie/flare-client-php
spatie/ignition
spatie/laravel-activitylog
spatie/laravel-ignition
spatie/laravel-package-tools
spatie/laravel-permission
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
nikic/php-parser
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
ISC License
------------------------------------------------------------

paragonie/sodium_compat

------------------------------------------------------------
LGPL Licenses
------------------------------------------------------------

dompdf/dompdf (LGPL-2.1)
dompdf/php-font-lib (LGPL-2.1-or-later)
dompdf/php-svg-lib (LGPL-3.0-or-later)
ezyang/htmlpurifier (LGPL-2.1-or-later)
smalot/pdfparser (LGPL-3.0)

------------------------------------------------------------

Full license texts for the above libraries can be found in the
vendor directories of this distribution or in the respective
upstream repositories.
    </pre>
</div>
@stop

@section('css')
@stop

@section('js')
@stop
