<?php

use App\Logic\ImageLogic;
use App\Logic\QuoteLogic;
use App\Logic\SmsLogic;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

function forgetDailyContentCache(): void
{
    Cache::forget(ImageLogic::CACHE_KEY);
    Cache::forget(QuoteLogic::CACHE_KEY_PREFIX . 'english');
    Cache::forget(QuoteLogic::CACHE_KEY_PREFIX . 'danish');
}

// Just a command to try to test if there are errors with generating new content
Artisan::command('generate:daily-content', function () {
    $imageLogic = new ImageLogic();
    $imageLogic->fetchAndStoreNewImage();

    $quoteLogic = new QuoteLogic();
    $quoteLogic->GenerateNewQuotesToDatabase();

    forgetDailyContentCache();
})->describe('Generate daily image and quotes manually');

Artisan::command('generate:new-image', function () {
    $this->info('Starting generate:new-image...');
    $imageLogic = new ImageLogic();
    $imageLogic->fetchAndStoreNewImage();
    Cache::forget(ImageLogic::CACHE_KEY);
    $this->info('generate:new-image finished.');
})->describe('Generate new daily image');


Schedule::call(function () {
    $imageLogic = new ImageLogic();
    $imageLogic->fetchAndStoreNewImage();

    $quoteLogic = new QuoteLogic();
    $quoteLogic->GenerateNewQuotesToDatabase();

    forgetDailyContentCache();

 })->timezone('Europe/Copenhagen')->dailyAt('00:00');

