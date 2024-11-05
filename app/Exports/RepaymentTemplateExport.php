<?php

namespace App\Exports;

use App\Models\Repayment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class RepaymentTemplateExport implements FromQuery, WithHeadings, WithColumnWidths
{
    /**
     * Query to fetch the data (in this case, an empty query).
     *
     * @return Builder
     */
    public function query()
    {
        $data = collect([]);
        return $data;
    }

    /**
     * Define the column headings for the export file.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'AMOUNT',
            'CHEQUE/TELLER NUMBER',
            'BANK CODE',
            'CODE',
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
            'A' => 45,
            'B' => 55,
            'C' => 30,
            'D' => 30,
            'E' => 30,
        ];
    }
}
