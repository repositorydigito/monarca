<?php

namespace App\Filament\Resources\TimeEntryReportResource\Pages;

use App\Filament\Resources\TimeEntryReportResource;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntryReports extends ListRecords
{
    protected static string $resource = TimeEntryReportResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
