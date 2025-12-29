<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Exports\ProductsChunkExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductsChunks extends Command
{
    protected $signature = 'export:products-chunks';
    protected $description = 'Export products into multiple Excel files (10k records each)';

    public function handle()
    {
        $chunkSize = 10000;
        $total     = Product::count();
        $chunks    = ceil($total / $chunkSize);

        $this->info("Total products: {$total}");
        $this->info("Creating {$chunks} Excel files");

        for ($i = 0; $i < $chunks; $i++) {
            $offset = $i * $chunkSize;
            $file   = "products_" . ($i + 1) . ".xlsx";

            Excel::store(
                new ProductsChunkExport($offset, $chunkSize),
                $file,
                'public'
            );

            $this->info("Created: {$file}");
        }

        $this->info('✅ Export completed successfully');
    }
}
