<?php

/**
**************************
* Application Web Routes
**************************
*/

Auth::routes();

Route::get('/', 'HomepageController');

Route::patch('/userlabel', 'UserController@updateLabel');
 
/**
 * Compounds 
 */
Route::group(['prefix' => 'compounds'], function() {
    Route::get('/new', 'CompoundController@create');
    Route::get('/import', 'CompoundController@import');
    Route::post('/import', 'CompoundController@storeFromImport');
    Route::put('/{compound}', 'CompoundController@updateAll');
    Route::get('/{compound}/delete', 'CompoundController@confirmDelete');
    Route::patch('/{compound}', 'CompoundController@update');
});

Route::resource('compounds', 'CompoundController', 
    ['only' => ['index', 'edit', 'show', 'store', 'destroy']]
);

/**
 * Supervisor-Student interaction routes 
 */
Route::get('/supervisor/add', 'SharingDataController@addSupervisor');
Route::post('/supervisor', 'SharingDataController@store');
Route::get('/students', 'SharingDataController@listStudents');
// View compounds of students 
Route::get('/students/view/data/{user}', 'CompoundController@studentIndex');

/**
 * Projects
 */
Route::resource('projects', 'ProjectController');
Route::get('/projects/{project}/export', 'ProjectController@export');

/**
 * Move Compounds between Projects
 */
Route::get('/project-compounds/{project}/edit', 'ProjectCompoundController@edit');
Route::patch('/project-compounds/{project}', 'ProjectCompoundController@update');

/**
 * Move Projects between Controllers
 */
Route::get('/bundle-projects/{bundle}/edit', 'BundleProjectController@edit');
Route::patch('/bundle-projects/{bundle}', 'BundleProjectController@update');

/**
 * Reactions
 */
Route::get('/reactions', 'ReactionController@index');
Route::get('/reactions/new/{project}', 'ReactionController@store');
Route::get('/reactions/{reaction}', 'ReactionController@show');
Route::patch('/reactions/{reaction}', 'ReactionController@update');

/**
 * Bundles
 */
Route::get('/bundles/new', 'BundleController@create');
Route::post('/bundles', 'BundleController@store');
