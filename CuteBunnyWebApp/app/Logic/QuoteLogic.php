<?php

namespace App\Logic;

use GuzzleHttp\Client;
use App\Models\Quote;
use Carbon\Carbon;

class QuoteLogic
{
    public function GetDailyQuote(string $language)
    {
        // Retrieve the latest quote from the database for the requested language
        $latestQuote = $this->GetLatestQuoteFromDatabase($language);

        // If a valid quote is found, return it
        if ($latestQuote) {
            return $latestQuote;
        }

        // Generate a new English quote and save it to the database
        $englishQuote = $this->GenerateNewEnglishQuoteAndSaveToDatabase();

        // If the requested language is Danish, translate the English quote to Danish
        if ($language === 'danish') {
            return $this->TranslateAndSaveQuoteToDanish($englishQuote);
        }

        return $englishQuote;
    }

    private function GetLatestQuoteFromDatabase(string $language): ?string
    {
        // Construct the column name dynamically
        $columnName = "{$language}_quote";

        // Retrieve the latest value of the specified column where it is not null
        return Quote::latest('created_at')
            ->whereNotNull($columnName)
            ->value($columnName); // Directly retrieve the column value
    }

    private function GenerateNewEnglishQuoteAndSaveToDatabase(): string
    {
        $apiKey = env("OPENAI_API_KEY");

        // Fetch the last 10 English quotes from the database
        $lastQuotes = Quote::latest()->take(10)->pluck('english_quote')->toArray();

        // Convert the last quotes into a string for the prompt
        $lastQuotesString = implode("\n", $lastQuotes);

        $client = new Client();

        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a master at creating unique, motivational, and slightly whimsical quotes about bunnies. These quotes can vary in length but should generally be between 15 and 45 words. Use playful, inspiring language that evokes happiness and imagination. Avoid quotation marks and keep the tone light, encouraging, and bunny-themed.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Create a unique, motivational bunny-related quote. Make sure the quote does not resemble or repeat the following examples:\n" . $lastQuotesString,
                        ],
                    ],
                ],
            ]);
            

            $response_data = json_decode($response->getBody(), true);
            $quote = $response_data['choices'][0]['message']['content'] ?? 'No quote available.';

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
        $apiKey = env("OPENAI_API_KEY");

        $client = new Client();

        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a master translator and will translate English quotes to Danish as accurately as possible.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Translate this English quote without adding quotation marks: $englishQuote",
                        ],
                    ],
                ],
            ]);

            $response_data = json_decode($response->getBody(), true);
            $translatedQuote = $response_data['choices'][0]['message']['content'] ?? 'Ingen citat tilgængelig.';

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
}
