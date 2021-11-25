<?php

namespace App\Http\Controllers;

use App\Http\Services\MicrosoftGraphService;

class HomeController extends Controller
{
    public function welcome()
    {
        $viewData = app(MicrosoftGraphService::class)->loadViewData();

        return view('welcome', $viewData);
    }
}
