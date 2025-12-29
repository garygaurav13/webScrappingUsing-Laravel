<?php

namespace App\Exports;

use App\Models\RecipientProduct;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RecipientsChunk implements FromQuery, WithHeadings, WithMapping
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
        return RecipientProduct::query()
            ->with('recipient:id,name,url')
            ->orderBy('id')
            ->offset($this->offset)
            ->limit($this->limit);
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
            optional($product->recipient)->name,
            optional($product->recipient)->url,
            $product->title,
            $product->url,
        ];
    }
}
