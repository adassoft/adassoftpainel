<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Suggestion;
use App\Models\SuggestionVote;
use Illuminate\Support\Facades\DB;

class FeatureRequests extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationLabel = 'Sugestões & Melhorias';
    protected static ?string $title = 'Central de Melhorias';
    protected static string $view = 'filament.app.pages.feature-requests';

    // Propriedade para filtrar por status na view (default: all)
    public $filterStatus = 'all';

    public function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Sugerir Melhoria')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('title')
                        ->label('Título da Sugestão')
                        ->required()
                        ->maxLength(255),
                    Select::make('software_id')
                        ->label('Software Relacionado (Opcional)')
                        ->options(\App\Models\Software::where('status', true)->pluck('nome_software', 'id'))
                        ->searchable(),
                    Textarea::make('description')
                        ->label('Descreva sua ideia')
                        ->helperText('Seja detalhado. Como isso ajudaria seu negócio?')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    Suggestion::create([
                        'user_id' => auth()->id(),
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'software_id' => $data['software_id'],
                        'status' => 'pending',
                        'votes_count' => 1, // O autor já começa votando na propria ideia
                    ]);

                    // Cria o voto do autor
                    $suggestion = Suggestion::latest()->first();
                    SuggestionVote::create([
                        'user_id' => auth()->id(),
                        'suggestion_id' => $suggestion->id
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Sugestão enviada!')
                        ->body('Obrigado por contribuir. Sua sugestão será analisada.')
                        ->send();
                })
        ];
    }

    public function toggleVote($suggestionId)
    {
        $userId = auth()->id();

        // Verifica se já votou
        $existingVote = SuggestionVote::where('user_id', $userId)
            ->where('suggestion_id', $suggestionId)
            ->first();

        DB::transaction(function () use ($existingVote, $userId, $suggestionId) {
            if ($existingVote) {
                // Remove voto
                $existingVote->delete();
                Suggestion::where('id', $suggestionId)->decrement('votes_count');
            } else {
                // Adiciona voto
                SuggestionVote::create([
                    'user_id' => $userId,
                    'suggestion_id' => $suggestionId
                ]);
                Suggestion::where('id', $suggestionId)->increment('votes_count');
            }
        });
    }

    protected function getViewData(): array
    {
        $query = Suggestion::with(['user', 'software'])
            ->withCount([
                'votes as voted_by_user' => function ($query) {
                    $query->where('user_id', auth()->id());
                }
            ]);

        if ($this->filterStatus !== 'all') {
            $query->where('status', $this->filterStatus);
        } else {
            // Se filtro "Todas", oculta as Pendentes (Moderação)
            // Mas permite que o usuário veja as SUAS próprias pendentes
            $query->where(function ($q) {
                $q->where('status', '!=', 'pending')
                    ->orWhere('user_id', auth()->id());
            });
        }

        return [
            'suggestions' => $query->orderByDesc('votes_count')->paginate(12)
        ];
    }
}
