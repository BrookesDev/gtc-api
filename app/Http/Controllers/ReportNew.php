<?php

namespace App\Http\Controllers;

use App\Exports\InflowExport;
use App\Models\Account;
use App\Models\TempJournal;
use App\Models\Cashbook;
use App\Models\Category;
use App\Models\Journal;
use App\Models\Continent;
use App\Models\Region;
use App\Models\Province;
use App\Models\Receipt;
use App\Models\Report;
use App\Models\Classes;
use App\Models\MyTransactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\IncomeStatementImport;
use App\Imports\JournalEntries;
use App\Exports\ChartOfAccountsExport;


class ReportNew extends Controller
{
    public function duplicateAccount()
    {
        $bAccounts = Account::where('company_id', 430)->get();
        $provinces = [436, 223, 3, 339, 433, 434];
        foreach ($bAccounts as $account) {
            foreach ($provinces as $province) {
                $getPData = Province::find($province);
                if ($getPData) {
                    // dd($province, $getPData);
                    Account::create([
                        "class_id" => $account->class_id,
                        "category_id" => $account->category_id,
                        "sub_category_id" => $account->sub_category_id,
                        "gl_name" => $account->gl_name,
                        "sub_sub_category_id" => $account->sub_sub_category_id,
                        "direction" => $account->direction,
                        "created_by" => $account->created_by,
                        "gl_code" => $account->gl_code,
                        "company_id" => $province,
                        "province_id" => $province,
                        // "company_id" => $province,
                        "region_id" => $getPData->region_id,
                        "continent_id" => $getPData->continent_id,
                    ]);
                }
            }
        }
        return respond(true, "All Done", $provinces, 200);
    }

    public function searchJournal($request)
    {
        $input = $request->all();
        // return $input;

        $input = $request;
        $allEmpty = true;

        foreach ($input as $in) {
            if (!empty($in)) {
                $allEmpty = false;
                break;
            }
        }

        if ($allEmpty) {
            return respond(false, "No object of query found", null, 400);
        }
        // if($request->filled("province_id")){
        //     dd("yeah");
        // }
        if ($request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->with('account')->orderBy("transaction_date", "ASC")->get();
            // dd($request->continent_id);
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && !$request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && !$request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $province = $request->province_id;
            $value->map(function ($val) use ($start_date, $province) {
                $val->opening_balance = $val->provinceOpeningBalance($start_date, $province); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif ($request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Glory did this
                return $val;
            });
            // $value = "here";
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && !$request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && !$request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && !$request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('province_id', $request->province_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('province_id', $request->province_id)->where('gl_code', $request->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('gl_code', $request->gl_code)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('gl_code', $request->gl_code)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("gl_code") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('gl_code', $request->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("gl_code") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("gl_code") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("gl_code") && !$request->filled("start_date") && $request->filled("end_date")) {
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->whereDate('transaction_date', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        }

        // dd("here");
        // $gl_code = $request->gl_code;
        if ($request->filled("continent_id")) {
            $name = Continent::find($request->continent_id);
            $input['name'] = $name->description;
        }
        if ($request->filled("region_id")) {
            $name = Region::find($request->region_id);
            $input['name'] = $name->description;
        }
        if ($request->filled("province_id")) {
            $name = Province::find($request->province_id);
            $input['name'] = $name->description;
        }
        $paramName = 'name';

        if (!array_key_exists($paramName, array($input))) {
            // The param exists in the input array
            $input['name'] = auth()->user()->display_name;
        }
        $data['journal'] = $value;
        $data['input'] = $input;

        return respond(true, 'Journal fetched successfuly!', $data, 201);
        // return json_encode($value);
    }

    public function getTrialBalance($request)
    {

        $accounts = Account::get();
        $journal = [];
        if (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            // nothing is fileld
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('gl_code', $check->gl_code)->get();
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
                    $stark = getFilterJournal()->where('gl_code', $credit->gl_code)->get();
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
                    $starks = getAllJournal()->where('gl_code', $debit->gl_code)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getCreditTrialBalance($start_date ?? null, $end_date ?? null);
                $value->nCredit = $value->getDebitTrialBalance($start_date ?? null, $end_date ?? null);
            }
        } elseif ($request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            //only continent id filled
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('continent_id', $request->continent_id)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('gl_code', $check->gl_code)->where('continent_id', $request->continent_id)->get();
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
                    $stark = getFilterJournal()->where('gl_code', $credit->gl_code)->where('continent_id', $request->continent_id)->get();
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
                    $starks = getAllJournal()->where('gl_code', $debit->gl_code)->where('continent_id', $request->continent_id)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getContinentCreditTrialBalance($start_date ?? null, $end_date ?? null, $request->continent_id);
                $value->nCredit = $value->getContinentDebitTrialBalance($start_date ?? null, $end_date ?? null, $request->continent_id);
            }
        } elseif ($request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("start_date") && $request->filled("end_date")) {
            //continent id , start date and end date filled
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('continent_id', $request->continent_id)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $check->gl_code)->where('continent_id', $request->continent_id)->get();
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
                    $stark = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $credit->gl_code)->where('continent_id', $request->continent_id)->get();
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
                    $starks = getAllJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $debit->gl_code)->where('continent_id', $request->continent_id)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    // dd($balances);
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getContinentCreditTrialBalance($start_date ?? null, $end_date ?? null, $request->continent_id);
                $value->nCredit = $value->getContinentDebitTrialBalance($start_date ?? null, $end_date ?? null, $request->continent_id);
            }
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            //continent and region  filled
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('region_id', $request->region_id)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('gl_code', $check->gl_code)->where('region_id', $request->region_id)->get();
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
                    $stark = getFilterJournal()->where('gl_code', $credit->gl_code)->where('region_id', $request->region_id)->get();
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
                    $starks = getAllJournal()->where('gl_code', $debit->gl_code)->where('region_id', $request->region_id)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getRegionCreditTrialBalance($start_date ?? null, $end_date ?? null, $request->region_id);
                $value->nCredit = $value->getRegionDebitTrialBalance($start_date ?? null, $end_date ?? null, $request->region_id);
            }
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            // continent , region, province  filled
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('province_id', $request->province_id)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('gl_code', $check->gl_code)->where('province_id', $request->province_id)->get();
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
                    $stark = getFilterJournal()->where('gl_code', $credit->gl_code)->where('province_id', $request->province_id)->get();
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
                    $starks = getAllJournal()->where('gl_code', $debit->gl_code)->where('province_id', $request->province_id)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getProvincialCreditTrialBalance($start_date ?? null, $end_date ?? null, $request->province_id);
                $value->nCredit = $value->getProvincialDebitTrialBalance($start_date ?? null, $end_date ?? null, $request->province_id);
            }
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            //contient, region , province , end date
            $start_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
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
                    $stark = getFilterJournal()->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
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
                    $starks = getAllJournal()->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("start_date") && $request->filled("end_date")) {
            // continent , region , province , start and end
            // dd("here");
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $check->gl_code)->get();
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
                    $stark = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $credit->gl_code)->get();
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
                    $starks = getAllJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $debit->gl_code)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getProvincialCreditTrialBalance($start_date ?? null, $end_date ?? null, $request->province_id);
                $value->nCredit = $value->getProvincialDebitTrialBalance($start_date ?? null, $end_date ?? null, $request->province_id);
            }
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            //only region
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('region_id', $request->region_id)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('gl_code', $check->gl_code)->where('region_id', $request->region_id)->get();
                    $debit = $outputValues->sum('debit');
                    $credit = $outputValues->sum('credit');
                    $balance = $debit - $credit;
                    if ($balance != 0) {
                        $trialBalance[] = $check;
                    }
                }
                // total credit value
                $creditsum = 0;
                foreach ($trialBalance as $key => $credit):
                    $stark = getFilterJournal()->where('gl_code', $credit->gl_code)->where('region_id', $request->region_id)->get();
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
                    $starks = getAllJournal()->where('gl_code', $debit->gl_code)->where('region_id', $request->region_id)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getRegionCreditTrialBalance($start_date ?? null, $end_date ?? null, $request->region_id);
                $value->nCredit = $value->getRegionDebitTrialBalance($start_date ?? null, $end_date ?? null, $request->region_id);
            }
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            // region , province
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('province_id', $request->province_id)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('gl_code', $check->gl_code)->where('province_id', $request->province_id)->get();
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
                    $stark = getFilterJournal()->where('gl_code', $credit->gl_code)->where('province_id', $request->province_id)->get();
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
                    $starks = getAllJournal()->where('gl_code', $debit->gl_code)->where('province_id', $request->province_id)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getProvincialCreditTrialBalance($start_date ?? null, $end_date ?? null, $request->province_id);
                $value->nCredit = $value->getProvincialDebitTrialBalance($start_date ?? null, $end_date ?? null, $request->province_id);
            }
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            //    region , province , start
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
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
                    $stark = getFilterJournal()->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
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
                    $starks = getAllJournal()->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("start_date") && $request->filled("end_date")) {
            // region , province ,start , end
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $check->gl_code)->get();
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
                    $stark = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $credit->gl_code)->get();
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
                    $starks = getAllJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $debit->gl_code)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getProvincialCreditTrialBalance($start_date, $end_date, $request->province_id);
                $value->nCredit = $value->getProvincialDebitTrialBalance($start_date, $end_date, $request->province_id);
            }
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            // province alone
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('region_id', $request->region_id)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            //dd($journal);

            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('gl_code', $check->gl_code)->where('province_id', $request->province_id)->get();
                    $debit = $outputValues->sum('debit');
                    $credit = $outputValues->sum('credit');
                    $balance = $debit - $credit;
                    if ($balance != 0) {
                        $trialBalance[] = $check;
                    }
                }
                // total credit value
                $creditsum = 0;
                foreach ($trialBalance as $key => $credit):
                    $stark = getFilterJournal()->where('gl_code', $credit->gl_code)->where('province_id', $request->province_id)->get();
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
                    $starks = getAllJournal()->where('gl_code', $debit->gl_code)->where('province_id', $request->province_id)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getProvincialCreditTrialBalance($start_date ?? null, $end_date ?? null, $request->province_id);
                $value->nCredit = $value->getProvincialDebitTrialBalance($start_date ?? null, $end_date ?? null, $request->province_id);
            }
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            //    province , start
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
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
                    $stark = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
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
                    $starks = getAllJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && $request->filled("start_date") && $request->filled("end_date")) {
            // region , start , end
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('region_id', $request->region_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }

            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('region_id', $request->region_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $check->gl_code)->get();
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
                    $stark = getFilterJournal()->where('region_id', $request->region_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $credit->gl_code)->get();
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
                    $starks = getAllJournal()->where('region_id', $request->region_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $debit->gl_code)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getRegionCreditTrialBalance($start_date, $end_date, $request->region_id);
                $value->nCredit = $value->getRegionDebitTrialBalance($start_date, $end_date, $request->region_id);
            }
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && $request->filled("start_date") && $request->filled("end_date")) {
            //province , start , end
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $check->gl_code)->get();
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
                    $stark = getFilterJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $credit->gl_code)->get();
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
                    $starks = getAllJournal()->where('province_id', $request->province_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $debit->gl_code)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getProvincialCreditTrialBalance($start_date, $end_date, $request->province_id);
                $value->nCredit = $value->getProvincialDebitTrialBalance($start_date, $end_date, $request->province_id);
            }
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            // only start
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->get();
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
                    $stark = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->get();
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
                    $starks = getAllJournal()->whereDate('transaction_date', '>=', $start_date)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("start_date") && $request->filled("end_date")) {
            // only start date and end date
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            foreach ($accounts as $account) {
                $details = getFilterJournal()->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->where('gl_code', $account->id)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            // dd($journal);
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->where('gl_code', $check->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
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
                    $stark = getFilterJournal()->where('gl_code', $credit->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
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
                    $starks = getAllJournal()->where('gl_code', $debit->gl_code)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    // dd($balance);
                    if ($balances < 0) {
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
                $value->direction = $value->direction();
                $value->name = $value->getAccountName();
                $value->code = $value->getAccountCode();
                $value->nDebit = $value->getCreditTrialBalance($start_date ?? null, $end_date ?? null);
                $value->nCredit = $value->getDebitTrialBalance($start_date ?? null, $end_date ?? null);
            }
            // $data['check'] = "na here";
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("start_date") && $request->filled("end_date")) {
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();

            foreach ($accounts as $account) {
                $details = getFilterJournal()->whereDate('transaction_date', $end_date)->first();
                if ($details) {
                    $journal[] = $details;
                }
            }
            if ($journal) {
                foreach ($journal as $check) {
                    $outputValues = getFilterJournal()->whereDate('transaction_date', $end_date)->get();
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
                    $stark = getFilterJournal()->whereDate('transaction_date', $end_date)->get();
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
                    $starks = getAllJournal()->whereDate('transaction_date', $end_date)->get();
                    $debits = $starks->sum('debit');
                    $credits = $starks->sum('credit');
                    $balances = $debits - $credits;
                    if ($balances < 0) {
                        // dd($balance);
                        $debitValue[$keys] = $credits - $debits;
                    } else {
                        $debitValue[$keys] = $debits - $credits;
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
            // $value = getAllJournal()->whereDate('transaction_date', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        }
        // dd("here");
        // $gl_code = $request->gl_code;

        if ($request->filled("continent_id")) {
            $name = Continent::find($request->continent_id);
            $data['name'] = $name->description;
        }
        if ($request->filled("region_id")) {
            $name = Region::find($request->region_id);
            $data['name'] = $name->description;
        }
        if ($request->filled("province_id")) {
            $name = Province::find($request->province_id);
            $data['name'] = $name->description;
        }
        $data['credit'] = $creditsum ?? "";
        $data['debit'] = $sum ?? "";
        $data['start_date'] = $start_date ?? "";
        $data['end_date'] = $end_date ?? "";
        $paramName = 'name';

        if (!array_key_exists($paramName, $data)) {
            // The param exists in the input array
            $data['name'] = auth()->user()->display_name;
        }


        return respond(true, 'Trial Balance fetched successfuly!', $data, 201);
        // return json_encode($value);
    }


    public function summaryIncomeReport($request)
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
            $value = getFilterJournal($request->isall)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
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
            $value = getFilterJournal($request->isall)->whereDate('transaction_date', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
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

    public function activityReportNew($request)
    {
        $input = $request->all();
        //validate inputs
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|array',
            'account_id.*' => 'required|exists:accounts,id',
            'continent_id' => 'nullable|exists:continents,id',
            'region_id' => 'nullable|exists:regions,id',
            'province_id' => 'nullable|exists:provinces,id',
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

        if ($request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && !$request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && !$request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $province = $request->province_id;
            $value->map(function ($val) use ($start_date, $province) {
                $val->opening_balance = $val->provinceOpeningBalance($start_date, $province); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && !$request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && !$request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('region_id', $request->region_id)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $province = $request->province_id;
            $value->map(function ($val) use ($start_date, $province) {
                $val->opening_balance = $val->provinceOpeningBalance($start_date, $province); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && !$request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('province_id', $request->province_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && $request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('province_id', $request->province_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $province = $request->province_id;
            $value->map(function ($val) use ($start_date, $province) {
                $val->opening_balance = $val->provinceOpeningBalance($start_date, $province); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif ($request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $continent = $request->continent_id;
            $value->map(function ($val) use ($start_date, $continent) {
                $val->opening_balance = $val->continentOpeningBalance($start_date, $continent); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif ($request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $region = $request->region_id;
            $value->map(function ($val) use ($start_date, $region) {
                $val->opening_balance = $val->regionOpeningBalance($start_date, $region); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && $request->filled("region_id") && !$request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->where('continent_id', $request->continent_id)->where('region_id', $request->region_id)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $region = $request->region_id;
            $value->map(function ($val) use ($start_date, $region) {
                $val->opening_balance = $val->regionOpeningBalance($start_date, $region); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("gl_code") && !$request->filled("start_date") && !$request->filled("end_date")) {
            $value = getFilterJournal($request->isall)->whereIn('gl_code', $request->gl_code)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && $request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->whereIn('gl_code', $request->account_id)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("account_id") && $request->filled("start_date") && !$request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->whereDate('transaction_date', $start_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("account_id") && $request->filled("start_date") && $request->filled("end_date")) {
            $start_date = Carbon::parse($request->start_date)
                ->toDateTimeString();
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
            $value->map(function ($val) use ($start_date) {
                $val->opening_balance = $val->openingBalance($start_date); // Example of adding a new column// Example of adding a new column
                return $val;
            });
        } elseif (!$request->filled("continent_id") && !$request->filled("region_id") && !$request->filled("province_id") && !$request->filled("account_id") && !$request->filled("start_date") && $request->filled("end_date")) {
            $end_date = Carbon::parse($request->end_date)
                ->toDateTimeString();
            $value = getFilterJournal($request->isall)->whereDate('transaction_date', $end_date)->with('account')->orderBy("transaction_date", "ASC")->get();
        }
        // dd("here");
        // $gl_code = $request->gl_code;
        if ($request->filled("continent_id")) {
            $name = Continent::find($request->continent_id);
            $input['name'] = $name->description;
        }
        if ($request->filled("region_id")) {
            $name = Region::find($request->region_id);
            $input['name'] = $name->description;
        }
        if ($request->filled("province_id")) {
            $name = Province::find($request->province_id);
            $input['name'] = $name->description;
        }
        $paramName = 'name';

        if (!array_key_exists($paramName, $input)) {
            // The param exists in the input array
            $input['name'] = auth()->user()->display_name;
        }

        $data['journal'] = $value;
        $data['input'] = $input;

        return respond(true, 'Activity Report fetched successfuly!', $data, 201);
    }

    public function getUnpaidPayables($request)
    {
        // Get all aging buckets
        $agingBuckets = DB::table('aging_buckets')->get();

        // Initialize an array to hold results
        $results = [];

        // Get the current date
        $currentDate = Carbon::now();

        // Loop through each aging bucket
        foreach ($agingBuckets as $bucket) {
            $minDays = $bucket->min_days;
            $maxDays = $bucket->max_days;

            // Calculate the date range
            $startDate = $currentDate->copy()->subDays($maxDays);
            $endDate = $currentDate->copy()->subDays($minDays);

            // Fetch unpaid payables within the date range
            $payables = allTransactions()
                ->where('type', 2) // Payables type
                ->where('balance', '>', 0) // Unpaid balance
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate)
                ->with('to')->get();

            if (!$payables->isEmpty()) {
                // Add to results
                $results[] = [
                    'description' => $bucket->description,
                    'payables' => $payables
                ];
            }
        }

        return respond(true, 'Data fetched successfully', $results, 200);
    }

    public function getUnpaidReceivables($request)
    {
        // Get all aging buckets
        $agingBuckets = DB::table('aging_buckets')->get();

        // Initialize an array to hold results
        $results = [];

        // Get the current date
        $currentDate = Carbon::now();

        // Loop through each aging bucket
        foreach ($agingBuckets as $bucket) {
            $minDays = $bucket->min_days;
            $maxDays = $bucket->max_days;

            // Calculate the date range
            $startDate = $currentDate->copy()->subDays($maxDays);
            $endDate = $currentDate->copy()->subDays($minDays);
            // Fetch unpaid payables within the date range
            $receivables = allTransactions()
                ->where('type', 1) // Payables type
                ->where('balance', '>', 0) // Unpaid balance
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate)
                ->with('customer')->get();
            // Add to results
            if (!$receivables->isEmpty()) {
                $results[] = [
                    'description' => $bucket->description,
                    'receivables' => $receivables
                ];
            }
        }

        return respond(true, 'Data fetched successfully', $results, 200);
    }

    public function balanceSheet($request)
    {
        try {
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            // dd($currentMonth);
            // $response = [];
            $classes = Classes::with('categories.accounts')->get();
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
                        $credit = $account->journals()->whereMonth('transaction_date', $currentMonth)->whereYear('transaction_date', $currentYear)->sum('credit');
                        $debit = $account->journals()->whereMonth('transaction_date', $currentMonth)->whereYear('transaction_date', $currentYear)->sum('debit');
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

    public function sortConsolidationReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'continent_id' => 'nullable|exists:continents,id',
            'region_id' => 'nullable|exists:regions,id',
            'province_id' => 'nullable|exists:provinces,id',
            'gl_code' => 'nullable|exists:accounts,id',
            'report' => 'required|string',
            'isall' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);



        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }

        switch ($request->report) {
            case 'Cashbook':
                return $this->searchJournal($request);
            case 'Trial Balance':
            case 'General Ledger':
                return $this->getTrialBalance($request);
            case 'Income Statement':
                return $this->summaryIncomeReport($request);
            case 'Account Activity Report':
                return $this->activityReportNew($request);
            case 'Age - Analysis Payables':
                return $this->getUnpaidPayables($request);
            case 'Age - Analysis Receivables':
                return $this->getUnpaidReceivables($request);
            case 'Statement Of Financial Position':
                return $this->balanceSheet($request);
            default:
                return respond(false, "Invalid report type", null, 400);
        }
    }


    public function downloadOpeningbalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'province_id' => 'required|exists:companies,id',

        ]);
        if ($validator->fails()) {
            return redirect()->back();
            // return respond(false, $validator->errors(), null, 400);
        }
        $companyId = $request->province_id;
        // dd($companyId);
        // Call the query method with the company_id
        // $customers = $this->query($companyId);

        // dd('here');
        return Excel::download(new IncomeStatementImport($companyId), 'Opening Balance.xlsx');
    }

    public function downloadJournalEntries()
    {
        return Excel::download(new JournalEntries(), 'Journal Entries.xlsx');
    }

    public function saveReviewedOpeningBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:temp_journals,uuid',
            'total_credit' => 'required',
            'total_debit' => 'required',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $totalCredit = $request->total_credit;
        $totalDebit = $request->total_debit;
        if ($totalCredit != $totalDebit) {
            return respond(false, 'Your Account is not balance', null, 400);
        }
        try {
            DB::beginTransaction();
            $uuid = $request->uuid;
            $getAllDatas = TempJournal::where('uuid', $uuid)->get();
            foreach ($getAllDatas as $single) {
                $getAccount = Account::where('gl_code', $single->gl_code)->where('company_id', auth()->user()->company_id)->first();
                if ($getAccount) {
                    $journal = new Journal();
                    $journal->transaction_date = $single->transaction_date;
                    $journal->gl_code = $getAccount->id;
                    $journal->details = $single->description;
                    $journal->debit = $single->debit;
                    $journal->credit = $single->credit;
                    $journal->uuid = $uuid;
                    $journal->transaction_date = $single->transaction_date;
                    $journal->save();
                }
            }

            DB::commit();
            return respond(true, "Reviewed data saved successfully!", $uuid, 200);
        } catch (\Exception $exception) {
            DB::rollback();

            return respond(false, $exception->getMessage(), null, 400);
        }
    }
    public function saveReviewedJournalEntries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|array',
            'gl_code' => 'required|array|exists:accounts,gl_code',
            'description' => 'required|array',
            'debit' => 'required|array',
            'credit' => 'required|array',
            'total_credit' => 'required',
            'total_debit' => 'required',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return respond(false, $validator->errors(), null, 400);
        }
        $TotalCredit = $request->total_credit;
        $TotalDebit = $request->total_debit;
        if ($TotalCredit != $TotalDebit) {
            return respond(false, 'Your Account is not balance', null, 400);
        }
        try {
            DB::beginTransaction();
            $uuid = $request->uuid;
            $getAllDatas = TempJournal::where('uuid', $uuid)->get();
            foreach ($getAllDatas as $single) {
                $getAccount = Account::where('gl_code', $single->gl_code)->where('company_id', auth()->user()->company_id)->first();
                if ($getAccount) {
                    $journal = new Journal();
                    $journal->transaction_date = $single->transaction_date;
                    $journal->gl_code = $getAccount->id;
                    $journal->details = $single->description;
                    $journal->debit = $single->debit;
                    $journal->credit = $single->credit;
                    $journal->uuid = $uuid;
                    $journal->transaction_date = $single->transaction_date;
                    $journal->save();
                }
            }

            DB::commit();
            TempJournal::where('uuid', $uuid)->delete();
            return respond(true, "Reviewed data saved successfully!", $uuid, 200);
        } catch (\Exception $exception) {
            DB::rollback();
            $errorCode = $exception->errorInfo[1] ?? $exception;
            if (is_int($errorCode)) {
                return respond(false, $exception->errorInfo[2], null, 400);
            } else {
                return respond(false, $exception->getMessage(), null, 400);
            }
        }


    }
    public function exportChartOfAccounts()
    {
        //    $provinceID = auth()->user()->province_id;
        return Excel::download(new ChartOfAccountsExport, 'Chart of Accounts.xlsx');

    }


}
