<?php

Auth::routes();

Route::get('/', 'CompoundController@index');

Route::get('/compounds/{compound}', 'CompoundController@show');
Route::get('/compounds/new', 'CompoundController@create');
Route::post('/compounds', 'CompoundController@store');

Route::delete('/compounds/{compound}', 'CompoundController@destroy');

