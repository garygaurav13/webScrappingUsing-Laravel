<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsChunkExport implements FromQuery, WithHeadings, WithMapping
{
    protected int $offset;
    protected int $limit;

    public function __construct(int $offset, int $limit)
    {
        $this->offset = $offset;
        $this->limit  = $limit;
    }

    public function query()
    {
        return Product::query()
            ->orderBy('id')
            ->offset($this->offset)
            ->limit($this->limit)
            ->with('collection');
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

    public function map($product): array
    {
        return [
            $product->collection->title ?? '',
            $product->collection->url ?? '',
            $product->title,
            $product->url,
        ];
    }
}
