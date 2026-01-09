<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;

class ManageSeo extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Configurações de SEO';

    protected static ?string $title = 'Configurações de SEO';

    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.manage-seo';

    public ?array $data = [];

    public function mount(): void
    {
        $config = Configuration::where('chave', 'seo_config')->first();

        if ($config) {
            $this->form->fill(json_decode($config->valor, true));
        } else {
            $this->form->fill([
                'site_name' => 'Adassoft Store',
                'site_title' => 'Loja Oficial',
                'default_image' => 'img/seo_default.jpg',
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Metadados Globais da Loja')
                    ->extraAttributes(['class' => 'seo-section'])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('site_name')
                                    ->label('Nome do Site / Loja')
                                    ->required()
                                    ->helperText('Aparece após o título (ex: Produto | Adassoft Store).'),

                                TextInput::make('site_title')
                                    ->label('Título Padrão da Home')
                                    ->required(),
                            ]),

                        Textarea::make('site_description')
                            ->label('Descrição Global (Meta Description)')
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText('Resumo curto (até 160 caracteres) que aparece nos resultados do Google.'),

                        TextInput::make('keywords')
                            ->label('Palavras-chave (Keywords)')
                            ->placeholder('software, pdv, gestão...')
                            ->helperText('Separadas por vírgula. (O Google ignora, mas outros buscadores usam).'),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('default_image')
                                    ->label('URL da Imagem Padrão (Open Graph)')
                                    ->columnSpan(2)
                                    ->helperText('Caminho relativo (img/logo.jpg) ou URL completa. Usada quando a página não tem imagem específica.'),

                                TextInput::make('twitter_handle')
                                    ->label('Twitter / X Handle')
                                    ->placeholder('@seuusuario'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Configuration::updateOrCreate(
            ['chave' => 'seo_config'],
            ['valor' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)]
        );

        Notification::make()
            ->title('Configurações salvas com sucesso!')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Salvar Alterações')
                ->submit('save'),
        ];
    }
}
