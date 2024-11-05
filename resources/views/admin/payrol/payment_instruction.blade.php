<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PATNA Accounting PRO.</title>
    <style>
        *{
            box-sizing: border-box;
        }
        body{
            background-color:rgb(211, 208, 208);

        }
        .container{
            width: 620px;
            margin: 0px auto;
            padding:40px;
            padding-top: 85px;
            background-color:white;
        }
        .address{
            display: flex;
            justify-content: space-between;
            padding-right: 20px;
        }
        table,th,td{
            border: 1px black solid;
            border-collapse: collapse;
            text-align:center;
            padding: 1px 10px;
        }
        table{
            width: 100%;
        }
        td{
            height: 19px;
        }
        @media(max-width:550px){
            .container{
                padding: 80px 20px;
                width:100%;

            }
            body{
                font-size: 12px;

            }

        }
    </style>
</head>

<body>
    <?php use App\Http\Controllers\PayrollController; ?>
    <div class="container">
        <div class="address">
            <p>
                <strong>To</strong><br>
                The Branch Manager, <br> United Bank for Africa, <br> Address of the Bank.
            </p>

            <p>
                Place: <br> Date: {{date('d-m-Y', strtotime(now()))}}
            </p>
        </div>
        <p style="text-align:center;">
            Sub: Employees Salary transfer letter for <strong>{{$payrolls[0]->month.' '.$payrolls[0]->year}}</strong>.
        </p>
        <p>Dear Sir/Madam,</p>
        <p>
           <span style="margin-left:40px;"> We</span> <strong>International School Ibadan</strong>
           request you to Kindly transfer the salaries of the following employees into their respective bank accounts by debiting from our account bearing a/c no.
           <strong>{{$bankLodged->gl_code ?? ''}}</strong> for the month of <strong>{{$payrolls[0]->month.' '.$payrolls[0]->year}}</strong>.
        </p>
        <table>
            <tr>
                <th>S No</th>
                <th>Account Name</th>
                <th>Bank Name</th>
                <th>A/C Number</th>
                <th>Amount</th>
            </tr>
            @foreach ($payrolls as $payroll)
            <tr>
                <td>{{$loop->iteration}}</td>
                <td>{{ $payroll->Staff->firstname.' '.$payroll->Staff->lastname.' '.$payroll->Staff->middlename }}</td>
                <td>{{$payroll->Staff->account_bank}}</td>
                <td>{{$payroll->Staff->account_number}}</td>
                <td>{{number_format($payroll->net_pay, 2)}}</td>
            </tr>
            @endforeach

            <tr>
                <td  style="text-align:right;"><strong>Total</strong></td>
                <td colspan="3"></td>
                <td>{{number_format($payrolls->sum('net_pay'), 2)}}</td>
            </tr>
            <tr>
                <td colspan="5" >{{ PayrollController::generateNumberInWords($payrolls->sum('net_pay')) }} Only</td>

            </tr>
        </table>
        <p style="text-align: center;">Thank You.</p>
        <p style="text-align: right;">For the <strong>[Company Name]</strong>,</p>
        <p style="text-align: right;">Authorized Person name & Signatory.</p>

    </div>
</body>

</html>
