<div>
    <x-filament::page>
        <div class="space-y-6">
            {{-- Header con navegación y botón de actualizar --}}
            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="flex items-center space-x-4">
                    <button wire:click="previousMonth" class="filament-button filament-button-size-md">
                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                    </button>

                    <h2 class="text-xl font-medium">
                        {{ Carbon\Carbon::parse($currentDate)->format('F Y') }}
                    </h2>

                    <button wire:click="nextMonth" class="filament-button filament-button-size-md">
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </button>
                </div>


            </div>

            {{-- Tabla de timesheet --}}
            <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="timesheet-table">
                    <thead>
                        <tr>
                            <th>Proyecto</th>
                            @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                <th class="{{ $day === now()->day ? 'bg-blue-100 font-bold dark:bg-blue-700 dark:text-white' : '' }}">
                                    {{ $day }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ Carbon\Carbon::parse($currentDate)->setDay($day)->format('D') }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (App\Models\Project::all() as $project)
                            <tr>
                                <td>{{ $project->name }}</td>
                                @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                    <td wire:click="selectCell({{ $project->id }}, {{ $day }})"
                                        class="cursor-pointer {{ $selectedCell && $selectedCell['project_id'] == $project->id && $selectedCell['day'] == $day ? 'selected-cell' : '' }}">
                                        @php
                                            $hours = $this->getCellEntries($project->id, $day);
                                        @endphp

                                        @if ($hours > 0)
                                            <div class="flex justify-center items-center">
                                                <span class="highlighted-cell">
                                                    {{ number_format($hours, 1) }}h
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">0.0h</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr class="timesheet-total-cell">
                            <td>Total por día</td>
                            @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                <td>
                                    {{ number_format($this->getDayTotal($day), 1) }}h
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Resumen del Mes --}}
            <div class="bg-white dark:bg-gray-900 rounded-lg p-4">
                <h3 class="text-lg font-medium mb-3">Resumen del Mes</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Horas</div>
                        <div class="text-xl font-bold">
                            {{ number_format(collect($this->entries)->sum('hours'), 1) }}h
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Días Trabajados</div>
                        <div class="text-xl font-bold">
                            {{ collect($this->entries)->pluck('date')->unique()->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::page>

    <style>
        .timesheet-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .timesheet-table th {
            background-color: #f3f4f6;
            padding: 10px 12px;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
            font-size: 13px;
            text-transform: uppercase;
            color: #374151;
        }

        .dark .timesheet-table th {
            background-color: #1f2937;
            color: #e5e7eb;
            border-bottom: 2px solid #374151;
        }

        .timesheet-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            color: #374151;
        }

        .dark .timesheet-table td {
            color: #e5e7eb;
            border-bottom: 1px solid #374151;
        }

        .timesheet-table tr:hover {
            background-color: #f3f4f6;
        }

        .dark .timesheet-table tr:hover {
            background-color: #374151;
        }

        .timesheet-total-cell {
            font-weight: bold;
            color: #1f2937;
            background-color: #f3f4f6;
        }

        .dark .timesheet-total-cell {
            color: #e5e7eb;
            background-color: #2d3748;
        }

        .timesheet-total-cell:hover {
            background-color: #e5e7eb;
        }

        .dark .timesheet-total-cell:hover {
            background-color: #4a5568;
        }

        .selected-cell {
            background-color: #f59e0b;
            color: white !important;
            font-weight: bold;
        }

        .highlighted-cell {
            padding: 2px 6px;
            border-radius: 4px;
            background-color: #e5e7eb;
            color: #374151;
        }

        .dark .highlighted-cell {
            background-color: #4a5568;
            color: #e5e7eb;
        }
    </style>
</div>
