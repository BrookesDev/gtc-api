<?php

namespace App\Http\Controllers;

use App\Mail\SendPayroll;
use App\Models\AllowanceAmount;
use App\Models\AllowanceType;
use App\Models\Bank;
use App\Models\Deduction;
use App\Models\DeductionType;
use App\Models\Grade;
use App\Models\Level;
use App\Models\MonthlyPayroll;
use App\Models\SalaryStructure;
use App\Models\Staff;
use App\Models\StaffAllowance;
use App\Models\Step;
use function App\Helpers\api_request_response;
use function App\Helpers\bad_response_status_code;
use function App\Helpers\generate_uuid;
use function App\Helpers\success_status_code;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use NumberFormatter;

class PayrollController extends Controller
{
    protected $currentRouteName;
    protected $email;
    public function __construct()
    {

        $this->currentRouteName = Route::currentRouteName();

    }
    public function index(Request $request){

         //connect to api to fetch all banks
         $baseURL = env('API_URL');
         if($baseURL== null){
             return redirect()->back()->withErrors('Please specify the api url');
         }
         $token = Session::get('PersonalAPIAccessToken');
         // dd($baseURL);

        //let's check the incoming url
        // if(url()->current()== route('payroll_home')){
        //     //i.e the route without any parameter, let's show the last month


        // }
        // dd('yaa');
        $data['banks']= Bank::all();
        $yearAndMonth = $this->getYeargetMonth();
        $data['years']=$yearAndMonth['years'];
        $data['months']=$yearAndMonth['months'];
        $data['allowances']=$allowances=AllowanceType::all();
        $data['deductions']= $deductions = DeductionType::all();
        $data['staffAllowances']=$staffAllowances = StaffAllowance::all();
        $data['staffDeductions']= $staffDeductions = Deduction::all();


        $monthlyPayrolls = MonthlyPayroll::get();
            foreach ($monthlyPayrolls as $key => $payroll) {
                    foreach ($allowances as $key => $allowance) {
                        $alldescription = $allowance->description. '_allowance';
                        $payroll->$alldescription =$staffAllowances->where('allowance_id', $allowance->id)
                        ->where('staff_id', $payroll->staff_id)->where('year',$payroll->year)->where('month',$payroll->month)
                        ->first()->amount?? 0;
                        # code...
                    }

                    foreach ($deductions as $key => $deduction) {
                        # code...
                        $deducdescription = $deduction->description. '_deduction';
                        $payroll->$deducdescription =$staffDeductions->where('deduction_type_id', $deduction->id)
                        ->where('staff_id', $payroll->staff_id)->where('year',$payroll->year)->where('month',$payroll->month)
                        ->first()->amount?? 0;
                        $payroll->staff_name =  $payroll->Staff->firstname.' '.$payroll->Staff->lastname.' '.$payroll->Staff->middlename;
                    }

                # code...
            }

            $data['payrolls']= $monthlyPayrolls;


        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["data" => $data, "message" => "Data fetch successfully"], 201);
        }

        // $data['banks']= Bank::all();
        // dd($data);
        return view('admin.payrol.index', $data);
    }

    public function generatePayroll(Request $request){
        $input= $request->all();

        $validator = Validator::make($request->all(), [
            'month'=> 'required',
            'year'=> 'required'

        ]);
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }


        //we can minimize the loop and also avoid duplicate
        $existingMonthlPayroll = MonthlyPayroll::where('month',$request->month)->where('year', $request->year)->pluck('staff_id');
                //get all staff;
        $staff = Staff::whereNotIn('id',$existingMonthlPayroll)->get();
        // dd($staff);

        $uuid = generate_uuid();
        // dd($uuid);

        $month= $request->month;
        $year= $request->year;
        //let the work begins
        foreach ($staff as $key => $employee) {
            $employeeLevel =$level= $employee->level;
            $employeeStep = $employee->step;


            //let's get his annual basic
            $employeeAnnualBasic = SalaryStructure::where('level', $employeeLevel)->where('step',$employeeStep)->first();
            //check whether the record exist

            if(!$employeeAnnualBasic){

                if(substr($this->currentRouteName,0,3)== "api"){
                    if ($validator->fails()) {
                        return response()->json(['error' => "There is no salary structure for level $employeeLevel step $employeeStep." ],400);
                    }
                }
                return 0;
                // throw new \Exception("There is no salary structure for level $employeeLevel step $employeeStep.");
            }

            //yes the record exist
            //get the annual basic
            $annualBasic = $employeeAnnualBasic->annual_basic;
            $monthlyBasic = round(($annualBasic/12),2);
            //get all allowances for this employee

            // $employeeAllowance = $monthlyBasic * each of those allowances
            //build up your allowance
            $allowances = $this->getEmployeeAllowances($level,$monthlyBasic);
            // check if any
            // dd($monthlyBasic);

            $allowancesRecords =$allowances['allowancesList']?? [];
            $allowanceSum =$allowances['allowanceSum']?? 0;
            //remember to save the allowances after the execution of the code
            //taxable income = $monthlyBasic + all allowances

            //calculate all statutory deductions i.e pension and nhfund
            $grossIncome = $monthlyBasic +$allowanceSum;
            $pension = $employee->pension==1? $grossIncome * 0.08: 0;
            $nhfund = $employee->nhfund==1? $grossIncome * 0.025: 0;
            //let's come back to define nhis
            $nhis = 100;
            $statutoryDeductions = $pension +$nhfund +$nhis;

            $taxable_income = $monthlyBasic -$statutoryDeductions;
            $grosPay = $monthlyBasic + $allowanceSum;
            //get the appropriate tax to be paid
            $tax = $this->calculateAnnualTax($taxable_income);
            //let's get other deductions
            $otherDeductionRecords = Deduction::where('staff_id', $employee->id)->where('year',$year)->where('month', $month)->get();
            $otherDeduction =round($otherDeductionRecords->sum('amount'),2);
            $total_deduction= $statutoryDeductions + $otherDeduction +$tax;
            $netPay = $grosPay -($total_deduction);


            // dd($grossIncome, $total_deduction,$nhfund,$monthlyBasic,$allowances);
            // save to MonthlyPayroll table

            $newMonthlyPayroll = new MonthlyPayroll();
            $newMonthlyPayroll->staff_id = $employee->id;
            $newMonthlyPayroll->staff_number = $employee->staff_id;
            $newMonthlyPayroll->year = $year;
            $newMonthlyPayroll->month = $month;
            $newMonthlyPayroll->annual_basic = $annualBasic;
            $newMonthlyPayroll->monthly_basic = $monthlyBasic;
            $newMonthlyPayroll->total_allowance = $allowanceSum;
            $newMonthlyPayroll->pension = $pension;
            $newMonthlyPayroll->nhis = $nhis;
            $newMonthlyPayroll->nhfund = $nhfund;
            $newMonthlyPayroll->tax = $tax;
            $newMonthlyPayroll->total_deduction = $total_deduction;
            $newMonthlyPayroll->gross_pay = $grosPay;
            $newMonthlyPayroll->net_pay = $netPay;
            $newMonthlyPayroll->uuid = $uuid;
            $saveMonthlyPayRoll= $newMonthlyPayroll->save();


            //save
            $this->saveStaffAllowance($allowancesRecords,$employee, $year,$month);

            # code...
        }

        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json([ "message" => "Payroll generated for the month of $month $year"], 201);
        }

        return redirect()->back()->with('success', "Payroll generated for the month of $month $year");

    }


    public function pay_slip(Request $request){
        //get id

        $id = $request->id;
        $data['payroll']=$payroll= MonthlyPayroll::find($id);
        $data['deductions']= Deduction::where('month',$payroll->month)->where('year', $payroll->year)
                                ->where('staff_id',$payroll->staff_id)->get();
        $data['allowances']= StaffAllowance::where('month',$payroll->month)->where('year', $payroll->year)
                                ->where('staff_id',$payroll->staff_id)->get();

        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["data"=> $data, "message" => "Data Fetched successfully"], 201);
        }
    return view('admin.payrol.pay_slip', $data);

    }



    public function calculateAnnualTax($taxable_income)
    {
        $amt_to_pay = 0;

        if ($taxable_income <= 300000) {
            $amt_to_pay = 0.07 * $taxable_income; //=IF(V4<=300000,7%*V4,
        } elseif ($taxable_income > 300000 && $taxable_income <= 600000) {
            $amt_to_pay = 21000 + ($taxable_income - 300000) * 0.11;
            // IF(AND(V4>300000,V4<=600000),21000+(V4-300000)*11%,
        } elseif ($taxable_income > 600000 && $taxable_income <= 1100000) {
            $amt_to_pay = 54000 + ($taxable_income - 600000) * 0.15;
            //IF(AND(V4>600000,V4<=1100000),54000+(V4-600000)*15%,
        } elseif ($taxable_income > 1100000 && $taxable_income <= 1600000) {
            $amt_to_pay = 129000 + ($taxable_income - 1100000) * 0.19;
            //IF(AND(V4>1100000,V4<=1600000),129000+(V4-1100000)*19%,
        } elseif ($taxable_income > 1600000 && $taxable_income <= 3200000) {
            $amt_to_pay = 224000 + ($taxable_income - 1600000) * 0.21;
            //IF(AND(V4>1600000,V4<=3200000),224000+(V4-1600000)*21%,
        } else {
            $amt_to_pay = 560000 + ($taxable_income - 3200000) * 0.24;
            //,560000+(V4-3200000)*24%)))))
        }

        return $amt_to_pay;
    }



    public function saveStaffAllowance($allowances,$staff, $year,$month){

        $staffId= $staff->id;
        $staff_number= $staff->staff_id;
        $allInput = [];
        // dd($allowances);

        foreach ($allowances as $key => $allowance) {
            $input['staff_id']= $staffId;
            $input['staff_number']= $staff_number;
            $input['allowance_id']= $allowance['allowance_id'];
            $input['amount']= $allowance['allowance_amount'];
            $input['month']= $month;
            $input['year']= $year;
            $input['created_at']= now();
            $input['updated_at']= now();
            //push the array for batch insertion to save our time
            array_push($allInput, $input);
        }

        $saveRecords = StaffAllowance::insert($allInput);
        return true;

    }
    public function getEmployeeAllowances($level, $monthlyBasic){
      $allowances= AllowanceAmount::where('upper_level','>=',$level)->where('lower_level','<=',$level)->get();

        $allowanceArray = [];
        $sumAllowances = 0;
        foreach ($allowances as $key => $allowance) {
            //check if percentage or fixed amount
            $isPercentage = $allowance->percentage!=0?true: false;
            $thisAllowance['allowance_id'] =$allowance->allowance_id;
            if($isPercentage==true){
                $thisAllowance['allowance_amount'] = round(($monthlyBasic * $allowance->percentage),2);
            }else{
                $thisAllowance['allowance_amount'] = round($allowance->fixed_amount,2);
            }

            array_push($allowanceArray, $thisAllowance);
            $sumAllowances= $sumAllowances+$thisAllowance['allowance_amount'];

            # code...
        }

        $data['allowancesList']= $allowanceArray;
        $data['allowanceSum']=round($sumAllowances,2);


        return $data;

    }



    public function salary_structure(){
        $data['SalaryStructure']= SalaryStructure::get();
        $data['levels']= Level::get();
        $data['steps']= Step::get();
        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json(["data"=> $data, "message" => "Data Fetched successfully"], 201);
        }
        return view('admin.payrol.salary_structure', $data);
    }

    public function addSalaryStructure(Request $request){
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'level'=> 'required',
            'step'=> 'required'

        ]);
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }

        $checkDuplicate = SalaryStructure::where('level',$request->level)->where('step', $request->step)->first();
        if($checkDuplicate){
            if(substr($this->currentRouteName,0,3)== "api"){
                return response()->json([ "message" => "Salary structure already specified"], 400);
            }
            return redirect()->back()->withErrors("Salary structure already specified");
        }

        $input['created_by']=auth()->user()->id;
        $saveRecord = SalaryStructure::create($input);
        if(substr($this->currentRouteName,0,3)== "api"){
            return response()->json([ "message" => "Salary Structure specified successfully"], 201);
        }
        return redirect()->back()->with('success', "Salary Structure specified successfully");
    }
    public function getYeargetMonth(){
        $year = $endYear= date('Y');
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = date('F', mktime(0, 0, 0, $month, 1, $year));
        }

        $data['months']=$months;
        $startYear = 2022;
        $years = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $years[] = $year;
        }

        $data['years']=array_reverse($years);

        return $data;
    }



    public function monthlyPAYERemittance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id'=> 'required'
        ]);
        //payment instruction
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }

        $companyID = $request->company_id;

       $data['banks']= Bank::where('company_id', $companyID)->get();
       $yearAndMonth = $this->getYeargetMonth();
       $data['years']=$yearAndMonth['years'];
       $data['months']=$yearAndMonth['months'];
       $data['allowances']=$allowances=AllowanceType::where('company_id', $companyID)->get();
       $data['deductions']= $deductions = DeductionType::where('company_id', $companyID)->get();
       $data['staffAllowances']=$staffAllowances = StaffAllowance::where('company_id', $companyID)->get();
       $data['staffDeductions']= $staffDeductions = Deduction::where('company_id', $companyID)->get();


       $monthlyPayrolls = MonthlyPayroll::where('company_id', $companyID)->get();
            foreach ($monthlyPayrolls as $key => $payroll) {
                   foreach ($allowances as $key => $allowance) {
                       $alldescription = $allowance->description. '_allowance';
                       $payroll->$alldescription =$staffAllowances->where('allowance_id', $allowance->id)
                       ->where('staff_id', $payroll->staff_id)->where('year',$payroll->year)->where('month',$payroll->month)
                       ->first()->amount?? 0;
                       # code...
                   }

                   foreach ($deductions as $key => $deduction) {
                       # code...
                       $deducdescription = $deduction->description. '_deduction';
                       $payroll->$deducdescription =$staffDeductions->where('deduction_type_id', $deduction->id)
                       ->where('staff_id', $payroll->staff_id)->where('year',$payroll->year)->where('month',$payroll->month)
                       ->first()->amount?? 0;
                       $payroll->staff_name =  $payroll->Staff->firstname.' '.$payroll->Staff->lastname.' '.$payroll->Staff->middlename;
                   }

               # code...
           }

           $data['payrolls']= $monthlyPayrolls;


       if(substr($this->currentRouteName,0,3)== "api"){
           return response()->json(["data" => $data, "message" => "Data fetch successfully"], 201);
       }


    }


    public function paymentInstruction(Request $request){
        $validator = Validator::make($request->all(), [
            'year'=> 'required',
            'month'=> 'required',


        ]);
        //payment instruction
        if(substr($this->currentRouteName,0,3)== "api"){
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()],400);
            }
        }

            $data['payrolls']=$payrolls= MonthlyPayroll::where('year', $request->year)->where('month', $request->month)->where('payment_status',0)->with(['Staff'])->get();
            if($payrolls->count()==0){

                if(substr($this->currentRouteName,0,3)== "api"){

                        return response()->json(['error' => "No avalaible record for the chosen month."],400);

                }
                return redirect()->back()->withErrors("No avalaible record for the chosen month.");
            }
            $bank = $request->bank;


            if(substr($this->currentRouteName,0,3)== "api"){
                    return response()->json(["data" => $data,'message' => "Data fetched successfully"],200);
            }

            return view('admin.payrol.payment_instruction', $data);
    }


    public static function generateNumberInWords($Numberamount)
    {

        $num    = ( string ) ( ( int ) $Numberamount );
        // dd($num,$amount);
        if( ( int ) ( $num ) && ctype_digit( $num ) )
        {
            $words  = array( );

            $num    = str_replace( array( ',' , ' ' ) , '' , trim( $num ) );

            $list1  = array('','one','two','three','four','five','six','seven',
                'eight','nine','ten','eleven','twelve','thirteen','fourteen',
                'fifteen','sixteen','seventeen','eighteen','nineteen');

            $list2  = array('','ten','twenty','thirty','forty','fifty','sixty',
                'seventy','eighty','ninety','hundred');

            $list3  = array('','thousand','million','billion','trillion',
                'quadrillion','quintillion','sextillion','septillion',
                'octillion','nonillion','decillion','undecillion',
                'duodecillion','tredecillion','quattuordecillion',
                'quindecillion','sexdecillion','septendecillion',
                'octodecillion','novemdecillion','vigintillion');

            $num_length = strlen( $num );
            $levels = ( int ) ( ( $num_length + 2 ) / 3 );
            $max_length = $levels * 3;
            $num    = substr( '00'.$num , -$max_length );
            $num_levels = str_split( $num , 3 );

            foreach( $num_levels as $num_part )
            {
                $levels--;
                $hundreds   = ( int ) ( $num_part / 100 );
                $hundreds   = ( $hundreds ? ' ' . $list1[$hundreds] . ' Hundred' . ( $hundreds == 1 ? '' : '' ) . ' ' : '' );
                $tens       = ( int ) ( $num_part % 100 );
                $singles    = '';

                if( $tens < 20 ) { $tens = ( $tens ? ' ' . $list1[$tens] . ' ' : '' );
                }
                else { $tens = ( int ) ( $tens / 10 ); $tens = ' and ' . $list2[$tens] . ' '; $singles = ( int ) ( $num_part % 10 ); $singles = ' ' . $list1[$singles] . ' ';
                 }
                $words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_part ) ) ? ' ' . $list3[$levels] . ' ' : '' ); } $commas = count( $words ); if( $commas > 1 )
            {
                $commas = $commas - 1;
            }

            $words  = implode( ', ' , $words );
            // dd($words);

            $words  = trim( str_replace( ' ,' , ',' , ucwords( $words ) )  , ', ' );
            if( $commas )
            {
                $words  = str_replace( ',' , ' and' , $words );
            }

            $prefix = "And";

            $var2 = str_replace("And", "and", $words);
            // dd($var2);
            if (substr($words, 0, strlen($prefix)) == $prefix) {
                $str = substr($words, strlen($prefix));
                // dd($str);
                $words = $str;
            }
            $amount = str_replace("And", "and", $words);//$words;




        }

        $formatter = new NumberFormatter('en', NumberFormatter::SPELLOUT);

        // Split the amount into naira and kobo
        $parts = explode('.', $Numberamount);
        $naira = $parts[0];
        $kobo = isset($parts[1]) ? str_pad($parts[1], 2, '0', STR_PAD_RIGHT) : '00';

        $amount = $amount .' naira';
        // Build the currency representation
        $currency = ucwords($naira) . ' naira';
        if ($kobo !== '00') {
            $formattedAmount = $formatter->format($kobo);
            $amount .= ', ' . ucwords($formattedAmount) . ' kobo';
        }


        return $amount ;
    }


    function send_payrollViaMail($id){

        //confirm that this thing exist
        // dd($id);
        $checkMonthlyPayroll = MonthlyPayroll::where('id', $id)->first();
        if(!$checkMonthlyPayroll){
            return redirect()->back()->withErrors("Data not found");
        }
        $this->email = $checkMonthlyPayroll->Staff->email;
        Mail::to($this->email)->send(new SendPayroll($id));

        return redirect()->back()->with('success', "Payroll sent successfully.");

    }
}
