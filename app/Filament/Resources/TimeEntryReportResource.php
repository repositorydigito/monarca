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
use Illuminate\Support\Facades\Log;

class TimeEntryReportResource extends Resource
{
    protected static ?string $model = TimeEntryReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte de Horas';
    protected static ?string $modelLabel = 'reporte de horas';
    protected static ?string $pluralModelLabel = 'reportes de horas';
    protected static ?string $slug = 'reportes-horas';



    public static function table(Table $table): Table
    {
        return $table
            ->query(
                TimeEntryReport::query()
                    ->select([
                        DB::raw('MIN(time_entries.id) as id'),
                        'users.name as user_name',
                        'projects.name as project_name',
                        'projects.code as project_code',
                        'time_entries.phase',
                        DB::raw('SUM(time_entries.hours) as total_hours'),
                        DB::raw('COUNT(DISTINCT DATE(time_entries.date)) as total_days'),
                        DB::raw('MIN(time_entries.date) as start_date'),
                        DB::raw('MAX(time_entries.date) as end_date')
                    ])
                    ->join('users', 'users.id', '=', 'time_entries.user_id')
                    ->join('projects', 'projects.id', '=', 'time_entries.project_id')
                    ->groupBy('users.name', 'projects.name', 'projects.code', 'time_entries.phase')
                    ->orderBy('users.name')
                    ->orderBy('projects.name')
                    ->orderBy('time_entries.phase')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-user')
                    ->toggleable()
                    ->extraAttributes(['class' => 'text-primary-600']),

                Tables\Columns\TextColumn::make('project_name')
                    ->label('Proyecto')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-briefcase')
                    ->toggleable()
                    ->description(fn($record): string => $record->project_code),

                Tables\Columns\TextColumn::make('phase')
                    ->label('Fase')
                    ->formatStateUsing(fn($state) => TimeEntryReport::PHASES[$state] ?? $state)
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'inicio' => 'info',
                        'planificacion' => 'warning',
                        'ejecucion' => 'success',
                        'control' => 'danger',
                        'cierre' => 'gray',
                        default => 'primary',
                    })
                    ->icon('heroicon-o-flag')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total Horas')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-clock')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
                    ]),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Días Trabajados')
                    ->numeric(0)
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-calendar-days')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total Días')
                    ]),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->icon('heroicon-o-calendar'),
            ])
            ->groups([
                Tables\Grouping\Group::make('project_name')
                    ->label('Proyecto')
                    ->collapsible(),
                Tables\Grouping\Group::make('user_name')
                    ->label('Usuario')
                    ->collapsible(),
                Tables\Grouping\Group::make('phase')
                    ->label('Fase')
                    ->collapsible(),
            ])
            ->defaultGroup('project_name')
            ->filters([
                Tables\Filters\SelectFilter::make('phase')
                    ->label('Fase')
                    ->options(TimeEntryReport::PHASES)
                    ->multiple()
                    ->indicator('Fases'),

                Tables\Filters\Filter::make('date_range')
                    ->label('Rango de Fechas')
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
                        if (! $data['from'] && ! $data['until']) {
                            return null;
                        }

                        if (! $data['until']) {
                            return 'Desde ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }

                        if (! $data['from']) {
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
            ->filtersFormColumns(3)
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
