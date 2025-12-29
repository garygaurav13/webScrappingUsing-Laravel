<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CollectionScraper;
use App\Models\Collection;
use App\Jobs\ScrapeCollectionProductsJob;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;

class CollectionScrap extends Controller
{
    public function productsMenu(CollectionScraper $crawler)
    {
        $data = $crawler->crawl('https://geckocustom.com/collections');



        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function createJob()
    {
        Collection::orderBy('id')->chunk(10, function ($collections) {
            foreach ($collections as $collection) {
                ScrapeCollectionProductsJob::dispatch($collection);
            }
        });
    }

    public function export()
    {
        
        return Excel::download(
            new ProductsExport(),
            'collections_products.xlsx'
        );
    }

    
}
