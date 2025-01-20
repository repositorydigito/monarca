<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationGroup = 'Proyectos';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles del Proyecto')
                    ->description('Información básica del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ingrese el nombre del proyecto')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('code')
                                    ->label('Código Proyecto')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: PROJ-001'),

                                Forms\Components\Select::make('entity_id')
                                    ->label('Entidad')
                                    ->relationship('entity', 'business_name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\Select::make('business_line_id')
                                    ->label('Linea de Negocio')
                                    ->relationship('businessLine', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripcion')
                                            ->maxLength(65535),
                                    ])
                            ]),
                    ]),

                Forms\Components\Section::make('Fechas del Proyecto')
                    ->description('Establezca el período del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Fecha de Inicio')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection(),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Fecha de Finalización')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->closeOnDateSelection()
                                    ->afterOrEqual('start_date')
                                    ->rules(['after_or_equal:start_date']),
                            ]),
                    ]),

                Forms\Components\Section::make('Descripción del Proyecto')
                    ->description('Detalle la información del proyecto')
                    ->collapsible()
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Descripción')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable() // Ordenamiento por Nombre
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código Proyecto')
                    ->sortable() // Ordenamiento por Código de Proyecto
                    ->searchable(),
                Tables\Columns\TextColumn::make('entity.business_name')
                    ->label('Entidad')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de Inicio')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                // Aquí puedes agregar filtros si es necesario
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relacionamientos si es necesario
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return 'Proyecto';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Proyectos';
    }
}
