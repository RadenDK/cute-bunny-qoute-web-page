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


// Make it so that it will send a sms notification every day at 8:15 AM Danish time
Schedule::call(function () {
    $quoteLogic = new QuoteLogic();
    $smsLogic = new SmsLogic($quoteLogic);
    $smsLogic->SendSmsReminder();
})->timezone('Europe/Copenhagen')->dailyAt('08:15');


Schedule::call(function () {
    $imageLogic = new ImageLogic();
    $imageLogic->GetNewImageForDatabase();

    $quoteLogic = new QuoteLogic();
    $quoteLogic->GenerateNewQuotesToDatabase();

 })->timezone('Europe/Copenhagen')->dailyAt('00:00');

