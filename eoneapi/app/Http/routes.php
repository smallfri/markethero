<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::resource('post', 'CustomerController@index');
Route::resource('user', 'UsersController@store');


/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});

Route::group(['prefix' => 'api/v1'], function()
{
    Route::resource('customer', 'CustomerController');
    Route::resource('customers', 'CustomersController');
    Route::resource('delivery-servers', 'DeliveryServerController@all');
    Route::resource('delivery-server', 'DeliveryServerController');
    Route::resource('bounce-servers', 'BounceServersController');
    Route::resource('bounce-server', 'BounceServerController');
});