<?php

namespace App\Exports;

use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use App\Customers;
use App\Models\Fixed_Asset_Register;

class AssetImport implements FromQuery, WithHeadings, ShouldAutoSize
{
    use Exportable;

    /**
     * Query to fetch the data (in this case, an empty query).
     *
     * @return Builder
     */
    protected $companyId;

    public function __construct($companyId)
    {
        //  dd($position,$value,$account);
        $this->companyId = $companyId;
        set_time_limit(8000000);
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G
    }
    // public function query()
    // {
    //     $data = collect([]);
    //     return $data;
    // }

    public function query()
    {
        // Fetch data from your database
        return customers::query()->where('company_id', $this->companyId)->select([
            'employee_no',
            'name',

        ]);

    }



    /**
     * Headings for the export file.
     *
     * @return array
     */
    public function headings(): array
    {
        // Get the columns from the staff table
        $columns = [ // whatever you want to put in the excel column would be here


            'MEMBER NUMBER',
            'MEMBER NAME',
            'AMOUNT'


        ];

        // You can customize this array if needed
        return $columns;

    }
    public function ColumnWidths(): array
    {
        return [
            'A' => 50,
            'B' => 50,
            'C' => 50,

        ];
    }

}
