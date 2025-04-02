<?php

namespace App\Filament\Pages;

use App\Models\Project;
use App\Models\TimeEntry;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Timesheet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Hojas de Tiempo';

    protected static ?string $title = 'Hojas de Tiempo';

    public $selectedCell = null;

    protected static string $view = 'filament.pages.timesheet';

    public $currentDate;

    public $selectedProject;

    public $selectedDay;

    public $entries;

    public $phaseHours = [];

    public $selectedUserId;

    public $users = [];

    public $phases = [
        'dia' => [
            'name' => 'Turno Día',
            'color' => 'bg-blue-400',
        ],
        'noche' => [
            'name' => 'Turno Noche',
            'color' => 'bg-blue-200',
        ],
        'madrugada' => [
            'name' => 'Turno Madrugada',
            'color' => 'bg-green-400',
        ],
    ];

    public function mount(): void
    {
        $this->currentDate = now();
        $user = auth()->user();
        $canViewAllUsers = $user->roles->contains(function ($role) {
            /** @var \App\Models\Role $role */
            return $role->can_view_all_users === true;
        });
        $this->selectedUserId = $canViewAllUsers ? null : $user->id;
        $this->users = \App\Models\User::pluck('name', 'id')->toArray();
        $this->loadEntries();
    }

    protected function getFormSchema(): array
    {
        $user = auth()->user();
        $canViewAllUsers = $user->roles->contains(function ($role) {
            /** @var \App\Models\Role $role */
            return $role->can_view_all_users === true;
        });
        $schema = [
            Select::make('user_id')
                ->label('Usuario')
                ->relationship('user', 'name')
                ->required()
                ->disabled(! $canViewAllUsers)
                ->default($canViewAllUsers ? null : $user->id),
            Select::make('project_id')
                ->label('Proyecto')
                ->relationship('project', 'name')
                ->required(),
            DatePicker::make('date')
                ->label('Fecha')
                ->required(),
            Repeater::make('hours')
                ->label('Horas')
                ->schema([
                    TextInput::make('phase')
                        ->label('Fase')
                        ->required(),
                    TextInput::make('hours')
                        ->label('Horas')
                        ->numeric()
                        ->required(),
                    Textarea::make('description')
                        ->label('Descripción')
                        ->required(),
                ])
                ->columns(3),
        ];

        return $schema;
    }

    public function selectCell($projectId, $day): void
    {
        Log::info('SelectCell llamado con:', [
            'projectId' => $projectId,
            'day' => $day,
        ]);

        $this->selectedProject = $projectId;
        $this->selectedDay = $day;
        $this->selectedCell = [
            'project_id' => $projectId,
            'day' => $day,
        ];

        Log::info('Estado después de selectCell:', [
            'selectedProject' => $this->selectedProject,
            'selectedDay' => $this->selectedDay,
            'selectedCell' => $this->selectedCell,
        ]);
    }

    public function updateSelectedUser($userId): void
    {
        $this->selectedUserId = $userId;
        $this->loadEntries();
    }

    public function getActions(): array
    {
        return [
            Action::make('timeEntry')
                ->label('Gestionar Horas')
                ->form([
                    Grid::make(3)
                        ->schema([
                            // Turno Día
                            \Filament\Forms\Components\Section::make('Turno Día')
                                ->schema([
                                    TextInput::make('phaseHours.dia')
                                        ->label('Horas')
                                        ->helperText('Horas dedicadas al Turno Día')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(24)
                                        ->step(0.5)
                                        ->suffix('horas'),
                                    \Filament\Forms\Components\Textarea::make('descriptions.dia')
                                        ->label('Comentarios')
                                        ->helperText('Agregue comentarios sobre las horas del turno día')
                                        ->rows(2),
                                ])
                                ->columnSpan(1)
                                ->collapsible(),

                            // Turno Noche
                            \Filament\Forms\Components\Section::make('Turno Noche')
                                ->schema([
                                    TextInput::make('phaseHours.noche')
                                        ->label('Horas')
                                        ->helperText('Horas dedicadas al Turno Noche')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(24)
                                        ->step(0.5)
                                        ->suffix('horas'),
                                    \Filament\Forms\Components\Textarea::make('descriptions.noche')
                                        ->label('Comentarios')
                                        ->helperText('Agregue comentarios sobre las horas del turno noche')
                                        ->rows(2),
                                ])
                                ->columnSpan(1)
                                ->collapsible(),

                            // Turno Madrugada
                            \Filament\Forms\Components\Section::make('Turno Madrugada')
                                ->schema([
                                    TextInput::make('phaseHours.madrugada')
                                        ->label('Horas')
                                        ->helperText('Horas dedicadas al Turno Madrugada')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(24)
                                        ->step(0.5)
                                        ->suffix('horas'),
                                    \Filament\Forms\Components\Textarea::make('descriptions.madrugada')
                                        ->label('Comentarios')
                                        ->helperText('Agregue comentarios sobre las horas del turno madrugada')
                                        ->rows(2),
                                ])
                                ->columnSpan(1)
                                ->collapsible(),
                        ]),
                ])
                ->modalWidth('5xl')
                ->modalHeading(function () {
                    $date = Carbon::parse($this->currentDate)->setDay($this->selectedDay)->format('d/m/Y');
                    $hasHours = $this->selectedCell && $this->getCellEntries($this->selectedCell['project_id'], $this->selectedCell['day']) > 0;

                    return $hasHours ? "Actualizar Horas: {$date}" : "Registrar Horas: {$date}";
                })
                ->modalDescription(function () {
                    $project = Project::find($this->selectedProject)?->name;
                    $user = \App\Models\User::find($this->selectedUserId)?->name;

                    return "Ingrese las horas trabajadas en cada turno para la locación «{$project}» del usuario «{$user}». Las horas deben estar entre 0 y 24.";
                })
                ->fillForm(function () {
                    $date = Carbon::parse($this->currentDate)->setDay($this->selectedDay);
                    $user = auth()->user();
                    $canViewAllUsers = $user->roles->contains(function ($role) {
                        return $role->can_view_all_users === true;
                    });

                    // Obtener las entradas existentes para esta fecha y proyecto
                    $entries = TimeEntry::where('user_id', $this->selectedUserId)
                        ->where('project_id', $this->selectedProject)
                        ->whereDate('date', $date)
                        ->get();

                    Log::info('Buscando entradas con:', [
                        'user_id' => $this->selectedUserId,
                        'project_id' => $this->selectedProject,
                        'date' => $date->format('Y-m-d'),
                        'canViewAllUsers' => $canViewAllUsers,
                    ]);

                    Log::info('Entradas encontradas:', $entries->toArray());

                    // Inicializar arrays para horas y descripciones
                    $phaseHours = [];
                    $descriptions = [];

                    // Llenar los arrays con los datos existentes
                    foreach ($entries as $entry) {
                        $phaseHours[$entry->phase] = $entry->hours;
                        $descriptions[$entry->phase] = $entry->description;
                    }

                    Log::info('Datos a cargar en el formulario:', [
                        'phaseHours' => $phaseHours,
                        'descriptions' => $descriptions,
                    ]);

                    return [
                        'user_id' => $canViewAllUsers ? $this->selectedUserId : $user->id,
                        'project_id' => $this->selectedProject,
                        'date' => $date->format('Y-m-d'),
                        'phaseHours' => $phaseHours,
                        'descriptions' => $descriptions,
                    ];
                })
                ->action(function (array $data): void {
                    $date = Carbon::parse($this->currentDate)->setDay($this->selectedDay);

                    TimeEntry::where('user_id', $this->selectedUserId)
                        ->where('project_id', $this->selectedProject)
                        ->whereDate('date', $date)
                        ->delete();

                    Log::info('Datos a crear:', $data);

                    foreach ($data['phaseHours'] as $phase => $hours) {
                        $hours = floatval($hours);
                        $description = ! empty($data['descriptions'][$phase]) ? $data['descriptions'][$phase] : null;

                        if ($hours > 0) {
                            $timeEntry = TimeEntry::create([
                                'user_id' => $this->selectedUserId,
                                'project_id' => $this->selectedProject,
                                'date' => $date,
                                'phase' => $phase,
                                'hours' => $hours,
                                'description' => $description,
                            ]);

                            Log::info('TimeEntry creado:', [
                                'id' => $timeEntry->id,
                                'phase' => $phase,
                                'hours' => $hours,
                                'description' => $description,
                            ]);
                        }
                    }

                    $this->loadEntries();
                    $this->selectedCell = null;

                    Notification::make()
                        ->title('Horas guardadas correctamente')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function openModal($projectId, $day): void
    {
        $this->selectCell($projectId, $day);
        $this->mountAction('timeEntry');
    }

    public function getCellEntries($projectId, $day)
    {
        $date = Carbon::parse($this->currentDate)->setDay($day)->toDateString();

        $filteredEntries = collect($this->entries)
            ->filter(function ($entry) use ($projectId, $date) {
                return $entry['project_id'] == $projectId
                    && Carbon::parse($entry['date'])->toDateString() === $date;
            });

        return $filteredEntries->sum('hours');
    }

    public function loadEntries(): void
    {
        $startOfMonth = Carbon::parse($this->currentDate)->startOfMonth();
        $endOfMonth = Carbon::parse($this->currentDate)->endOfMonth();

        $this->entries = TimeEntry::query()
            ->where('user_id', $this->selectedUserId)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'project_id' => $entry->project_id,
                    'date' => $entry->date,
                    'hours' => (float) $entry->hours,
                    'phase' => $entry->phase,
                    'description' => $entry->description,
                ];
            })
            ->toArray();
    }

    public function nextMonth(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addMonth();
        $this->loadEntries();
    }

    public function previousMonth(): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->subMonth();
        $this->loadEntries();
    }

    public function getDayTotal($day)
    {
        $date = Carbon::parse($this->currentDate)->setDay($day)->toDateString();

        return collect($this->entries)
            ->filter(fn ($entry) => Carbon::parse($entry['date'])->toDateString() === $date)
            ->sum('hours');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
