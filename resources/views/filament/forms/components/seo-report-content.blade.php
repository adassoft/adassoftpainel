<div class="p-4" style="min-height: 300px;">
    @if($this->isAnalyzing)
        <div class="text-center py-10">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mb-4"></div>
            <p class="font-bold text-gray-800 text-lg">A Inteligência Artificial está auditando sua página...</p>
            <p class="text-gray-500">Isso pode levar alguns segundos.</p>
        </div>
    @elseif($this->analysisResult)
        <div class="prose max-w-none text-gray-700">
            {!! \Illuminate\Support\Str::markdown($this->analysisResult) !!}
        </div>
    @else
        <div class="text-center py-20 text-gray-400">
            <p>O relatório detalhado aparecerá aqui após a análise.</p>
        </div>
    @endif
</div>