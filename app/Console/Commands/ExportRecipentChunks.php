<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RecipientProduct;
use App\Exports\RecipientsChunk;
use Maatwebsite\Excel\Facades\Excel;

class ExportRecipentChunks extends Command
{
    protected $signature = 'export:rec-chunks';
    protected $description = 'Export products into multiple Excel files (10k records each)';

    public function handle()
    {
        $chunkSize = 5000;
        $total     = RecipientProduct::count();
        $chunks    = ceil($total / $chunkSize);

        $this->info("Total rows: {$total}");
        $this->info("Creating {$chunks} Excel files");

        for ($i = 0; $i < $chunks; $i++) {
            $offset = $i * $chunkSize;
            $file   = "recipient_products_" . ($i + 1) . ".xlsx";

            Excel::store(
                new RecipientsChunk($offset, $chunkSize),
                $file,
                'public'
            );

            $this->info("Created: {$file}");
        }

        $this->info('✅ Export completed successfully');
    }
}
