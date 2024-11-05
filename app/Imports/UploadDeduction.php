<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Session;
use App\Models\Staff;
use App\Models\Deduction;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;


class UploadDeduction implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        // dd($collection);
        //let's do batch insertion
        $allInputs = [];
        foreach ($collection as $key => $row) {
            //check whether staff exist
            $staff = Staff::where('staff_id', $row['staff_number'])->first();
            $staffNumber = $row['staff_number'];
            if (!$staff) {
                throw new \Exception("There is no staff with the staff number ($staffNumber)");
            }
            # code...

            $newDeduction['staff_id'] = $staff->id;
            $newDeduction['amount'] = $row['amount'];
            $newDeduction['created_at'] = now();
            $newDeduction['updated_at'] = now();
            $newDeduction['year'] = Session::get('year');
            $newDeduction['company_id'] = Session::get('company_id');
            $newDeduction['month'] = Session::get('month');
            $newDeduction['deduction_type_id'] = Session::get('deduction');
            array_push($allInputs, $newDeduction);
        }
        $saveALlInputs = Deduction::insert($allInputs);
        return true;
    }




}
