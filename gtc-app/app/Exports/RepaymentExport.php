<?php

namespace App\Exports;

use App\Models\Repayment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class RepaymentExport implements WithHeadings, ShouldAutoSize, WithColumnWidths
{
    /**
     * Query to fetch the data (in this case, an empty query).
     *
     * @return Builder
     */


    /**
     * Define the column headings for the export file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'AMOUNT',
            'BANK CODE',
            'CODE',
            'CHEQUE/TELLER NUMBER'
        ];
    }

    /**
     * Define the column widths for the export file.
     *
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 30,
            'C' => 30,
            'D' => 30,
        ];
    }
}
