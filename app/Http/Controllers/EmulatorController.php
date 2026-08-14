<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class EmulatorController extends Controller
{
    public function index(): View
    {
        return view('emulator');
    }

    public function play(): View
    {
        return view('play');
    }
}
