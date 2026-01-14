<?php

namespace App\Logic;

use App\Models\ImageUrl;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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

        // Return local image path instead of external URL
        return $latestImage ? $this->getLocalImagePath() : null;
    }

    private function getLocalImagePath(): string
    {
        return '/images/daily-bunny.jpg';
    }

    private function downloadAndSaveImage(string $imageUrl): bool
    {
        try {
            $client = new Client();
            $response = $client->get($imageUrl);
            
            // Ensure the images directory exists
            $imagesPath = public_path('images');
            if (!File::exists($imagesPath)) {
                File::makeDirectory($imagesPath, 0755, true);
            }

            // Save the image, overwriting if exists
            $filePath = public_path('images/daily-bunny.jpg');
            file_put_contents($filePath, $response->getBody()->getContents());
            
            return true;
        } catch (\Throwable $e) {
            Log::error('Error downloading image: ' . $e->getMessage());
            return false;
        }
    }

    public function fetchAndStoreNewImage(): void
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
                // Download and save the image locally
                if ($this->downloadAndSaveImage($chosenUrl)) {
                    ImageUrl::create(['image_url' => $chosenUrl]);
                }
            } else {
                Log::info('Chosen image is duplicate or invalid.');
            }

        } catch (\Throwable $e) {
            Log::error('Error fetching image from Pexels: ' . $e->getMessage());
        }
    }
}
