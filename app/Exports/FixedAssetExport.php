<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class FixedAssetExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
    }

    public function query()
    {
        $data = collect([]);
        return $data;
    }
    public function headings(): array
    {
        // Get the columns from the staff table
        $columns = [ // whatever you want to put in the excel column would be here

            'IDENTIFICATION NUMBER',
            'ASSET NAME',
            'MODEL / TYPE',
            'SERIAL NUMBER',
            'QUANTITY',
            'DATE PURCHASED',
            'UNIT PRICE',
            'TOTAL AMOUNT',
            'LOCATION',
            'LIFETIME IN YEARS',
            'NET BOOK VALUE',
            'RESIDUAL VALUE',
            // 'DATE DISPOSED',
            'PROCEED ON SALE',
            'REMARKS'

            // 'ZONE ID',
            // 'AREA ID',
            // 'PARISH ID',


        ];

        // You can customize this array if needed
        return $columns;

    }

    
}
