<?php

namespace App\Jobs;

use App\Models\Collection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeCollectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $baseUrl = 'https://geckocustom.com';
        $page = 1;

        do {
            $url = $baseUrl . '/collections?page=' . $page;

            logger()->info("Scraping page: " . $url);

            $response = Http::withoutVerifying()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0'
                ])
                ->get($url);

            if (!$response->successful()) {
                break;
            }

            $crawler = new Crawler($response->body());

            // ✅ Get collections
            $items = $crawler->filter('div.product-wrap');

            if ($items->count() === 0) {
                break; // stop pagination
            }

            $items->each(function (Crawler $node) use ($baseUrl) {

                $title = $node->filter('span.title')->count()
                    ? trim($node->filter('span.title')->text())
                    : null;

                $link = $node->filter('a.collection-info__caption')->count()
                    ? $node->filter('a.collection-info__caption')->attr('href')
                    : null;

                if ($link && str_starts_with($link, '/')) {
                    $link = $baseUrl . $link;
                }

                $image = null;
                if ($node->filter('img')->count()) {
                    $image = $node->filter('img')->first()->attr('data-src')
                        ?? $node->filter('img')->first()->attr('src');

                    if ($image && str_starts_with($image, '//')) {
                        $image = 'https:' . $image;
                    }
                }

                if (!$title || !$link) {
                    return;
                }

                // ✅ Save or skip duplicate
                Collection::updateOrCreate(
                    ['url' => $link],
                    [
                        'title' => $title,
                        'image' => $image
                    ]
                );
            });

            $page++;
        } while (true);

        logger()->info('Collection scraping completed');
    }
}
