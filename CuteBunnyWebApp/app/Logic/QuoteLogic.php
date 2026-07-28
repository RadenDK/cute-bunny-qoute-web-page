<?php

namespace App\Logic;

use App\Models\Quote;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuoteLogic
{
    public const CACHE_KEY_PREFIX = 'daily_quote_';

    public function GetDailyQuote(string $language = 'english'): string
    {
        return Cache::remember(
            self::CACHE_KEY_PREFIX . $language,
            now('Europe/Copenhagen')->endOfDay(),
            fn () => $this->fetchDailyQuoteFromDatabase($language)
        );
    }

    private function fetchDailyQuoteFromDatabase(string $language): string
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
        try {
            // Generate a new English quote
            $englishQuote = $this->GenerateNewEnglishQuote();
            // Generate a new Danish quote
            $danishQuote = $this->GenerateNewDanishQuote($englishQuote);

            // Save both quotes to the database
            Quote::create([
                'english_quote' => $englishQuote,
                'danish_quote' => $danishQuote,
            ]);
        } catch (\Throwable $e) {
            // Leave the previous quote in place rather than showing an error message as content
            Log::error('Failed to generate daily quotes, keeping previous quote', ['exception' => $e->getMessage()]);
        }
    }

    private function GenerateNewEnglishQuote(): string
    {
        // Fetch the last 10 English quotes from the database
        $lastQuotes = Quote::latest()->take(10)->pluck('english_quote')->toArray();

        // Convert the last quotes into a string for the prompt
        $lastQuotesString = implode("\n", $lastQuotes);

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

        return $this->GetAiGeneratedText($messages) ?? throw new \RuntimeException('Empty response from AI for English quote');
    }

    private function GenerateNewDanishQuote(string $englishQuote): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a master translator and will translate English quotes to Danish as accurately as possible. Respond with ONLY the Danish translation itself — no preamble, no explanation, no notes, no quotation marks, nothing before or after the translation.',
            ],
            [
                'role' => 'user',
                'content' => "Translate this English quote to Danish. Reply with the translation only, nothing else: $englishQuote",
            ],
        ];

        return $this->GetAiGeneratedText($messages) ?? throw new \RuntimeException('Empty response from AI for Danish translation');
    }

    // Might throw an exception from OpenRouter
    public function GetAiGeneratedText(array $messages)
    {
        $apiKey = env("OPENROUTER_AI_KEY");

        $client = new Client();

        try {
            $response = $client->post('https://openrouter.ai/api/v1/chat/completions', [
                'timeout' => 30,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
                'json' => [
                    'model' => 'google/gemma-4-31b-it:free',
                    'messages' => $messages,
                    'reasoning' => [
                        'enabled' => true
                    ]
                ],
            ]);
        } catch (RequestException $e) {
            // The free model tier can rate-limit (429) or time out under load — log the
            // status/body so that's distinguishable from an auth or payload error.
            Log::error('OpenRouter request failed', [
                'status' => $e->getResponse()?->getStatusCode(),
                'body' => $e->getResponse()?->getBody()?->getContents(),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            Log::error('OpenRouter request timed out', ['message' => $e->getMessage()]);
            throw $e;
        }

        $response_data = json_decode($response->getBody(), true);

        $response_text = $response_data['choices'][0]['message']['content'];

        return $response_text === null ? null : $this->stripLeakedCommentary($response_text);
    }

    // Reasoning models sometimes leak a preamble ("Here is the translation:") or a
    // trailing explanation alongside the actual answer despite being told not to.
    private function stripLeakedCommentary(string $text): string
    {
        $text = trim($text);
        // Drop a leading line that reads like a preamble (ends in ':')
        $text = preg_replace('/^[^\n]*:\s*\n+/u', '', $text, 1);
        // Anything after a blank line is treated as commentary, not part of the answer
        $text = trim(explode("\n\n", $text)[0]);

        return trim($text, "\"'“”„»«");
    }
}
