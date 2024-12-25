<?php

use App\Http\Controllers\DailyContentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DailyContentController::class, "ShowDailyContent"]);