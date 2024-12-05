<?php

namespace App\Filament\Resources;

use App\Models\TimeEntryReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TimeEntryReportResource extends Resource
{
    protected static ?string $model = TimeEntryReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Reportes';
    protected static ?string $navigationLabel = 'Reporte por Usuario';
    protected static ?string $modelLabel = 'reporte por usuario';
    protected static ?string $pluralModelLabel = 'reportes por usuario';
    protected static ?string $slug = 'reportes-usuario';

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                TimeEntryReport::query()
                    ->join('users', 'users.id', '=', 'time_entries.user_id')
                    ->join('projects', 'projects.id', '=', 'time_entries.project_id')
                    ->select([
                        'time_entries.id',
                        'users.name as user_name',
                        'projects.name as project_name',
                        'time_entries.phase',
                        DB::raw('YEAR(time_entries.date) as year'),
                        DB::raw('MONTH(time_entries.date) as month'),
                        DB::raw('SUM(time_entries.hours) as total_hours'),
                        DB::raw('(SUM(time_entries.hours) / (SELECT SUM(hours) FROM time_entries WHERE user_id = users.id AND YEAR(date) = YEAR(time_entries.date) AND MONTH(date) = MONTH(time_entries.date)) * 100) as dedication_percentage')
                    ])
                    ->groupBy('time_entries.id', 'users.id', 'users.name', 'projects.name', 'phase', 'year', 'month')
                    ->orderBy('user_name')
                    ->orderBy('year')
                    ->orderBy('month')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('project_name')
                    ->label('Proyecto')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-o-briefcase'),

                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('month')
                    ->label('Mes')
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::create()->month($state)->format('F'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('phase')
                    ->label('Fase')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'inicio' => 'info',
                        'planificacion' => 'warning',
                        'ejecucion' => 'success',
                        'control' => 'danger',
                        'cierre' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total Horas')
                    ->numeric(2)
                    ->sortable()
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('Total')
                            ->formatStateUsing(fn($state) => number_format($state, 2) . ' hrs')
                    ]),

                Tables\Columns\TextColumn::make('dedication_percentage')
                    ->label('% Dedicación')
                    ->numeric(2)
                    ->formatStateUsing(fn($state) => number_format($state, 2) . '%')
                    ->sortable()
                    ->alignEnd(),
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
                    })
            ])
            ->filtersFormColumns(2)
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\TimeEntryReportResource\Pages\ListTimeEntryReports::route('/'),
        ];
    }
}
