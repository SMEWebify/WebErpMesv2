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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/companiesnew', function () {
    return view('companies/companies-new');
})->middleware(['auth'])->name('companiesnew');

Route::get('/companies', 'App\Http\Controllers\CompaniesController@List')->middleware(['auth'])->name('companies');
Route::get('/companies/{id}', 'App\Http\Controllers\CompaniesController@show')->middleware(['auth'])->name('companies.show');


Route::get('/users', 'App\Http\Controllers\UsersController@List')->middleware(['auth'])->name('users');

require __DIR__.'/auth.php';

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

