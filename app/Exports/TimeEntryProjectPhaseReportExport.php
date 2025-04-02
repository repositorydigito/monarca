<?php

namespace App\Exports;

use App\Models\TimeEntryProjectPhaseReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimeEntryProjectPhaseReportExport implements FromCollection, WithHeadings, WithStyles
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
        // Creamos las columnas para cada fase
        $phaseColumns = collect(TimeEntryProjectPhaseReport::PHASES)->map(function ($phaseName, $phaseKey) {
            return "SUM(CASE WHEN time_entries.phase = '{$phaseKey}' THEN time_entries.hours ELSE 0 END) as phase_{$phaseKey}";
        })->implode(', ');

        $query = TimeEntryProjectPhaseReport::query()
            ->join('projects', 'projects.id', '=', 'time_entries.project_id')
            ->select([
                'projects.name as project_name',
                DB::raw($phaseColumns),
                DB::raw('SUM(COALESCE(time_entries.hours, 0)) as total_hours'),
            ])
            ->whereBetween('time_entries.date', [$this->from, $this->until])
            ->groupBy('projects.id', 'projects.name')
            ->orderBy('projects.name');

        return $query->get();
    }

    public function headings(): array
    {
        return $this->headers;
    }

    protected function getHeaders(): array
    {
        $headers = ['Locaciones'];

        foreach (TimeEntryProjectPhaseReport::PHASES as $phaseName) {
            $headers[] = $phaseName;
        }

        $headers[] = 'Total';

        return $headers;
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el encabezado
        $sheet->getStyle('A1:'.$this->getLastColumn().'1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
        ]);

        // Ajustar el ancho de las columnas
        $sheet->getColumnDimension('A')->setWidth(30);
        foreach (range('B', $this->getLastColumn()) as $column) {
            $sheet->getColumnDimension($column)->setWidth(15);
        }

        // Alinear las columnas numéricas a la derecha
        foreach (range('B', $this->getLastColumn()) as $column) {
            $sheet->getStyle($column.'2:'.$column.$sheet->getHighestRow())
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        }

        // Agregar bordes a toda la tabla
        $sheet->getStyle('A1:'.$this->getLastColumn().$sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // Formato para los números
        foreach (range('B', $this->getLastColumn()) as $column) {
            $sheet->getStyle($column.'2:'.$column.$sheet->getHighestRow())
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }
    }

    protected function getLastColumn(): string
    {
        return chr(65 + $this->columnCount - 1); // 65 es el código ASCII para 'A'
    }
}
