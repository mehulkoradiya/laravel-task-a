<?php

namespace App\Http\Controllers;

class FrontendController extends Controller
{
    public function dashboard()
    {
        return view('dashboard');
    }

    public function imports()
    {
        return view('imports');
    }

    public function uploads()
    {
        return view('uploads');
    }
}
