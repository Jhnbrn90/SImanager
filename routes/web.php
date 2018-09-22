<?php

Auth::routes();

Route::get('/', 'CompoundController@index');

Route::get('/compounds/new', 'CompoundController@create');

Route::get('/compounds/{compound}', 'CompoundController@show');

Route::post('/compounds', 'CompoundController@store');

Route::patch('/compounds/{compound}', 'CompoundController@update');

Route::delete('/compounds/{compound}', 'CompoundController@destroy');

