<?php
//use Illuminate\Support\Facades\Route;

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
Route::resource('dashboard', 'DashboardController');
Route::resource('logs', 'LogsController');
Route::resource('logs/viewLog', 'LogsController@viewLog');
Route::resource('subscribers', 'DashboardController@subscribers');
Route::resource('campaigns', 'DashboardController@campaigns');
Route::resource('transactional-emails', 'DashboardController@transactional_emails');

Route::resource('v1/unsubscribe', 'ListSubscribersController@unsubscribe');
//Route::resource('v1/subscriber', 'ListSubscribersController@search');
Route::post('v1/subscriber/update', 'ListSubscribersController@update');


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
Route::get('v1/list/customer/{customer_uid}/page/{page}/per_page/{per_page}', 'ListController@index');
//Route::put('v1/list/{list_uid}', 'ListController@save');

Route::group(['prefix' => 'v1'], function()
{
    Route::resource('customer', 'CustomerController');
    Route::resource('customers', 'CustomersController');
    Route::resource('delivery-servers', 'DeliveryServerController@all');
    Route::resource('delivery-server', 'DeliveryServerController');
    Route::resource('bounce-servers', 'BounceServersController');
    Route::resource('bounce-server', 'BounceServerController');
    Route::resource('subscriber', 'ListSubscribersController');
    Route::resource('campaign', 'CampaignController');
    Route::resource('user', 'UsersController');
    Route::resource('list', 'ListController');
    Route::resource('segment', 'SegmentController');
    Route::resource('segmentcondition', 'SegmentConditionController');
    Route::resource('fields', 'FieldsController');
    Route::resource('emails', 'TransactionalEmailsController');
    Route::resource('bounce', 'BounceController');
    Route::resource('spam', 'SpamController');
    Route::resource('blacklist', 'BlacklistController');

});