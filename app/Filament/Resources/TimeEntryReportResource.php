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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Recurso')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total de Horas')
                    ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total de Horas')
                            ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs'),
                    ]),
            ])
            ->query(function (Builder $query) {
                $startDate = request('tableFilters.date_range.from') ?? now()->startOfMonth()->toDateString();
                $endDate = request('tableFilters.date_range.until') ?? now()->toDateString();

                return TimeEntryReport::query()
                    ->join('users', 'users.id', '=', 'time_entries.user_id')
                    ->whereRaw('DATE(time_entries.date) BETWEEN ? AND ?', [
                        $startDate,
                        $endDate
                    ])
                    ->groupBy('users.id', 'users.name')
                    ->select([
                        'users.id',
                        'users.name as user_name',
                        DB::raw('SUM(COALESCE(time_entries.hours, 0)) as total_hours'),
                    ])
                    ->orderBy('users.name');
            })
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->default(fn() => now()->startOfMonth())
                            ->format('Y-m-d')
                            ->required(),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->default(now())
                            ->format('Y-m-d')
                            ->required(),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['from'] || ! $data['until']) {
                            return null;
                        }

                        return Carbon::parse($data['from'])->format('d/m/Y') . ' - ' .
                            Carbon::parse($data['until'])->format('d/m/Y');
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('time_entries.date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('time_entries.date', '<=', $date),
                            );
                    }),
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
