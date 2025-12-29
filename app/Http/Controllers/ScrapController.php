<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductMenuCrawler;
use App\Models\Category;
use App\Models\SubCategories;
use Illuminate\Support\Str;
use App\Jobs\ScrapeSubCategoryProductsJob;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use App\Jobs\ScrapeCollectionsJob;
use App\Models\Product;

class ScrapController extends Controller
{
    public function productsMenu(ProductMenuCrawler $crawler)
    {
        $data = $crawler->crawl('https://geckocustom.com');

        foreach ($data as $item) {
            $category = Category::firstOrCreate(
                ['name' => $item['category']],
                [
                    'slug' => Str::slug($item['category'])
                ]
            );

            foreach ($item['sub_categories'] as $sub) {

                SubCategories::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'slug' => Str::slug($sub['name']),
                    ],
                    [
                        'name' => $sub['name'],
                        'url'  => Str::startsWith($sub['url'], 'https://geckocustom.com') ? $sub['url'] : 'https://geckocustom.com' . $sub['url'],
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function scrapeAll()
    {
        SubCategories::whereNotNull('url')
            ->chunk(10, function ($subs) {
                foreach ($subs as $sub) {
                    ScrapeSubCategoryProductsJob::dispatch($sub->id)
                        ->onQueue('scraper');
                }
            });

        return response()->json([
            'success' => true,
            'message' => 'Scraping jobs started'
        ]);
    }
}
