<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\DB;
use App\Customers;
use App\Models\Account;
use App\Models\Fixed_Asset_Register;

class IncomeStatementImport implements FromQuery, WithHeadings, ShouldAutoSize
{
    use Importable;
    /**
    * @param Collection $collection
    * @return Builder
    */
    protected $companyId;
    public function collection(Collection $collection)
    {
        //
    }

    public function __construct($companyId)
    {
        //  dd($position,$value,$account);
        $this->companyId = $companyId;
        set_time_limit(8000000);
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G
    }

    public function query()
    {
        // Fetch data from your database
        // dd(Account::where('province_id', $this->companyId)->get());
        // dd($this->companyId);
        return Account::query()->where('company_id', $this->companyId)->orderBy('class_id','ASC')->select([
            // dd($this->companyId),
            'gl_code',
            'gl_name',
            DB::raw('0 as debit'),
            DB::raw('0 as credit')

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


            'ACCOUNT CODE',
            'ACCOUNT NAME',
            'DEBIT',
            'CREDIT'


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
            'D' => 50,

        ];
    }
}
