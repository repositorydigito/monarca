<?php

namespace App\Filament\Resources;

use App\Models\TimeEntryReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\TimeEntry;
use Illuminate\Validation\ValidationException;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Support\Enums\MaxWidth;
use App\Exports\TimeEntryReportExport;
use Maatwebsite\Excel\Facades\Excel;

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
        $livewire = $table->getLivewire();
        $filters = $livewire->tableFilters['date_range'] ?? [];
        
        \Log::info('1. Inicio del método table', [
            'filters' => $filters,
            'tiene_filtros' => isset($filters['from']) && isset($filters['until'])
        ]);

        // Columna base que siempre estará presente
        $columns = [
            Tables\Columns\TextColumn::make('user_name')
                ->label('Recurso')
                ->sortable()
                ->searchable()
                ->icon('heroicon-o-user'),
        ];

        // Solo agregamos columnas dinámicas si hay filtros aplicados
        if (isset($filters['from']) && isset($filters['until'])) {
            $startDate = Carbon::parse($filters['from'])->startOfDay();
            $endDate = Carbon::parse($filters['until'])->endOfDay();
            $dates = collect($startDate->copy()->daysUntil($endDate->copy()));

            foreach ($dates as $date) {
                $columns[] = Tables\Columns\TextColumn::make("day_{$date->format('Y_m_d')}")
                    ->label($date->format('d/m'))
                    ->numeric()
                    ->alignEnd()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' hrs')
                    ]);
            }

            $columns[] = Tables\Columns\TextColumn::make('total_hours')
                ->label('Total')
                ->numeric()
                ->alignEnd()
                ->summarize([
                    Tables\Columns\Summarizers\Sum::make()
                        ->label('Total General')
                        ->numeric(decimalPlaces: 2)
                        ->suffix(' hrs')
                ]);
        }

        return $table
            ->deferLoading()
            ->columns($columns)
            ->query(function (Builder $query) use ($filters): Builder {
                // Si no hay filtros, retornamos una query vacía
                if (!isset($filters['from']) || !isset($filters['until'])) {
                    return TimeEntryReport::query()->whereRaw('1 = 0');
                }

                $startDate = Carbon::parse($filters['from'])->startOfDay();
                $endDate = Carbon::parse($filters['until'])->endOfDay();
                $dates = collect($startDate->copy()->daysUntil($endDate->copy()));

                $dateColumns = $dates->map(function ($date) {
                    $dateStr = $date->toDateString();
                    return "SUM(CASE WHEN DATE(time_entries.date) = '{$dateStr}' THEN time_entries.hours ELSE 0 END) as day_{$date->format('Y_m_d')}";
                })->implode(', ');

                return TimeEntryReport::query()
                    ->join('users', 'users.id', '=', 'time_entries.user_id')
                    ->select([
                        'users.id',
                        'users.name as user_name',
                        DB::raw($dateColumns),
                        DB::raw('SUM(COALESCE(time_entries.hours, 0)) as total_hours'),
                    ])
                    ->whereBetween('time_entries.date', [$startDate, $endDate])
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('users.name');
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
                                        if (!$state || !$get('until')) return;
                                        
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
                                        if (!$state || !$get('from')) return;
                                        
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
                            ->columns(2)
                    ])
                    ->modalWidth('md')
                    ->modalHeading('Filtrar por Rango de Fechas')
                    ->modalDescription('Seleccione el rango de fechas para el reporte')
                    ->modalSubmitActionLabel('Aplicar Filtros')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (array $data) use ($livewire): void {
                        // Validación final antes de aplicar los filtros
                        if (!isset($data['from']) || !isset($data['until'])) {
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
                        if (!isset($filters['from']) || !isset($filters['until'])) {
                            Notification::make()
                                ->danger()
                                ->title('Error')
                                ->body('Debe aplicar un filtro de fechas antes de exportar')
                                ->send();
                            return;
                        }

                        return Excel::download(
                            new TimeEntryReportExport($filters['from'], $filters['until']),
                            'reporte_horas_' . date('Y-m-d') . '.xlsx'
                        );
                    })
                    ->visible(fn () => isset($filters['from']) && isset($filters['until']))
            ])
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