<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\FromCollection;

class PaymentExport implements WithHeadings, ShouldAutoSize, WithColumnWidths
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function headings(): array
    {
        // dd('here');
        return [
            'Transaction Date',
            'Description',
            'Debit GL Code',
            'Credit GL Code',
            'Amount',
            'Bank Name',
            'Account Name',
            'Account Number'
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
