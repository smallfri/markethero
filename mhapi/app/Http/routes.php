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
Route::get('getXMLReport', 'XMLController@get100GroupForXML');

Route::get('email-tester', 'DashboardController@test_emails');
Route::post('email-tester', ['as' => 'email_test_path', 'uses' => 'DashboardController@send_emails']);
Route::get('load-test', ['as' => 'load_test_path', 'uses' => 'DashboardController@loadTest']);
Route::post('v1/pause', ['as' => 'pause_path', 'uses' => 'PauseGroupController@store']);
Route::post('v1/un-pause', ['as' => 'pause_path', 'uses' => 'PauseGroupController@delete']);

Route::resource('dashboard', 'DashboardController@index');
Route::resource('logs', 'LogsController@index');
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
Route::resource('customer/{customer_id}/edit', 'CustomersController@edit');
Route::get('servers', 'DashboardController@servers');
Route::resource('servers/{server_id}/edit', 'DashboardController@editServer');


//Route::resource('v1/unsubscribe', 'ListSubscribersController@unsubscribe');
//Route::resource('v1/subscriber', 'ListSubscribersController@search');
Route::post('v1/subscriber/update', 'ListSubscribersController@update');
Route::get('v1/groups/{group_id}/approve', 'ManageGroupController@approve');
Route::get('v1/groups/{group_id}/pause', 'ManageGroupController@pause');
Route::get('v1/groups/{group_id}/resume', 'ManageGroupController@resume');
Route::get('v1/groups/{group_id}/setassent', 'ManageGroupController@setAsSent');
Route::get('v1/bounces/{customer_id}', 'BounceController@show');


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


Route::resource('v1/abuse', 'GroupEmailAbuseController@store');
Route::post('v1/customer', 'CustomerController@store');
Route::get('v1/customer/{email}', 'CustomerController@show');
Route::get('v1/customers', 'CustomersController@index');
Route::post('v1/emails', 'TransactionalEmailsController@store');
Route::get('v1/spam', 'GroupEmailAbuseController@index');
Route::post('v1/unsubscribe', 'UnsubscribeController@store');
Route::get('v1/blacklist', 'BlacklistController@index');
Route::get('v1/blacklist/{email}', 'BlacklistController@show');
Route::post('v1/blacklist/{email_address}', 'BlacklistController@store');
Route::post('v1/create-group-email-group', 'GroupEmailsGroupController@store');
Route::post('v1/create-group-email', 'GroupEmailsController@store');

/*
 *  Login/Logout Routes
 *
 */

Route::get('login', ['as' => 'login_path', 'uses' => 'SessionsController@create']);

Route::post('login', ['as' => 'login_path', 'uses' => 'SessionsController@store']);

Route::get('logout', ['as' => 'logout_path', 'uses' => 'SessionsController@destroy']);

Route::get('not_authorized', 'SessionsController@not_authorized');

Route::resource('v1/klipfolio/customers', 'KlipfolioController@customerCount');
Route::resource('v1/klipfolio/getSMTPBounceRate', 'KlipfolioController@getSMTPBounceRate');
Route::resource('v1/klipfolio/getUnsubscribeStats', 'KlipfolioController@getUnsubscribeStats');
Route::resource('v1/klipfolio/groups', 'KlipfolioController@groupCount');
Route::resource('v1/klipfolio/getGroups', 'KlipfolioController@getGroups');
Route::resource('v1/klipfolio/getDeliveryStats14Days', 'KlipfolioController@getDeliveryStats14Days');
Route::resource('v1/klipfolio/getBounceStats', 'KlipfolioController@getBounceStats');
Route::resource('v1/klipfolio/getLast100Groups', 'KlipfolioController@getLast100Groups');
Route::resource('v1/klipfolio/getAllGroupEmails', 'KlipfolioController@getAllGroupEmails');
Route::resource('v1/klipfolio/getAllTransactionalEmails', 'KlipfolioController@getAllTransactionalEmails');
Route::resource('v1/klipfolio/getAbuseStats', 'KlipfolioController@getAbuseStats');
Route::resource('v1/klipfolio/getTraceLogs', 'KlipfolioController@getTraceLogs');
Route::resource('v1/klipfolio/getBounceServerStatus', 'KlipfolioController@getBounceServerStatus');
Route::resource('v1/klipfolio/getDeliveryServerStatus', 'KlipfolioController@getDeliveryServerStatus');
Route::resource('v1/klipfolio/getSpamReports', 'KlipfolioController@getSpamReports');
Route::resource('v1/klipfolio/getGodStats', 'KlipfolioController@getGodStats');
Route::resource('v1/klipfolio/getGodFrame', 'KlipfolioController@getGodFrame');
Route::resource('v1/klipfolio/moveCustomerToPool', 'KlipfolioController@moveCustomerToPool');
