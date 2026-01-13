namespace App\Filament\Resources\MessageLogResource\Pages;

use App\Filament\Resources\MessageLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMessageLog extends ViewRecord
{
protected static string $resource = MessageLogResource::class;

protected function getHeaderActions(): array
{
return [
Actions\EditAction::make(),
];
}
}