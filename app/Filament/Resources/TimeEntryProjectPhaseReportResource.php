<?php

namespace App\Filament\Resources;

use App\Exports\TimeEntryProjectPhaseReportExport;
use App\Models\TimeEntryProjectPhaseReport;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class TimeEntryProjectPhaseReportResource extends Resource
{
    protected static ?string $model = TimeEntryProjectPhaseReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Reportes';

    protected static ?string $navigationLabel = 'Reporte de Horas por Fase';

    protected static ?string $modelLabel = 'reporte de horas por fase';

    protected static ?string $pluralModelLabel = 'reportes de horas por fase';

    protected static ?string $slug = 'reportes-horas-fase';

    public static function table(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $filters = $livewire->tableFilters['date_range'] ?? [];

        // Columnas base que siempre estarán presentes
        $columns = [
            Tables\Columns\TextColumn::make('project_name')
                ->label('Locaciones')
                ->sortable()
                ->searchable()
                ->icon('heroicon-o-briefcase'),
        ];

        // Agregamos una columna por cada fase
        foreach (TimeEntryProjectPhaseReport::PHASES as $phaseKey => $phaseName) {
            $columns[] = Tables\Columns\TextColumn::make("phase_{$phaseKey}")
                ->label($phaseName)
                ->numeric()
                ->alignEnd()
                ->summarize([
                    Tables\Columns\Summarizers\Sum::make()
                        ->label('Total')
                        ->numeric(decimalPlaces: 2)
                        ->suffix(' hrs'),
                ]);
        }

        // Agregamos la columna de total
        $columns[] = Tables\Columns\TextColumn::make('total_hours')
            ->label('Total')
            ->numeric()
            ->alignEnd()
            ->summarize([
                Tables\Columns\Summarizers\Sum::make()
                    ->label('Total General')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' hrs'),
            ]);

        return $table
            ->deferLoading()
            ->columns($columns)
            ->query(function (Builder $query) use ($filters): Builder {
                // Si no hay filtros, retornamos una query vacía
                if (! isset($filters['from']) || ! isset($filters['until'])) {
                    return TimeEntryProjectPhaseReport::query()->whereRaw('1 = 0');
                }

                $startDate = Carbon::parse($filters['from'])->startOfDay();
                $endDate = Carbon::parse($filters['until'])->endOfDay();

                // Creamos las columnas para cada fase
                $phaseColumns = collect(TimeEntryProjectPhaseReport::PHASES)->map(function ($phaseName, $phaseKey) {
                    return "SUM(CASE WHEN time_entries.phase = '{$phaseKey}' THEN time_entries.hours ELSE 0 END) as phase_{$phaseKey}";
                })->implode(', ');

                $query = TimeEntryProjectPhaseReport::query()
                    ->join('projects', 'projects.id', '=', 'time_entries.project_id')
                    ->select([
                        'projects.id',
                        'projects.name as project_name',
                        DB::raw($phaseColumns),
                        DB::raw('SUM(COALESCE(time_entries.hours, 0)) as total_hours'),
                    ])
                    ->whereBetween('time_entries.date', [$startDate, $endDate])
                    ->groupBy('projects.id', 'projects.name')
                    ->orderBy('projects.name');

                // Registramos la consulta SQL
                Log::info('SQL Query del Reporte Proyecto-Fase:', [
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings(),
                    'fecha_inicio' => $startDate,
                    'fecha_fin' => $endDate,
                ]);

                return $query;
            })
            ->headerActions([
                Tables\Actions\Action::make('filter_dates')
                    ->form([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DatePicker::make('from')
                                    ->label('Desde')
                                    ->required()
                                    ->maxDate(now())
                                    ->minDate('2000-01-01')
                                    ->default(null)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if (! $state || ! $get('until')) {
                                            return;
                                        }

                                        if (Carbon::parse($state)->gt(Carbon::parse($get('until')))) {
                                            $set('from', null);
                                            Notification::make()
                                                ->danger()
                                                ->title('Error')
                                                ->body('La fecha inicial no puede ser mayor que la fecha final')
                                                ->send();
                                        }
                                    })
                                    ->columnSpan(1),

                                Forms\Components\DatePicker::make('until')
                                    ->label('Hasta')
                                    ->required()
                                    ->maxDate(now())
                                    ->minDate('2000-01-01')
                                    ->default(null)
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if (! $state || ! $get('from')) {
                                            return;
                                        }

                                        if (Carbon::parse($get('from'))->gt(Carbon::parse($state))) {
                                            $set('until', null);
                                            Notification::make()
                                                ->danger()
                                                ->title('Error')
                                                ->body('La fecha final no puede ser menor que la fecha inicial')
                                                ->send();
                                        }
                                    })
                                    ->columnSpan(1),
                            ])
                            ->columns(2),
                    ])
                    ->modalWidth('md')
                    ->modalHeading('Filtrar por Rango de Fechas')
                    ->modalDescription('Seleccione el rango de fechas para el reporte')
                    ->modalSubmitActionLabel('Aplicar Filtros')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (array $data) use ($livewire): void {
                        // Validación final antes de aplicar los filtros
                        if (! isset($data['from']) || ! isset($data['until'])) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Ambas fechas son requeridas')
                                ->send();

                            return;
                        }

                        $from = Carbon::parse($data['from']);
                        $until = Carbon::parse($data['until']);

                        if ($from->gt($until)) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('La fecha inicial no puede ser mayor que la fecha final')
                                ->send();

                            return;
                        }

                        $livewire->tableFilters['date_range'] = $data;
                        $livewire->resetTable();
                    })
                    ->label('Filtrar por Fechas')
                    ->button(),

                Tables\Actions\Action::make('download')
                    ->label('Exportar Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () use ($filters) {
                        if (! isset($filters['from']) || ! isset($filters['until'])) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Debe aplicar un filtro de fechas antes de exportar')
                                ->send();

                            return;
                        }

                        return Excel::download(
                            new TimeEntryProjectPhaseReportExport($filters['from'], $filters['until']),
                            'reporte_horas_fase_'.date('Y-m-d').'.xlsx'
                        );
                    })
                    ->visible(fn () => isset($filters['from']) && isset($filters['until'])),
            ])
            ->striped()
            ->paginated([5, 10, 25, 50, 100, 'all']);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\TimeEntryProjectPhaseReportResource\Pages\ListTimeEntryProjectPhaseReports::route('/'),
        ];
    }
}
