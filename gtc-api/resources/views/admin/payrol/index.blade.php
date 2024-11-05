@extends('layouts.master')

@section('styles')
    <style>
        table.dataTable {
            width: 100% !important;
        }

        table.table-striped {
            width: 100%;
        }
    </style>
@endsection
@section('content')
    <div class="content-header row align-items-center m-0">
        <nav aria-label="breadcrumb" class="col-sm-4 order-sm-last mb-3 mb-sm-0 p-0 ">
            <ol class="breadcrumb d-inline-flex font-weight-600 fs-13 bg-white mb-0 float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Payroll</li>
            </ol>
        </nav>
        <div class="col-sm-8 header-title p-0">
            <div class="media">
                <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div>
                <div class="media-body">
                    <h1 class="font-weight-bold">Manage Payroll</h1>
                    <small>Manage Payroll </small>
                </div>
            </div>
        </div>
    </div>

    <!--/.Content Header (Page header)-->
    <div class="body-content">


        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fs-17 font-weight-600 mb-0 textInput">Manage Payroll</h6>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-info rounded-pill w-100p btn-sm mr-1"
                                data-toggle="modal" data-target="#exampleModal1">Generate Payroll</button>

                            <button type="button" class="btn btn-success rounded-pill w-100p btn-sm mr-1"
                                data-toggle="modal" data-target="#exampleModal2">Generate Payment Instruction</button>
                                <a href="{{ route('download.database') }}">download DB</a>



                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table data-page-length="1000" class="table table-striped table-bordered nowrap">
                            <thead>
                                <tr class="ligth">
                                    <th>SN</th>
                                    <th>Staff Number</th>
                                    <th>Staff Name</th>
                                    <th>Year</th>
                                    <th>Month</th>

                                    <th>Monthly Basic</th>
                                    @foreach ($allowances as $allowance)
                                    <th>{{ $allowance->description }} Allowance</th>
                                    @endforeach
                                    <th>Pension</th>
                                    <th>NHIS</th>
                                    <th>NHFUND</th>
                                    <th>Tax</th>
                                    @foreach ($deductions as $deduction)
                                    <th>{{ $deduction->description }}</th>

                                    @endforeach
                                    <th>Gross Pay</th>
                                    <th>NetPay</th>

                                    <th>Created At</th>

                                    <th style="min-width: 100px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payrolls as $payroll)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $payroll->Staff->staff_id }}</td>
                                    <td>{{ $payroll->Staff->firstname.' '.$payroll->Staff->lastname.' '.$payroll->Staff->middlename }}</td>
                                    <td>{{ $payroll->year }}</td>

                                    <td>{{ $payroll->month }}</td>

                                    <td class="text-right">{{ number_format($payroll->monthly_basic,2) }}</td>
                                    @foreach ($allowances as $allowance)
                                    <td class="text-right">{{ number_format(($staffAllowances->where('allowance_id', $allowance->id)
                                    ->where('staff_id', $payroll->staff_id)->where('year',$payroll->year)->where('month',$payroll->month)
                                    ->first()->amount?? 0),2) }}</td>
                                    @endforeach
                                    <td class="text-right">{{ number_format($payroll->pension,2) }}</td>
                                    <td class="text-right">{{ number_format($payroll->nhis,2) }}</td>
                                    <td class="text-right">{{ number_format($payroll->nhfund,2) }}</td>
                                    <td class="text-right">{{ number_format($payroll->tax,2) }}</td>
                                    @foreach ($deductions as $deduction)
                                    <td class="text-right">{{ number_format(($staffDeductions->where('deduction_type_id', $deduction->id)
                                        ->where('staff_id', $payroll->staff_id)->where('year',$payroll->year)->where('month',$payroll->month)
                                        ->first()->amount?? 0),2) }}</td>
                                    @endforeach
                                    <td class="text-right">{{ number_format($payroll->gross_pay,2) }}</td>
                                    <td class="text-right">{{ number_format($payroll->net_pay,2) }}</td>

                                    <td>{{ $payroll->created_at }}</td>
                                    <td>
                                        <a href="{{ route('pay_slip', $payroll->id) }}" class="btn btn-sm btn-info">Pay Slip</a>
                                    </td>

                                </tr>

                                @endforeach




                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- generate payroll  --}}
    <div class="modal fade" id="exampleModal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Generate Payroll</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('generate_monthly_payroll') }}" method="post" onsubmit="$('#loaderg').show()" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-600">Year</label>
                            <select name="year" id="" class="form-control">
                                <option value="">Select Year</option>
                                @foreach ($years as $year)
                                <option value="{{ $year}}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-600">Month</label>
                            <select name="month" class="form-control" required>
                                <option value="">Select </option>
                                @foreach ($months as $month)
                                <option value="{{ $month }}">{{ $month }}</option>
                                @endforeach

                            </select>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger " data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success"><span id="loaderg"
                                class="spinner-border spinner-border-sm me-2" role="status"
                                style="display: none"></span>Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- generate payment Instruction  --}}
    <div class="modal fade" id="exampleModal2" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Generate Payment Instruction</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('generate_payment_instruction') }}" method="get" onsubmit="$('#loaderf').show()">

                    <div class="modal-body">
                        <div class="form-group">
                            <label class="font-weight-600">Bank</label>
                            <select name="bank" id="" class="form-control">
                                <option value="">Select Bank</option>
                                @foreach ($banks as $bank)
                                <option value="{{ $bank['id']}}">{{ $bank['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-600">Year</label>
                            <select name="year" id="" class="form-control">
                                <option value="">Select Year</option>
                                @foreach ($years as $year)
                                <option value="{{ $year}}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-600">Month</label>
                            <select name="month" class="form-control" required>
                                <option value="">Select </option>
                                @foreach ($months as $month)
                                <option value="{{ $month }}">{{ $month }}</option>
                                @endforeach

                            </select>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger " data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success"><span id="loaderf"
                                class="spinner-border spinner-border-sm me-2" role="status"
                                style="display: none"></span>Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--/.body content-->
@endsection
@section('scripts')
    @include('includes.admin-datatable-scripts')
    @include('includes.delete-js')

    <script>
        $(document).ready(function() {
        $('.table').dataTable();

    //when user clicks on edit button
    $('body').on('click', '#editButton', function() {
        // alert("Nathaniel")
        var id = $(this).data('id');
        var description = $(this).data('description');
        $('#recordID').val(id);
        $('#description').val(description);

    });


});
</script>
@endsection
