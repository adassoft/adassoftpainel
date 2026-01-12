<?php

namespace App\Filament\Reseller\Resources;

use App\Filament\Reseller\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Services\LicenseManager;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Placeholder;
use Livewire\Component;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Meus Pedidos';

    protected static ?string $modelLabel = 'Pedido';
    protected static ?string $pluralModelLabel = 'Meus Pedidos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) =>
                $query->where('cnpj_revenda', Auth::user()->cnpj)
            )
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\View::make('filament.reseller.resources.order-resource.columns.order-card'),
            ])
            ->defaultSort('id', 'desc')
            ->defaultPaginationPageOption(12)
            ->paginated([12, 24, 36, 48, 'all'])
            ->actions([
                Action::make('liberar')
                    ->label('Liberar Licença')
                    ->icon('heroicon-o-key')
                    ->color('info')
                    ->button()
                    ->extraAttributes(['style' => 'display: none !important'])
                    ->visible(fn(Order $record) => strtolower($record->status) === 'pago' && $record->status_entrega !== 'entregue')
                    ->requiresConfirmation()
                    ->modalHeading('Liberar Licença Manualmente')
                    ->modalDescription('O valor do plano será debitado do seu saldo se ainda não foi. Deseja continuar?')
                    ->modalSubmitActionLabel('Sim, Liberar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Order $record) {
                        try {
                            DB::beginTransaction();
                            (new LicenseManager())->processarEntregaPedido($record);
                            DB::commit();
                            Notification::make()->title('Licença liberada com sucesso!')->success()->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()->title('Erro ao liberar licença')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('receber')
                    ->label('Receber (Baixar)')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->button()
                    ->extraAttributes(['style' => 'display: none !important'])
                    ->visible(fn(Order $record) => strtolower($record->status) !== 'pago' && strtolower($record->status) !== 'cancelado')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Recebimento')
                    ->modalDescription('O pedido será marcado como PAGO, o saldo será debitado e a licença liberada. Confirma?')
                    ->modalSubmitActionLabel('Sim, Receber')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Order $record) {
                        try {
                            DB::beginTransaction();
                            (new LicenseManager())->processarEntregaPedido($record);
                            DB::commit();
                            Notification::make()->title('Recebimento confirmado e licença liberada!')->success()->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()->title('Erro ao receber pedido')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOrders::route('/'),
        ];
    }
}
