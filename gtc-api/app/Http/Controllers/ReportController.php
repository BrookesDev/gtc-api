<?php

namespace App\Http\Controllers;

use App\Exports\InflowExport;
use App\Models\Account;
use App\Models\Cashbook;
use App\Models\Category;
use App\Models\Journal;
use App\Models\Receipt;
use App\Models\Report;
use App\Models\Classes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function monthlyIncomeSummary(Request $request)
    {
        $data['month'] = Carbon::now()->format('F Y');
        $month = Carbon::now();
        // dd($month);
        if ($request->has('month')) {
            $formattedDate = $request->input('month');

            // Use Carbon to parse the formatted date
            $parsedDate = Carbon::createFromFormat('F Y', $formattedDate);
            // dd($parsedDate);
            // Extract the month and year
            $month = $parsedDate;
            // $year = $parsedDate->year;
            $data['month'] = $formattedDate;
        }
        $monthNumber = $month->month;
        $year = $month->year;
        $journal = Journal::whereMonth('transaction_date', $monthNumber)->whereYear('transaction_date', $year)->where('credit', ">", 0)->pluck('gl_code')->toArray();
        $income = Category::where('description', 'LIKE', 'INCOME')->pluck('id')->toArray();
        $incomeGroup = Category::whereIn('category_parent', $income)->pluck('id')->toArray();
        $data['accounts'] = $accounts = Account::whereIn('category_id', $incomeGroup)->whereIn('id', $journal)->get();
        $result = Journal::whereIn('gl_code', $accounts->pluck('id')->toArray())->select(DB::raw('MIN(transaction_date) as min_date'), DB::raw('MAX(transaction_date) as max_date'))
            ->first();
        if ($result) {
            // Extract the minimum and maximum dates
            $minDate = \Carbon\Carbon::parse($result->min_date);
            $maxDate = \Carbon\Carbon::parse($result->max_date);

            // Create an array to store the months
            $months = [];

            while ($minDate->lte($maxDate)) {
                // Format the date as "Month Year" (e.g., "January 2021")
                $formattedDate = $minDate->format('F Y');

                // Add the formatted date to the array
                $months[] = $formattedDate;

                // Move to the next month
                $minDate->addMonth();
            }

            $data['available'] = $months ?? [];
        }
        // dd($month->year);
        // $formattedMonth = $month->format('F Y');

        // dd($data);
        $data['total'] = Journal::whereIn('gl_code', $accounts->pluck('id')->toArray())->whereMonth('transaction_date', $monthNumber)->whereYear('transaction_date', $year)->sum('credit');
        return view('admin.report.monthly_income_summary_report', $data);
    }
    public function printMonthlyIncomeSummary(Request $request)
    {
        $data['month'] = Carbon::now()->format('F Y');
        $month = Carbon::now();
        // dd($month);
        if ($request->has('month')) {
            $formattedDate = $request->input('month');

            // Use Carbon to parse the formatted date
            $parsedDate = Carbon::createFromFormat('F Y', $formattedDate);
            // dd($parsedDate);
            // Extract the month and year
            $month = $parsedDate;
            // $year = $parsedDate->year;
            $data['month'] = $formattedDate;
        }
        $monthNumber = $month->month;
        $year = $month->year;
        $journal = Journal::whereMonth('transaction_date', $monthNumber)->whereYear('transaction_date', $year)->pluck('gl_code')->toArray();
        $income = Category::where('description', 'LIKE', 'INCOME')->pluck('id')->toArray();
        $incomeGroup = Category::whereIn('category_parent', $income)->pluck('id')->toArray();
        $data['accounts'] = $accounts = Account::whereIn('category_id', $incomeGroup)->whereIn('id', $journal)->get();
        // dd($month->year);
        // $formattedMonth = $month->format('F Y');

        // dd($data);
        $data['total'] = Journal::whereIn('gl_code', $accounts->pluck('id')->toArray())->whereMonth('transaction_date', $monthNumber)->whereYear('transaction_date', $year)->sum('credit');
        return view('admin.report.print_monthly_income_summary_report', $data);
    }


    public function inflow(Request $request)
    {
        if ($request->has('start_date') && $request->has('end_date')) {
            // dd($request->start_date);
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $data['receipts'] = Receipt::whereIn('payment_mode', ['cheque', 'cash'])
                ->select('currency_id', 'currency_symbol', 'bank_lodged')
                ->distinct()
                ->groupBy('currency_id', 'currency_symbol', 'bank_lodged')
                ->where('lodgement_status', 1)->whereBetween('transaction_date', [
                        $start_date,
                        $end_date,
                    ])->get();
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
                ->where('payment_mode', 'cheque')->whereBetween('transaction_date', [
                        $start_date,
                        $end_date,
                    ])->get();
            $data['start'] = $start_date;
            $data['end'] = $end_date;
        } else {
            // $data['cheques'] = Receipt::where('payment_mode', 'cheque')->where('lodgement_status', 0)->get();
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
            // $data['receipts'] = Receipt::wherein('payment_mode', ['cheque','cash'])->where('lodgement_status', 1)->get();
            $data['receipts'] = Receipt::whereIn('payment_mode', ['cheque', 'cash'])
                ->select('currency_id', 'currency_symbol', 'bank_lodged', DB::raw('SUM(amount) as sumamount'), )
                ->distinct()
                ->groupBy('currency_id', 'currency_symbol', 'bank_lodged')
                ->where('lodgement_status', 1)
                ->get();
        }
        // dd($data);
        $sumCheque = $data['receipts']->filter(function ($receipt) {
            return $receipt->payment_mode === 'cheque';
        });
        $sumCash = $data['receipts']->filter(function ($receipt) {
            return $receipt->payment_mode === 'cash';
        });
        $data['cashTotalAmount'] = $sumCash->sum('sumamount');
        $data['chequeTotalAmount'] = $sumCheque->sum('sumamount');
        // dd($data);
        return view('admin.report.inflow', $data);
    }

    public function downloadInflow(Request $request)
    {
        $start = $request->start;
        $end = $request->end;
        return Excel::download(new InflowExport($start, $end), "INFLOW REPORT.xlsx");
    }

    public function printInflow(Request $request)
    {
        if ($request->start != 1 && $request->end != 1) {
            // dd($request->start, $request->end);
            $start_date = Carbon::parse($request->start)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end)
                ->toDateTimeString();
            $data['receipts'] = Receipt::whereIn('payment_mode', ['cheque', 'cash'])
                ->select('currency_id', 'currency_symbol', 'bank_lodged')
                ->distinct()
                ->groupBy('currency_id', 'currency_symbol', 'bank_lodged')
                ->where('lodgement_status', 1)->get();
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
                ->where('payment_mode', 'cheque')->whereBetween('transaction_date', [
                        $start_date,
                        $end_date,
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
            // $data['receipts'] = Receipt::wherein('payment_mode', ['cheque','cash'])->where('lodgement_status', 1)->get();
            $data['receipts'] = Receipt::whereIn('payment_mode', ['cheque', 'cash'])
                ->select('currency_id', 'currency_symbol', 'bank_lodged')
                ->distinct()
                ->groupBy('currency_id', 'currency_symbol', 'bank_lodged')
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
        //dd($data);
        return view('admin.report.print_inflow', $data);
    }

    public function chartOfAccount()
    {
        $data['transactions'] = Receipt::where('lodgement_status', 0)->get();
        $revenue = Receipt::all();
        $data['revenue'] = $revenue->sum('amount');
        $data['lodge'] = Receipt::where('lodgement_status', 1)->sum('amount');
        $data['outstanding'] = Receipt::where('lodgement_status', 0)->sum('amount');
        $data['accounts'] = Account::all();
        return view('admin.report.charts_of_account', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function generalLedger(Request $request)
    {
        // $firstDay = Carbon::now()->startOfMonth()->toDateString();
        // $currentDay = Carbon::now()->toDateString();
        // $data['accounts'] = Account::where('company_id', auth()->user()->company_id)->get();
        // $data['ledgers'] = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $firstDay)->whereDate('transaction_date', '<=', $currentDay)->get();
        $data['ledgers'] = Journal::where('company_id', auth()->user()->company_id)->get();

        return respond(true, 'Record fetched successfully!', $data, 201);
        // dd($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function cashbook(Request $request)
    {
        $data['transactions'] = Receipt::where('lodgement_status', 0)->where('company_id', auth()->user()->company_id)->get();
        $revenue = Receipt::where('company_id', auth()->user()->company_id)->get();
        $data['revenue'] = $revenue->sum('amount');
        $data['lodge'] = Receipt::where('lodgement_status', 1)->sum('amount');
        $data['outstanding'] = Receipt::where('lodgement_status', 0)->sum('amount');
        $income = Category::where('description', 'LIKE', "%BANK%")->pluck('id')->toArray();
        // $incomeGroup =  Category::whereIn('category_parent', $income)->pluck('id')->toArray();
        $data['accounts'] = Account::whereIn('category_id', $income)->where('company_id', auth()->user()->company_id)->get();
        $data['cashbooks'] = Cashbook::where('company_id', auth()->user()->company_id)->get();

        return respond(true, 'Record fetched successfully!', $data, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function trialBalance(Request $request)
    {
        $data['transactions'] = Receipt::where('lodgement_status', 0)->where('company_id', auth()->user()->company_id)->get();
        $data['accounts'] = Account::where('company_id', auth()->user()->company_id)->get();
        return respond(true, 'Record fetched successfully!', $data, 200);
    }
    public function incomeExpenditure()
    {
        return view('admin.report.income_expenditure');
    }


    public function scheduleOfReceivable(Request $request)
    {
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $allEmpty = true;

        foreach ($input as $value) {
            if (!empty($value)) {
                $allEmpty = false;
                break;
            }
        }

        if ($allEmpty) {
            return respond(false, "No object of query found", null, 400);
        }
        // dd($request->all());
        $category = Category::find(8);
        $id = 1;
        // $accounts = Account::where('company_id', getCompanyid())->where('category_id', $id)->pluck('id')->toArray();
        // dd($accounts);


        if ($request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = allTransactionsFilter()->where('type', 1)->whereIn('debit_gl_code', $id)->where('balance', '>', 0)->whereDate('transaction_date', $start_date)->pluck('debit_gl_code')->toArray();
            $valuedAccounts = Account::whereIn('id', $value)->with([
                'receivables' => function ($query) use ($start_date) {
                    $query->whereDate('transaction_date', '>=', $start_date)->where('balance', '>', 0);
                }
            ])
                ->get();
            $valuedAccounts->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif ($request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $valuedAccounts = Account::select('gl_name','category_id','id')->where('company_id', getCompanyid())->where('category_id', $id)->with([
                'receivables' => function ($query) use ($start_date, $end_date) {
                    $query->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('balance', '>', 0);
                }
            ])
                ->get();
            $valuedAccounts->map(function ($val) use ($start_date, $end_date) {
                $transactions = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $val->id)->get();
                $debit = $transactions->sum('debit');
                $credit = $transactions->sum('credit');
                $classId = $val->class_id;
                $openingBalance =  $val->openingBalance($start_date);
                if (in_array($classId, [1, 5])) {
                    $end = $openingBalance + ($debit - $credit);
                } else {
                    $end = $openingBalance + ($credit - $debit);
                }
                $val->closing_balance = $end;
                $val->opening_balance =  $openingBalance; // Example of adding a new column// Example of adding a new column
                return $val;
            });
            // dd("here");
        } elseif (!$request->filled("start_date") && $request->filled("end_date")) {
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = allTransactionsFilter()->where('type', 1)->whereIn('debit_gl_code', $id)->where('balance', '>', 0)->whereDate('transaction_date', $end_date)->pluck('debit_gl_code')->toArray();
            $valuedAccounts = Account::whereIn('id', $value)->with([
                'receivables' => function ($query) use ($end_date) {
                    $query->whereDate('transaction_date', '<=', $end_date)->where('balance', '>', 0);
                }
            ])
                ->get();
            $valuedAccounts->map(function ($val) use ($end_date) {
                $val->opening_balance = $val->openingBalance($end_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        }
        // dd($valuedAccounts, $accounts);

        return respond(true, 'Schedule Of Receivables Fetched Successfuly!', $valuedAccounts, 201);
        // return json_encode($value);

    }
    public function activityReportNew11(Request $request)
    {
        $input = $request->all();
        //validate inputs
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|array',
            'account_id.*' => 'required|exists:accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $allEmpty = true;

        foreach ($input as $value) {
            if (!empty($value)) {
                $allEmpty = false;
                break;
            }
        }

        if ($allEmpty) {
            return respond(false, "No object of query found", null, 400);
        }


        if ($request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal()->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date);
                return $val;
            });
        } elseif (!$request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();

            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date);
                return $val;
            });
        } elseif ($request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal()->whereIn('gl_code', $request->account_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("account_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal()->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("account_id") && !$request->filled("start_date") && $request->filled("end_date")) {
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal()->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("account_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal()->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("account_id") && !$request->filled("start_date") && $request->filled("end_date")) {
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal()->whereDate('transaction_date', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        }
        $data['journal'] = $value;
        $data['input'] = $input;

        return respond(true, 'Activity Report fetched successfuly!', $data, 201);
    }

    public function activityReportNew(Request $request)
    {
        $input = $request->all();
        //validate inputs
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|array',
            'account_id.*' => 'required|exists:accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $allEmpty = true;

        foreach ($input as $value) {
            if (!empty($value)) {
                $allEmpty = false;
                break;
            }
        }

        if ($allEmpty) {
            return respond(false, "No object of query found", null, 400);
        }

        if ($request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            //start and end date
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = Account::whereIn('id', $request->account_id)->orderBy('class_id', 'ASC')->orderBy('category_id', 'ASC')
                ->with([
                    'journals' => function ($query) use ($end_date, $start_date) {
                        $query->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date);
                    }
                ])
                ->get();
            $value->map(function ($val) use ($start_date, $end_date) {
                $openingBalance = $val->openingBalance($start_date);
                $val->opening_balance = $openingBalance;//$val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                $transactions = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $val->id)->get();
                $debit = $transactions->sum('debit');
                $credit = $transactions->sum('credit');
                $getAccount = Account::find($val->id);
                $classId = $getAccount->class_id;

                // $nDebit = $value->getCreditTrialBalance($start_date ?? null, $end_date ?? null);
                //    dd($value);
                if (in_array($classId, [1, 5])) {
                    $end = $openingBalance + ($debit - $credit);
                    $type = "DB";
                } else {
                    $end = $openingBalance + ($credit - $debit);
                    $type = "CR";
                }
                $val->closing_balance = $end;
                return $val;
            });

            $data['journal'] = $value;
            $data['input'] = $input;

            return respond(true, 'Activity Report fetched successfuly!', $data, 201);
        }
    }


    public function financialPositionTest(Request $request)
    {
        try {

            $input = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);
            // dd("here");
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            if (!$request->filled("start_date") && !$request->filled("end_date")) {
                $classes = Classes::whereNotIn('id', [4, 5])->get();
                foreach ($classes as $class) {
                    $categories = $class->categories;
                    foreach ($categories as $category) {
                        $sum = 0;
                        // Get accounts for this category
                        $accounts = $category->subCategories;
                        $postings = [];
                        // Loop through each account

                        // $account->map(function ($account,$currentMonth,$currentYear,$sum) use ($request) {
                        $name = $category->description;
                        // dd($account->accounts);

                        $credit = 0;
                        $debit = 0;

                        $getAllId = $accounts->pluck('id')->toArray();
                        $credit = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->sum('credit');

                        $debit = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->sum('debit');
                        if (in_array($class->id, [1])) {
                            $amount = $debit - $credit;
                        } else {
                            $amount = $credit - $debit;
                        }

                        $absolute = abs($amount); // Calculate absolute value if needed
                        $sum += $amount;

                        $postings[] = ["name" => $name, "amount" => $amount];

                        if ($class->id == 3) {
                            // dd($class->id);
                            $extraAmount = getConsolidatedTotalIncome() - getConsolidatedTotalExpense();
                            $sum += $extraAmount;
                            $postings[] = ["name" => "EXCESS INCOME / EXPENSES", "amount" => $extraAmount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts']);
                    }
                }
            } elseif ($request->filled("start_date") && $request->filled("end_date")) {
                // $currentMonth = Carbon::now()->month;
                // $currentYear = Carbon::now()->year;
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $end_date = Carbon::parse($request->end_date)
                    ->toDateTimeString();
                // dd($currentMonth);
                // $response = [];
                $classes = Classes::whereNotIn('id', [4, 5])->get();
                foreach ($classes as $class) {
                    $categories = $class->categories;
                    foreach ($categories as $category) {
                        $sum = 0;
                        // Get accounts for this category
                        $accounts = $category->last;
                        $postings = [];
                        // Loop through each account
                        foreach ($accounts as $account) {
                            // $account->map(function ($account,$currentMonth,$currentYear,$sum) use ($request) {
                            $name = $account->description;
                            // dd($account->accounts);
                            $allAccounts = $account->accounts;
                            $credit = 0;
                            $debit = 0;

                            $getAllId = $allAccounts->pluck('id')->toArray();
                            $credit = getFilterJournal()
                                ->whereIn('gl_code', $getAllId)
                                ->whereDate('transaction_date', '>=', $start_date)
                                ->whereDate('transaction_date', '<=', $end_date)
                                ->sum('credit');

                            $debit = getFilterJournal()
                                ->whereIn('gl_code', $getAllId)
                                ->whereDate('transaction_date', '>=', $start_date)
                                ->whereDate('transaction_date', '<=', $end_date)
                                ->sum('debit');
                            $getCreditOpeningBalance = getFilterJournal()
                                ->whereIn('gl_code', $getAllId)
                                ->whereDate('transaction_date', '<', $start_date)->sum('credit');
                            $getDebitOpeningBalance = getFilterJournal()
                                ->whereIn('gl_code', $getAllId)
                                ->whereDate('transaction_date', '<', $start_date)->sum('debit');

                            if (in_array($class->id, [1])) {
                                $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                                $amount = $openingBalance + ($debit - $credit);
                                // $amount = $debit - $credit;
                            } else {
                                $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                                $amount = $openingBalance + ($credit - $debit);
                                // $amount = $credit - $debit;
                            }
                            $absolute = abs($amount); // Calculate absolute value if needed
                            $sum += $amount;

                            $postings[] = ["name" => $name, "amount" => $amount];
                        }
                        if ($class->id == 3) {
                            // dd($class->id);
                            $extraAmount = getTotalIncomeWithEndDate($end_date) - getTotalExpensesWithEndDate($end_date);
                            $sum += $extraAmount;
                            $postings[] = ["name" => "EXCESS INCOME / EXPENSES", "amount" => $extraAmount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts']);
                    }
                }
            }
            $input['records'] = $classes;

            return respond(true, 'Record fetched successfully!', $input, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function financialPositionSummaryNew(Request $request)
    {
        try {
            //addsomenew changes

            $input = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);
            // dd("here");
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            if (!$request->filled("start_date") && !$request->filled("end_date")) {
                $classes = Classes::whereNotIn('id', [4, 5])->get();
                foreach ($classes as $class) {
                    $categories = $class->categories;
                    // dd($categories);
                    foreach ($categories as $category) {
                        $sum = 0;
                        $accounts = $category->subCategories;
                        $postings = [];
                        // Loop through each sub categories

                        $name = $category->description;
                        // dd($account->accounts);
                        // $getLastLeg = $accounts->last;
                        $pluckIdToArray = $accounts->pluck('id')->toArray();
                        $credit = 0;
                        $debit = 0;

                        $getAllId = Account::whereIn('category_id', $pluckIdToArray)->pluck('id')->toArray();

                        $getCreditOpeningBalance = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->sum('credit');
                        //get debit opening balance
                        $getDebitOpeningBalance = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)->sum('debit');

                        $credit = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->sum('credit');

                        $debit = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->sum('debit');
                        if (in_array($class->id, [1])) {
                            $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                            $amount = $openingBalance + ($debit - $credit);
                        } else {
                            $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                            $amount = $openingBalance + ($credit - $debit);
                        }
                        $absolute = abs($amount); // Calculate absolute value if needed
                        $sum += $amount;

                        $postings[] = ["name" => $name, "amount" => $amount];
                        $accounts->makeHidden(['subCategories']);

                        if ($class->id == 3) {
                            // dd($class->id);
                            $extraAmount = getConsolidatedTotalIncome() - getConsolidatedTotalExpense();
                            $sum += $extraAmount;
                            $postings[] = ["name" => "EXCESS INCOME / EXPENSES", "amount" => $extraAmount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts', 'subCategories']);
                    }
                }
                $input['records'] = $classes;
            } elseif ($request->filled("start_date") && $request->filled("end_date")) {
                // start and end date
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $end_date = Carbon::parse($request->end_date)
                    ->toDateTimeString();
                // dd($currentMonth);
                // $response = [];
                $classes = Classes::whereNotIn('id', [4, 5])->get();
                foreach ($classes as $class) {
                    $categories = $class->categories;
                    foreach ($categories as $category) {
                        $sum = 0;
                        // Get accounts for this category
                        $accounts = $category->subCategories;
                        $postings = [];
                        // dd("here");
                        // Loop through each sub categories

                        $name = $category->description;
                        // $getLastLeg = $accounts->last;
                        $pluckIdToArray = $accounts->pluck('id')->toArray();
                        $credit = 0;
                        $debit = 0;
                        $getAllId = Account::whereIn('sub_category_id', $pluckIdToArray)->pluck('id')->toArray();
                        //get credit opening balance
                        $getCreditOpeningBalance = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->whereDate('transaction_date', '<', $start_date)->sum('credit');
                        //get debit opening balance
                        $getDebitOpeningBalance = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->whereDate('transaction_date', '<', $start_date)->sum('debit');

                        $credit = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->whereDate('transaction_date', '>=', $start_date)
                            ->whereDate('transaction_date', '<=', $end_date)
                            ->sum('credit');

                        $debit = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->whereDate('transaction_date', '>=', $start_date)
                            ->whereDate('transaction_date', '<=', $end_date)
                            ->sum('debit');

                        if (in_array($class->id, [1])) {
                            $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                            $amount = $openingBalance + ($debit - $credit);
                        } else {
                            $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                            $amount = $openingBalance + ($credit - $debit);
                        }
                        $absolute = abs($amount); // Calculate absolute value if needed
                        $sum += $amount;

                        $postings[] = ["name" => $name, "amount" => $amount];
                        $accounts->makeHidden(['subCategories']);

                        if ($class->id == 3) {
                            // dd($class->id);
                            $extraAmount = getTotalIncomeWithEndDate($end_date) - getTotalExpensesWithEndDate($end_date);
                            $sum += $extraAmount;
                            $postings[] = ["name" => "EXCESS INCOME / EXPENSES", "amount" => $extraAmount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts', 'subCategories']);
                    }
                }
                $input['records'] = $classes;
            }
            return respond(true, 'Record fetched successfully!', $input, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function balanceSheet(Request $request)
    {
        try {
            // $currentMonth = Carbon::now()->month;
            // $currentYear = Carbon::now()->year;
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            // dd($currentMonth);
            // $response = [];
            $classes = Classes::whereNotIn('id', [4, 5])->with('categories.accounts')->get();
            foreach ($classes as $class) {
                $categories = $class->categories;
                foreach ($categories as $category) {
                    $sum = 0;
                    // Get accounts for this category
                    $accounts = $category->accounts;
                    $postings = [];
                    // Loop through each account
                    foreach ($accounts as $account) {
                        // $account->map(function ($account,$currentMonth,$currentYear,$sum) use ($request) {
                        $name = $account->gl_name;
                        // $credit = $account->journals()->whereMonth('transaction_date', $currentMonth)->whereYear('transaction_date', $currentYear)->sum('credit');
                        // $debit = $account->journals()->whereMonth('transaction_date', $currentMonth)->whereYear('transaction_date', $currentYear)->sum('debit');
                        $credit = $account->journals()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->sum('credit');
                        $debit = $account->journals()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->sum('debit');
                        $amount = $credit - $debit;
                        $absolute = abs($amount); // Output account details
                        $sum += $amount;
                        // $single->date = $value[0]->transaction_date;

                        $postings[] = ["name" => $name, "amount" => $amount];
                        // });
                    }
                    $category->setAttribute('postings', $postings);
                    $category->setAttribute('total', $sum);
                    // $response[] = [
                    //     'class' => $class,
                    //     'postings' => $postings,
                    //     'total' =>  $sum
                    // ];
                }
            }
            return respond(true, 'Record fetched successfully!', $classes, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function printGeneralLedger1()
    {
        $value = Journal::all();
        $data['values'] = $value;
        return view('admin.report.pjournal', $data);
    }

    public function printCashbook2()
    {
        $value = Cashbook::all();
        $data['values'] = $value;
        return view('admin.report.dcashbook', $data);
    }

    public function sessionSave(Request $request)
    {
        dd($request->id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Report  $report
     * @return \Illuminate\Http\Response
     */
    public function printIncomeExpenditure($start, $end)
    {
        try {
            // $input = $request->all();
            $start_date = Carbon::parse($start)
                ->toDateTimeString();
            $end_date = Carbon::parse($end)
                ->toDateTimeString();
            $value = Journal::whereBetween('transaction_date', [
                $start_date,
                $end_date,
            ])->get();
            // }
            // dd($value);
            $data['start'] = $start_date;
            $data['end'] = $end_date;
            $category = Category::where('description', 'INCOME')->pluck('id')->toArray();
            // dd($category);
            $check = Category::whereIn('category_parent', $category)->first();
            if ($check) {
                $group = Category::whereIn('category_parent', $category)->pluck('id')->toArray();
            } else {
                $group = $category;
            }
            $income = Category::where('description', 'EXPENSES')->pluck('id')->toArray();
            $checkIncome = Category::whereIn('category_parent', $income)->first();
            if ($checkIncome) {
                $groupIncome = Category::whereIn('category_parent', $income)->pluck('id')->toArray();
            } else {
                $groupIncome = $income;
            }
            // dd($group,$groupIncome);
            $data['accounts'] = $accounts = Account::whereIn('category_id', $groupIncome)->orWhereIn('category_id', $group)->get();
            // dd($accounts);
            $journal = [];
            foreach ($accounts as $account) {
                $details = Journal::whereBetween('transaction_date', [
                    $start_date,
                    $end_date,
                ])->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = Journal::where('gl_code', $check->gl_code)->whereBetween('transaction_date', [
                        $start_date,
                        $end_date,
                    ])->get();
                    $debit = $outputValues->sum('debit');
                    $credit = $outputValues->sum('credit');
                    $balance = $debit - $credit;
                    if ($balance != 0) {
                        $trialBalance[] = $check;
                    }
                }
                // dd($trialBalance);
                // total credit value
                $creditsum = 0;
                foreach ($trialBalance as $key => $credit):
                    $stark = Journal::where('gl_code', $credit->gl_code)->whereBetween('transaction_date', [
                        $start_date,
                        $end_date,
                    ])->get();
                    $starkdebit = $stark->sum('debit');
                    $starcredit = $stark->sum('credit');
                    $starbalance = $starkdebit - $starcredit;
                    if ($starbalance > 0) {
                        // dd($balance);
                        $creditValue[$key] = $starbalance;
                    } else {
                        $creditValue[$key] = 0;
                    }
                endforeach;
                // dd($creditValue);
                foreach ($creditValue as $k => $d):
                    $creditsum = $creditsum + $d;
                endforeach;
                // dd($creditsum);
                //total debit value
                $sum = 0;
                foreach ($trialBalance as $keys => $debit):
                    $starks = Journal::where('gl_code', $debit->gl_code)->whereBetween('transaction_date', [
                        $start_date,
                        $end_date,
                    ])->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    }
                endforeach;
                // dd($debitValue);
                foreach ($debitValue as $k => $v):
                    $sum = $sum + $v;
                endforeach;
                $data['values'] = $trialBalance;
            } else {
                $data['values'] = [];
            }

            // dd($sum);
            $data['credit'] = $creditsum ?? "";
            $data['debit'] = $sum ?? "";
            $data['start_date'] = $start_date;
            $data['end_date'] = $end_date;
            // $data['values'] =  $trialBalance;
            return view('admin.report.p-income-expenditure', $data);
        } catch (\Exception $exception) {

            return Redirect::back()->withErrors($exception->getMessage());
        }
        // dd($start,$end);
    }

    public function validateTrial()
    {
        $uuids = Journal::pluck('uuid')->toArray();
        foreach ($uuids as $uuid) {
            $credit = Journal::where('uuid', $uuid)->sum('credit');
            $debit = Journal::where('uuid', $uuid)->sum('debit');
            if ($credit != $debit) {
                $value[] = $uuid ?? "";
            } else {
                $step[] = $uuid ?? "";
            }
        }
        // dd($step, $value);
    }

    public function getSummaryTrialBalance(Request $request){
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $accounts = Account::where('company_id', auth()->user()->company_id)->orderBy('class_id', 'ASC')->orderBy('category_id', 'ASC')->get();
        $start_date = Carbon::parse($request->start_date)
        ->toDateTimeString();
        $end_date = Carbon::parse($request->end_date)
            ->toDateTimeString();
        $values = $accounts;

        // $alls = 0;
        $data['values'] = $accounts;
        // dd($values);
        foreach ($values as $value) {
            $openingBalance = $value->openingBalance($start_date);
            // $nCredit = $value->getDebitTrialBalance($start_date ?? null, $end_date ?? null);
            $transactions = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $value->id)->get();
            $debit = $transactions->sum('debit');
            $credit = $transactions->sum('credit');
            $classId = $value->class_id;

            $balance = $debit - $credit;

            // $nDebit = $value->getCreditTrialBalance($start_date ?? null, $end_date ?? null);
            //    dd($value);
            if (in_array($classId, [1, 5])) {
                // if ($value->sub_category_id == 64) {
                //     $end = $openingBalance + ($credit - $debit);
                //     $type = "CR";
                // } else {
                    $end = $openingBalance + ($debit - $credit);
                    $type = "DB";
                // }
            } else {
                $end = $openingBalance + ($credit - $debit);
                $type = "CR";
            }

            if ($balance < 0) {
                $nCredit = abs($balance);
                $nDebit = 0;
            } else {
                $nCredit = 0;
                $nDebit = abs($balance);
            }

            // dd($end);
            $value->opening_balance = $openingBalance;
            $value->closing_balance = $end;//$openingBalance + ( $nDebit - $nCredit);
            $value->direction = $value->direction;
            $value->name = $value->gl_code . "  " . $value->gl_name;
            $value->code = $value->gl_code;
            $value->nDebit = $nDebit;
            $value->nCredit = $nCredit;
            $value->type = $type;//$value->getAccountSubCategory();
        }
        $data['values'] = $data['values']->reject(function ($item) {
            return $item->opening_balance == 0
                 && $item->closing_balance == 0
                 && $item->nCredit == 0
                 && $item->nDebit == 0;
        })->values(); // Re-indexes the array
        $data['credit'] = $values->sum('nCredit') ?? 0; //$creditsum ?? "";
        $data['debit'] =    $values->sum('nDebit') ??0; //$sum ?? "";
        $data['start_date'] = $start_date ?? "";
        $data['end_date'] = $end_date ?? "";
        return respond(true, 'Trial Balance fetched successfuly!', $data, 201);
    }

    public function getTrialBalance(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            // dd("here");
            $input = $request->all();
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
            // }
            // dd($value);
            $data['start'] = $input['start_date'];
            $data['end'] = $input['end_date'];
            $accounts = Account::where('company_id', auth()->user()->company_id)->orderBy('class_id', 'ASC')->orderBy('category_id', 'ASC')->get();
            // dd($accounts);
            $journal = [];
            foreach ($accounts as $account) {
                $details = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $check->gl_code)->get(); //whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
                    $debit = $outputValues->sum('debit');
                    $credit = $outputValues->sum('credit');
                    $balance = $debit - $credit;
                    if ($balance != 0) {
                        $trialBalance[] = $check;
                    }
                }
                // dd($trialBalance);
                // total credit value
                $creditsum = 0;
                foreach ($trialBalance as $key => $credit):
                    $stark = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $credit->gl_code)->get(); //->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
                    $starkdebit = $stark->sum('debit');
                    $starcredit = $stark->sum('credit');
                    $starbalance = $starkdebit - $starcredit;
                    if ($starbalance > 0) {
                        // dd($balance);
                        $creditValue[$key] = $starbalance;
                    } else {
                        $creditValue[$key] = 0;
                    }
                endforeach;
                // dd($creditValue);
                foreach ($creditValue as $k => $d):
                    $creditsum = $creditsum + $d;
                endforeach;
                // dd($creditsum);
                //total debit value
                $sum = 0;
                foreach ($trialBalance as $keys => $debit):
                    $starks = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $debit->gl_code)->get(); //whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = 0;
                    }
                endforeach;
                // dd($debitValue);
                foreach ($debitValue as $k => $v):
                    $sum = $sum + $v;
                endforeach;
                $data['values'] = $trialBalance;
            } else {
                $data['values'] = [];
            }

            $values = $data['values'];
            foreach ($values as $value) {
                $openingBalance = $value->openingBalance($start_date);
                // $nCredit = $value->getDebitTrialBalance($start_date ?? null, $end_date ?? null);
                $transactions = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $value->gl_code)->get();
                $debit = $transactions->sum('debit');
                $credit = $transactions->sum('credit');
                $getAccount = Account::find($value->gl_code);
                $classId = $getAccount->class_id;

                if (in_array($classId, [1, 5])) {
                    if ($getAccount->sub_category_id == 64) {
                        $end = $openingBalance + ($credit - $debit);
                        $type = "CR";
                    } else {
                        $end = $openingBalance + ($debit - $credit);
                        $type = "DB";
                    }
                } else {
                    $end = $openingBalance + ($credit - $debit);
                    $type = "CR";
                }

                // dd($end);
                $value->opening_balance = $openingBalance;
                $value->closing_balance = $end; //$openingBalance + ( $nDebit - $nCredit);
                $value->direction = $value->direction();
                $value->name = $value->getAccountCode() . "  " . $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $debit;
                $value->nCredit = $credit;
                $value->type = $type; //$value->getAccountSubCategory();
            }

            $data['credit'] = array_sum(array_column($values, 'nCredit')); //$creditsum ?? "";
            $data['debit'] = array_sum(array_column($values, 'nDebit')); //$sum ?? "";
            $data['start_date'] = $start_date;
            $data['end_date'] = $end_date;
            return respond(true, 'Trial Balance fetched successfuly!', $data, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function getIncomeExpenditure2(Request $request)
    {
        try {
            $input = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                // 'gl_code' => 'required',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors()->first(), null, 400);
            }

            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
            // }
            // dd($value);
            $data['start'] = $input['start_date'];
            $data['end'] = $input['end_date'];
            //please revisit this code
            $accounts = Account::where('company_id', auth()->user()->company_id)->where('class_id', 4)->orWhere('class_id', 5)->get();
            // dd($accounts);
            $journal = [];
            foreach ($accounts as $account) {
                $details = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $check->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
                    $debit = $outputValues->sum('debit');
                    $credit = $outputValues->sum('credit');
                    $balance = $debit - $credit;
                    if ($balance != 0) {
                        $trialBalance[] = $check;
                    }
                }
                // dd($trialBalance);
                // total credit value
                $creditsum = 0;
                foreach ($trialBalance as $key => $credit):
                    $stark = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $credit->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
                    $starkdebit = $stark->sum('debit');
                    $starcredit = $stark->sum('credit');
                    $starbalance = $starkdebit - $starcredit;
                    if ($starbalance > 0) {
                        // dd($balance);
                        $creditValue[$key] = $starbalance;
                    } else {
                        $creditValue[$key] = $starcredit - $starkdebit;
                        ;
                    }
                endforeach;
                // dd($creditValue);
                foreach ($creditValue as $k => $d):
                    $creditsum = $creditsum + $d;
                endforeach;
                // dd($creditsum);
                //total debit value
                $sum = 0;
                foreach ($trialBalance as $keys => $debit):
                    $starks = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $debit->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = 0;
                    }
                endforeach;
                // dd($debitValue);
                foreach ($debitValue as $k => $v):
                    $sum = $sum + $v;
                endforeach;
                $data['values'] = $trialBalance;
            } else {
                $data['values'] = [];
            }

            $values = $data['values'];
            // dd($values);
            foreach ($values as $value) {
                $value->direction = $value->direction();
                $value->code = $value->getAccountCode();
                $value->name = $value->getAccountName();
                $value->nDebit = $value->getCreditTrialBalance($start_date, $end_date);
                $value->nCredit = $value->getDebitTrialBalance($start_date, $end_date);
            }
            // dd($sum);
            $data['credit'] = $creditsum ?? "";
            $data['debit'] = $sum ?? "";
            $data['start_date'] = $start_date;
            $data['end_date'] = $end_date;
            return respond(true, 'Income expenditure fetched successfuly!', $data, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), $data, 400);
        }
    }
    public function getIncomeExpenditure(Request $request)
    {
        try {
            $input = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                // 'gl_code' => 'required',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return respond(false, $validator->errors()->first(), null, 400);
            }

            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
            // }
            // dd($value);
            $data['start'] = $input['start_date'];
            $data['end'] = $input['end_date'];
            //please revisit this code
            $accounts = Account::where('company_id', auth()->user()->company_id)->where('class_id', 4)->orWhere('class_id', 5)->get();
            // dd($accounts);
            $journal = [];
            $incomeSum = 0;
            $expenseSum = 0;
            foreach ($accounts as $account) {
                $details = Journal::where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $account->id)->get();
                if ($details->count() > 0) {
                    // dd($details[0]->account->id);
                    $credit = $details->sum('credit');
                    $debit = $details->sum('debit');
                    $amount = $credit - $debit;
                    $absolute = abs($amount);
                    if ($details[0]->account->class_id == 4) {
                        $incomeSum = $incomeSum + $amount;
                        $type = 1;
                    } else {
                        $expenseSum = $expenseSum + $amount;
                        $type = 2;
                    }
                    $journal[] = ["account_code" => $details[0]->account->gl_code, "account_name" => $details[0]->account->gl_name, "amount" => $absolute, "type" => $type];
                    // dd($amount);
                }
            }
            $data['journals'] = $journal;
            $data['totalIncome'] = abs($incomeSum);
            $data['totalExpense'] = abs($expenseSum);
            $data['status'] = $incomeSum - $expenseSum;
            // dd($journal);
            return respond(true, 'Income expenditure fetched successfuly!', $data, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function getMonthlyIncomeExpenditure(Request $request)
    {
        try {
            $input = $request->all();
            if ($request->has('month')) {
                // dd($request->month);
                $date = Carbon::createFromFormat('F Y', $request->month);
                $currentMonth = $date->month;
                $currentYear = $date->year;
                // dd($currentMonth);
            } else {
                $currentMonth = Carbon::now()->month;
                $currentYear = Carbon::now()->year;
            }
            //validate inputs, request, startdate, enddate

            $value = Journal::where('company_id', auth()->user()->company_id)->whereMonth('transaction_date', $currentMonth)->whereYear('transaction_date', $currentYear)->get();
            // }
            // dd($value);
            //please revisit this code
            $accounts = Account::where('company_id', auth()->user()->company_id)->where('class_id', 4)->orWhere('class_id', 5)->get();
            // dd($accounts);
            $journal = [];
            $incomeSum = 0;
            $expenseSum = 0;
            $yincomeSum = 0;
            $yexpenseSum = 0;
            // dd($accounts);
            foreach ($accounts as $account) {
                $details = Journal::where('gl_code', $account->id)->where('company_id', auth()->user()->company_id)->whereMonth('transaction_date', $currentMonth)->whereYear('transaction_date', $currentYear)->get();
                $yearly = Journal::where('gl_code', $account->id)->where('company_id', auth()->user()->company_id)->whereYear('transaction_date', $currentYear)->get();
                if ($details->count() > 0 || $yearly->count() > 0) {
                    // dd($details[0]->account->id);
                    $ycredit = $yearly->sum('credit');
                    $ydebit = $yearly->sum('debit');
                    $yamount = $ycredit - $ydebit;
                    $yabsolute = abs($yamount);
                    $credit = $details->sum('credit');
                    $debit = $details->sum('debit');
                    $amount = $credit - $debit;
                    $absolute = abs($amount);
                    if ($details->count() > 0) {
                        $checkType = $details[0]->account->class_id;
                        $name = $details[0]->account->gl_name;
                        $code = $details[0]->account->gl_code;
                    } else {
                        $checkType = $yearly[0]->account->class_id;
                        $name = $yearly[0]->account->gl_name;
                        $code = $yearly[0]->account->gl_code;
                    }
                    if ($checkType == 4) {
                        $yincomeSum = $yincomeSum + $yamount;
                        $incomeSum = $incomeSum + $amount;
                        $type = 1;
                    } else {
                        $yexpenseSum = $yexpenseSum + $yamount;
                        $expenseSum = $expenseSum + $amount;
                        $type = 2;
                    }
                    $journal[] = ["account_code" => $code, "account_name" => $name, "amount" => $absolute, "type" => $type, "yearly" => $yabsolute];
                    // dd($amount);
                }
            }
            $data['journals'] = $journal;
            $data['totalYearlyIncome'] = abs($yincomeSum);
            $data['totalYearlyExpense'] = abs($expenseSum);
            $data['totalIncome'] = abs($incomeSum);
            $data['totalExpense'] = abs($expenseSum);
            $data['status'] = $incomeSum - $expenseSum;
            // dd($journal);
            return respond(true, 'Income expenditure fetched successfuly!', $data, 201);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function searchLedger(Request $request)
    {
        //  dd(session()->all());
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'gl_code' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        // dd($input);

        $id = $input['gl_code'];
        if ($input['start_date'] == null || $input['end_date'] == null) {
            if ($id == "null") {
                $value = getJournalFilter()->get();
            } else {
                $value = getJournalFilter()->where('gl_code', $id)->get();
            }
        }

        if ($input['start_date'] == null && $input['end_date'] != null) {
            if ($id == "null") {
                $value = getJournalFilter()->whereDate('transaction_date', '=<', $input['end_date'])->get();
            } else {
                $value = getJournalFilter()->where('gl_code', $id)->whereDate('transaction_date', '=<', $input['end_date'])->get();
            }
        }
        // dd($value);
        if ($input['start_date'] != null && $input['end_date'] == null) {
            if ($id == "null") {
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $value = getJournalFilter()->whereDate('transaction_date', '=', $input['start_date'])->get();
            } else {
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $value = getJournalFilter()->where('gl_code', $id)->whereDate('transaction_date', '=', $input['start_date'])->get();
            }
        }

        if ($input['start_date'] != null && $input['end_date'] != null) {
            if ($id == "null") {
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $end_date = Carbon::parse($request->end_date)
                    ->toDateTimeString();
                $value = getJournalFilter()->whereBetween('transaction_date', [
                    $start_date,
                    $end_date,
                ])->get();
            } else {
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $end_date = Carbon::parse($request->end_date)
                    ->toDateTimeString();
                $value = getJournalFilter()->where('gl_code', $id)->whereBetween('transaction_date', [
                    $start_date,
                    $end_date,
                ])->get();
            }
        }

        if ($id == null) {
            $income = Category::where('description', 'LIKE', "%BANK%")->pluck('id')->toArray();
            // $incomeGroup =  Category::whereIn('category_parent', $income)->pluck('id')->toArray();
            $accounts = Account::where('company_id', auth()->user()->company_id)->whereIn('category_id', $income)->pluck('id')->toArray();
            $value = getJournalFilter()->whereIn('gl_code', $accounts)->get();
            // dd($value);
        }
        // $starve = Session::get('starve');
        // dd(session()->all());
        // dd($starve);
        $data = $input;
        $data['cashbook'] = $value;
        return respond(true, 'Cashook records fetched successfully!', $data, 201);
    }

    public function printCashbook(Request $request)
    {
        // $input = Session::get('input');
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'gl_code' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        // dd($input);
        $id = $input['gl_code'];
        // dd(strtotime($input['start_date']));
        if (strtotime($input['start_date']) == false || strtotime($input['end_date']) == false) {
            $value = Cashbook::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->get();
        }

        if (strtotime($input['start_date']) == false && strtotime($input['end_date']) != false) {
            // $value = Receipt::where('gl_code',  $id)->get();
            $value = Cashbook::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereDate('transaction_date', '<', $input['end_date'])->get();
        }

        if (strtotime($input['start_date']) != null && strtotime($input['end_date']) == false) {
            // $start_date = Carbon::parse($request->start_date)
            // ->toDateTimeString();
            // dd($input['start_date']);
            $value = Cashbook::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereDate('transaction_date', '=', $input['start_date'])->get();
        }

        if (strtotime($input['start_date']) != false && strtotime($input['end_date']) != false) {
            // dd($input['start_date']);
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = Cashbook::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereBetween('transaction_date', [
                $start_date,
                $end_date,
            ])->get();
        }
        $data['start'] = $input['start_date'];
        $data['end'] = $input['end_date'];
        $data['values'] = $value;
        $data['cash'] = $value->sum('cash');
        $data['bank'] = $value->sum('bank');
        $data['pbank'] = $value->sum('pbank');
        $data['pcash'] = $value->sum('pcash');

        return respond(true, 'Cashbook report fetched successfuly!', $data, 201);
        // return view('admin.report.dcashbook', $data);
        // return json_encode($value);
    }

    public function printGeneralLedger(Request $request)
    {
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'gl_code' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $id = $input['gl_code'];
        $gl_code = $request->gl_code;

        if (strtotime($input['start_date']) == false || strtotime($input['end_date']) == false) {
            $value = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->get();
        }

        if (strtotime($input['start_date']) == false && strtotime($input['end_date']) != false) {
            // $value = Receipt::where('gl_code',  $id)->get();
            $value = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereDate('transaction_date', '<', strtotime($input['end_date']))->get();
        }

        if (strtotime($input['start_date']) != false && strtotime($input['end_date']) == false) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            // dd(strtotime($input['start_date']));
            $value = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereDate('transaction_date', '=', $start_date)->get();
        }

        if (strtotime($input['start_date']) != null && strtotime($input['end_date']) != false) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereBetween('transaction_date', [
                $start_date,
                $end_date,
            ])->get();
        }
        $data['start'] = $input['start_date'];
        $data['end'] = $input['end_date'];
        $data['general_ledger'] = $value;
        $data['credit'] = $value->sum('credit');
        $data['debit'] = $value->sum('debit');
        // dd($data);
        return respond(true, 'General ledger fetched successfuly!', $data, 201);
        // return view('admin.report.pjournal', $data);
    }
    public function searchJournal(Request $request)
    {
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'gl_code' => 'required|exists:accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $id = $input['gl_code'];
        $gl_code = $request->gl_code;

        if ($input['start_date'] == null || $input['end_date'] == null) {
            $value = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->with('account')->orderBy("transaction_date", "ASC")->get();
        }

        if ($input['start_date'] == null && $input['end_date'] != null) {
            // $value = Receipt::where('gl_code',  $id)->get();
            $value = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereDate('transaction_date', '=<', $input['end_date'])->orderBy("transaction_date", "ASC")->with('account')->get();
        }

        if ($input['start_date'] != null && $input['end_date'] == null) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            // dd($input['start_date']);
            $value = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereDate('transaction_date', '=', $input['start_date'])->orderBy("transaction_date", "ASC")->with('account')->get();
        }

        if ($input['start_date'] != null && $input['end_date'] != null) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
                // dd("here");
            $value = Account::where('id', $id)->orderBy('class_id', 'ASC')->orderBy('category_id', 'ASC')
            ->with([
                'journals' => function ($query) use ($end_date, $start_date) {
                    $query->where('company_id', auth()->user()->company_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date);
                }
            ])
            ->get();
            // Mapping through accounts and journals
            $value = $value->map(function ($account) use ($start_date) {
                // Example of adding a new variable to each account
                $openingBalance = $account->openingBalance($start_date);
                $account->opening_balance = $openingBalance;
                // Mapping through each journal related to the account
                $balance =  $openingBalance;
                $account->journals->map(function ($journal) use (&$balance) {
                    // Example of adding a new variable to each journal
                    if ($journal->credit > 0) {
                        $balance = $balance + $journal->debit - $journal->credit;
                        $lAccount = "debit";
                        $oAmount = $journal->credit;
                    } else {
                        $balance = $balance + $journal->debit - $journal->credit;
                        $oAmount = $journal->debit;
                        $lAccount = "credit";
                    }
                    //get the other account leg
                    $getOtherLeg = Journal::where('uuid', $journal->uuid)->where('id', '!=', $journal->id)->where($lAccount, $oAmount)->first();
                    if($getOtherLeg){
                        $oLAccount = Account::where('id', $getOtherLeg->gl_code)->first();
                        // if($oLAccount){
                            $journal->second = $oLAccount->gl_name ?? "";
                        // }
                    }else{
                        $journal->second = "";
                    }
                    // Add the calculated balance to each journal entry
                    $journal->balance = $balance;
                });
                $account->closing_balance = $balance;
                return $account;
            });
            // $value = Journal::where('company_id', auth()->user()->company_id)->where('gl_code', $id)
            //     ->whereDate('transaction_date', '>=', $start_date)
            //     ->whereDate('transaction_date', '<=', $end_date)->orderBy("transaction_date", "ASC")->with('account')->get();
            // dd("here");
        }

        $data['journal'] = $value;
        $data['input'] = $input;

        // return api_request_response(
        //     "ok",
        //     "Search Complete!",
        //     success_status_code(),
        //     [$value, $input]
        // );

        return respond(true, 'Journal fetched successfuly!', $data, 201);
        // return json_encode($value);
    }

    public function searchReceiptByCode(Request $request)
    {
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'gl_code' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $id = $input['gl_code'];
        $gl_code = $request->gl_code;

        if ($input['start_date'] == null || $input['end_date'] == null) {
            $value = Receipt::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->with(['user'])->get();
        }

        if ($input['start_date'] == null && $input['end_date'] != null) {
            // $value = Receipt::where('company_id', auth()->user()->company_id)->where('gl_code',  $id)->get();
            $value = Receipt::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereDate('transaction_date', '<', $input['end_date'])->with(['user'])->get();
        }

        if ($input['start_date'] != null && $input['end_date'] == null) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            // dd($input['start_date']);
            $value = Receipt::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereDate('transaction_date', '=', $input['start_date'])->with(['user'])->get();
        }

        if ($input['start_date'] != null && $input['end_date'] != null) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = Receipt::where('company_id', auth()->user()->company_id)->where('gl_code', $id)->whereBetween('transaction_date', [
                $start_date,
                $end_date,
            ])->with(['user'])->get();
        }

        $data['receipts'] = $value;
        return respond(true, 'Receipts fetched successfuly!', $data, 201);

        // return api_request_response(
        //     "ok",
        //     "Search Complete!",
        //     success_status_code(),
        //     $value
        // );
        // return json_encode($value);
    }
    public function searchReceipt(Request $request)
    {
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'gl_code' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        $id = $input['gl_code'];
        $gl_code = $request->gl_code;

        if ($input['start_date'] == null || $input['end_date'] == null) {
            $value = Receipt::where('company_id', auth()->user()->company_id)->where('bank_lodged', $id)->where('lodgement_status', 1)->with(['user', 'lodger'])->get();
        }

        if ($input['start_date'] == null && $input['end_date'] != null) {
            // $value = Receipt::where('company_id', auth()->user()->company_id)->where('bank_lodged',  $id)->get();
            $value = Receipt::where('company_id', auth()->user()->company_id)->where('bank_lodged', $id)->whereDate('transaction_date', '<', $input['end_date'])->where('lodgement_status', 1)->with(['user', 'lodger'])->get();
        }

        if ($input['start_date'] != null && $input['end_date'] == null) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            // dd($input['start_date']);
            $value = Receipt::where('company_id', auth()->user()->company_id)->where('bank_lodged', $id)->whereDate('transaction_date', '=', $input['start_date'])->where('lodgement_status', 1)->with(['user', 'lodger'])->get();
        }

        if ($input['start_date'] != null && $input['end_date'] != null) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = Receipt::where('company_id', auth()->user()->company_id)->where('bank_lodged', $id)->whereBetween('transaction_date', [
                $start_date,
                $end_date,
            ])->with(['user', 'lodger'])->where('lodgement_status', 1)->get();
        }

        $data['receipts'] = $value;
        return respond(true, 'Receipts fetched successfuly!', $data, 201);
        // return api_request_response(
        //     "ok",
        //     "Search Complete!",
        //     success_status_code(),
        //     $value
        // );
        // return json_encode($value);
    }

    public function scheduleOfPayable(Request $request)
    {
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $allEmpty = true;

        foreach ($input as $value) {
            if (!empty($value)) {
                $allEmpty = false;
                break;
            }
        }

        if ($allEmpty) {
            return respond(false, "No object of query found", null, 400);
        }
        $all = 3;

        if ($request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $valuedAccounts =Account::where('company_id', auth()->user()->company_id)->where('category_id', $all)->with([
                'transactions' => function ($query) {
                    $query->where('balance', '>', 0);
                }
            ])->get();
            $valuedAccounts->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif ($request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $valuedAccounts = Account::select('gl_name','category_id','id')->where('company_id', auth()->user()->company_id)->where('category_id', $all)->with([
                'transactions' => function ($query) use ($start_date, $end_date) {
                    $query->whereDate('transaction_date', '>=', $start_date)
                        ->whereDate('transaction_date', '<=', $end_date)->where('balance', '>', 0);
                }
            ])
                ->get(); //start-date
            $valuedAccounts->map(function ($val) use ($start_date, $end_date) {
                $transactions = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $val->id)->get();
                $debit = $transactions->sum('debit');
                $credit = $transactions->sum('credit');
                $classId = $val->class_id;
                $openingBalance =  $val->openingBalance($start_date);
                if (in_array($classId, [1, 5])) {
                    $end = $openingBalance + ($debit - $credit);
                } else {
                    $end = $openingBalance + ($credit - $debit);
                }
                $val->closing_balance = $end;
                $val->opening_balance = $openingBalance; // Example of adding a new column// Example of adding a new column
                return $val;
            });
            // $valuedAccounts = Account::whereIn('id', $value)->with('transactions')->get();
        } elseif (!$request->filled("start_date") && $request->filled("end_date")) {
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $valuedAccounts = Account::where('company_id', auth()->user()->company_id)->where('category_id', $all)->with([
                'transactions' => function ($query) {
                    $query->where('balance', '>', 0);
                }
            ])->get();
            $valuedAccounts->map(function ($val) use ($end_date) {
                $val->opening_balance = $val->openingBalance($end_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        }

        return respond(true, 'Schedule Of Payables Fetched Successfuly!', $valuedAccounts, 201);
        // return json_encode($value);
    }

    public function summaryIncomeReportOld(Request $request)
    {
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $allEmpty = true;

        foreach ($input as $value) {
            if (!empty($value)) {
                $allEmpty = false;
                break;
            }
        }

        if ($allEmpty) {
            return respond(false, "No object of query found", null, 400);
        }
        // all incomes accounts
        $incomeAccounts = Account::where('class_id', 4)->pluck('id')->toArray();
        // dd($incomeCategory);
        // all expenses
        $expenseAccounts = Account::where('class_id', 5)->pluck('id')->toArray();
        // dd($incomeAccounts,$expenseAccounts);

        if ($request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal()->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $incomeTransactions = getFilterJournal()->whereIn('gl_code', $incomeAccounts)->whereDate('transaction_date', '>=', $start_date)->pluck('gl_code')->toArray();
            $expenseTransactions = getFilterJournal()->whereIn('gl_code', $expenseAccounts)->whereDate('transaction_date', '>=', $start_date)->pluck('gl_code')->toArray();
            $valuedIncomeAccounts = Account::whereIn('id', $incomeTransactions)->get();
            $valuedExpenseAccounts = Account::whereIn('id', $expenseTransactions)->get();
        } elseif ($request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $incomeTransactions = getFilterJournal()->whereIn('gl_code', $incomeAccounts)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->pluck('gl_code')->toArray();
            $expenseTransactions = getFilterJournal()->whereIn('gl_code', $expenseAccounts)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->pluck('gl_code')->toArray();
            $valuedIncomeAccounts = Account::whereIn('id', $incomeTransactions)->get();
            $valuedExpenseAccounts = Account::whereIn('id', $expenseTransactions)->get();
            $valuedIncomeAccounts->map(function ($val) use ($start_date, $end_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                $val->current = $val->totalBalance($start_date, $end_date); // Example of adding a new column// Example of adding a new column
                $val->total = abs($val->opening_balance - $val->current); // Example of adding a new column// Example of adding a new column
                return $val;
            });
            $valuedExpenseAccounts->map(function ($val) use ($start_date, $end_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                $val->current = $val->totalBalance($start_date, $end_date); // Example of adding a new column// Example of adding a new column
                $val->total = abs($val->opening_balance - $val->current); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("start_date") && $request->filled("end_date")) {
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal()->whereDate('transaction_date', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $incomeTransactions = getFilterJournal()->whereIn('gl_code', $incomeAccounts)->whereDate('transaction_date', '<=', $end_date)->pluck('gl_code')->toArray();
            $expenseTransactions = getFilterJournal()->whereIn('gl_code', $expenseAccounts)->whereDate('transaction_date', '<=', $end_date)->pluck('gl_code')->toArray();
            $valuedIncomeAccounts = Account::whereIn('id', $incomeTransactions)->get();
            $valuedExpenseAccounts = Account::whereIn('id', $expenseTransactions)->get();
        }

        $data['income'] = $valuedIncomeAccounts;
        $data['expenses'] = $valuedExpenseAccounts;




        return respond(true, 'Summary Income Report Fetched Successfuly!', $data, 201);
        // return json_encode($value);
    }
    public function summaryIncomeReport(Request $request)
    {
        try {

            $input = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $allEmpty = true;

            foreach ($input as $value) {
                if (!empty($value)) {
                    $allEmpty = false;
                    break;
                }
            }

            if ($allEmpty) {
                return respond(false, "No object of query found", null, 400);
            }

            if ($request->filled("start_date") && !$request->filled("end_date")) {
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $classes = Classes::whereIn('id', [4, 5])->get();
                // dd($classes);
                foreach ($classes as $class) {
                    $categories = $class->catAccounts;
                    foreach ($categories as $category) {
                        $sum = 0;
                        // Get accounts for this category
                        $accounts = $category->accounts;
                        $postings = [];
                        // Loop through each account
                        foreach ($accounts as $account) {
                            $name = $account->gl_name;
                            $credit = 0;
                            $debit = 0;
                            $getCreditOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $start_date)->sum('credit');
                            $getDebitOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $start_date)->sum('debit');
                            $credit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '>=', $start_date)
                                ->sum('credit');

                            $debit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '>=', $start_date)
                                ->sum('debit');
                            if ($class->id == 4) {
                                $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                                $amount = $openingBalance + ($credit - $debit);
                            } else {
                                $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                                $amount = $openingBalance + ($debit - $credit);
                            }
                            $absolute = abs($amount); // Calculate absolute value if needed
                            $sum += $amount;

                            $postings[] = ["name" => $name, "amount" => $amount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts']);
                    }
                }
            } elseif (!$request->filled("start_date") && $request->filled("end_date")) {
                $end_date = Carbon::parse($request->end_date)
                    ->toDateTimeString();
                $classes = Classes::whereIn('id', [4, 5])->get();
                foreach ($classes as $class) {
                    $categories = $class->catAccounts;
                    foreach ($categories as $category) {
                        $sum = 0;
                        // Get accounts for this category
                        $accounts = $category->accounts;
                        $postings = [];
                        // Loop through each account
                        foreach ($accounts as $account) {
                            $name = $account->gl_name;
                            $credit = 0;
                            $debit = 0;
                            $getCreditOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $end_date)->sum('credit');
                            $getDebitOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $end_date)->sum('debit');
                            $credit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<=', $end_date)
                                ->sum('credit');

                            $debit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<=', $end_date)
                                ->sum('debit');
                            if ($class->id == 4) {
                                $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                                $amount = $openingBalance + ($credit - $debit);
                            } else {
                                $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                                $amount = $openingBalance + ($debit - $credit);
                            }
                            $absolute = abs($amount); // Calculate absolute value if needed
                            $sum += $amount;

                            $postings[] = ["name" => $name, "amount" => $amount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts']);
                    }
                }
            } elseif ($request->filled("start_date") && $request->filled("end_date")) {
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $end_date = Carbon::parse($request->end_date)
                    ->toDateTimeString();
                $classes = Classes::whereIn('id', [4, 5])->get();
                foreach ($classes as $class) {
                    $categories = $class->catAccounts;
                    $sum = 0;
                    $postings = [];
                    foreach ($categories as $category) {
                        // dd($category);
                        $name = $category->description;
                        $getAllId = Account::where('company_id', auth()->user()->company_id)->where('sub_category_id', $category->id)->pluck('id')->toArray();
                        // dd($getAllId);
                        // Loop through each account
                        $credit = 0;
                        $debit = 0;
                        $getCreditOpeningBalance = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->whereDate('transaction_date', '<', $start_date)->sum('credit');
                        $getDebitOpeningBalance = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->whereDate('transaction_date', '<', $start_date)->sum('debit');


                        $credit = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->whereDate('transaction_date', '>=', $start_date)
                            ->whereDate('transaction_date', '<=', $end_date)
                            ->sum('credit');

                        $debit = getFilterJournal()
                            ->whereIn('gl_code', $getAllId)
                            ->whereDate('transaction_date', '>=', $start_date)
                            ->whereDate('transaction_date', '<=', $end_date)
                            ->sum('debit');


                        if ($class->id == 4) {
                            $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                            $amount = $openingBalance + ($credit - $debit);
                        } else {
                            $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                            $amount = $openingBalance + ($debit - $credit);
                        }
                        $absolute = abs($amount); // Calculate absolute value if needed
                        $sum += $amount;
                        $postings[] = ["name" => $name, "amount" => $amount];
                        $class->setAttribute('postings', $postings);
                        $class->setAttribute('total', $sum);
                        $class->makeHidden(['last', 'accounts', 'subCategories', 'catAccounts']);
                        $category->makeHidden(['last', 'accounts', 'subCategories']);
                    }
                    $category->makeHidden(['last', 'accounts', 'subCategories']);
                    $class->makeHidden(['subCategories']);
                }
            }
            $input['records'] = $classes;


            return respond(true, 'Record fetched successfully!', $classes, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function summaryProfitLoss(Request $request)
    {
        try {

            $input = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $classes = Classes::whereIn('id', [4, 5])->get();
            foreach ($classes as $class) {
                $categories = $class->catAccounts;
                $sum = 0;
                $postings = [];
                foreach ($categories as $category) {
                    // dd($category);
                    $name = $category->description;
                    $getAllId = Account::where('company_id', auth()->user()->company_id)->where('sub_category_id', $category->id)->pluck('id')->toArray();
                    // dd($getAllId);
                    // Loop through each account
                    $credit = 0;
                    $debit = 0;


                    $credit = getFilterJournal()
                        ->whereIn('gl_code', $getAllId)
                        ->whereDate('transaction_date', '>=', $start_date)
                        ->whereDate('transaction_date', '<=', $end_date)
                        ->sum('credit');

                    $debit = getFilterJournal()
                        ->whereIn('gl_code', $getAllId)
                        ->whereDate('transaction_date', '>=', $start_date)
                        ->whereDate('transaction_date', '<=', $end_date)
                        ->sum('debit');


                    if ($class->id == 4) {
                        $amount = $credit - $debit;
                    } else {
                        $amount = $debit - $credit;
                    }
                    $absolute = abs($amount); // Calculate absolute value if needed
                    $sum += $amount;
                    $postings[] = ["name" => $name, "amount" => $amount];
                    $class->setAttribute('postings', $postings);
                    $class->setAttribute('total', $sum);
                    $class->makeHidden(['last', 'accounts', 'subCategories', 'catAccounts']);
                    $category->makeHidden(['last', 'accounts', 'subCategories']);
                }
                $category->makeHidden(['last', 'accounts', 'subCategories']);
                $class->makeHidden(['subCategories']);
            }
            $input['records'] = $classes;


            return respond(true, 'Record fetched successfully!', $classes, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function incomeDetailReport(Request $request)
    {
        try {

            $input = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }
            $allEmpty = true;

            foreach ($input as $value) {
                if (!empty($value)) {
                    $allEmpty = false;
                    break;
                }
            }

            if ($allEmpty) {
                return respond(false, "No object of query found", null, 400);
            }


            if ($request->filled("start_date") && !$request->filled("end_date")) {
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $classes = Classes::whereIn('id', [4, 5])->get();
                // dd($classes);
                foreach ($classes as $class) {
                    $categories = $class->catAccounts;
                    foreach ($categories as $category) {
                        $sum = 0;
                        // Get accounts for this category
                        $accounts = $category->accounts;
                        $postings = [];
                        // Loop through each account
                        foreach ($accounts as $account) {
                            $name = $account->gl_name;
                            $credit = 0;
                            $debit = 0;
                            $getCreditOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $start_date)->sum('credit');
                            $getDebitOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $start_date)->sum('debit');
                            $credit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '>=', $start_date)
                                ->sum('credit');

                            $debit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '>=', $start_date)
                                ->sum('debit');
                            if ($class->id == 4) {
                                $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                                $amount = $openingBalance + ($credit - $debit);
                            } else {
                                $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                                $amount = $openingBalance + ($debit - $credit);
                            }
                            $absolute = abs($amount); // Calculate absolute value if needed
                            $sum += $amount;

                            $postings[] = ["name" => $name, "amount" => $amount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts']);
                    }
                }
            } elseif (!$request->filled("start_date") && $request->filled("end_date")) {
                $end_date = Carbon::parse($request->end_date)
                    ->toDateTimeString();
                $classes = Classes::whereIn('id', [4, 5])->get();
                foreach ($classes as $class) {
                    $categories = $class->catAccounts;
                    foreach ($categories as $category) {
                        $sum = 0;
                        // Get accounts for this category
                        $accounts = $category->accounts;
                        $postings = [];
                        // Loop through each account
                        foreach ($accounts as $account) {
                            $name = $account->gl_name;
                            $credit = 0;
                            $debit = 0;
                            $getCreditOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $end_date)->sum('credit');
                            $getDebitOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $end_date)->sum('debit');
                            $credit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<=', $end_date)
                                ->sum('credit');

                            $debit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<=', $end_date)
                                ->sum('debit');
                            if ($class->id == 4) {
                                $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                                $amount = $openingBalance + ($credit - $debit);
                            } else {
                                $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                                $amount = $openingBalance + ($debit - $credit);
                            }
                            $absolute = abs($amount); // Calculate absolute value if needed
                            $sum += $amount;

                            $postings[] = ["name" => $name, "amount" => $amount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts']);
                    }
                }
            } elseif ($request->filled("start_date") && $request->filled("end_date")) {
                $start_date = Carbon::parse($request->start_date)
                    ->toDateTimeString();
                $end_date = Carbon::parse($request->end_date)
                    ->toDateTimeString();
                $classes = Classes::whereIn('id', [4, 5])->get();
                foreach ($classes as $class) {
                    $categories = $class->catAccounts;
                    // dd($categories);
                    foreach ($categories as $category) {
                        $sum = 0;
                        // Get accounts for this category
                        $accounts = $category->accounts;
                        $postings = [];
                        // Loop through each account
                        foreach ($accounts as $account) {
                            $name = $account->gl_name;
                            $credit = 0;
                            $debit = 0;
                            $getCreditOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $start_date)->sum('credit');
                            $getDebitOpeningBalance = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '<', $start_date)->sum('debit');

                            $credit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '>=', $start_date)
                                ->whereDate('transaction_date', '<=', $end_date)
                                ->sum('credit');

                            $debit = getFilterJournal()
                                ->where('gl_code', $account->id)
                                ->whereDate('transaction_date', '>=', $start_date)
                                ->whereDate('transaction_date', '<=', $end_date)
                                ->sum('debit');

                            if ($class->id == 4) {
                                $openingBalance = $getCreditOpeningBalance - $getDebitOpeningBalance;
                                $amount = $openingBalance + ($credit - $debit);
                            } else {
                                $openingBalance = $getDebitOpeningBalance - $getCreditOpeningBalance;
                                $amount = $openingBalance + ($debit - $credit);
                            }
                            $absolute = abs($amount); // Calculate absolute value if needed
                            $sum += $amount;

                            $postings[] = ["name" => $name, "amount" => $amount];
                        }
                        $category->setAttribute('postings', $postings);
                        $category->setAttribute('total', $sum);
                        $category->makeHidden(['last', 'accounts']);
                    }
                }
            }
            $input['records'] = $classes;


            return respond(true, 'Record fetched successfully!', $input, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function profitLossReport(Request $request)
    {
        try {

            $input = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $classes = Classes::whereIn('id', [4, 5])->get();
            foreach ($classes as $class) {
                $categories = $class->catAccounts;
                // dd($categories);
                foreach ($categories as $category) {
                    $sum = 0;
                    // Get accounts for this category
                    $accounts = $category->accounts;
                    $postings = [];
                    // Loop through each account
                    foreach ($accounts as $account) {
                        $name = $account->gl_name;
                        $credit = 0;
                        $debit = 0;

                        $credit = getFilterJournal()
                            ->where('gl_code', $account->id)
                            ->whereDate('transaction_date', '>=', $start_date)
                            ->whereDate('transaction_date', '<=', $end_date)
                            ->sum('credit');

                        $debit = getFilterJournal()
                            ->where('gl_code', $account->id)
                            ->whereDate('transaction_date', '>=', $start_date)
                            ->whereDate('transaction_date', '<=', $end_date)
                            ->sum('debit');

                        if ($class->id == 4) {
                            $amount = $credit - $debit;
                        } else {
                            $amount = $debit - $credit;
                        }
                        $sum += $amount;

                        $postings[] = ["name" => $name, "amount" => $amount];
                    }
                    $category->setAttribute('postings', $postings);
                    $category->setAttribute('total', $sum);
                    $category->makeHidden(['last', 'accounts']);
                }
            }
            $input['records'] = $classes;


            return respond(true, 'Record fetched successfully!', $input, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function profitLossReportBuild(Request $request)
    {
        try {

            $data = $request->all();
            //validate inputs, request, startdate, enddate
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date',
            ]);
            if ($validator->fails()) {
                return respond(false, $validator->errors(), null, 400);
            }

            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
                //sales
            $credit = getFilterJournal()
            ->where('gl_code', 257)
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->sum('credit');

            $debit = getFilterJournal()
                ->where('gl_code', 257)
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date)
                ->sum('debit');

            $amount = $credit - $debit;
            $data['sales'] = ["name" => "Sales", "amount" => $amount];
            //cost of sales
            $credit = getFilterJournal()
            ->where('gl_code', 280)
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->sum('credit');

            $debit = getFilterJournal()
                ->where('gl_code', 280)
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date)
                ->sum('debit');

            $amount = $debit - $credit;
            $data['cost_of_sales'] =  ["name" => "Cost of Sales / Purchase", "amount" => $amount];
            //other income
            $credit = getFilterJournal()
            ->where('gl_code', 312)
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->sum('credit');

            $debit = getFilterJournal()
                ->where('gl_code', 312)
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date)
                ->sum('debit');

            $amount = $credit - $debit;
            $data['other_income'] =  ["name" => "Other Income", "amount" => $amount];
            //expenses
            $accounts = Account::where('class_id',  5)->where('company_id', auth()->user()->company_id)->
            where('id','!=', 280)->get();
            $postings = [];
            // Loop through each account
            foreach ($accounts as $account) {
                $name = $account->gl_name;
                $credit = 0;
                $debit = 0;

                $credit = getFilterJournal()
                    ->where('gl_code', $account->id)
                    ->whereDate('transaction_date', '>=', $start_date)
                    ->whereDate('transaction_date', '<=', $end_date)
                    ->sum('credit');

                $debit = getFilterJournal()
                    ->where('gl_code', $account->id)
                    ->whereDate('transaction_date', '>=', $start_date)
                    ->whereDate('transaction_date', '<=', $end_date)
                    ->sum('debit');

                if ($account->class_id == 4) {
                    $amount = $credit - $debit;
                } else {
                    $amount = $debit - $credit;
                }

                $postings[] = ["name" => $name, "amount" => $amount];
            }
            $account->setAttribute('postings', $postings);

            $data['expenses'] = $postings;
            $data['title'] = "Profit And Loss Report" ;
            return respond(true, 'Record fetched successfully!', $data, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }
    }

    public function profitAndLoss(Request $request){
        $input = $request->all();
        //validate inputs, request, startdate, enddate
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        try {
            $start_date = Carbon::parse($request->start_date)
            ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
            ->toDateTimeString();
            $data['income'] = allTransactions()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('type', 3)->with('supplier','customer')->orderBy("transaction_date", "ASC");
            $data['$expenses'] = allTransactions()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('type', 4)->with('supplier','customer')->orderBy('transaction_date','DESC')->get();
            return respond(true, 'Record fetched successfully!', $data, 200);
        } catch (\Exception $exception) {
            return respond(false, $exception->getMessage(), null, 400);
        }

    }
}
