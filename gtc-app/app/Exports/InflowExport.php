<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Models\Receipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InflowExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnWidths
{
    protected $start;
    protected $end;
    function __construct($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        //    dd($this->start,$this->end);
        if ($this->start != 1 &&  $this->end != 1) {
            // dd($request->start, $request->end);
            $start_date = Carbon::parse($this->start)
                ->toDateTimeString();
            $end_date = Carbon::parse($this->end)
                ->toDateTimeString();
            $data['receipts'] = Receipt::whereIn('payment_mode', ['cheque', 'cash'])
                ->select('currency_id', 'currency_symbol',  'bank_lodged')
                ->distinct()
                ->groupBy('currency_id', 'currency_symbol',  'bank_lodged')
                ->where('lodgement_status', 1)->get();
            $data['start'] = $start_date;
            $data['end'] = $end_date;
        } else {
            // $data['receipts'] = Receipt::wherein('payment_mode', ['cheque','cash'])->where('lodgement_status', 1)->get();
            $data['receipts'] = Receipt::whereIn('payment_mode', ['cheque', 'cash'])
                ->select('currency_id', 'currency_symbol',  'bank_lodged')
                ->distinct()
                ->groupBy('currency_id', 'currency_symbol',  'bank_lodged')
                ->where('lodgement_status', 1)
                ->get();
            $data['start'] = 1;
            $data['end'] = 1;
        }
        $sumCheque = $data['receipts']->filter(function ($receipt) {
            return $receipt->payment_mode === 'cheque';
        });
        $sumCash = $data['receipts']->filter(function ($receipt) {
            return $receipt->payment_mode === 'cash';
        });
        $data['cashTotalAmount'] = $sumCash->sum('amount');
        $data['chequeTotalAmount'] = $sumCheque->sum('amount');
        $receipt = new Receipt();
        $data['receipts']->push([
            'gl_name' => 'Total',
            'amount' => "₦" . ''. number_format($receipt->getTotalCheques($this->start, $this->end), 2),
            "cash" => "₦" . ''. number_format($receipt->getTotalCash($this->start, $this->end), 2),
        ]);
        $additionalData = [
            [
                'gl_name' => 'Empty',
            ],
            [
                'gl_name' => 'Check',
                'amount' => 'Value 1',
                'cash' => 'Value 2',
            ],
            // Add more rows as needed
        ];
        foreach ($additionalData as $dataRow) {
            $data['receipts']->push($dataRow);
        }
        if ($this->start != 1 &&  $this->end != 1) {
            // dd($request->start, $request->end);
            $start_date = Carbon::parse($this->start)
                ->toDateTimeString();
            $end_date = Carbon::parse($this->end)
                ->toDateTimeString();
            $data['cheques'] = Receipt::select(
                'bank_id',
                'currency_id',
                'currency_symbol',
                DB::raw('SUM(amount) as sumamount'),
            )
                ->groupBy('currency_id')
                ->groupBy('currency_symbol')
                ->groupBy('bank_id')
                ->where('lodgement_status', 0)
                ->where('payment_mode', 'cheque')->whereBetween('created_at', [
                    $start_date, $end_date
                ])->get();
            $data['start'] = $start_date;
            $data['end'] = $end_date;
        } else {
            $data['cheques'] = Receipt::select(
                'bank_id',
                'currency_id',
                'currency_symbol',
                DB::raw('SUM(amount) as sumamount'),
            )
                ->groupBy('currency_id')
                ->groupBy('currency_symbol')
                ->groupBy('bank_id')
                ->where('lodgement_status', 0)
                ->where('payment_mode', 'cheque')
                ->get();
            $data['start'] = 1;
            $data['end'] = 1;
        }
        foreach ($data['cheques'] as $chequeData) {
            $data['receipts']->push($chequeData);
        }
        $data['receipts']->push([
            'gl_name' => 'cheque',
            'amount' => "₦" . ''. number_format($data['cheques']->sum('sumamount'), 2),
        ]);
        // $data['receipts']->push($data['cheques']);
        // dd($data);
        return $data['receipts'];
    }

    public function map($requesst): array
    {
        if ($requesst instanceof Receipt) {
            if($requesst->bank && $requesst->bank->gl_name){
                $value = $requesst->bank->gl_name;
                $amount =  $requesst->getCheques($this->start,$this->end);
            }else{
                $value = $requesst->nuban->bank_name;
                $amount =  $requesst->currency_symbol .''. number_format($requesst->sumamount, 2);
            }
            return [
                $value,
                $amount,
                "",
                $requesst->getCash($this->start,$this->end),
                "",
            ];
        } elseif ($requesst['gl_name'] === 'Total') {
            return [
                'TOTAL',
                 $requesst['amount'],
                '',
                $requesst['cash'],
            ];
        }elseif ($requesst['gl_name'] === 'cheque'){
            return [
                'TOTAL',
                $requesst['amount'],
                '',
                '',
            ];
        }elseif ($requesst['gl_name'] === 'Empty'){
            return [
                '',
                '',
                '',
                '',
            ];
        }else{
            return [
                'CHEQUES IN HAND',
                 '',
                '',
                '',
            ]; 
        }

        return [];
        // return [
        //     // $requesst->id,
        //     $requesst->gl_name,
        //    number_format($requesst->getSummary($this->month), 2) ,
        //     // $requesst->last_name,

        // ] ;


    }
    public function headings(): array
    {
        // dd('here');
        return [
            'CHEQUES AT BANK',
            '',
            '',
            'CASH AT BANK',
            ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 55,
            'B' => 25,
            'C' => 25,
            'D' => 45,
            'E' => 25,
        ];
    }
}
