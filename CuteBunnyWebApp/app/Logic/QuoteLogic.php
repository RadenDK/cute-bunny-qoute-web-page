<?php

namespace App\Logic;

use App\Models\Quote;
use GuzzleHttp\Client;

class QuoteLogic
{

    public function GetDailyQuote(string $language = 'english'): string
    {
        // Construct the column name dynamically
        $columnName = "{$language}_quote";

        // Retrieve the latest record where the specified column is not null
        $latestQuote = Quote::latest('created_at')
            ->whereNotNull($columnName)
            ->first();

        // Check if the latest quote is null
        if ($latestQuote === null || $latestQuote->$columnName === null) {
            // Attempt to generate new quotes
            $this->GenerateNewQuotesToDatabase();

            // Retrieve the latest record again after attempting to generate new quotes
            $latestQuote = Quote::latest('created_at')
                ->whereNotNull($columnName)
                ->first();
        }

        return $latestQuote->$columnName;
    }

    public function GenerateNewQuotesToDatabase(): void
    {
        // Generate a new English quote
        $englishQuote = $this->GenerateNewEnglishQuote();
        // Generate a new Danish quote
        $danishQuote = $this->GenerateNewDanishQuote($englishQuote);

        // Save both quotes to the database
        Quote::create([
            'english_quote' => $englishQuote,
            'danish_quote' => $danishQuote,
        ]);
    }

    private function GenerateNewEnglishQuote(): string
    {
        // Fetch the last 10 English quotes from the database
        $lastQuotes = Quote::latest()->take(10)->pluck('english_quote')->toArray();

        // Convert the last quotes into a string for the prompt
        $lastQuotesString = implode("\n", $lastQuotes);

        try {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a master at creating unique, motivational, and slightly whimsical quotes about bunnies. Each quote must be short—ideally between 15 and 45 words and no more than 2 sentences long. Use playful, inspiring language that evokes happiness and imagination. Avoid quotation marks and keep the tone light, encouraging, and bunny-themed.',
                ],
                [
                    'role' => 'user',
                    'content' => "Create a unique, motivational bunny-related quote that is no more than 2 sentences long and preferably between 15 and 45 words. Do not base the length of your response on the previous quotes. Make sure the quote does not resemble or repeat the following examples:\n" . $lastQuotesString,
                ],
            ];

            $response_text = $this->GetAiGeneratedText($messages);

            return $response_text ?? 'No quote available.';
        } catch (\Exception $e) {
            // Handle API errors
            return "Error fetching quote: " . $e->getMessage();
        }
    }

    private function GenerateNewDanishQuote(string $englishQuote): string
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

            return $response_text ?? 'Ingen citat tilgængelig.';
        } catch (\Exception $e) {
            // Handle API errors
            return "Fejl ved oversættelse af citat: " . $e->getMessage();
        }
    }

    // Might throw an exception from OpenRouter
    public function GetAiGeneratedText(array $messages)
    {
        $apiKey = env("OPENROUTER_AI_KEY");

        $client = new Client();

        $response = $client->post('https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => [
                'model' => 'xiaomi/mimo-v2-flash:free',
                'messages' => $messages,
                'reasoning' => [
                    'enabled' => true
                ]
            ],
        ]);

        $response_data = json_decode($response->getBody(), true);

        $response_text = $response_data['choices'][0]['message']['content'];

        return $response_text;
    }
}
