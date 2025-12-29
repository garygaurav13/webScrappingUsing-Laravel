<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
// use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductScraper
{
    protected string $host = 'https://geckocustom.com';

    public function scrapeSubCategory(int $subCategoryId, string $url): void
    {
        $client = HttpClient::create([
            'headers' => ['User-Agent' => 'Mozilla/5.0']
        ]);

        // 1️⃣ Load first page
        $response = $client->request('GET', $url);
        $crawler  = new Crawler($response->getContent());

        // 2️⃣ Detect total pages
        $lastPage = $this->getLastPage($crawler);

        // 3️⃣ Loop through all pages
        for ($page = 1; $page <= $lastPage; $page++) {

            $pageUrl = $page === 1 ? $url : $url . '?page=' . $page;
            $html = $client->request('GET', $pageUrl)->getContent();
            $pageCrawler = new Crawler($html);
Log::info('PRODUCT NODE HTML', [
        'html' => $pageCrawler->filter('.product-list__item')
    ]);
            // 4️⃣ Extract products
            $pageCrawler->filter('.product-wrap')->each(function (Crawler $node) use ($subCategoryId) {
                $this->saveProduct($node, $subCategoryId);
            });

            sleep(rand(1, 3)); // Anti-ban
        }
    }

    private function getLastPage(Crawler $crawler): int
    {
        $pages = $crawler->filter('.paginate .page a');

        if ($pages->count() === 0) {
            return 1;
        }

        return max(
            $pages->each(fn (Crawler $a) => (int) $a->text())
        );
    }

    private function saveProduct(Crawler $node, int $subCategoryId): void
    {
        $name  = trim($node->filter('.product-title')->text());
        $href  = $node->filter('a')->attr('href');
        $url   = str_starts_with($href, 'http')
            ? $href
            : $this->host . $href;

        $image = $node->filter('img')->attr('src');
        $price = $node->filter('.money')->count()
            ? trim($node->filter('.money')->text())
            : null;
dd($name);
        // Product::updateOrCreate(
        //     ['url' => $url],
        //     [
        //         'sub_category_id' => $subCategoryId,
        //         'name' => $name,
        //         'slug' => Str::slug($name),
        //         'price' => $price,
        //         'image' => $image,
        //     ]
        // );
    }
}
