<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class ManageGoogle extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Google e IA';

    protected static ?string $title = 'Integrações Google';

    protected static ?string $navigationGroup = 'Integrações';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.manage-google';

    public ?array $data = [];

    public function mount(): void
    {
        $config = Configuration::where('chave', 'google_config')->first();

        if ($config) {
            $this->form->fill(json_decode($config->valor, true));
        } else {
            $this->form->fill([
                'gemini_model' => 'gemini-1.5-flash',
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Login Social (OAuth 2.0)')
                    ->extraAttributes(['class' => 'google-section-oauth'])
                    ->schema([
                        ViewField::make('redirect_uri_box')
                            ->view('filament.forms.components.google-redirect-uri'),

                        TextInput::make('client_id')
                            ->label('Client ID (ID do Cliente):'),

                        TextInput::make('client_secret')
                            ->label('Client Secret (Chave Secreta):'),
                    ]),

                Section::make('Inteligência Artificial (Gemini API)')
                    ->icon('heroicon-o-cpu-chip')
                    ->extraAttributes(['class' => 'google-section-ai'])
                    ->schema([
                        TextInput::make('gemini_api_key')
                            ->label('Gemini API Key:')
                            ->placeholder('Ex: AIzaSy...')
                            ->prefixIcon('heroicon-o-key'),

                        TextInput::make('gemini_model')
                            ->label('Modelo de IA (ID do Modelo):')
                            ->datalist([
                                'gemini-1.5-flash',
                                'gemini-1.5-pro',
                                'gemini-2.0-flash',
                                'gemini-2.0-flash-lite-preview-02-05',
                            ])
                            ->helperText('Selecione da lista ou cole o ID que você copiou (Ex: gemini-1.5-flash).'),

                        ViewField::make('models_button')
                            ->view('filament.forms.components.google-models-button'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Configuration::updateOrCreate(
            ['chave' => 'google_config'],
            ['valor' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]
        );

        Notification::make()
            ->title('Configurações salvas com sucesso!')
            ->success()
            ->send();
    }
}
