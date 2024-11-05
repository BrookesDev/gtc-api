<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class JournalEntries implements WithHeadings, ShouldAutoSize, WithColumnWidths
{
    /**
    * @param Collection $collection
    */
    public function headings(): array
    {
        // dd('here');
        return [
            'TRANSACTION DATE',
            'DESCRIPTION',
            'GL CODE',
            'DEBIT',
            'CREDIT',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 55,
            'C' => 15,
            'D' => 15,

        ];
    }
}
