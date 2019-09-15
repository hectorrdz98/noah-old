<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('main');
});

Route::group(['prefix' => 'question'], function () {
    Route::post('/', ['uses' => 'QuestionController@newQuestion', 'as' => 'question.newQuestion']);
});

Route::group(['prefix' => 'chat'], function () {
    Route::post('/', ['uses' => 'ConversationController@chatReceived', 'as' => 'chat.chatReceived']);
    Route::get('addTokens', ['uses' => 'ConversationController@addTokens', 'as' => 'chat.addTokens']);
    Route::post('addTokens', ['uses' => 'ConversationController@newToken', 'as' => 'chat.newToken']);
});

Route::get('test', ['uses' => 'QuestionController@testInput', 'as' => 'question.testInput']);