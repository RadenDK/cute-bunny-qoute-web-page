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

        // Fetch the last 20 images from the database
        $lastTenImages = ImageUrl::latest()->take(20)->pluck('image_url')->toArray();

        // Query multiple images
        $response = $client->request('GET', $baseUrl, [
            'headers' => ['Ocp-Apim-Subscription-Key' => $apiKey],
            'query' => [
                'q' => "cute bunny high resolution",
                'count' => 25, // Fetch multiple images
                'offset' => rand(0, 100), // Randomize starting position
                'safeSearch' => 'Strict',
                'imageType' => 'Photo',
            ],
        ]);

        $response_json = json_decode($response->getBody());

        // Ensure the response contains images
        if (isset($response_json->value) && count($response_json->value) > 0) {
            // Find the first unique image
            foreach ($response_json->value as $image) {
                if (!in_array($image->contentUrl, $lastTenImages)) {
                    $img_url = $image->contentUrl;

                    // Save the unique image to the database
                    ImageUrl::create(["image_url" => $img_url]);
                }
            }
        }
    }
}
