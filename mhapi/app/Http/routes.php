<?php
use Illuminate\Support\Facades\Route;

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
Route::resource('groups', 'DashboardController@groups');
Route::resource('controls', 'DashboardController@controls');
Route::resource('transactional-emails', 'DashboardController@transactional_emails');
Route::resource('group-emails', 'DashboardController@group_emails');
Route::resource('customers', 'DashboardController@customers');
Route::resource('viewLog', 'LogsController@viewLog');
Route::resource('trace-logs', 'LogsController@index');
Route::resource('application-logs', 'LogsController@applicationLog');




Route::resource('v1/unsubscribe', 'ListSubscribersController@unsubscribe');
//Route::resource('v1/subscriber', 'ListSubscribersController@search');
Route::post('v1/subscriber/update', 'ListSubscribersController@update');
Route::get('v1/groups/{group_id}/approve', 'ManageGroupController@approve');
Route::get('v1/groups/{group_id}/pause', 'ManageGroupController@pause');
Route::get('v1/groups/{group_id}/resume', 'ManageGroupController@resume');
Route::get('v1/groups/{group_id}/setassent', 'ManageGroupController@setAsSent');


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
Route::post('v1/blacklist/subscriber/{email}', 'BlacklistController@store');
//Route::put('v1/list/{list_uid}', 'ListController@save');

Route::group(['prefix' => 'v1'], function()
{
    Route::resource('abuse', 'GroupEmailAbuseController');
//    Route::resource('group-abuse', 'GroupEmailAbuseController');
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
    Route::resource('spam', 'GroupEmailAbuseController');
    Route::resource('unsubscribe', 'UnsubscribeController');
    Route::resource('blacklist', 'BlacklistController');
    Route::resource('create-group-email-group', 'GroupEmailsGroupController');
    Route::resource('create-group-email', 'GroupEmailsController');

});

/*
 *  Login/Logout Routes
 *
 */

Route::get('login',['as' => 'login_path','uses' => 'SessionsController@create']);

Route::post('login',['as' => 'login_path','uses' => 'SessionsController@store']);

Route::get('logout',['as' => 'logout_path','uses' => 'SessionsController@destroy']);

Route::get('not_authorized','SessionsController@not_authorized');
