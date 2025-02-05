<?php

namespace App\Logic;

use App\Logic\QuoteLogic;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SmsLogic
{

    private $quoteLogic;

    public function __construct(QuoteLogic $quoteLogic)
    {
        $this->quoteLogic = $quoteLogic;
    }


    // This is a method to send a sms reminder using Twilio. This was just some fun custom stuff I did for a friend
    public function SendSmsReminder()
    {
        try {
            $sid = env('TWILIO_ACCOUNT_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $twilio = new Client($sid, $token);

            $ai_prompt = [
                [
                    'role' => 'system',
                    'content' => 'Du er en kreativ AI, der genererer engagerende, varierende og korte SMS-beskeder for at promovere hjemmesiden julianesmotiverendekanin.dk. Hver besked skal være frisk, spændende og ikke længere end 160 tegn.',
                ],
                [
                    'role' => 'user',
                    'content' => 'Generér en kort, engagerende SMS-besked, der opfordrer folk til at tjekke julianesmotiverendekanin.dk. Sørg for, at hver besked er forskellig og sjov! Du bør vide at det eneste som er på hjemmeside er et billede af en sød kanin og en motiverende quote, dette vil fornyes hver dag og derfor sender du en påmindelse.',
                ],
            ];


            $message_text = $this->quoteLogic->GetAiGeneratedText($ai_prompt);

            $twilio->messages
                ->create(env("TWILIO_RECEIVING_PHONE_NUMBER"), // to
                    array(
                        "from" => env("TWILIO_SENDER_PHONE_NUMBER"),
                        "body" => $message_text
                    )
                );
        } catch (\Exception $e) {
            // Handle the exception (e.g., log the error, notify someone, etc.)
            Log::error('Failed to send SMS: ' . $e->getMessage());
        }
    }
}
