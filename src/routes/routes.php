<?php

// Route list

Route::get(config('laravel-commentable.route_api').'/{type}/{id}', 'Keggermont\Commentable\Http\CommentableController@get')->name("api.commentable.get")->where("type", "[a-zA-Z]+")->where("id","[0-9]+")->middleware(config('laravel-commentable.api_middleware_get'));
Route::post(config('laravel-commentable.route_api').'/create/{type}/{id}', 'Keggermont\Commentable\Http\CommentableController@create')->name("api.commentable.create")->where("type", "[a-zA-Z]+")->where("id","[0-9]+")->middleware(config('laravel-commentable.api_middleware_create'));
Route::delete(config('laravel-commentable.route_api').'/{id}', 'Keggermont\Commentable\Http\CommentableController@delete')->name("api.commentable.delete")->where("id","[0-9]+")->middleware(config('laravel-commentable.api_middleware_delete'));
