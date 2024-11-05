<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Models\Fixed_Asset_Register;
use App\Models\AssetDisposal;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class FixedAssetImport implements ToModel, WithHeadingRow, WithChunkReading, WithValidation
{
    /**
    * @param Collection $collection
    */
    protected $category;
    public function collection(Collection $collection)
    {
        //
    }

    public function rules(): array
    {
        return [
            'date_purchased' => 'required',
            'quantity' => 'required|numeric',
            'unit_price' => 'required|numeric',
            'total_amount' => 'required|numeric',
            'net_book_value' => 'nullable|numeric',
            'residual_value' => 'nullable|numeric',
            'proceed_on_sale' => 'nullable|numeric',
            'lifetime_in_years' => 'nullable|numeric',
        ];

    }
    public function __construct($category)
    {
        //dd($position,$value,$account);
        $this->category = $category;
        set_time_limit(8000000);
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G
    }

    public function customValidationMessages()
    {
        return [
            'date_purchased.date' => 'Date purchased must be in Y-M-D format!',
        ];
    }
    public function model(array $row)
    {
        // dd( $this->category);
        // dd($row);
        // $datePurchased = $this->parseExcelDate($row["date_purchased"]);
        $asset = Fixed_Asset_Register::create([
            "identification_number" => $row["identification_number"],
            "description" => $row["asset_name"] ?? "",
            "category_id" => $this->category,
            "model_type" => $row["model_type"],
            "serial_number" => $row["serial_number"],
            "quantity" => $row["quantity"],
            "date_purchased" => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row["date_purchased"])->format('Y-m-d'),//$row["date_purchased"],
            "unit_price" => preg_replace('/[^\d.]/', '', $row["unit_price"]),
            "amount_purchased" => preg_replace('/[^\d.]/', '', $row["total_amount"]),
            "location" => $row["location"],
            "lifetime_in_years" => $row["lifetime_in_years"],
            "net_book_value" => $row["net_book_value"],
            "residual_value" => $row["residual_value"],
            "date_disposed" => isset($row["date_disposed"]) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row["date_disposed"])->format('Y-m-d') : null,//$row["date_disposed"],
            "proceed_on_sale" => $row["proceed_on_sale"],
            "remarks" => $row["remarks"] ?? "",
        ]);
        if (isset($row["date_disposed"])) {
            AssetDisposal::create([
                "asset_id" => $asset->id,
                "amount_disposed" => isset($row["amount_disposed"]) ? preg_replace('/[^\d.]/', '', $row["amount_disposed"]) : 0,
                "date_disposed" => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row["date_disposed"])->format('Y-m-d'),
            ]);
        }

        // dd($row[0]) ;


    }
    private function parseExcelDate($dateString)
    {
        // Remove escape characters from the date string
        $dateString = str_replace('\\', '', $dateString);

        // Try parsing date using Excel's excelToDateTimeObject method
        try {
            $dateTimeObject = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateString);
            return $dateTimeObject->format('Y-m-d');
        } catch (\Exception $e) {
            // If parsing fails, try using Carbon's parse method
            $carbonDate = Carbon::parse($dateString);
            if ($carbonDate->isValid()) {
                return $carbonDate->format('Y-m-d');
            }
        }

        // Return null if parsing fails
        return null;
    }
    public function chunkSize(): int
    {
        return 100;
    }
}
