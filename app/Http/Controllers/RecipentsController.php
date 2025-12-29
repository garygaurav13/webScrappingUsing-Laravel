<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use App\Models\Recipent;
Use App\jobs\RecipientProductsJob;

class RecipentsController extends Controller
{
    public function index()
    {
        $url = "https://geckocustom.com";
        $client = HttpClient::create([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0'
            ]
        ]);

        $html = $client->request('GET', $url)->getContent();

        $crawler = new Crawler($html);

        $recipients = [];

        $crawler
            ->filter('summary:contains("Recipients") + ul.nav-desktop__tier-2 > li')
            ->each(function (Crawler $li) use (&$recipients) {

                $name = trim($li->filter('a span')->text(''));
                $url  = $li->filter('a')->attr('href');
                $link = "https://geckocustom.com" . $url;
                $slug = basename(parse_url($link, PHP_URL_PATH));


                if ($name && $url) {
                    $recipients[] = [
                        'name' => $name,
                        'url'  => $link,
                        'slug' => $slug
                    ];
                    Recipent::create([
                        'name' => $name,
                        'url'  => $link,
                        'slug' => $slug
                    ]);
                }
            });
        // Recipent::create($recipients);
        return $recipients;
    }

    public function createJob()
    {
        
        Recipent::orderBy('id')->chunk(10, function ($collections) {
            foreach ($collections as $collection) {
                RecipientProductsJob::dispatch($collection)
                ->onQueue('recipient-products');
            }
        });
    }
}
