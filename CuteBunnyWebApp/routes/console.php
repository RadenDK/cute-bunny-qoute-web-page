<?php

use App\Logic\QuoteLogic;
use App\Logic\SmsLogic;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');



// Make it so that it will send a sms notification every day at 8:15 AM Danish time
Schedule::call(function () {
    $quoteLogic = new QuoteLogic();
    $smsLogic = new SmsLogic($quoteLogic);
    $smsLogic->SendSmsReminder();
})->timezone('Europe/Copenhagen')->dailyAt('08:15');

