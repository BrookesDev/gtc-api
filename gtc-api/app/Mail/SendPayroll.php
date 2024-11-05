<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\AllowanceType;
use App\Models\DeductionType;
use App\Models\StaffAllowance;
use App\Models\MonthlyPayroll;
use App\Models\Deduction;

class SendPayroll extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $payroll_id;
  

    public function __construct($payroll_id)
    {
        $this->payroll=$payroll= MonthlyPayroll::find($payroll_id);
        $this->deductions= Deduction::where('month',$payroll->month)->where('year', $payroll->year)
        ->where('staff_id',$payroll->staff_id)->get();
        $this->allowances=$allowances= StaffAllowance::where('month',$payroll->month)->where('year', $payroll->year)
        ->where('staff_id',$payroll->staff_id)->get();
        
        
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data['payroll']= $this->payroll;
        $data['allowances']= $this->allowances;
        $data['deductions']= $this->deductions;
        
        return $this
        ->subject('ISI PAYSLIP')
        ->view('view.email.payslip', $data);
    }
}
