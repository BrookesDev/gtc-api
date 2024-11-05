<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class MemberExport implements WithHeadings, ShouldAutoSize, WithColumnWidths
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function headings(): array
    {
        // dd('here');
        return [
            'NAME',
            'IPPIS NO',
            'EMPLOYEE NO',
            'DEPT',
            'TEL',
            'EMAIL',
            'AMOUNT',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 55,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }
}