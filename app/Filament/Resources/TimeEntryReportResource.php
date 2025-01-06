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
use Carbon\CarbonPeriod;

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
        $startDate = request('tableFilters.date_range.from') ?? now()->startOfMonth();
        $endDate = request('tableFilters.date_range.until') ?? now();

        // Columnas base
        $columns = [
            Tables\Columns\TextColumn::make('user_name')
                ->label('Recurso')
                ->sortable()
                ->searchable()
                ->icon('heroicon-o-user')
                ->weight('bold'),
        ];

        // Agregar columnas para cada fecha en el rango
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $formattedDate = $date->format('Y-m-d');
            $dateKey = "day_" . $date->format('Ymd');

            $columns[] = Tables\Columns\TextColumn::make($dateKey)
                ->label($date->format('d/m/Y'))
                ->numeric(2)
                ->formatStateUsing(fn($state) => $state ? number_format($state, 2) . ' hrs' : '-')
                ->alignEnd()
                ->summarize([
                    Tables\Columns\Summarizers\Sum::make()
                        ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
                ]);
        }

        // Agregar columna de total
        $columns[] = Tables\Columns\TextColumn::make('total_hours')
            ->label('Total')
            ->numeric(2)
            ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
            ->icon('heroicon-o-clock')
            ->color('success')
            ->alignEnd()
            ->summarize([
                Tables\Columns\Summarizers\Sum::make()
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
            ]);

        return $table
            ->query(function (Builder $query) use ($startDate, $endDate) {
                $dateColumns = collect(CarbonPeriod::create($startDate, $endDate))
                    ->map(fn($date) => [
                        'date' => $date->format('Y-m-d'),
                        'key' => "day_" . $date->format('Ymd')
                    ])
                    ->reduce(function ($carry, $date) {
                        $carry[] = DB::raw("COALESCE(SUM(CASE WHEN DATE(time_entries.date) = '{$date['date']}' THEN time_entries.hours ELSE 0 END), 0) as `{$date['key']}`");
                        return $carry;
                    }, []);

                return TimeEntryReport::query()
                    ->join('users', 'users.id', '=', 'time_entries.user_id')
                    ->whereBetween('time_entries.date', [$startDate, $endDate])
                    ->groupBy('users.id', 'users.name')
                    ->select([
                        'users.id',
                        'users.name as user_name',
                        ...$dateColumns,
                        DB::raw('SUM(time_entries.hours) as total_hours')
                    ])
                    ->orderBy('users.name');
            })
            ->columns($columns)
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->default(fn() => now()->startOfMonth())
                            ->native(false)
                            ->required(),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->default(now())
                            ->native(false)
                            ->required(),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['from'] && !$data['until']) {
                            return null;
                        }
                        return Carbon::parse($data['from'])->format('d/m/Y') . ' - ' . Carbon::parse($data['until'])->format('d/m/Y');
                    }),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Recurso')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple()
            ])
            ->filtersFormColumns(2)
            ->filtersTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Filtros')
                    ->icon('heroicon-o-funnel'),
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
