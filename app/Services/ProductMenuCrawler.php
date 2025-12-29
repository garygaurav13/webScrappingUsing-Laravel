<?php

namespace App\Services;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class ProductMenuCrawler
{
    public function crawl(string $url): array
    {
        $client = HttpClient::create([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0'
            ]
        ]);

        $html = $client->request('GET', $url)->getContent();

        $crawler = new Crawler($html);

        $products = [];

        // Target only "Products" menu
        $crawler
            ->filter('summary:contains("Products") + ul.nav-desktop__tier-2 > li')
            ->each(function (Crawler $node) use (&$products) {

                $category = trim(
                    $node->filter('summary span')->first()->text('')
                );

                $subCategories = [];

                $node
                    ->filter('ul.nav-desktop__tier-3 a')
                    ->each(function (Crawler $a) use (&$subCategories) {
                        $subCategories[] = [
                            'name' => trim($a->text()),
                            'url'  => $a->attr('href'),
                        ];
                    });

                if ($category) {
                    $products[] = [
                        'category' => $category,
                        'sub_categories' => $subCategories,
                    ];
                }
            });

        return $products;
    }
}
