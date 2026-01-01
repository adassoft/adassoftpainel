<?php

namespace App\Filament\App\Resources\TicketResource\Pages;

use App\Filament\App\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Services\GeminiService;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    public function showAiSuggestionAction(): Actions\Action
    {
        return Actions\Action::make('showAiSuggestion')
            ->label('Sugestão da IA')
            ->modalHeading('Sugestão Automática')
            ->modalDescription('Nossa IA analisou seu problema e encontrou esta possível solução:')
            ->modalContent(fn($arguments) => new HtmlString(
                '<div class="prose dark:prose-invert max-w-none p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">' .
                Str::markdown($arguments['text'] ?? '') .
                '</div>'
            ))
            ->modalSubmitActionLabel('Não resolveu, criar chamado')
            ->modalCancelActionLabel('Resolveu! (Cancelar)')
            ->action(function () {
                // Update form data state
                $this->data['ai_checked'] = true;
                // Como create() pega do form->getState(), precisamos que o form tenha esse valor
                // Mas getState() roda validação.
                // Vamos apenas chamar create() e ele vai ler o state. 
                // O problema é persistir o 'ai_checked' no state do form atual.
                $this->form->fill(['ai_checked' => true] + $this->form->getState());
                $this->create();
            });
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Abrir Chamado')
            ->action('checkAndCreate'); // Redireciona a ação do botão
    }

    public function checkAndCreate()
    {
        // 1. Valida o form chama getState. Se falhar, lança exception e para.
        $data = $this->form->getState();

        // 2. Se já checou, cria
        if (($data['ai_checked'] ?? false) == true) {
            $this->create();
            return;
        }

        // 3. IA Check
        Notification::make()->title('Analisando solicitação...')->body('Buscando soluções na base de conhecimento.')->info()->send();

        try {
            $service = new GeminiService();
            $response = $service->analyzeTicketRequest($data['subject'], strip_tags($data['description']));

            if ($response['success']) {
                // Abre modal customizado
                $this->mountAction('showAiSuggestion', ['text' => $response['reply']]);
            } else {
                // Falha silenciosa da IA, cria o ticket
                $this->create();
            }
        } catch (\Exception $e) {
            $this->create();
        }
    }
}
