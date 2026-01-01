<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages;
use App\Filament\Resources\RedirectResource\RelationManagers;
use App\Models\Redirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $navigationGroup = 'Sistema e Site';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('path')
                    ->label('Caminho Antigo (ex: /meu-post)')
                    ->required()
                    ->prefix('/')
                    ->helperText('O caminho da URL que será redirecionada. Não inclua o domínio.'),
                Forms\Components\TextInput::make('target_url')
                    ->label('Nova URL de Destino')
                    ->required()
                    ->url() // Valida se é URL. Se quiser rotas internas, remova ->url() e trate.
                    ->helperText('A URL completa para onde o usuário será enviado.'),
                Forms\Components\Select::make('status_code')
                    ->label('Tipo de Redirecionamento')
                    ->options([
                        301 => '301 - Permanente (Recomendado para SEO)',
                        302 => '302 - Temporário',
                    ])
                    ->default(301)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('path')
                    ->label('De (Path)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_url')
                    ->label('Para (URL)')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_code')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '301' => 'success',
                        '302' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListRedirects::route('/'),
            'create' => Pages\CreateRedirect::route('/create'),
            'edit' => Pages\EditRedirect::route('/{record}/edit'),
        ];
    }
}
