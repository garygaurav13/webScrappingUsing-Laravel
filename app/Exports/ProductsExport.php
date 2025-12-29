<?php

namespace App\Exports;

use App\Models\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
     protected $rows;

    public function __construct()
    {
        $this->rows = collect();
    }

    public function collection()
    {
        Collection::with('products')
            ->orderBy('id')
            ->limit(1)
            ->chunk(1, function ($collections) {
                foreach ($collections as $collection) {
                    foreach ($collection->products as $product) {
                        $this->rows->push([
                            'collection' => $collection,
                            'product' => $product
                        ]);
                    }
                }
            });

        return $this->rows;
    }

    public function map($row): array
    {
        return [
            $row['collection']->title,
            $row['collection']->url,
            $row['product']->title,
            $row['product']->url,
        ];
    }

    public function headings(): array
    {
        return [
            'Collection Name',
            'Collection URL',
            'Product Title',
            'Product URL',
        ];
    }
}
