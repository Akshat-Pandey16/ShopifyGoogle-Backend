<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\ShopifyController;

Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);

Route::group(
    ['middleware' => 'auth:sanctum'],
    function () {
        Route::get('profile',[UserController::class,'profile']);
        Route::post('logout',[UserController::class,'logout']);
        Route::post('google/auth',[GoogleController::class,'google_auth']);
        Route::post('shopify/auth',[ShopifyController::class,'shopify_auth']);
        Route::get('shopify/products',[ShopifyController::class,'shopify_products']);
        Route::get('google/sheets',[GoogleController::class,'getSheets']);
        Route::post('google/sheets',[GoogleController::class, 'createSheet']);
        Route::post('google/sheets/update',[GoogleController::class, 'updateSheet']);
    }
);