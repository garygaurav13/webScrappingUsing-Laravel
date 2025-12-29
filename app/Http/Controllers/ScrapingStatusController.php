<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;

class ScrapingStatusController extends Controller
{
    public function index()
    {
        $collections = Collection::all();
        $products = Product::all();

        return view('scraping-status', [
            'collections' => $collections,
            'products' => $products,
        ]);
    }
}
