<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductosExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting, WithEvents
{
    public function __construct(private Collection $productos)
    {
    }

    public function collection(): Collection
    {
        return $this->productos;
    }

    public function headings(): array
    {
        return [
            'Codigo',
            'Producto',
            'Categoria',
            'Cantidad compras activas',
            'Stock actual',
            'Precio compra',
            'Precio venta',
            'Precio mayoreo',
            'Estado',
        ];
    }

    public function map($producto): array
    {
        return [
            $producto->codigo_barras,
            $producto->nombre,
            $producto->categoria?->nombre ?? 'Sin categoria',
            (int) ($producto->cantidad_compras_activas ?? 0),
            (int) $producto->stock_actual,
            (float) $producto->precio_compra,
            (float) $producto->precio_venta,
            (float) $producto->precio_mayoreo,
            $producto->activo ? 'Activo' : 'Inactivo',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER,
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => '"Bs." #,##0.00',
            'G' => '"Bs." #,##0.00',
            'H' => '"Bs." #,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = $this->productos->count() + 1;
                $totalRow = $lastDataRow + 1;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:I{$lastDataRow}");

                $sheet->getStyle('A1:I1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A1:I{$lastDataRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                $sheet->setCellValue("C{$totalRow}", 'TOTAL');
                $sheet->setCellValue("D{$totalRow}", $this->productos->sum(fn($p) => (int) ($p->cantidad_compras_activas ?? 0)));
                $sheet->setCellValue("E{$totalRow}", $this->productos->sum(fn($p) => (int) $p->stock_actual));

                $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ECFDF5']],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color' => ['rgb' => '10B981'],
                        ],
                    ],
                ]);

                $sheet->getStyle("D2:E{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F2:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("A1:I{$totalRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
