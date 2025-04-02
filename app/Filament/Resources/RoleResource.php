<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roles';

    public static function form(Form $form): Form
    {
        // Obtener todos los permisos y agruparlos por módulo
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Dividir el nombre del permiso por guiones bajos
            $parts = explode('_', $permission->name);

            // Si el nombre tiene más de una parte, usar la última como módulo
            if (count($parts) > 1) {
                // Tomar la última palabra y capitalizarla
                $module = end($parts);

                return ucfirst($module);
            }

            // Si solo tiene una palabra, usar "General"
            return 'General';
        });

        // Crear el esquema de checkboxes agrupados
        $permissionCheckboxes = [];
        foreach ($permissions as $module => $modulePermissions) {
            // Ordenar los permisos alfabéticamente
            $sortedPermissions = $modulePermissions->sortBy('name');

            $permissionCheckboxes[] = Section::make($module)
                ->schema([
                    CheckboxList::make('permissions')
                        ->relationship('permissions', 'name')
                        ->options($sortedPermissions->pluck('name', 'id'))
                        ->columns(2)
                        ->gridDirection('row')
                        ->searchable()
                        ->bulkToggleable()
                        ->label(''),
                ])
                ->collapsible();
        }

        return $form
            ->schema([
                Section::make('Información General')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nombre'),
                        Forms\Components\TextInput::make('guard_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Guard'),
                        Toggle::make('can_view_all_users')
                            ->label('Puede ver todos los usuarios')
                            ->helperText('Si está activado, el usuario podrá ver y seleccionar todos los usuarios en el timesheet')
                            ->default(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $record) {
                                if ($record) {
                                    $record->forceFill(['can_view_all_users' => $state])->save();
                                }
                            }),
                    ]),
                Section::make('Permisos')
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('selectAll')
                                ->label('Seleccionar todos los permisos')
                                ->icon('heroicon-o-check-circle')
                                ->action(function (Get $get, Set $set) use ($permissions) {
                                    $allPermissionIds = $permissions->flatMap(function ($modulePermissions) {
                                        return $modulePermissions->pluck('id');
                                    })->toArray();

                                    $set('permissions', $allPermissionIds);
                                })
                                ->color('success'),
                        ]),
                        ...$permissionCheckboxes,
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),
                TextColumn::make('guard_name')
                    ->searchable()
                    ->sortable()
                    ->label('Guard'),
                IconColumn::make('can_view_all_users')
                    ->boolean()
                    ->sortable()
                    ->label('Ver todos los usuarios'),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->sortable()
                    ->label('Cantidad de Permisos'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creado'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actualizado'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
