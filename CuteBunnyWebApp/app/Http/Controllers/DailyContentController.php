<?php

namespace App\Http\Controllers;

use App\Logic\ImageLogic;
use App\Logic\QuoteLogic;
use Illuminate\Http\Request;

class DailyContentController extends Controller
{
    protected $imageLogic;
    protected $quoteLogic;

    public function __construct(ImageLogic $imageLogic, QuoteLogic $quoteLogic)
    {
        $this->imageLogic = $imageLogic;
        $this->quoteLogic = $quoteLogic;
    }

    public function ShowDailyContent(Request $request)
    {
        $language = $request->cookie('language', 'english');

        $image_url = $this->imageLogic->GetDailyImageUrl();

        $quote = $this->quoteLogic->GetDailyQuote($language);

        return view("welcome", [
            "imageUrl" => $image_url,
            "quote" => $quote
        ]);
    }

    public function SetLanguage(string $language)
    {
        // Validate and set the language cookie
        if (in_array($language, ['english', 'danish'])) {
            return redirect()->back()->withCookie(cookie()->forever('language', $language));
        }

        // Default fallback if an invalid language is passed
        return redirect()->back();
    }
}
