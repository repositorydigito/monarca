<?php

namespace App\Filament\Resources\TimeEntryProjectReportResource\Pages;

use App\Filament\Resources\TimeEntryProjectReportResource;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntryProjectReports extends ListRecords
{
    protected static string $resource = TimeEntryProjectReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
