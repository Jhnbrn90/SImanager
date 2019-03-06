<?php

Auth::routes();

Route::get('/', 'CompoundController@index');

Route::patch('/userlabel', 'UserController@updateLabel');

Route::get('/compounds/new', 'CompoundController@create');
Route::get('/compounds/import', 'CompoundController@import');
Route::post('/compounds/import', 'CompoundController@storeFromImport');

Route::get('/compounds/{compound}/edit', 'CompoundController@edit');
Route::get('/compounds/{compound}/delete', 'CompoundController@confirmDelete');
Route::get('/compounds/{compound}', 'CompoundController@show');

Route::put('/compounds/{compound}', 'CompoundController@updateAll');

Route::post('/compounds', 'CompoundController@store');

Route::patch('/compounds/{compound}', 'CompoundController@update');

Route::delete('/compounds/{compound}', 'CompoundController@destroy');

Route::get('/supervisor/add', 'SharingDataController@addSupervisor');
Route::post('/supervisor', 'SharingDataController@store');
Route::get('/students', 'SharingDataController@listStudents');

Route::get('/students/view/data/{user}', 'CompoundController@studentIndex');

Route::get('/projects', 'ProjectController@index');
Route::post('/projects', 'ProjectController@store');
Route::get('/projects/create', 'ProjectController@create');
Route::get('/projects/{project}', 'ProjectController@show');
Route::get('/projects/{project}/edit', 'ProjectController@edit');
Route::patch('/projects/{project}', 'ProjectController@update');
Route::get('/projects/{project}/delete', 'ProjectController@destroy');
Route::get('/projects/{project}/export', 'ProjectController@export');

Route::get('/project-compounds/{project}/edit', 'ProjectCompoundController@edit');
Route::patch('/project-compounds/{project}', 'ProjectCompoundController@update');

Route::get('/bundle-projects/{bundle}/edit', 'BundleProjectController@edit');
Route::patch('/bundle-projects/{bundle}', 'BundleProjectController@update');

Route::get('/reactions', 'ReactionController@index');
Route::get('/reactions/new/{project}', 'ReactionController@store');
Route::get('/reactions/{reaction}', 'ReactionController@show');
Route::patch('/reactions/{reaction}', 'ReactionController@update');

Route::get('/bundles/new', 'BundleController@create');
Route::post('/bundles', 'BundleController@store');
