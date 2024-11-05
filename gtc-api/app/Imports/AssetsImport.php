<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Models\Fixed_Asset_Register;
use App\Models\AssetDisposal;
use App\Customers;
use App\Models\User;
use App\Models\MemberSavings;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AssetsImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation
{
    protected $category;
    public function __construct($category)
    {
        //dd($position,$value,$account);
        $this->category = $category;
        set_time_limit(8000000);
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G
    }

    // public function customValidationMessages()
    // {
    //     return [
    //         'date_purchased.date' => 'Date purchased must be in Y-M-D format!',
    //     ];
    // }

    public function model(array $row)
    {
        // dd( $this->category);

        $verify = Customers::where('employee_no', $row["employee_no"])->where('company_id', getCompanyid())->first();
        if ($verify) {
            return null;
        }
        // $user = User::create([
        //     "name" => $row["name"],
        //     "phone_no" => $row["tel"],
        //     "email" => $row["email"],
        //     "password" => Hash::make("secret"),
        //     "user_type" => "Member",
        //     "company_id" => getCompanyid(),
        //     "created_by" => auth()->user()->id,
        // ]);

        $employeeNo = $row["employee_no"] ?? $this->generateUniqueEmployeeNo();

        $customer = Customers::create([
            "name" => $row["name"],
            "ippis_no" => $row["ippis_no"],
            "employee_no" => $employeeNo,
            "department" => $row["dept"],
            "phone_no" => $row["tel"],
            "email" => $row["email"],
            "amount" => $row["amount"],
        ]);



    }

    private function generateUniqueEmployeeNo()
    {
        do {
            $employeeNo = rand(10000, 99999);
        } while (Customers::where('employee_no', $employeeNo)->exists());

        return $employeeNo;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'ippis_no' => 'required|unique:customers,ippis_no',
            'employee_no' => 'nullable|unique:customers,employee_no',
            // 'dept' => 'required|string|max:255',
            'tel' => 'nullable|unique:customers,phone_no',
            'email' => 'nullable|unique:customers,email',
            'amount' => 'nullable|numeric',
        ];
    }
    public function chunkSize(): int
    {
        return 100;
    }
}
