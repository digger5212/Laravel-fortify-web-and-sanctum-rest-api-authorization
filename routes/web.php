<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});
Route::middleware(['auth','verified'])->group(function () {
    Route::view('home', 'home')->name('home');
    //Route::view('password/update', 'auth.passwords.update')->name('passwords.update');
    Route::get('/reset-password', function(){
        return view('auth.passwords.update');
    })->name('password.reset');
});

Route::post('/tokens/create', function(Request $request){
    $token = $request->user()->createToken($request->token_name);

    return ['token' => $token->plainTextToken];
});