<?php

Auth::routes();

Route::get('/', 'CompoundController@index');

Route::get('/compounds/new', 'CompoundController@create');

Route::get('/compounds/{compound}/edit', 'CompoundController@edit');
Route::get('/compounds/{compound}', 'CompoundController@show');

Route::put('/compounds/{compound}', 'CompoundController@updateAll');

Route::post('/compounds', 'CompoundController@store');

Route::patch('/compounds/{compound}', 'CompoundController@update');

Route::delete('/compounds/{compound}', 'CompoundController@destroy');

Route::get('/supervisor/add', 'SharingDataController@addSupervisor');
Route::post('/supervisor', 'SharingDataController@store');
Route::get('/students', 'SharingDataController@listStudents');

Route::get('/students/view/data/{user}', 'CompoundController@studentIndex');
