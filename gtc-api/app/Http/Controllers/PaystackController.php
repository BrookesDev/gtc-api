<?php

namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\User;
use App\Models\Transaction;
use Paystack;// Paystack package
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaystackController extends Controller
{
    public function handleGatewayCallback()
    {

        $paymentDetails = Paystack::getPaymentData(); //this comes with all the data needed to process the transaction
        // Getting the value via an array method
        // dd($paymentDetails);
        $inv_id = $paymentDetails['data']['reference'];// Getting InvoiceId I passed from the form
        $status = $paymentDetails['data']['status']; // Getting the status of the transaction
        $amount = $paymentDetails['data']['amount']; //Getting the Amount
        $status = $paymentDetails['data']['status']; //Getting the status
        // get the company details

        // dd($status);
        if($status == "success"){ //Checking to Ensure the transaction was succesful

            $company = Company::where('reference', $inv_id)->first();
            if($company){
                $user = User::create([
                    'name' => $company->name,
                    'email' => $company->user_email,
                    'phone_no' => $company->phone,
                    'is_admin' => 1,
                    'user_type' => 'Super Admin',
                    'company_id' => $company->id,
                    'password' => $company->password,
                ]);
                $currentDateTime = Carbon::now();
                // Add one year to the current date and time
                $nextYearDateTime = $currentDateTime->addYear();
                $company->update(["status" => 1, "expiry_date" => $nextYearDateTime]);
                transactionInsertion($company->id,$amount,$inv_id,1);
            }else{
                $transaction = Transaction::where('reference',$inv_id)->first();
                if($transaction){
                    $transaction->update(["status" => 1]);
                }
            }

            $url = ("https://promixaccounting.com/login");
            // dd($url);
            return redirect()->to($url);
        }

        // Now you have the payment details,
        // you can store the authorization_code in your DB to allow for recurrent subscriptions
        // you can then redirect or do whatever you want
    }
}
