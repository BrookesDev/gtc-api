<?php

namespace App\Exports;

use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Database\Query\Builder;

class StaffTemplateExport implements FromQuery, WithHeadings
{
    use Exportable;

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
     * Headings for the export file.
     *
     * @return array
     */
    public function headings(): array
    {
        // Get the columns from the staff table
        $columns = [
            'TITLE',
            'LASTNAME',
            'FIRSTNAME',
            'MIDDLENAME',
            'DOB',
            'GENDER',
            'MARITAL_STATUS',
            'PHONE_NUMBER',
            'EMAIL',
            'STAFF_ID',
            'RSA_NUMBER',
            'QUALIFICATION',
            'STEP',
            'GRADE',
            'LEVEL',
            'DEPARTMENT',
            'ADDRESS',
            'COUNTRY',
            'STATE',
            'LGA',
            'CITY',
            'EMPLOYMENT_DATE',
            'ACCOUNT_NUMBER',
            'ACCOUNT_BANK',
            'MEDICAL_CONDITION'
        ];

        // You can customize this array if needed
        return $columns;

    }
}
