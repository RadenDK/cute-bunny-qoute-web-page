<?php

namespace App\Http\Controllers;

use App\Logic\ImageLogic;
use App\Logic\QouteLogic;
use Illuminate\Http\Request;

class DailyContentController extends Controller
{
    protected  $imageLogic; 
    protected $qouteLogic;

    public function __construct(ImageLogic $imageLogic, QouteLogic $qouteLogic) 
    {
        $this->imageLogic = $imageLogic;
        $this->qouteLogic = $qouteLogic;
    }

    public function ShowDailyContent()
    {
        $image_url = $this->imageLogic->getDailyImageUrl();
                
        $qoute = $this->qouteLogic->getDailyQoute();
        
        return view ("welcome", [
            "imageUrl" => $image_url,
            "qoute" => $qoute
        ]);
    }
}
