<?php

namespace App\Imports;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Models\Receipt;
use App\Models\Account;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Http\Controllers\CashbookController;
use App\Http\Controllers\journalController;
use Carbon\Carbon;

class IncomeImport implements ToCollection, WithBatchInserts, WithHeadingRow, WithChunkReading, WithValidation
{
    public function __construct()
    {
        set_time_limit(8000000);
        
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G
        
  
    }
   
    public function collection(Collection $rows)
    {
        set_time_limit(400);
        // DB::beginTransaction();
        // try {
            
            // DB::beginTransaction();
            foreach ($rows as $row)
            {
                // dd($row);
                $input['uuid'] = $uuid = $chq_teller =rand();
                $debit = Account::where('gl_code', $row['debit_gl_code'])->first();
                $credit = Account::where('gl_code', $row['credit_gl_code'])->first();
                // dd($debit,$credit);
                $member = Receipt::create([
                    'uuid' =>$uuid, 
                    'teller_number' =>$uuid, 
                    'voucher_number' =>$uuid, 
                    'description' => $row['description'], 
                    'particulars' => $row['description'],
                    'transaction_date' =>  \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row["transaction_date"])->format('Y-m-d'),
                    'amount' => $row['amount'],
                    'initial_amount' => $row['amount'],
                    'date_lodged' => now(),
                    'lodgement_status' => 1,
                    'lodge_by' => Auth::user()->id,
                    'created_by' => Auth::user()->id,
                ]);
                // dd($member);
                $glcode = $credit->id;
                $gl_code = $debit->id;
                $bank_amount = $row['amount'];
                $detail = $row['description'];
                $particular = $row['description'];
                $transaction_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row["transaction_date"])->format('Y-m-d');
                $payment_mode = "cash";
                postDoubleEntries($uuid, $glcode, 0 , $bank_amount, $detail,$transaction_date) ; // credit the cash account

                postDoubleEntries($uuid, $gl_code, $bank_amount,0, $detail,$transaction_date) ;  ; // debit the cash account
                // $postCashbook = new CashbookController;
                // $postCashbook->postToCashbook($transaction_date, $particular, $detail, $bank_amount, $gl_code, $chq_teller, $uuid, $payment_mode) ;
            
            }
        // DB::commit();
        // } catch (\Exception $e) {
        //     DB::rollback(); // Rollback the transaction if any error occurred
            // Handle the exception as needed (log, report, etc.)
            // For example:
            // Log::error($e->getMessage());
        // }

    }

    
    public function rules(): array
    {
        return [
            //columns in the excel
            'transaction_date' => 'required',
            'description' => 'required|max:500',
            'amount'     => 'required|numeric',
            'credit_gl_code' => [Rule::exists(Account::class, 'gl_code') , 'required'],
            'debit_gl_code' => [Rule::exists(Account::class, 'gl_code') , 'required'],
            // 'commodity' => "numeric|nullable",
            
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            //All Email Validation for Staff
            'name.required' => ' Member Name must not be empty!',
            'mem_no.unique'    => 'Member No Already Exists!',
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
