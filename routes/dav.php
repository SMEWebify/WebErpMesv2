<?php

use App\Http\Controllers\Files\OfficeDavController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WebDAV pour Excel / Word (« Modifier dans Excel » de la GED)
|--------------------------------------------------------------------------
|
| Hors du groupe « web » : Office n'a ni la session ni le jeton CSRF du
| navigateur. L'accès repose sur le jeton temporaire de OfficeEdit, placé dans
| le chemin (Office perd parfois la chaîne de requête). Hors du groupe de
| langue mcamara : Office suit l'adresse telle quelle.
|
*/

Route::match(
    ['OPTIONS', 'GET', 'HEAD', 'PUT', 'PROPFIND', 'PROPPATCH', 'LOCK', 'UNLOCK', 'DELETE', 'MOVE', 'COPY', 'MKCOL'],
    '/dav/{token}/{name?}',
    [OfficeDavController::class, 'handle'],
)
    ->where('token', '[A-Za-z0-9]{48}')
    ->where('name', '.*')
    ->middleware('throttle:300,1')
    ->name('office.dav.file');
