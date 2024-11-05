<?php

namespace App\Imports;
use App\Models\TempJournal;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class OpeningBalance implements ToCollection, WithBatchInserts, WithHeadingRow, WithChunkReading, WithValidation
{
    /**
    * @param Collection $collection
    */
    protected $rows;
    protected $uuid;
    protected $date;

    public function __construct($uuid, $date)
    {
        $this->rows = new Collection();
        $this->uuid = $uuid;
        $this->date = $date;
        set_time_limit(8000000);
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $key => $row) {
            // dd($row);
            $formatCredit =  number_format($row["credit"], 2, '.', ',');
            $formatDebit =  number_format($row["debit"], 2, '.', ',');
            $debit = str_replace(',', '', $formatDebit);
            $credit = str_replace(',', '', $formatCredit);
            // dd( number_format($row["credit"],2));
            TempJournal::create([
                "transaction_date" => $this->date,//\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row["transaction_date"])->format('Y-m-d'),
                "uuid" => $this->uuid,
                "debit" => $debit,//number_format($row["debit"], 2, '.', ','),
                "credit" =>   $credit,//number_format($row["credit"], 2, '.', ','),
                "description" => $row["account_name"],
                "gl_code" => $row["account_code"],
            ]);
        }
    }

    public function getRows()
    {
        return $this->rows;
    }

    public function rules(): array
    {
        return [
            //'description' => 'required|string|max:255',
            'account_name' => 'required',
            'account_code' => 'required',
            'debit' => 'required|numeric',
            'credit' => 'required|numeric',
        ];
    }

    public function chunkSize(): int
    {
        return 3000;
    }

    public function batchSize(): int
    {
        return 3000;
    }

}
