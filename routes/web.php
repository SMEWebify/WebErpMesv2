<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/dashboard', 'App\Http\Controllers\HomeController@index')->middleware(['auth'])->name('dashboard');

Route::group(['prefix' => 'companies'], function () {
    //companie route
    Route::get('/', 'App\Http\Controllers\Companies\CompaniesController@index')->middleware(['auth'])->name('companies'); 
    Route::post('/create', 'App\Http\Controllers\Companies\CompaniesController@store')->middleware(['auth'])->name('companies.store');
    Route::get('/create', 'App\Http\Controllers\Companies\CompaniesController@create')->middleware(['auth'])->name('companies.create');
    //contact route
    Route::post('/contacts/create/{id}', 'App\Http\Controllers\Companies\ContactsController@store')->middleware(['auth'])->name('contacts.store');
    Route::get('/contacts/create/{id}', 'App\Http\Controllers\Companies\ContactsController@create')->middleware(['auth'])->name('contacts.create');
    Route::post('/contacts/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@update')->middleware(['auth'])->name('contacts.update');
    Route::get('/contacts/edit/{id}', 'App\Http\Controllers\Companies\ContactsController@edit')->middleware(['auth'])->name('contacts.edit');
    //adresses route
    Route::post('/addresses/create/{id}', 'App\Http\Controllers\Companies\AddressesController@store')->middleware(['auth'])->name('addresses.store');
    Route::get('/addresses/create/{id}', 'App\Http\Controllers\Companies\AddressesController@create')->middleware(['auth'])->name('addresses.create');
    Route::post('/addresses/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@update')->middleware(['auth'])->name('addresses.update');
    Route::get('/addresses/edit/{id}', 'App\Http\Controllers\Companies\AddressesController@edit')->middleware(['auth'])->name('addresses.edit');

    Route::get('/{id}', 'App\Http\Controllers\Companies\CompaniesController@show')->middleware(['auth'])->name('companies.show');
    
});

Route::group(['prefix' => 'accouting'], function () {
    Route::get('/', 'App\Http\Controllers\Accounting\AccountingController@index')->middleware(['auth'])->name('accounting');
});

Route::group(['prefix' => 'quality'], function () {
    Route::get('/', 'App\Http\Controllers\Quality\QualityController@index')->middleware(['auth'])->name('quality');
    Route::post('/quality/Failling/create', 'App\Http\Controllers\Quality\QualityFailureController@store')->middleware(['auth'])->name('quality.failling.create');
    Route::post('/quality/Cause/create', 'App\Http\Controllers\Quality\QualityCauseController@store')->middleware(['auth'])->name('quality.cause.create');
    Route::post('/quality/Correction/create', 'App\Http\Controllers\Quality\QualityCorrectionController@store')->middleware(['auth'])->name('quality.correction.create');
    Route::post('/quality/Device/create', 'App\Http\Controllers\Quality\QualityCorrectionController@store')->middleware(['auth'])->name('quality.device.create');
    Route::post('/quality/Action/create', 'App\Http\Controllers\Quality\QualityCorrectionController@store')->middleware(['auth'])->name('quality.action.create');
    
    
});

Route::group(['prefix' => 'methods'], function () {
    Route::get('/', 'App\Http\Controllers\Methods\MethodsController@index')->middleware(['auth'])->name('methods');
});




Route::get('/users', 'App\Http\Controllers\UsersController@List')->middleware(['auth'])->name('users');

require __DIR__.'/auth.php';

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

