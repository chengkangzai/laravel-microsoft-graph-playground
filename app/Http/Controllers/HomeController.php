<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function welcome()
    {
        $viewData = $this->loadViewData();

        return view('welcome', $viewData);
    }
}
