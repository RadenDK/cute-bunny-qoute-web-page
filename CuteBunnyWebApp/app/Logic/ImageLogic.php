<?php

namespace App\Logic;

use App\Models\ImageUrl;
use GuzzleHttp\Client;

class ImageLogic
{
    public function GetDailyImageUrl()
    {
        // Retrieve the latest record from the database
        $latestImage = ImageUrl::latest('created_at')->first();

        // Check if the latest image URL is null
        if ($latestImage === null || $latestImage->image_url === null) {
            // Attempt to get a new image
            $this->GetNewImageForDatabase();

            // Retrieve the latest record again after attempting to get a new image
            $latestImage = ImageUrl::latest('created_at')->first();
        }

        return $latestImage->image_url;
    }


    public function GetNewImageForDatabase()
    {
        $apiKey = env('BING_IMAGE_SEARCH_API_KEY');
        $baseUrl = 'https://api.bing.microsoft.com/v7.0/images/search';
        $client = new Client();

        // Get the last 20 saved image URLs
        $lastTwentyImages = ImageUrl::latest()->take(20)->pluck('image_url')->toArray();

        // Fetch 25 new images from Bing
        $response = $client->request('GET', $baseUrl, [
            'headers' => ['Ocp-Apim-Subscription-Key' => $apiKey],
            'query' => [
                'q' => "cute bunny high resolution",
                'count' => 25,
                'offset' => rand(0, 100),
                'safeSearch' => 'Strict',
                'imageType' => 'Photo',
            ],
        ]);

        $response_json = json_decode($response->getBody());

        // Check for valid image data
        if (isset($response_json->value) && is_array($response_json->value)) {
            foreach ($response_json->value as $image) {
                if (!in_array($image->contentUrl, $lastTwentyImages)) {
                    // Save the first unique image only
                    ImageUrl::create(['image_url' => $image->contentUrl]);
                    break; // Stop after saving one
                }
            }
        }
    }
}
