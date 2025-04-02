<?php

namespace App\Filament\Resources\SalesTargetResource\Pages;

use App\Filament\Resources\SalesTargetResource;
use App\Models\BusinessLine;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesTargets extends ListRecords
{
    protected static string $resource = SalesTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected static function canDeleteBusinessLine(BusinessLine $line): bool
    {
        // Lógica para determinar si la línea de negocio puede ser eliminada
        return true; // Cambia esto según tus reglas de negocio
    }

    public function deleteLine($lineId)
    {
        $line = BusinessLine::find($lineId);

        if ($line && static::canDeleteBusinessLine($line)) {
            $line->salesTargets()->delete();
            $line->delete();

            Notification::make()
                ->title('Línea eliminada correctamente')
                ->success()
                ->send();
        }
    }
}
