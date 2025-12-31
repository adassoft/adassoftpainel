<?php

namespace App\Filament\Reseller\Resources;

use App\Filament\Reseller\Resources\LicenseResource\Pages;
use App\Filament\Reseller\Resources\LicenseResource\RelationManagers;
use App\Models\License;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $modelLabel = 'Licença';
    protected static ?string $pluralModelLabel = 'Gerenciamento de Licenças';
    protected static ?string $navigationLabel = 'Licenças dos Clientes';

    // Ensure reseller sees only their licenses
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('cnpj_revenda', auth()->user()->cnpj);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes da Licença')
                    ->schema([
                        Forms\Components\TextInput::make('company.razao')
                            ->label('Cliente')
                            ->disabled(),
                        Forms\Components\TextInput::make('software.nome_software')
                            ->label('Software')
                            ->disabled(),
                        Forms\Components\DatePicker::make('data_expiracao')
                            ->label('Nova Validade')
                            ->required(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.razao')
                    ->label('Cliente')
                    ->description(fn(License $record) => $record->company?->cnpj ?? '-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->description(fn(License $record) => 'v' . ($record->software?->versao ?? '?'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_expiracao')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn(License $record) => \Carbon\Carbon::parse($record->data_expiracao)->isPast() ? 'danger' : (\Carbon\Carbon::parse($record->data_expiracao)->diffInDays(now()) <= 15 ? 'warning' : 'success'))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('terminais')
                    ->label('Terminais')
                    ->alignCenter()
                    ->getStateUsing(fn(License $record) => "{$record->terminais_utilizados} / {$record->terminais_permitidos}"),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'ativo' => 'success',
                        'suspenso' => 'warning',
                        'expirado' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state))
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ativo' => 'Ativo',
                        'suspenso' => 'Suspenso',
                        'expirado' => 'Expirado',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('renovar')
                    ->label('')
                    ->icon('heroicon-m-arrow-path')
                    ->color('primary')
                    ->button()
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->tooltip('Renovar Licença')
                    ->action(fn() => \Filament\Notifications\Notification::make()->title('Funcionalidade em desenvolvimento')->send()),

                Tables\Actions\Action::make('instalações')
                    ->label('')
                    ->icon('heroicon-m-computer-desktop')
                    ->color('info')
                    ->button()
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->tooltip('Gerenciar Terminais')
                    ->action(fn() => \Filament\Notifications\Notification::make()->title('Funcionalidade em desenvolvimento')->send()),

                Tables\Actions\Action::make('token')
                    ->label('')
                    ->icon('heroicon-m-clipboard')
                    ->color('warning')
                    ->button()
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->tooltip('Copiar Token')
                    ->action(fn($record) => \Filament\Notifications\Notification::make()->title('Token copiado!')->body($record->serial_atual)->success()->send()),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLicenses::route('/'),
            'create' => Pages\CreateLicense::route('/create'),
            'edit' => Pages\EditLicense::route('/{record}/edit'),
        ];
    }
}
