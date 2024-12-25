<?php

namespace App\Logic;

use GuzzleHttp\Client;
use App\Models\Quote;
use Carbon\Carbon;

class QouteLogic
{
    public function getDailyQoute()
    {
        // Retrieve the latest record from the database
        $latestQuote = $this->getLatestRecordFromDatabase();

        // Check if the data matches the current date
        $today = Carbon::today()->toDateString();
        if ($latestQuote && $latestQuote->created_at->toDateString() === $today) {
            // Return the quote if it matches today's date
            return $latestQuote->quote;
        }

        // Generate a new quote and save it to the database
        return $this->getNewQuoteAndAddToDatabase();
    }

    private function getLatestRecordFromDatabase()
    {
        // Retrieve the latest record by created_at timestamp
        return Quote::latest('created_at')->first();
    }

    private function getNewQuoteAndAddToDatabase()
    {
        $apiKey = env("OPENAI_API_KEY");

        // Fetch the last 10 quotes from the database
        $lastQuotes = Quote::latest()->take(10)->pluck('quote')->toArray();

        // Convert the last quotes into a string for the prompt
        $lastQuotesString = implode("\n", $lastQuotes);

        $client = new Client();

        // Prompt to AI is in Danish because the response should be in Danish.
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
                        'content' => 'Du er en mester i at lave inspirerende citater og svarer altid på dansk. Sørg for, at citaterne er unikke og ikke indeholder anførselstegn.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Lav et unikt og inspirerende citat om kaniner. Undgå at gentage eller ligne følgende citater:\n" . $lastQuotesString,
                    ],
                ],
            ],
        ]);

        // Decode the JSON response from OpenAI
        $response_data = json_decode($response->getBody(), true);

        // Extract the generated content
        $quote = $response_data['choices'][0]['message']['content'] ?? 'No quote available.';

        // Save the quote to the database
        Quote::create(["quote" => $quote]);

        return $quote;
    }
}
