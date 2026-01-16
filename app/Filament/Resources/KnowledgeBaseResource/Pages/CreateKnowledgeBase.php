<?php

namespace App\Filament\Resources\KnowledgeBaseResource\Pages;

use App\Filament\Resources\KnowledgeBaseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeBase extends CreateRecord
{
    protected static string $resource = KnowledgeBaseResource::class;

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        // Loop through all attached categories to set the correct sort_order
        foreach ($record->categories as $category) {
            // Get the max sort_order for this category (excluding the current record to be safe, though it works if it's 0)
            $maxOrder = \Illuminate\Support\Facades\DB::table('kb_category_knowledge_base')
                ->where('kb_category_id', $category->id)
                ->where('knowledge_base_id', '!=', $record->id)
                ->max('sort_order');

            $nextOrder = $maxOrder !== null ? $maxOrder + 1 : 0;

            // Update the pivot record
            $record->categories()->updateExistingPivot($category->id, ['sort_order' => $nextOrder]);
        }
    }
}
