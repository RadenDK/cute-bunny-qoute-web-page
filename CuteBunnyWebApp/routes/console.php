<?php

use App\Logic\ImageLogic;
use App\Logic\QuoteLogic;
use App\Logic\SmsLogic;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


// Just a command to try to test if there are errors with generating new content
Artisan::command('generate:daily-content', function () {
    $imageLogic = new ImageLogic();
    $imageLogic->GetNewImageForDatabase();

    $quoteLogic = new QuoteLogic();
    $quoteLogic->GenerateNewQuotesToDatabase();
})->describe('Generate daily image and quotes manually');



Schedule::call(function () {
    $imageLogic = new ImageLogic();
    $imageLogic->GetNewImageForDatabase();

    $quoteLogic = new QuoteLogic();
    $quoteLogic->GenerateNewQuotesToDatabase();

 })->timezone('Europe/Copenhagen')->dailyAt('00:00');

