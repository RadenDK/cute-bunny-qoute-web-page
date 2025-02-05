<?php

namespace App\Logic;

use GuzzleHttp\Client;
use App\Models\Quote;
use Carbon\Carbon;

class QuoteLogic
{
    public function GetDailyQuote(string $language)
    {
        // Retrieve todays quote if there is any from the database for the requested language. If no quote for today in db then value is null
        $todaysQuote = $this->GetTodaysQuoteFromDatabase($language);

        // If a valid quote is found, return it
        if ($todaysQuote) {
            return $todaysQuote;
        }

        // Generate a new English quote and save it to the database
        $englishQuote = $this->GenerateNewEnglishQuoteAndSaveToDatabase();
        // Then translate the quote to danish and save that to the database
        $danishQuote = $this->TranslateAndSaveQuoteToDanish($englishQuote);

        if ($language === 'danish') {
            return $danishQuote;
        } else {
            return $englishQuote;
        }
    }

    private function GetTodaysQuoteFromDatabase(string $language): ?string
    {
        // Construct the column name dynamically
        $columnName = "{$language}_quote";

        // Retrieve the latest record where the specified column is not null
        $latestQuote = Quote::latest('created_at')
            ->whereNotNull($columnName)
            ->first();

        // Check if a record exists and the created_at date matches today's date
        if ($latestQuote && $latestQuote->created_at->isToday()) {
            return $latestQuote->$columnName;
        }

        // Return null if no record exists or the date does not match
        return null;
    }


    private function GenerateNewEnglishQuoteAndSaveToDatabase(): string
    {
        // Fetch the last 10 English quotes from the database
        $lastQuotes = Quote::latest()->take(10)->pluck('english_quote')->toArray();

        // Convert the last quotes into a string for the prompt
        $lastQuotesString = implode("\n", $lastQuotes);

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a master at creating unique, motivational, and slightly whimsical quotes about bunnies. These quotes can vary in length but should generally be between 15 and 45 words. Use playful, inspiring language that evokes happiness and imagination. Avoid quotation marks and keep the tone light, encouraging, and bunny-themed.',
                ],
                [
                    'role' => 'user',
                    'content' => "Create a unique, motivational bunny-related quote. Make sure the quote does not resemble or repeat the following examples:\n" . $lastQuotesString,
                ],
            ];

            $response_text = $this->GetAiGeneratedText($messages);

            $quote = $response_text ?? 'No quote available.';

            // Save the English quote to the database
            Quote::create(['english_quote' => $quote]);

            return $quote;
        } catch (\Exception $e) {
            // Handle API errors
            return "Error fetching quote: " . $e->getMessage();
        }
    }

    private function TranslateAndSaveQuoteToDanish(string $englishQuote): string
    {
        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a master translator and will translate English quotes to Danish as accurately as possible.',
                ],
                [
                    'role' => 'user',
                    'content' => "Translate this English quote without adding quotation marks: $englishQuote",
                ],
            ];

            $response_text = $this->GetAiGeneratedText($messages);

            $translatedQuote = $response_text ?? 'Ingen citat tilgængelig.';

            // Update the database record with the Danish translation
            $latestEnglishQuote = Quote::latest('created_at')->where('english_quote', $englishQuote)->first();
            if ($latestEnglishQuote) {
                $latestEnglishQuote->update(['danish_quote' => $translatedQuote]);
            }

            return $translatedQuote;
        } catch (\Exception $e) {
            // Handle API errors
            return "Fejl ved oversættelse af citat: " . $e->getMessage();
        }
    }

    // Might throw an exception from OpenAi
    public function GetAiGeneratedText(array $messages)
    {
        $apiKey = env("OPENAI_API_KEY");

        $client = new Client();

        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => $messages
            ],
        ]);

        $response_data = json_decode($response->getBody(), true);

        $response_text = $response_data['choices'][0]['message']['content'];

        return $response_text;

    }
}
