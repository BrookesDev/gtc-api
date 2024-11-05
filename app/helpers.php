<?php
// use Jenssegers\Agent\Facades\Agent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use App\Models\Category;
use Carbon\Carbon;
use App\Models\Stock;
use App\Models\SaleInvoice;
use App\Models\PurchaseInvoice;
use App\Models\StockInventory;
use App\Models\BookingExpense;
use App\Models\Account;
use App\Models\AllTransaction;
use App\Models\Cashbook;
use App\Models\User;
use App\Models\AssetDisposal;
use App\Models\AssetTransfer;
use App\Models\Fixed_Asset_Register;
use App\Models\Company;
use App\Models\SalesOrders;
use App\Models\Requisition;
use App\Models\Receipt;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\PurchaseOrder;
use App\Models\Payable_Type;
use App\Models\Pincard;
use App\Models\Budget;
use App\Models\BookingPayment;
use App\Models\Asset;
use App\Models\Province;
use App\Models\Booking;
use App\Models\Beneficiary;
use App\Models\PaymentVoucherBreakdown;
use App\Models\Region;
use App\Models\Continent;
use App\Models\Journal;
use App\Customers;
use App\Models\CustomerPersonalLedger;
use App\Models\SupplierPersonalLedger;
use App\Models\Sale;
use App\Models\SaleTransaction;
use App\Models\MyTransactions;
use App\Models\Quotes;

function audit($action, $modelType, $modelId, $oldValues = [], $newValues = [], $description = null, $agents = null, $auditable = null)
{
    $agent = new Agent();
    // Get device information
    $deviceName = $agent->device();
    // $deviceName = $device['device'];
    // Get operating system information
    $platform = $agent->platform();
    // Get browser information
    $browser = $agent->browser();
    $userAgent = $agent->getUserAgent();
    // dd($userAgent);
    //   $deviceName = Agent::device();
    //   $platform = Agent::platform();
    //   $browser = Agent::browser();
    $userId = Auth::id() ?? 19292;
    // $name = Auth::user()->first_name ?? "" . ' '. Auth::user()->last_name ?? "";
    DB::table('audit_trails')->insert([
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'model_type' => $modelType,
        'url' => url()->current(),
        'machine_name' => $deviceName . ' , ' . $platform . ' , ' . $browser . ' ' . $userAgent,
        'ip_address' => request()->ip(),
        'model_id' => $modelId,
        'auditable_id' => $auditable,
        'old_values' => json_encode($oldValues),
        'new_values' => json_encode($newValues),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}


function formatPhoneNumber($phoneNumber)
{
    $nigeriaPrefixes = [
        '070',
        '080',
        '081',
        '090', // Mobile prefixes
        '0700',
        '0802',
        '0803',
        '0804',
        '0805',
        '0806',
        '0807',
        '0808',
        '0809', // Mobile prefixes
        '0810',
        '0811',
        '0812',
        '0813',
        '0814',
        '0815',
        '0816',
        '0817',
        '0818',
        '0819', // Mobile prefixes
        '0902',
        '0903',
        '0904',
        '0905',
        '0906',
        '0907',
        '0908',
        '0909', // Mobile prefixes
        '07025',
        '07026',
        '07027',
        '07028',
        '07029', // Landline prefixes
        '0802',
        '0803',
        '0804',
        '0805',
        '0806',
        '0807',
        '0808',
        '0809', // Landline prefixes
        '0810',
        '0811',
        '0812',
        '0813',
        '0814',
        '0815',
        '0816',
        '0817',
        '0818',
        '0819', // Landline prefixes
        '0902',
        '0903',
        '0904',
        '0905',
        '0906',
        '0907',
        '0908',
        '0909', // Landline prefixes
        '01',
        '02',
        '03',
        '04',
        '05',
        '06',
        '07',
        '09' // Landline prefixes
    ];

    // Remove any non-digit characters from the phone number
    $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

    // Check if the phone number starts with a Nigerian prefix
    $startsWithPrefix = false;
    foreach ($nigeriaPrefixes as $prefix) {
        if (substr($phoneNumber, 0, strlen($prefix)) === $prefix) {
            $startsWithPrefix = true;
            break;
        }
    }

    // Format the phone number accordingly
    if (strlen($phoneNumber) === 11 && $startsWithPrefix) {
        // Phone number with the Nigerian prefix
        $formattedNumber = '+234' . substr($phoneNumber, 1);
    } elseif (strlen($phoneNumber) === 10 && !$startsWithPrefix) {
        // Phone number without the Nigerian prefix
        $formattedNumber = '+234' . $phoneNumber;
    } else {
        // Invalid phone number
        return [
            'status' => false,
            'message' => 'Invalid phone number',
            'data' => $phoneNumber,
        ];
    }

    return [
        'status' => true,
        'message' => 'Valid phone number',
        'data' => $formattedNumber,
    ];
}

function success_status_code()
{
    return 200;
}

function bad_response_status_code()
{
    return 400;
}
function api_request_response($status, $message, $statusCode, $data = [], $return = false, $returnArray = false)
{
    $responseArray = [
        "status" => $status,
        "message" => $message,
        "data" => $data
    ];

    $response = response()->json(
        $responseArray
    );

    if ($returnArray) {
        return $returnArray;
    }

    if ($return) {
        return $response;
    }

    header('Content-Type: application/json', true, $statusCode);

    echo json_encode($response->getOriginalContent());

    exit();
}

function generate_uuid()
{
    return \Ramsey\Uuid\Uuid::uuid1()->toString();
}


function hasConsecutiveDuplicates($array)
{
    $length = count($array);

    for ($i = 0; $i < $length - 1; $i++) {
        if ($array[$i] === $array[$i + 1]) {
            return true; // Consecutive duplicates found
        }
    }

    return false; // No consecutive duplicates found
}
function convertToUppercase($word)
{
    $words = explode(' ', $word);
    $result = '';
    foreach ($words as $word) {
        $result .= strtoupper(substr($word, 0, 1));
    }
    return $result;
    // return response()->json(['converted_word' => $result]);
}

function getplanID()
{
    $companyId = Auth::user()->company_id;
    // get user company by company id
    $company = Company::find($companyId);
    $planId = $company->plan_id;
    // get plan of the company by plan id
    $plan = Plan::find($planId);
    return $plan;
}
function getUserStatus($email)
{
    $status = true;
    $companyId = User::where('email', $email)->first();
    // get user company by company id
    $company = Company::find($companyId->company_id);
    // dd($company);
    if (!$company) {
        $status = true;
    } else {
        $planId = $company->plan_id;
        // get plan of the company by plan id
        if ($planId == 0) {
            $status = true;
        } else {
            $currentDateTime = Carbon::now();
            if ($currentDateTime > $company->expiry_date) {
                $status = false;
            }
        }
    }

    return $status;
}

function getIncomeExpenses()
{
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
    $accounts = Account::whereIn('category_id', $groupIncome)->orWhereIn('category_id', $group)->get();
    return $accounts;
}

function postDoubleEntries($uuid, $glcode, $debit, $credit, $detail, $transaction_date = NULl)
{
    // dd($uuid, $glcode, $debit,$credit, $detail,$transaction_date = NULl);
    $newJournal = new Journal();
    $newJournal->gl_code = $glcode;
    $newJournal->debit = $debit;
    $newJournal->credit = $credit;
    $newJournal->details = $detail;
    $newJournal->company_id = auth()->user()->company_id;
    $newJournal->uuid = $uuid;
    $newJournal->transaction_date = $transaction_date ?? now();
    $newJournal->save();
}
function customerledger($ci, $in,  $cd, $zero, $userId, $userCompany, $des, $bl )
{
    $newJournal = new CustomerPersonalLedger();
    $newJournal->customer_id = $ci;
    $newJournal->invoice_number = $in;
    $newJournal->debit = $cd;
    $newJournal->credit = 0;
    $newJournal->created_by = $userId;
    $newJournal->company_id = $userCompany;
    $newJournal->description = $des;
    $newJournal->balance = $bl;
    $newJournal->save();
}

function respond($status, $message, $data, $code)
{
    return response()->json([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], $code);
}

function getCompanyid()
{
    $user = Auth::user()->company_id;
    return $user;
}

function postToCashbook($transaction_date, $particular, $details, $bank, $gl_code, $chq_teller, $uuid, $payment_mode, $currency = NULL)
{
    // dd($currency);
    $cashbook = new Cashbook();
    $cashbook->transaction_date = $transaction_date;
    $cashbook->particular = $particular;
    $cashbook->details = $details;
    if (in_array($payment_mode, ["cash", "cheque"])) {
        $cashbook->cash = $bank;
    } else {
        $cashbook->pbank = $bank;
    }
    $cashbook->gl_code = $gl_code;
    $cashbook->chq_teller = $chq_teller;
    $cashbook->uuid = $uuid;
    $cashbook->currency_amount = $currency;
    $cashbook->save();
}

function getAvailableAssets()
{
    $assets = Fixed_Asset_Register::orderBy('created_at', 'desc');
    return $assets;
}

function getDisposedAssets()
{
    $user = auth()->user();
    // dd($user);
    $assets = AssetDisposal::where('province_id', $user->company_id)->orderBy('created_at', 'desc'); //->whereNull('parish_id');
    return $assets;
}

function getAssetTransfer()
{
    $assets = AssetTransfer::where('company_id', auth()->user()->company_id)->orderBy('created_at', 'desc');
    return $assets;

}
function insertOnBookingPayments($amount, $id, $bank, $mode, $teller, $pDate = NULl)
{
    $invoice = new BookingPayment();
    $invoice->booking_id = $id;
    $invoice->amount = $amount;
    $invoice->mode = $mode;
    $invoice->bank = $bank;
    $invoice->teller_number = $teller;
    $invoice->payment_date = $pDate ?? now();
    $invoice->save();
}

function getUserReceipt()
{
    $receipts = Receipt::where('company_id', getCompanyid());
    return $receipts;
}
function getSales()
{
    $sales = Sale::where('company_id', getCompanyid());
    return $sales;
}
function getSaleTransactons()
{
    $sales = SaleTransaction::where('company_id', getCompanyid())->with(['item', 'users']);
    return $sales;
}
function getUserPaymentVoucher()
{
    $payments = PaymentVoucherBreakdown::where('company_id', getCompanyid());
    return $payments;
}

function getJournalFilter()
{

    $ledgers = Journal::where('company_id', getCompanyid());
    return $ledgers;
}
function getFilterJournal()
{
    $ledgers = Journal::where('company_id', getCompanyid())->orderBy('created_at', 'desc');
    return $ledgers;
}

function getConsolidatedTotalIncome()
{
    // $pluckIdToArray = Category::where()
    $getAllId = Account::where('class_id', 4)->pluck('id')->toArray();
    // Loop through each account
    $credit = 0;
    $debit = 0;
    $credit = getFilterJournal()
        ->whereIn('gl_code', $getAllId)
        ->sum('credit');

    $debit = getFilterJournal()
        ->whereIn('gl_code', $getAllId)
        ->sum('debit');
    $amount = $credit - $debit;
    return $amount;
}
function getConsolidatedTotalExpense()
{
    $getAllId = Account::where('class_id', 5)->pluck('id')->toArray();
    // Loop through each account
    $credit = 0;
    $debit = 0;
    $credit = getFilterJournal()
        ->whereIn('gl_code', $getAllId)
        ->sum('credit');

    $debit = getFilterJournal()
        ->whereIn('gl_code', $getAllId)
        ->sum('debit');
    $amount = $debit - $credit;
    return $amount;
}

function getTotalIncomeWithEndDate($end)
{
    // $pluckIdToArray = Category::where()
    $getAllId = Account::where('class_id', 4)->pluck('id')->toArray();
    // Loop through each account
    $credit = 0;
    $debit = 0;
    $credit = getFilterJournal()
        ->whereIn('gl_code', $getAllId)
        ->whereDate('transaction_date', '<=', $end)
        ->sum('credit');

    $debit = getFilterJournal()
        ->whereIn('gl_code', $getAllId)
        ->whereDate('transaction_date', '<=', $end)
        ->sum('debit');

    $amount = $credit - $debit;
    return $amount;
}

function getTotalExpensesWithEndDate($end)
{
    // $pluckIdToArray = Category::where()
    $getAllId = Account::where('class_id', 5)->pluck('id')->toArray();
    // Loop through each account
    $credit = 0;
    $debit = 0;
    $credit = getFilterJournal()
        ->whereIn('gl_code', $getAllId)
        ->whereDate('transaction_date', '<=', $end)
        ->sum('credit');

    $debit = getFilterJournal()
        ->whereIn('gl_code', $getAllId)
        ->whereDate('transaction_date', '<=', $end)
        ->sum('debit');

    $amount = $debit - $credit;
    return $amount;
}
function allTransactions()
{

    $transactions = MyTransactions::where('company_id', getCompanyid());
    return $transactions;
}
function topCustomer()
{

    $transactions = Customers::where('company_id', getCompanyid());
    return $transactions;
}
function getDirtyJournalFilter()
{
    $ledgers = Journal::where('company_id', getCompanyid());
    return $ledgers;
}
function getSalesInvoice()
{
    $sales = SaleInvoice::where('company_id', getCompanyid());
    return $sales;
}
function getPurchaseInvoice()
{
    $sales = PurchaseInvoice::where('company_id', getCompanyid());
    return $sales;
}
function getPurchaseOrders()
{
    $orders = PurchaseOrder::where('company_id', getCompanyid())->with(['supplier']);
    return $orders;
}
function getRequisition()
{
    $request = Requisition::where('company_id', getCompanyid())->with(['department', 'stocks', 'user', 'description']);
    return $request;
}

function stockInventory($item, $old, $new, $quantity, $stock, $amount, $detail)
{
    // dd($item,$old,$new,$quantity,$stock,$amount);
    $inventory = new StockInventory();
    $inventory->item_id = $item;
    $inventory->stock_id = $stock;
    $inventory->amount = $amount;
    $inventory->old_quantity = $old;
    $inventory->new_quantity = $new;
    $inventory->quantity = $quantity;
    $inventory->description = $detail;
    $inventory->save();
}



function validateBooking()
{
    // DB::beginTransaction();
    $currentDateTime = Carbon::now();
    // dd($currentDateTime->toTimeString());
    $bookings = Booking::where('company_id', getCompanyid())
        ->where(function ($query) {
            $query->where('status', 'pending')
                ->orWhere('status', 'ongoing');
        })
        ->where(function ($query) use ($currentDateTime) {
            $query->whereDate('event_date', '<=', $currentDateTime->toDateString());
        })->get();
    // dd($bookings);
    foreach ($bookings as $booking) {
        // if($booking->event_date <= $currentDateTime->toDateString()){
        $description = $booking->description;
        if ($booking->event_date < $currentDateTime->toDateString()) {
            $booking->update(['status' => 'completed']);
            // check the balance of booking and total to know how to go about the posting
            $total = $booking->amount;
            $paid = $booking->paid;
            $balance = $booking->balance;
            $uuid = $booking->uuid;
            // this is advance account
            $advance = $booking->booking_account;
            // this is income account
            $glcode = $booking->income_account;
            // this is receivable account
            $receivable = $booking->asset_account;
            $detail = $booking->description;
            $particular = $booking->particulars;
            if ($balance > 0) {
                // debit receivables with the balance
                postDoubleEntries($uuid, $receivable, $balance, 0, $detail, $booking->event_date);
                // credit income account with total booking amount
                postDoubleEntries($uuid, $glcode, 0, $total, $detail, $booking->event_date);
                if ($paid > 0) {
                    // debit advance account with paid
                    postDoubleEntries($uuid, $advance, $paid, 0, $detail, $booking->event_date);
                }
                //post the balance to receivable
                insertBalanceTransaction($balance, $balance, 0, $booking->end_date, $detail, $booking->booking_order, 1, $uuid, $booking->event_date, "Booking", $particular, $receivable);
            } else {
                // credit income account with total booking amount
                postDoubleEntries($uuid, $glcode, 0, $total, $detail, $booking->event_date);
                // debit advance account with total booking amount
                postDoubleEntries($uuid, $advance, $total, 0, $detail, $booking->event_date);
                //post total amount as receipt
                insertTransaction($total, 0, 0, now(), $detail, $booking->booking_order, 3, $uuid, $booking->event_date, "Booking");
            }


        } elseif ($booking->start_hour < $currentDateTime->toTimeString()) {
            // dd($booking);
            if ($booking->start_hour <= $currentDateTime->toTimeString() && $booking->end_hour > $currentDateTime->toTimeString()) {
                $booking->update(['status' => 'ongoing']);
                // dd($booking->end_hour, $currentDateTime->toTimeString());
                return true;
            }
            // dd("here");
            // if($booking->start_hour <= $currentDateTime->toTimeString() && $booking->end_hour <= $currentDateTime->toTimeString()){
            $booking->update(['status' => 'completed']);

            $total = $booking->amount;
            $paid = $booking->paid;
            $balance = $booking->balance;
            $uuid = $booking->uuid;
            // this is advance account
            $advance = $booking->booking_account;
            // this is income account
            $glcode = $booking->income_account;
            // this is receivable account
            $receivable = $booking->asset_account;
            $detail = $booking->particulars;
            if ($balance > 0) {
                // debit receivables with the balance
                postDoubleEntries($uuid, $receivable, $balance, 0, $detail, $booking->event_date);
                // credit income account with total booking amount
                postDoubleEntries($uuid, $glcode, 0, $total, $detail, $booking->event_date);
                if ($paid > 0) {
                    // debit advance account with paid
                    postDoubleEntries($uuid, $advance, $paid, 0, $detail, $booking->event_date);
                }
                //post the balance to receivable
                insertTransaction($balance, $balance, 0, now(), $detail, $booking->booking_order, 1, $uuid, $booking->event_date, "Booking");
            } else {
                // credit income account with total booking amount
                postDoubleEntries($uuid, $glcode, 0, $total, $detail, $booking->event_date);
                // debit advance account with total booking amount
                postDoubleEntries($uuid, $advance, $total, 0, $detail, $booking->event_date);
                //post total amount as receipt
                insertTransaction($total, 0, 0, now(), $detail, $booking->booking_order, 3, $uuid, $booking->event_date, "Booking");
            }
        }

    }
    // DB::commit();
    // ->update(['status' => 'completed']);
    return true;

    // DB::rollback();
}

function customerLedgers()
{
    $payables = CustomerPersonalLedger::where('company_id', auth()->user()->company_id)->orderBy('created_at', 'DESC');
    return $payables;
}

function saveCustomerLedger($customer, $number, $debit, $credit, $description = NULL, $balance = 0)
{
    $save = new CustomerPersonalLedger();
    $save->customer_id = $customer;
    $save->invoice_number = $number;
    $save->debit = $debit;
    $save->credit = $credit;
    $save->description = $description;
    $save->balance = $balance;
    $save->save();
}
function saveSupplierLedger($customer, $number, $debit, $credit, $description = NULL, $balance = 0)
{
    $save = new SupplierPersonalLedger();
    $save->supplier_id = $customer;
    $save->invoice_number = $number;
    $save->debit = $debit;
    $save->credit = $credit;
    $save->description = $description;
    $save->balance = $balance;
    $save->save();
}

function uploadImage($file, $path)
{
    if ($file->getSize() > 2 * 1024 * 1024) {
        return 'The image size must not exceed 2MB.';
    }
    $image_name = $file->getClientOriginalName();
    $image_name_withoutextensions = pathinfo($image_name, PATHINFO_FILENAME);
    $name = str_replace(" ", "", $image_name_withoutextensions);
    $image_extension = $file->getClientOriginalExtension();
    $file_name_extension = trim($name . '.' . $image_extension);
    $uploadedFile = $file->move(public_path($path), $file_name_extension);
    return url('/') . '/' . $path . '/' . $file_name_extension;
}

function transactionInsertion($company, $amount, $reference, $status = NULL)
{
    Transaction::create([
        "company_id" => $company,
        "amount" => $amount,
        "reference" => $reference,
        "status" => $status,
    ]);
}
function receivablesInsertion($amount, $type, $description, $transaction_date, $reference = NULL, $transaction_type = NULL)
{
    AllTransaction::create([
        "amount" => $amount,
        "transaction_number" => $reference,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $transaction_date ?? now(),
        "transaction_type" => $transaction_type,
    ]);
}

function receivablesInsertionNew($amount, $reference, $type, $description, $transaction_date, $transaction_type = NULL)
{
    MyTransactions::create([
        "amount" => $amount,
        "invoice_number" => $reference,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $transaction_date ?? now(),
        "saving_type" => $transaction_type,
    ]);
}
function payables()
{
    $payables = MyTransactions::where('company_id', auth()->user()->company_id)->where('type', 2)->orderBy('created_at', 'DESC');
    return $payables;
}

//1 is for receivables 2 is for payables 3 is for receipts 4 is for expenses
function insertTransaction($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration)
{
    // if($type == 3){
    $check = MyTransactions::where('uuid', $uuid)->where('balance', '>', 0)->first();
    if ($check) {
        $prev = $check->balance;
        $new = $prev - $amount;
        $total = $check->amount_paid + $amount;
        $check->update(['amount_paid' => $total, 'balance' => $new]);
    }

    // }

    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
    ]);
}
function insertBalanceTransaction($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $particular, $receivable)
{
    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "particular" => $particular,
        "debit_gl_code" => $receivable,
    ]);
}
//1 is for receivables 2 is for payables 3 is for receipts 4 is for expenses
function insertReceiptTransaction($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $debitGl, $invId)
{

    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "debit_gl_code" => $debitGl,
        "invoice_id" => $invId,
    ]);
}
function insertExpenseTransaction($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $debitGl, $invId)
{

    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "credit_gl_code" => $debitGl,
        "invoice_id" => $invId,
    ]);
}
//1 is for receivables 2 is for payables 3 is for receipts 4 is for expenses
function insertPayabale($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $supplier)
{

    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "supplier_id" => $supplier,
    ]);
}

function getAllAssets()
{
    $type = auth()->user()->model;
    // dd($type);
    // $type = auth()->user()->model_id;
    $user = auth()->user();
    //  dd($user->model, $user->type);
    switch ($type) {
        case 'Checker':
            // Handle case 3
            $assets = Fixed_Asset_Register::where('parish_id', $user->parish_id)->orderBy('created_at', 'desc');
            return $assets;
            break;
        case 'Initiator':
            // Handle case 3
            $assets = Fixed_Asset_Register::where('parish_id', $user->parish_id)->orderBy('created_at', 'desc');
            return $assets;
            break;
        case 'Continent':
            // Handle case 3
            $assets = Fixed_Asset_Register::where('continent_id', $user->continent_id)->orderBy('created_at', 'desc');
            return $assets;
            break;
        case 'Region':

            // Handle case 3
            $assets = Fixed_Asset_Register::where('region_id', $user->region_id)->orderBy('created_at', 'desc');
            return $assets;
            break;
        case 'Province':

            // dd($user->province_id);
            // Handle case 3
            $assets = Fixed_Asset_Register::where('province_id', $user->province_id);
            // ->orderBy('created_at', 'desc')->whereNull('date_disposed');


            return $assets;
            break;
        case 'Zone':
            // Handle case 3
            $assets = Fixed_Asset_Register::where('zone_id', $user->zone_id)->orderBy('created_at', 'desc');
            return $assets;
            break;
        case 'Area':
            // Handle case 3
            $assets = Fixed_Asset_Register::where('area_id', $user->area_id)->orderBy('created_at', 'desc');
            return $assets;
            break;
        case 'Parish':
            // Handle case 3
            // dd($user->parish_id);
            $assets = Fixed_Asset_Register::where('parish_id', $user->parish_id);
            // ->orderBy('created_at', 'desc')->whereNull('date_disposed')->get();
            // dd($assets);
            return $assets;

            break;

        default:
            // Handle default case or unknown cases
            // dd($type);
            $assets = Fixed_Asset_Register::query();
            return $assets;
            break;
    }

}

function getYearRange($startYear, $endYear)
{
    $years = [];
    for ($year = $startYear; $year <= $endYear; $year++) {
        $years[] = $year;
    }
    return $years;
}

function transferAsset($fixedAsset, $newValue)
{
    $transfer = new AssetTransfer();
    // dd($fixedAsset,$newValue);
    $transfer->previous_continent = $fixedAsset['continent_id'];
    $transfer->previous_region = $fixedAsset['region_id'];
    $transfer->previous_province = $fixedAsset['province_id'];
    $transfer->previous_zone = $fixedAsset['zone_id'];
    $transfer->previous_area = $fixedAsset['area_id'];
    $transfer->previous_parish = $fixedAsset['parish_id'];
    $transfer->new_continent = $newValue->continent_id;
    $transfer->new_region = $newValue->region_id;
    $transfer->new_province = $newValue->province_id;
    $transfer->new_zone = $newValue->zone_id;
    $transfer->new_area = $newValue->area_id;
    $transfer->new_parish = $newValue->parish_id;
    $transfer->asset_id = $newValue->id;
    $transfer->remarks = "transfer";
    $transfer->save();
}
function getAssets()
{

    $assets = Fixed_Asset_Register::where('province_id', auth()->user()->company_id)
        ->orderBy('created_at', 'desc')->whereNull('date_disposed');
    return $assets;

}

function insertPayabaleCode($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $supplier, $credit, $invoiceId)
{

    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "supplier_id" => $supplier,
        "credit_gl_code" => $credit,
        "invoice_id" => $invoiceId,
    ]);
}
function insertReceivable($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $customer)
{

    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "customer_id" => $customer,
    ]);
}
function insertReceivableCode($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $customer, $debit, $invoiceID)
{

    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "customer_id" => $customer,
        "debit_gl_code" => $debit,
        "invoice_id" => $invoiceID,
    ]);
}
function insertReceipt($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $mode, $teller, $particular)
{
    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "payment_mode" => $mode,
        "teller_number" => $teller,
        "particular" => $particular,
    ]);



}

function allTransactionsFilter()
{
    $transaction = MyTransactions::where('company_id', getCompanyid())->orderBy('created_at', 'DESC');
    return $transaction;
}
function getSalesOrders()
{

    $salesorder = SalesOrders::where('company_id', auth()->user()->company_id)
        ->with('customer', 'currency', 'company', 'supporting_document', 'general_invoice')->orderBy('created_at', 'DESC');
    return $salesorder;
}
function getPurchaseOrder()
{

    $purchaseorder = PurchaseOrder::where('company_id', auth()->user()->company_id)
        ->with('supplier', 'company', 'supporting_document', 'general_invoice')->orderBy('created_at', 'DESC');
    return $purchaseorder;
}
function getAllQuotes()
{

    $salesorder = Quotes::where('company_id', auth()->user()->company_id)
        ->with('customer', 'currency', 'company', 'supporting_document', 'general_invoice')->orderBy('created_at', 'DESC');
    return $salesorder;
}

function removeReceivable($invoiceNumber)
{
    // Find the receivable record by invoice number
    $receivable = MyTransactions::where('invoice_number', $invoiceNumber)->first();

    if ($receivable) {

        $receivable->delete();


    }
}
function restoreReceivable($invoiceNumber)
{
    // Find the receivable record by invoice number
    $receivable = MyTransactions::withTrashed()->where('invoice_number', $invoiceNumber)->first();

    if ($receivable) {

        $receivable->restore();


    }
}
function forceDeleteReceivable($invoiceNumber)
{
    // Find the receivable record by invoice number
    $receivable = MyTransactions::withTrashed()->where('invoice_number', $invoiceNumber)->first();

    if ($receivable) {

        $receivable->forceDelete();


    }
}
function removePayable($invoiceNumber)
{
    // Find the receivable record by invoice number
    $payable = MyTransactions::where('invoice_number', $invoiceNumber)->first();

    if ($payable) {

        $payable->delete();


    }
}
function forceDeletePayable($invoiceNumber)
{
    // Find the receivable record by invoice number
    $payable = MyTransactions::withTrashed()->where('invoice_number', $invoiceNumber)->first();

    if ($payable) {

        $payable->forceDelete();


    }
}
function restorePayable($invoiceNumber)
{
    // Find the receivable record by invoice number
    $payable = MyTransactions::withTrashed()->where('invoice_number', $invoiceNumber)->first();

    if ($payable) {

        $payable->restore();


    }
}
function insertPayable($amount, $balance, $paid, $date, $description, $invoice, $type, $uuid, $invoiceDate, $narration, $supplier, $credit, $debit, $document, $teller, $payableType = NULL)
{

    MyTransactions::create([
        "amount" => $amount,
        "balance" => $balance,
        "amount_paid" => $paid,
        "invoice_number" => $invoice,
        "teller_number" => $teller,
        "description" => $description,
        "type" => $type,
        "transaction_date" => $date,
        "date_of_invoice" => $invoiceDate,
        "uuid" => $uuid,
        "narration" => $narration,
        "supplier_id" => $supplier,
        "credit_gl_code" => $credit,
        "debit_gl_code" => $debit,
        "document" => $document,
        "payable_type" => $payableType,
    ]);
}
function getPayableTypes()
{
    $type = auth()->user()->type;
    // dd($type);
    // $type = auth()->user()->model_id;
    $user = auth()->user();
    //  dd($user->model, $user->type);
    switch ($type) {
        case '1':
            // Handle case 3
            $assets = Payable_Type::orderBy('created_at', 'desc');
            return $assets;
            break;
        // case '2':
        //     // Handle case 3
        //     $assets = Payable_Type::where('continent_id', $user->continent_id)->orderBy('created_at', 'desc');
        //     return $assets;
        //     break;
        // case '3':
        //     // Handle case 3
        //     $assets = Payable_Type::where('region_id', $user->region_id)->orderBy('created_at', 'desc');
        //     return $assets;
        //     break;
        // case '4':
        //     // Handle case 3
        //     $assets = Payable_Type::where('province_id', $user->province_id)->orderBy('created_at', 'desc');
        //     return $assets;
        //     break;

        default:
            // Handle default case or unknown cases
            // dd($type);
            $assets = Payable_Type::where('province_id', $user->company_id)->orderBy('created_at', 'desc');
            return $assets;
            break;
    }

}
function stockInventoryReversal($item, $old, $new, $quantity, $stock, $amount, $detail)
{
    // Create a new StockInventory record
    $inventory = new StockInventory();
    $inventory->item_id = $item;
    $inventory->stock_id = $stock;
    $inventory->amount = $amount;
    $inventory->old_quantity = $old;
    $inventory->new_quantity = $new;
    $inventory->quantity = $quantity;
    $inventory->description = $detail;
    $inventory->save();
}

function myaudit($action, $model, $description = null)
{
    $agent = new Agent();
    // Get device information
    $deviceName = $agent->device();
    // Get operating system information
    $platform = $agent->platform();
    // Get browser information
    $browser = $agent->browser();
    $userAgent = $agent->getUserAgent();

    //let's check the differences in their values

    $userId = Auth::id() ?? 0;

    DB::table('audit_trails')->insert([
        'user_id' => $userId,
        'action' => $action,
        'description' => $description,
        'model_type' => get_class($model) ?? '',
        'url' => url()->current(),
        'machine_name' => $deviceName . ' , ' . $platform . ' , ' . $browser . ' ' . $userAgent,
        'ip_address' => request()->ip(),
        'model_id' => $model->id ?? '',
        'auditable_id' => $model->id ?? '',
        'old_values' => json_encode($model->getOriginal() ?? []),
        'new_values' => json_encode($model->getAttributes() ?? []),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}
