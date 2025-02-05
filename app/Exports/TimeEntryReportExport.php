<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use App\Models\TimeEntryReport;
use Illuminate\Support\Facades\DB;

class TimeEntryReportExport implements FromCollection, WithHeadings, WithStyles
{
    protected $from;
    protected $until;
    protected $headers;
    protected $columnCount;

    public function __construct($from, $until)
    {
        $this->from = Carbon::parse($from)->startOfDay();
        $this->until = Carbon::parse($until)->endOfDay();
        $this->headers = $this->getHeaders();
        $this->columnCount = count($this->headers);
    }

    public function collection()
    {
        $dates = collect($this->from->copy()->daysUntil($this->until->copy()));
        
        $dateColumns = $dates->map(function ($date) {
            $dateStr = $date->toDateString();
            return "SUM(CASE WHEN DATE(time_entries.date) = '{$dateStr}' THEN time_entries.hours ELSE 0 END) as day_{$date->format('Y_m_d')}";
        })->implode(', ');

        $query = TimeEntryReport::query()
            ->join('users', 'users.id', '=', 'time_entries.user_id')
            ->select([
                'users.name as user_name',
                DB::raw($dateColumns),
                DB::raw('SUM(COALESCE(time_entries.hours, 0)) as total_hours'),
            ])
            ->whereBetween('time_entries.date', [$this->from, $this->until])
            ->groupBy('users.id', 'users.name')
            ->orderBy('users.name');

        return $query->get();
    }

    public function headings(): array
    {
        return $this->headers;
    }

    protected function getHeaders(): array
    {
        $headers = ['Recurso'];
        
        $dates = collect($this->from->copy()->daysUntil($this->until->copy()));
        foreach ($dates as $date) {
            $headers[] = $date->format('d/m');
        }
        
        $headers[] = 'Total';
        
        return $headers;
    }

    public function styles(Worksheet $sheet)
    {
        // Obtener la letra de la última columna
        $lastColumnIndex = $this->columnCount - 1;
        $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColumnIndex + 1);
        
        // Estilo para el encabezado
        $sheet->getStyle("A1:{$lastColumnLetter}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '000000'],
            ],
        ]);

        // Ajustar el ancho de las columnas
        $sheet->getColumnDimension('A')->setWidth(30);
        
        // Ajustar el ancho de las columnas numéricas
        for ($i = 1; $i < $this->columnCount; $i++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($columnLetter)->setWidth(12);
        }

        // Alinear las columnas numéricas a la derecha
        $lastRow = $sheet->getHighestRow();
        if ($lastColumnIndex > 0) {
            $sheet->getStyle("B2:{$lastColumnLetter}{$lastRow}")->applyFromArray([
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                ],
            ]);
        }
    }
} 