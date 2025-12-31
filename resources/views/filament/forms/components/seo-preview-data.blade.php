<div class="p-4 bg-gray-50 rounded text-sm text-gray-600" style="min-height: 100px;">
    @if($this->currentPreview)
        <div style="margin-bottom: 0.5rem;">
            <strong>Config Global:</strong><br>
            Site: {{ $this->currentPreview['site'] }}<br>
            Title Base: {{ $this->currentPreview['title_base'] }}
        </div>
        <div>
            {!! nl2br(e($this->currentPreview['target_info'])) !!}
        </div>
    @else
        Selecione uma opção para ver os dados.
    @endif
</div>