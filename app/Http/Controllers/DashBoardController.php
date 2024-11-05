<?php

namespace App\Http\Controllers;
use App\Models\Account;

use Illuminate\Http\Request;

class DashBoardController extends Controller
{
    public function totalPayables()
    {

        $payable = allTransactions()->where('type', 2)->sum('balance');
        return respond(true, 'Total Payables fetched successfully', $payable, 200);
    }
    public function totalReceivables()
    {

        $receiavble = allTransactions()->where('type', 1)->sum('balance');
        return respond(true, 'Total Receivable fetched successfully', $receiavble, 200);
    }
    public function totalIncomes()
    {

        $income = allTransactions()->where('type', 3)->sum('amount');
        return respond(true, 'Total Income fetched successfully', $income, 200);
    }
    public function totalExpenses()
    {

        $expenses = allTransactions()->where('type', 4)->sum('amount');
        return respond(true, 'Total Income fetched successfully', $expenses, 200);
    }
    public function topCustomer()
    {

        $customer = topCustomer()->orderByDesc('balance') // Order by 'amount' column in descending order
            ->take(10) // Limit to top 10 customers
            ->get(); // Fetch the result


        return respond(true, 'Total Income fetched successfully', $customer, 200);
    }

    public function board()
    {
        //1 is for receivables, 2 is for payables, 3 is for receipts, 4 is for expenses
        $id = getCompanyid();
        $payable = allTransactions()->where('type', 2)->where('balance', '>', 0)->sum('balance');
        $receivable = allTransactions()->where('type', 1)->where('balance', '>', 0)->sum('balance');
        $income = allTransactions()->where('type', 3)->sum('amount');
        $expenses = allTransactions()->where('type', 4)->sum('amount');
        $customer = topCustomer()->orderByDesc('balance') // Order by 'amount' column in descending order
            ->take(10) // Limit to top 10 customers
            ->get(); // Fetch the result
        $getreceivable = allTransactions()->where('type', 1)->where('balance', '>', 0)->with('customer')
            ->orderBy('created_at', 'DESC')->take(10)->get();
        $accounts = Account::where('company_id', $id)
            ->with(['class', 'category', 'Subcategory'])->orderBy('class_id', 'ASC')->orderBy('category_id', 'ASC')->orderBy('sub_category_id', 'ASC')->get();

        $data = [
            'total_payables' => $payable,
            'total_receivables' => $receivable,
            'total_incomes' => $income,
            'total_expenses' => $expenses,
            'top_customers' => $customer,
            'account_receivables' => $getreceivable,
            'chart_of_account' => $accounts,

        ];
        return respond(true, 'Data fetched successfully', $data, 200);
    }

}
