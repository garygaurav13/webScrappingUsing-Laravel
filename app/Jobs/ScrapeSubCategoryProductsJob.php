<?php

namespace App\Jobs;

use App\Models\SubCategories;
use App\Services\ProductScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeSubCategoryProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 3;

    public function __construct(public int $subCategoryId) {}

    public function handle(ProductScraper $scraper): void
    {
        $sub = SubCategories::find($this->subCategoryId);

        if (! $sub) return;

        $scraper->scrapeSubCategory($sub->id, $sub->url);
    }
}
