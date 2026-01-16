<?php

namespace App\Filament\Resources\KnowledgeBaseResource\Pages;

use App\Filament\Resources\KnowledgeBaseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeBase extends CreateRecord
{
    protected static string $resource = KnowledgeBaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['category_id'])) {
            $maxOrder = \App\Models\KnowledgeBase::where('category_id', $data['category_id'])->max('sort_order');
            $data['sort_order'] = $maxOrder !== null ? $maxOrder + 1 : 0;
        } else {
            $data['sort_order'] = 0;
        }

        return $data;
    }
}
