@php
    $logo = $getRecord()->logo_path;
    if ($logo && !str_starts_with($logo, 'http')) {
        $logo = \Illuminate\Support\Facades\Storage::url($logo);
    }
@endphp

<div class="flex items-center gap-2 py-2">
    @if($logo)
        <img src="{{ $logo }}" class="w-8 h-8 rounded shadow-sm border border-gray-200 object-contain bg-white">
    @endif
    <div class="flex flex-col">
        <span class="font-bold text-gray-900 dark:text-white">{{ $getRecord()->nome_sistema }}</span>
        <span class="text-xs text-gray-500">{{ $getRecord()->slogan }}</span>
    </div>
</div>