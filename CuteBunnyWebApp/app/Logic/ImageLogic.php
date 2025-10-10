<?php

namespace App\Logic;

use App\Models\ImageUrl;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ImageLogic
{
    public function getDailyImageUrl(): ?string
    {
        // Get the latest image, or fetch one if missing
        $latestImage = ImageUrl::latest('created_at')->first();

        if (!$latestImage || !$latestImage->image_url) {
            $this->fetchAndStoreNewImage();
            $latestImage = ImageUrl::latest('created_at')->first();
        }

        return $latestImage?->image_url;
    }

    public function GetNewImageForDatabase(): void
    {
        $apiKey = env('PEXELS_API_KEY');
        $baseUrl = 'https://api.pexels.com/v1/search';
        $client = new Client();

        $page = rand(1, 20);
        $perPage = 20;
        $query = 'cute bunny rabbit';
        $orientation = 'landscape';

        try {
            $response = $client->get($baseUrl, [
                'headers' => ['Authorization' => $apiKey],
                'query' => [
                    'query' => $query,
                    'orientation' => $orientation,
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            if (empty($data['photos'])) {
                Log::warning('No photos returned from Pexels API.');
                return;
            }

            // Get last 20 URLs from the database
            $lastTwentyImages = ImageUrl::latest()
                ->take(20)
                ->pluck('image_url')
                ->toArray();

            // Pick a random photo index
            $randomIndex = rand(0, count($data['photos']) - 1);
            $chosenPhoto = $data['photos'][$randomIndex];

            // Extract the actual image URL (not the HTML page URL)
            $chosenUrl = $chosenPhoto['src']['original'] ?? null;

            if ($chosenUrl && !in_array($chosenUrl, $lastTwentyImages)) {
                ImageUrl::create(['image_url' => $chosenUrl]);
            } else {
                Log::info('Chosen image is duplicate or invalid.');
            }

        } catch (\Throwable $e) {
            Log::error('Error fetching image from Pexels: ' . $e->getMessage());
        }
    }
}
