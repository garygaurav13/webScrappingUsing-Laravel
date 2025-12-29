<?php

namespace App\Jobs;

use App\Models\Collection;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Facades\Log;
use \GuzzleHttp\Promise\PromiseInterface;


class ScrapeCollectionProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Collection $collection;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    public function handle()
    {
        $page = 1;

        do {
            $url = $this->collection->url . '?page=' . $page;
            logger()->info("Scraping products: {$url}");

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
                ])
                ->timeout(30)
                ->get($url);

            // Wait for the response if it's a promise
            if ($response instanceof PromiseInterface) {
                $response = $response->wait();
            }

            // Check if response is successful
            if ($response->getStatusCode() !== 200) {
                logger()->error("Failed to fetch: {$url}");
                break;
            }

            // Get response body content
            $crawler = new Crawler((string) $response->getBody());

            // ✅ FIXED selector
            $products = $crawler->filter('.product-list__item');

            if ($products->count() === 0) {
                logger()->info('No products found, stopping pagination');
                break;
            }

            $products->each(function (Crawler $node) {

                // TITLE
                $title = $node->filter('span.title')->count()
                    ? trim($node->filter('span.title')->text())
                    : null;

                // LINK
                $link = $node->filter('a')->count()
                    ? $node->filter('a')->first()->attr('href')
                    : null;

                if ($link && str_starts_with($link, '/')) {
                    $link = 'https://geckocustom.com' . $link;
                }

                if (!$title || !$link) {
                    return;
                }

                $slug = basename(parse_url($link, PHP_URL_PATH));

                // IMAGE (robust)
                $imageUrl = null;
                if ($node->filter('img')->count()) {
                    $img = $node->filter('img')->first();

                    $imageUrl =
                        $img->attr('data-src')
                        ?? $img->attr('src')
                        ?? null;

                    if (!$imageUrl && $img->attr('data-srcset')) {
                        $srcset = explode(',', $img->attr('data-srcset'));
                        $imageUrl = trim(explode(' ', end($srcset))[0]);
                    }

                    if ($imageUrl && str_starts_with($imageUrl, '//')) {
                        $imageUrl = 'https:' . $imageUrl;
                    }
                }

                // PRICES
                $price = $node->filter('.current_price .money')->count()
                    ? (float) preg_replace('/[^0-9.]/', '', $node->filter('.current_price .money')->text())
                    : null;

                $comparePrice = $node->filter('.was_price .money')->count()
                    ? (float) preg_replace('/[^0-9.]/', '', $node->filter('.was_price .money')->text())
                    : null;

                // logger()->info('Saving product', [
                //     'title' => $title,
                //     'url' => $link,
                //     'image' => $imageUrl,
                //     'price' => $price,
                // ]);

                Product::updateOrCreate(
                    ['url' => $link],
                    [
                        'collection_id' => $this->collection->id,
                        'title' => $title,
                        'slug' => $slug,
                        'image' => $imageUrl,
                        'price' => $price,
                        'compare_price' => $comparePrice,
                    ]
                );
            });

            $page++;
            sleep(1);
        } while (true);

        logger()->info("Completed collection: {$this->collection->title}");
    }

   
}
