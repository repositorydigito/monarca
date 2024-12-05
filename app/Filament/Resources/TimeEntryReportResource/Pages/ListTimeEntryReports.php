<?php

namespace App\Filament\Resources\TimeEntryReportResource\Pages;

use App\Filament\Resources\TimeEntryReportResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListTimeEntryReports extends ListRecords
{
    protected static string $resource = TimeEntryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download')
                ->label('Exportar Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Aquí puedes implementar la lógica de exportación si lo deseas
                })
        ];
    }
}
