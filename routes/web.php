<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScrapController;
use App\Http\Controllers\CollectionScrap;
use App\Http\Controllers\RecipentsController;
use App\Http\Controllers\ScrapingStatusController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/scraping-status', [ScrapingStatusController::class, 'index']);


Route::get('/crawl-products-menu', [ScrapController::class, 'productsMenu']);
Route::get('/scrap-products',[ScrapController::class, 'scrapeAll']);



Route::get('/createJob',[CollectionScrap::class, 'createJob']);

Route::get('/export',[CollectionScrap::class,'export']);

//recipients routes

Route::get('/rec',[RecipentsController::class,'index']);

//create jobs according to recipients
Route::get('/rec-job',[RecipentsController::class,'createJob']);


