<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THE INTERNATIONAL SCHOOL UNIVERSITY OF IBADAN</title>

</head>
<style>
@import url('https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@300;400;700&display=swap');
body{
    margin: 0;
    padding: 0;
    font-size: 16px;
    font-weight: 300;
    width: 100%;
    background-color: #fff;
    font-family: 'Roboto Condensed', sans-serif, 'Times New Roman', Times, serif;
}

@media print{
    #printButtonArea {
        display: none;
    }
}
*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
.maindiv{
    width: 700px;
    height: 600px;
    margin-left: 350px;
    margin-top: 50px;
    background: #fff;
    display: block;
    margin: auto;
    position: relative;
}
.tab{
    margin-left: 55px;
}
table, th, td {
    border:1px solid black;
    border-collapse: collapse;
}
.div1{
    /* box-shadow: 0 0 40px rgba(0, 0, 0, 0.3); */
    padding: 30px;
}
.tab1{
    width: 640px;
}
.tab2{
    width: 640px;
}
.tab3{
    width: 550px;
}
.tab4 {
    width: 640px;
  }
</style>
<body>
    <div class="maindiv">
        <div class="div1">
            <div class="tablediv">
                <table class="tab1">
                    <tr>
                        <th
                            style="text-align: center; color: #000;padding: 10px; font-family: 'Times New Roman', Times, serif; ">
                            THE INTERNATIONAL SCHOOL<br>UNIVERSITY OF IBADAN<br>IBADAN, NIGERIA</th>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding: 10px; font-family: 'Times New Roman', Times, serif; ">
                            <b> Monthly Salary Payslip</b></td>
                    </tr>
                </table>
                <table class="tab2">

                        <tr style="padding-bottom: 20px: border:none ">
                            <td style="margin-right: 10px; font-family: 'Times New Roman', Times, serif; border:none ">Employee
                                Name: </td>
                                <td style=" font-family: 'Times New Roman', Times, serif; border:none "><h3>{{ $payroll->Staff->firstname.' '.$payroll->Staff->lastname.' '.$payroll->Staff->middlename }}</h3></td>

                        </tr>

                        <tr style="padding-bottom: 10px">
                            <td style="margin-right: 20px; font-family: 'Times New Roman', Times, serif; border:none ">Month:</td>
                            <td style=" font-family: 'Times New Roman', Times, serif; border:none ">
                                <h3>{{ $payroll->month.' '.$payroll->year }}</h3></td>
                        </tr>



                </table>
                <div style="display: flex">
                    <table style="width:50%;" class="tab3">
                        <tr>
                            <th
                                style="background-color: rgb(236, 236, 241); color: #000; font-family: 'Times New Roman', Times, serif; ">
                                Earnings</th>
                            <th
                                style="background-color: rgb(236, 236, 241);; color: #000; font-family: 'Times New Roman', Times, serif; ">
                                Amount</th>

                        </tr>

                        <tr>
                           <td style="padding-left: 10px; font-family: 'Times New Roman', Times, serif; ">Basic</td>
                           <td style="text-align: center; font-family: 'Times New Roman', Times, serif; ">₦ {{ number_format($payroll->monthly_basic,2) }}</td>



                       </tr>
                        @foreach ($allowances as $allowance)
                                     <tr>
                                        <td style="padding-left: 10px; font-family: 'Times New Roman', Times, serif; ">{{ $allowance->AllowanceDetail->description }}</td>
                                        <td style="text-align: center; font-family: 'Times New Roman', Times, serif; ">₦ {{ number_format($allowance->amount,2) }}</td>



                                    </tr>
                                    @endforeach

                               <tr>
                                   <td
                                   style="background-color: rgb(236, 236, 241);; color: #000; padding-left: 10px; font-family: 'Times New Roman', Times, serif; ">
                                   Total Income</td>
                               <td style="text-align: center; font-family: 'Times New Roman', Times, serif; color:#000 ">
                                <h3>{{number_format($payroll->gross_pay,2) }}</h3></td>

                            </tr>
                    </table>

                    <table style="width:50%;">
                        <tr>
                            <th
                                style="background-color: rgb(236, 236, 241); color: #000; font-family: 'Times New Roman', Times, serif; ">
                                Deductions</th>
                            <th
                                style="background-color: rgb(236, 236, 241);; color: #000; font-family: 'Times New Roman', Times, serif; ">
                                Amount</th>

                        </tr>
                        @if($payroll->tax!=0)
                        <tr>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> Tax</td>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> ₦ {{number_format($payroll->tax,2)}}</td>


                        </tr>
                        @endif
                        @if($payroll->pension!=0)
                        <tr>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> Pension</td>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> ₦ {{number_format($payroll->pension,2)}}</td>


                        </tr>
                        @endif
                        @if($payroll->nhfund!=0)
                        <tr>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> NHFUND</td>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> ₦ {{number_format($payroll->nhfund,2)}}</td>


                        </tr>
                        @endif

                        @if($payroll->nhis!=0)
                        <tr>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> NHIS</td>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> ₦ {{number_format($payroll->nhis,2)}}</td>


                        </tr>
                        @endif
                        @foreach ($deductions as $deduction)
                        <tr>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> {{ $deduction->DeductionType->description }}</td>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;  "> ₦ {{number_format($deduction->amount,2)}}</td>


                        </tr>

                        @endforeach
                        <tr>

                            <td
                                style="background-color: rgb(236, 236, 241);; color: #000; padding-left: 10px; font-family: 'Times New Roman', Times, serif; ">
                                Total Deduction</td>
                            <td style="text-align: center; font-family: 'Times New Roman', Times, serif;color:#000 "><h3>{{number_format($payroll->total_deduction,2) }}</h3></td>
                        </tr>
                    </table>
                </div>

                <table style="width:100%;" class="tab3">


                    <tr>
                        <td style="width: 20%; border:none"></td>
                        <td style="height: 20px; width: 20%;border:none"></td>

                        <td style="text-align: center; font-family: 'Times New Roman', Times, serif; ">
                            <b>Gross Pay</b></td>
                        <td
                            style="text-align: center; color:#000; font-family: 'Times New Roman', Times, serif; ">
                            <h2>₦ {{number_format($payroll->gross_pay,2) }}</h2>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 20%; border:none"></td>
                        <td style="height: 20px; width: 20%;border:none"></td>

                        <td style="text-align: center; font-family: 'Times New Roman', Times, serif; ">
                            <b>Net Pay</b></td>
                        <td
                            style="text-align: center; color:#000; font-family: 'Times New Roman', Times, serif; ">
                            <h2>₦ {{number_format($payroll->net_pay,2) }}</h2>
                        </td>
                    </tr>

                    <table class="tab4">
                        <td style="margin-right: 20px; padding: 20px; font-family: 'Times New Roman', Times, serif; ">
                            &nbsp;&nbsp;Name of Bank:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <u>{{ $payroll->staff->account_bank }}</u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Account No:
                            <u>{{ $payroll->staff->account_number }}</u><br><br>&nbsp;&nbsp;<br><br>
                        </td>
                    </table>
                </table>

                <div id="printButtonArea" style="margin: 10px;text-align:right">
                    <button onclick="window.print()" style="background-color: green; color:#fff; padding: 10px; border-radius: 3px">Print</button>
                    <a href="{{ route('send_payroll_via_mail', $payroll->id) }}" style="background-color: rgb(19, 113, 236); color:#fff; padding: 10px;border-radius: 3px; text-decoration: none">Send via Mail</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
