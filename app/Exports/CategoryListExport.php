<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryListExport implements FromView, ShouldAutoSize, WithStyles,WithColumnWidths ,WithHeadings, WithEvents
{
    use Exportable;
    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('file-exports.category-list', [
            'data' => $this->data,
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A4:E4')->getFont()->setBold(true)->getColor()
            ->setARGB('FFFFFF');

        $sheet->getStyle('A4:E4')->getFill()->applyFromArray([
            'fillType' => 'solid',
            'rotation' => 0,
            'color' => ['rgb' => '063C93'],
        ]);

        $sheet->setShowGridlines(false);
        return [
            // Define the style for cells with data
            'A1:E'.$this->data['categories']->count() + 4 => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'], // Specify the color of the border (optional)
                    ],
                ],
            ],
        ];
    }
    public function setImage($workSheet) {
        $this->data['categories']->each(function($item,$index) use($workSheet) {
            $tempImagePath = null;
            $filePath = 'category/'.$item->icon_full_url['key'];
            $fileCheck = fileCheck(disk:'public',path: $filePath);
            if($item->icon_full_url['path'] && !$fileCheck){
                $tempImagePath = getTemporaryImageForExport($item->icon_full_url['path']);
                $imagePath = getImageForExport($item->icon_full_url['path']);
                $drawing = new MemoryDrawing();
                $drawing->setImageResource($imagePath);
            }else{
                $drawing = new Drawing();
                $drawing->setPath(is_file(storage_path('app/public/'.$filePath)) ? storage_path('app/public/'.$filePath) : public_path('assets/back-end/img/placeholder/category.png'));
            }
            $drawing = new Drawing();
            $drawing->setName($item->name);
            $drawing->setDescription($item->name);
            $drawing->setHeight(50);
            $drawing->setOffsetX(40);
            $drawing->setOffsetY(7);
            $drawing->setResizeProportional(true);
            $index+=5;
            $drawing->setCoordinates("B$index");
            $drawing->setWorksheet($workSheet);
            if($tempImagePath){
                imagedestroy($tempImagePath);
            }
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getStyle('A1:E1') // Adjust the range as per your needs
                ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A4:E'.$this->data['categories']->count() + 4) // Adjust the range as per your needs
                ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $event->sheet->getStyle('A2:E3') // Adjust the range as per your needs
                ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $event->sheet->mergeCells('A1:E1');
                $event->sheet->mergeCells('A2:B2');
                $event->sheet->mergeCells('C2:E2');
                $event->sheet->mergeCells('A3:B3');
                $event->sheet->mergeCells('C3:E3');
                $event->sheet->mergeCells('D2:E2');
                $event->sheet->getRowDimension(2)->setRowHeight(60);
                $event->sheet->getRowDimension(1)->setRowHeight(30);
                $event->sheet->getRowDimension(3)->setRowHeight(30);
                $event->sheet->getRowDimension(4)->setRowHeight(30);
                $event->sheet->getDefaultRowDimension()->setRowHeight(50);
                if ($this->data['title'] == 'category'){
                    $workSheet = $event->sheet->getDelegate();
                    $this->setImage($workSheet);
                }
                if ($this->data['title'] == 'sub_category'){
                    $event->sheet->mergeCells('D4:E4');
                    $this->data['categories']->each(function($item,$index) use($event) {
                        $index+=5;
                        $event->sheet->mergeCells("D$index:E$index");
                    });
                }
            },
        ];
    }
    public function headings(): array
    {
        return [
            '1'
        ];
    }
}
