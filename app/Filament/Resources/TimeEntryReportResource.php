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
    protected static ?string $navigationLabel = 'Reporte de Horas por Usuario';
    protected static ?string $modelLabel = 'reporte de horas por usuario';
    protected static ?string $pluralModelLabel = 'reportes de horas por usuario';
    protected static ?string $slug = 'reportes-horas-usuario';

    public static function table(Table $table): Table
    {
        $baseQuery = TimeEntryReport::query()
            ->join('users', 'users.id', '=', 'time_entries.user_id')
            ->join('projects', 'projects.id', '=', 'time_entries.project_id');

        $userProjectQuery = clone $baseQuery;
        $userProjectQuery->select([
            DB::raw('MIN(time_entries.id) as id'),
            'users.name as user_name',
            'projects.name as project_name',
            'projects.code as project_code',
            DB::raw('GROUP_CONCAT(DISTINCT time_entries.phase ORDER BY time_entries.phase SEPARATOR ", ") as phases'),
            DB::raw('SUM(time_entries.hours) as total_hours'),
            DB::raw('MIN(time_entries.date) as start_date'),
            DB::raw('MAX(time_entries.date) as end_date'),
            'users.id as user_id'
        ])
            ->groupBy('users.id', 'users.name', 'projects.id', 'projects.name', 'projects.code')
            ->orderBy('users.name')
            ->orderBy('projects.name');

        return $table
            ->query($userProjectQuery)
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Recurso')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-user')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('project_name')
                    ->label('Proyecto')
                    ->description(fn($record) => $record->project_code)
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-briefcase'),

                Tables\Columns\TextColumn::make('phases')
                    ->label('Fases')
                    ->icon('heroicon-o-rectangle-stack')
                    ->wrap(),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total Horas')
                    ->numeric(2)
                    ->sortable()
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
                    ->icon('heroicon-o-clock')
                    ->color('success')
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
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
                    }),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Recurso')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('phase')
                    ->label('Fase')
                    ->options([
                        'inicio' => 'Inicio',
                        'planificacion' => 'Planificación',
                        'ejecucion' => 'Ejecución',
                        'control' => 'Control',
                        'cierre' => 'Cierre',
                    ])
                    ->multiple(),
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
