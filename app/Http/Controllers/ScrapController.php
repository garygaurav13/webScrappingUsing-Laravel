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

    public function test()
    {
        $url = 'https://geckocustom.com/collections/all-product-human-face?page=4';
        $client = HttpClient::create([
            'headers' => ['User-Agent' => 'Mozilla/5.0']
        ]);

        // 1️⃣ Load first page
        $response = $client->request('GET', $url);
        $crawler  = new Crawler($response->getContent());

        $products = [];
        $crawler->filter('.product-list__item')->each(function (Crawler $node) use (&$products) {

            $title = $node->filter('span.title[itemprop="name"]')->text();
            // Current price
            $currentPrice = $node->filter('span.current_price .money')->count()
                ? trim($node->filter('span.current_price .money')->text())
                : null;

            // Original price / Was price
            $originalPrice = $node->filter('span.was_price .money')->count()
                ? trim($node->filter('span.was_price .money')->text())
                : null;
            $imageNode = $node->filter('div.image__container img')->first();
            $imageUrl = null;
            if ($imageNode->count()) {
                // Prefer data-src for lazy-loaded images
                $imageUrl = $imageNode->attr('data-src') ?? $imageNode->attr('src');

                // Make sure URL is absolute
                if ($imageUrl && str_starts_with($imageUrl, '//')) {
                    $imageUrl = 'https:' . $imageUrl;
                }
            }
            $link = $node->filter('a')->attr('href');

                if ($link && str_starts_with($link, '/')) {
                    $link = 'https://geckocustom.com' . $link;
                }

                $slug = basename(parse_url($link, PHP_URL_PATH));

           

            $products[] = [
                'title' => $title,
                'current'=>$currentPrice,
                'original'=>$originalPrice,
                'image'=>$imageUrl,
                'link'=>$link,
                'slug'=>$slug
            ];
        });

        dd($products);
    }

    public function run(){
        ScrapeCollectionsJob::dispatch();
    }

    public function scrap(){
        
            $client = HttpClient::create([
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                ],
                'verify_peer' => false,
                'verify_host' => false,
                'timeout' => 30,
            ]);
            $url="https://geckocustom.com/collections/all-product-human-face?page=4";
            $response = $client->request('GET', $url);
            $html     = $response->getContent(false);

            $crawler = new Crawler($html);

            // ✅ IMPORTANT: Loop product container, not <a>
            $products = $crawler->filter('div.product-wrap');

          

            $products->each(function (Crawler $node) {

                // ---------- TITLE ----------
                if (!$node->filter('a.product-info__caption span.title')->count()) {
                    return;
                }

                $title = trim(
                    $node->filter('a.product-info__caption span.title')->text()
                );
dd($title);
                // ---------- LINK ----------
                $link = $node
                    ->filter('a.product-info__caption')
                    ->attr('href');

                if (str_starts_with($link, '/')) {
                    $link = 'https://geckocustom.com' . $link;
                }

                $slug = basename(parse_url($link, PHP_URL_PATH));

                // ---------- IMAGE ----------
                $imageUrl = null;

                if ($node->filter('div.image__container img')->count()) {
                    $img = $node->filter('div.image__container img')->first();

                    // Lazy-load support
                    $imageUrl =
                        $img->attr('data-src')
                        ?? $img->attr('src')
                        ?? null;

                    // fallback: take largest from srcset
                    if (!$imageUrl && $img->attr('data-srcset')) {
                        $srcset = explode(',', $img->attr('data-srcset'));
                        $imageUrl = trim(explode(' ', end($srcset))[0]);
                    }

                    // make absolute
                    if ($imageUrl && str_starts_with($imageUrl, '//')) {
                        $imageUrl = 'https:' . $imageUrl;
                    }
                }

                // ---------- PRICE ----------
                $price = null;
                $comparePrice = null;

                if ($node->filter('.current_price .money')->count()) {
                    $price = preg_replace(
                        '/[^0-9.]/',
                        '',
                        $node->filter('.current_price .money')->text()
                    );
                }

                if ($node->filter('.was_price .money')->count()) {
                    $comparePrice = preg_replace(
                        '/[^0-9.]/',
                        '',
                        $node->filter('.was_price .money')->text()
                    );
                }

                // ---------- SAVE ----------
                Product::updateOrCreate(
                    ['url' => $link],
                    [
                        'collection_id' => 1,
                        'title'          => $title,
                        'slug'           => $slug,
                        'image'          => $imageUrl,
                        'price'          => $price,
                        'compare_price'  => $comparePrice,
                    ]
                );
            });
    }
}
