<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\MemberLoan;
use App\Models\NominalLedger;
use App\Models\Account;
use App\Models\Repayment;
use Carbon\Carbon;

class RepaymentImport implements ToCollection, WithBatchInserts, WithHeadingRow, WithChunkReading, WithValidation
{
    /**
    * @param Collection $collection
    */
    protected $date;
    public function __construct($date)
    {
       $this->date = $date;
        set_time_limit(8000000);
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G

    }


    public function collection(Collection $rows)
    {
        // dd($this->date);
        set_time_limit(400);
        foreach ($rows as $key => $row) {
            $loanCode = $row['code'];
            // Check whether loan exists
            $loan = MemberLoan::where('prefix', $loanCode)->first();
            $accountId = $loan->id;// = MemberLoan::where('prefix', $row['code'])->first();
            $memberId = $loan->employee_id;
            $amount =  str_replace(',', '', $row['amount']);
            if ($loan->balance < $amount) {
                continue;
            }
            $loan->balance -= $amount;
            $loan->save();

            // Get the account based on the bank code
            $bankCode = $row['bank_code'];
            $account = Account::where('gl_code', $bankCode)->first();
            $type = $loan->loan_name;
            $ledger = NominalLedger::find($type);
            if(!$ledger){
                continue;
            }
            $id = $account->id;
            // dd($account->id);
            // Create a new repayment instance
            $newRepayment = Repayment::create([
                'amount' => $amount,
                'cheque_number' => $row['chequeteller_number'], // Assuming it's the cheque/tecller number
                'customer_id' => $memberId,
                'account_id' => $accountId,
                'bank' => $id, // Assuming bank refers to account ID
                'type' => 2, // Assuming the type is constant
                'transaction_date' => $this->date,
            ]);
            // dd("here");
            // dd($ledger);
            $bank = $account->id;
            $glcode = $ledger->report_to;
            $detail = $loan->beneficiary->name . ' ' . "loan repayment ";
            // credit report to account
            postDoubleEntries($loan->prefix, $glcode, 0, $amount, $detail, $this->date);
            // debit the bank
            postDoubleEntries($loan->prefix, $bank, $amount, 0, $detail, $this->date);
            saveCustomerLedger($memberId, $loan->prefix, $amount, 0, $detail, $loan->balance);
            receivablesInsertion($amount, $row['chequeteller_number'], 3, $detail, $this->date);

        }
    }




    public function rules(): array
    {
        return [
            //columns in the excel
            'amount' => 'required',
            'bank_code' => [Rule::exists(Account::class, 'gl_code'),'required'],
            'code' => [Rule::exists(MemberLoan::class, 'prefix'),'required'],
            'chequeteller_number' => 'required',
            // 'commodity' => "numeric|nullable",

        ];
    }

    public function customValidationMessages(): array
    {
        return [
            //All Email Validation for Staff
            'name.required' => ' Member Name must not be empty!',
            'mem_no.unique' => 'Member No Already Exists!',
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
