<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeEntryResource\Pages;
use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Personalización de etiquetas
    protected static ?string $label = 'hoja de tiempo'; // Singular
    protected static ?string $pluralLabel = 'hojas de tiempo'; // Plural
    protected static ?string $navigationLabel = 'Ingreso Manual'; // Menú de navegación

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make([
                    Forms\Components\Select::make('project_id')
                        ->label('Proyecto')
                        ->relationship('project', 'name')
                        ->required()
                        ->placeholder('Selecciona un proyecto')
                        ->prefixIcon('heroicon-o-briefcase'),
                    Forms\Components\Hidden::make('user_id')
                        ->default(auth()->id()) // Asigna el usuario autenticado
                        ->required(),
                    Forms\Components\DatePicker::make('date')
                        ->label('Fecha')
                        ->required()
                        ->prefixIcon('heroicon-o-calendar'),
                    Forms\Components\TextInput::make('hours')
                        ->label('Horas trabajadas')
                        ->required()
                        ->numeric()
                        ->prefixIcon('heroicon-o-clock'),
                    Forms\Components\Select::make('phase')
                        ->label('Fase')
                        ->options(TimeEntry::PHASES) // Usa la constante PHASES del modelo
                        ->required()
                        ->placeholder('Selecciona una fase')
                        ->prefixIcon('heroicon-o-chart-bar'),
                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->placeholder('Agrega una descripción (opcional)')
                        ->columnSpanFull()
                        ->rows(4),
                ])
                    ->columns(2) // Divide los campos en 2 columnas para mejor estilo
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hours')
                    ->label('Horas trabajadas')
                    ->numeric()
                    ->sortable()
                    ->color('success'), // Color verde para destacar valores
                Tables\Columns\BadgeColumn::make('phase') // Usa badges para fases
                    ->label('Fase')
                    ->formatStateUsing(fn($state) => TimeEntry::PHASES[$state] ?? $state) // Traducción amigable
                    ->colors([
                        'primary' => 'inicio',
                        'warning' => 'planificacion',
                        'success' => 'ejecucion',
                        'danger' => 'control',
                        'secondary' => 'cierre',
                    ]), // Colores según el estado
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name')
                    ->placeholder('Todos los proyectos'),
                Filter::make('date')
                    ->label('Fecha')
                    ->form([
                        Forms\Components\DatePicker::make('start')->label('Desde'),
                        Forms\Components\DatePicker::make('end')->label('Hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['start'] ?? null) {
                            $query->where('date', '>=', $data['start']);
                        }
                        if ($data['end'] ?? null) {
                            $query->where('date', '<=', $data['end']);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
