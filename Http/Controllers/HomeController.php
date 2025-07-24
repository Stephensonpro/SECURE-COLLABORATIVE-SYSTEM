<?php

namespace App\Http\Controllers;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\ComplaintCategory;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $airlines = Airline::where('status', 'active')->take(8)->get();
        $airports = Airport::where('status', 'active')->take(8)->get();
        $categories = ComplaintCategory::all();

        return view('home', compact('airlines', 'airports', 'categories'));
    }
}
