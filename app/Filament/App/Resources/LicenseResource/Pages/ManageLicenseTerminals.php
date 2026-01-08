<?php

namespace App\Filament\App\Resources\LicenseResource\Pages;

use App\Filament\App\Resources\LicenseResource;
use App\Models\License;
use App\Models\Terminal;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ManageLicenseTerminals extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = LicenseResource::class;

    protected static string $view = 'filament.app.resources.license-resource.pages.manage-license-terminals';

    public function getTitle(): string|Htmlable
    {
        return 'Terminais Vinculados - ' . ($this->record->software->nome_software ?? 'Licença');
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Terminal::query()
                    ->join('terminais_software', 'terminais.CODIGO', '=', 'terminais_software.terminal_codigo')
                    ->where('terminais_software.licenca_id', $this->record->id)
                    ->where('terminais_software.ativo', 1)
                    ->select('terminais.*', 'terminais_software.ultima_atividade', 'terminais_software.ativo as status_vinculo')
            )
            ->columns([
                Tables\Columns\TextColumn::make('NOME_COMPUTADOR')
                    ->label('Computador')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('MAC')
                    ->label('MAC Address')
                    ->searchable()
                    ->fontFamily(\Filament\Support\Enums\FontFamily::Mono),
                Tables\Columns\TextColumn::make('ultima_atividade')
                    ->label('Última Atividade')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('desvincular')
                    ->label('Desvincular')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Desvincular Terminal')
                    ->modalDescription('Tem certeza que deseja desvincular este terminal? Ele perderá o acesso ao software imediatamente.')
                    ->action(function (Terminal $record) {
                        // $record aqui é uma instância de Terminal (mas vinda do join)
                        // Precisamos desvincular usando a tabela pivot
                        \Illuminate\Support\Facades\DB::table('terminais_software')
                            ->where('terminal_codigo', $record->CODIGO)
                            ->where('licenca_id', $this->record->id)
                            ->update(['ativo' => 0]);

                        \Filament\Notifications\Notification::make()
                            ->title('Terminal desvinculado')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }
}
