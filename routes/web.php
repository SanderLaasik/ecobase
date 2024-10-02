<?php

use App\Http\Controllers\StandController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/stands/{catastralNbr}', function (string $catastralNbr) {

    $controller = new StandController();
    $stands = $controller->getEligibleStands($catastralNbr);
    return $stands;
});
