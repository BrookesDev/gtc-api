<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Customers;
use App\Models\MonthlyDeduction;
use App\Models\MemberLoan;
use App\Models\MemberSavings;
use App\Models\NominalLedger;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class Import implements ToModel, WithHeadingRow, WithChunkReading, WithValidation, WithEvents
{
    private $totalAmount = 0;

    protected $date;
    protected $description;
    protected $type;
    protected $mode;
    protected $bank;
    protected $uuid;
    public function rules(): array
    {
        return [
            'member_number' => 'nullable|exists:customers,employee_no',
            'amount' => 'nullable|numeric',
        ];

    }
    public function __construct($date,$description, $type, $mode,$bank, $uuid)
    {
      //  dd($position,$value,$account);
        $this->date = $date;
        $this->description = $description;
        $this->type = $type;
        $this->mode = $mode;
        $this->bank = $bank;
        $this->uuid =  $uuid;
        set_time_limit(8000000);
        ini_set('max_execution_time', '5000'); //300 seconds = 5 minutes
        ini_set('memory_limit', '1024M'); // or you could use 1G
        ini_set('upload_max_filesize', '500M'); // or you could use 1G
    }


    public function generatePrefix($type, $mode){
        $savings = NominalLedger::find($type);
        $prefix = convertToUppercase($savings->description);
        if($mode == "saving"){
            $orders = MemberSavings::where('savings_type', $type)->get();
        }else{
            $orders = MemberLoan::where('loan_name', $type)->get();
        }

        $count = count($orders);
        $figure = $count + 1;
        $length = strlen($figure);
        if ($length == 1) {
            $code = "00" . $figure;
        }
        if ($length == 2) {
            $code = "00" . $figure;
        }
        if ($length == 3) {
            $code = "0" . $figure;
        }
        if ($length == 4) {
            $code = $figure;
        }
        $new = $prefix . '-' . $code;
        return $new ;
    }

    public function model(array $row)
    {
        //  dd( $this->date, $this->description);
        if (empty($row["amount"]) || !is_numeric($row["amount"]) || empty($row["member_number"])) {
            return null;
        }
        $getCustomer = Customers::where('employee_no', $row["member_number"])->where('company_id', auth()->user()->company_id)->first();
        if(!$getCustomer){
            return null;
        }
        $initial = $getCustomer->balance;
        $prefix = $this->generatePrefix($this->type, $this->mode);
        // dd($this->mode);
        if($this->mode == 'saving'){
            $getMemberSaving = MemberSavings::where('member_id', $getCustomer->id)->where('company_id',getCompanyid())->where('savings_type',$this->type)->first();
            $prefix =  $getMemberSaving->prefix;
            if(!$getMemberSaving){
                $getMemberSaving = MemberSavings::create([
                    "member_id" => $getCustomer->id,
                    "amount" => $row["amount"],
                    "balance" => 0,
                    "uuid" => rand(),
                    "savings_type" => $this->type,
                    "prefix" => $prefix,
                ]);
            }

           // $bank = $getMemberSaving->debit_account;
        }else{
            $getMemberSaving = MemberLoan::where('employee_id', $getCustomer->id)->where('company_id',getCompanyid())->first();
            $prefix =  $getMemberSaving->prefix;
            if(!$getMemberSaving){
                $getMemberSaving = MemberLoan::create([
                    "employee_id" => $getCustomer->id,
                    "loan_name" => $this->type,
                    "prefix" => $prefix,
                ]);
            }

          //  $bank = $getMemberSaving->bank;
        }
        $bank = $this->bank;
        // insert into monthly deduction table
        MonthlyDeduction::create([
            "member_id" => $getCustomer->id,
            "member_number" => $row["member_number"],
            "amount" => $row["amount"],
            "type" => $this->type,
            "mode" => $this->mode,
            "uuid" => $this->uuid,
            "transaction_date" => $this->date,
            "description" => $this->description,
            "company_id" => auth()->user()->company_id,
        ]);

        $amount = $row["amount"];
        $this->totalAmount += $amount;

        $credit = $this->mode == 'saving' ? $row["amount"] : 0 ;
        $name = $this->mode == 'saving' ? "Saving" : "Loan" ;
        $debit = $this->mode != 'saving' ? $row["amount"] : 0 ;
        $balance = $this->mode != 'saving' ? $initial - $row["amount"] : $initial + $row["amount"] ;
        $getCustomer->update(['balance' => $balance]);
        $getMemberSaving->update(['balance' => $balance]);
        $report = $this->type;
        // if($this->mode == 'saving'){
        //     postDoubleEntries($getMemberSaving->prefix, $bank, $credit, $debit, $this->description, $this->date); // debit the bank account
        //     postDoubleEntries($getMemberSaving->prefix, $report, $debit, $credit, $this->description, $this->date); // credit the savings account

        // }else{
        //     postDoubleEntries($getMemberSaving->prefix, $report, $credit, $debit, $this->description, $this->date); // credit the savings account
        //     postDoubleEntries($getMemberSaving->prefix, $bank, $debit, $credit, $this->description, $this->date); // debit the bank account
        // }
        //post as receipt //
        insertTransaction($amount,0,0,$this->date,$this->description,$this->uuid,3,$this->uuid,now(),"$name Deduction");
        saveCustomerLedger($getCustomer->id, $prefix, $debit, $credit,$this->description,  $balance);

    }

    public static function afterImport(AfterImport $event, $importInstance)
    {
        // Logic to create a single journal posting after the import is complete
        $import = $event->getConcernable();
        $totalAmount = $import->totalAmount;
        $mode = $import->mode;
        $type = $import->type;
        $date = $import->date;
        $description = $import->description;
        $bank = $import->bank;
        $uuid = $importInstance->uuid;
        $credit = $mode == 'saving' ? $totalAmount : 0;
        $debit = $mode != 'saving' ? $totalAmount : 0;
        $getLedger = NominalLedger::find($type);
        $report = $getLedger->report_to;
        if ($mode == 'saving') {
            postDoubleEntries($uuid, $bank, $credit, $debit, $description, $date); // debit the bank account
            postDoubleEntries($uuid, $report, $debit, $credit, $description, $date); // credit the savings account
        } else {
            postDoubleEntries($uuid, $report, $credit, $debit, $description, $date); // credit the loan account
            postDoubleEntries($uuid, $bank, $debit, $credit, $description, $date); // debit the bank account
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                static::afterImport($event,$this);
            },
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
