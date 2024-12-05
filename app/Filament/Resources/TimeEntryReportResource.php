<?php

namespace App\Filament\Resources;

use App\Models\TimeEntryReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Enums\FiltersLayout;

class TimeEntryReportResource extends Resource
{
    protected static ?string $model = TimeEntryReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte Gerencial de Horas';
    protected static ?string $modelLabel = 'reporte gerencial de horas';
    protected static ?string $pluralModelLabel = 'reportes gerenciales de horas';
    protected static ?string $slug = 'reportes-gerenciales';

    public static function table(Table $table): Table
    {
        $baseQuery = TimeEntryReport::query()
            ->join('users', 'users.id', '=', 'time_entries.user_id')
            ->join('projects', 'projects.id', '=', 'time_entries.project_id');

        // Project grouping query with nested resources
        $projectQuery = clone $baseQuery;
        $projectQuery->select([
            'projects.id',
            'projects.name as project_name',
            'projects.code as project_code',
            'users.id as user_id',
            'users.name as user_name',
            DB::raw('SUM(time_entries.hours) as total_hours'),
            DB::raw('COUNT(DISTINCT DATE(time_entries.date)) as total_days'),
            DB::raw('SUM(time_entries.hours) / COUNT(DISTINCT DATE(time_entries.date)) as average_hours_per_day'),
            DB::raw('MIN(time_entries.date) as start_date'),
            DB::raw('MAX(time_entries.date) as end_date'),
            DB::raw('CONCAT(projects.name, " (", projects.code, ")") as project_display_name'),
            DB::raw('NULL as parent_summary')
        ])
            ->groupBy('projects.id', 'projects.name', 'projects.code', 'users.id', 'users.name')
            ->orderBy('projects.name')
            ->orderBy('users.name');

        // Resource grouping query with nested projects
        $resourceQuery = clone $baseQuery;
        $resourceQuery->select([
            'users.id',
            'users.name as user_name',
            'projects.id as project_id',
            'projects.name as project_name',
            'projects.code as project_code',
            DB::raw('SUM(time_entries.hours) as total_hours'),
            DB::raw('COUNT(DISTINCT DATE(time_entries.date)) as total_days'),
            DB::raw('SUM(time_entries.hours) / COUNT(DISTINCT DATE(time_entries.date)) as average_hours_per_day'),
            DB::raw('MIN(time_entries.date) as start_date'),
            DB::raw('MAX(time_entries.date) as end_date'),
            DB::raw('CONCAT(projects.name, " (", projects.code, ")") as project_display_name'),
            DB::raw('NULL as parent_summary')
        ])
            ->groupBy('users.id', 'users.name', 'projects.id', 'projects.name', 'projects.code')
            ->orderBy('users.name')
            ->orderBy('projects.name');

        return $table
            ->query(function () use ($projectQuery, $resourceQuery, $table) {
                return $table->getGrouping()?->getId() === 'project_name' ? $projectQuery : $resourceQuery;
            })
            ->columns([
                // Project/Resource Name Column
                Tables\Columns\TextColumn::make('project_name')
                    ->label(fn(Table $table) => $table->getGrouping()?->getId() === 'project_name' ? 'Proyecto' : 'Recurso')
                    ->formatStateUsing(function ($state, $record, Table $table) {
                        if ($table->getGrouping()?->getId() === 'project_name') {
                            return $record->project_display_name;
                        }
                        return $record->user_name;
                    })
                    ->description(function ($record, Table $table) {
                        if ($table->getGrouping()?->getId() === 'project_name') {
                            return $record->user_name;
                        }
                        return $record->project_display_name;
                    })
                    ->sortable()
                    ->searchable()
                    ->icon(fn(Table $table) => $table->getGrouping()?->getId() === 'project_name' ? 'heroicon-o-briefcase' : 'heroicon-o-user')
                    ->weight('bold'),

                // Metrics Columns
                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total Horas')
                    ->numeric(2)
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->color('success')
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
                    ]),

                Tables\Columns\TextColumn::make('average_hours_per_day')
                    ->label('Promedio Hrs/Día')
                    ->numeric(2)
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
                    ->icon('heroicon-o-chart-bar')
                    ->color('warning')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Días Trabajados')
                    ->numeric(0)
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Días')
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->groups([
                Tables\Grouping\Group::make('project_name')
                    ->label('Por Proyecto')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false),
                Tables\Grouping\Group::make('user_name')
                    ->label('Por Recurso')
                    ->collapsible()
                    ->titlePrefixedWithLabel(false)
            ])
            ->defaultGroup('project_name')
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->default(fn() => now()->startOfMonth())
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->default(now())
                            ->native(false),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['from'] && !$data['until']) {
                            return null;
                        }

                        if (!$data['until']) {
                            return 'Desde ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }

                        if (!$data['from']) {
                            return 'Hasta ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }

                        return \Carbon\Carbon::parse($data['from'])->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['from'],
                            fn(Builder $query, $date): Builder => $query->whereDate('time_entries.date', '>=', $date),
                        )->when(
                            $data['until'],
                            fn(Builder $query, $date): Builder => $query->whereDate('time_entries.date', '<=', $date),
                        );
                    })
            ])
            ->filtersFormColumns(2)
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros')
                    ->icon('heroicon-o-funnel'),
            )
            ->toggleColumnsTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Columnas')
                    ->icon('heroicon-o-view-columns'),
            )
            ->striped()
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\TimeEntryReportResource\Pages\ListTimeEntryReports::route('/'),
        ];
    }
}
