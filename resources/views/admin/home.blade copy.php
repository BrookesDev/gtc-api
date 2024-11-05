@extends('layouts.admin.main')

@section('content')
    <!--Content Header (Page header)-->
    <div class="content-header row align-items-center m-0">
        {{-- <nav aria-label="breadcrumb" class="col-sm-4 order-sm-last mb-3 mb-sm-0 p-0 ">
            <ol class="breadcrumb d-inline-flex font-weight-600 fs-13 bg-white mb-0 float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav> --}}
        <div class="col-sm-8 header-title p-0">
            <div class="media">
                {{-- <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div> --}}
                <div class="media-body">
                    <h1 class="font-weight-bold">Dashboard </h1>

                </div>
            </div>
        </div>


    </div>

    <!--/.Content Header (Page header)-->
    <div class="body-content">


<div class="row">

    <div class="col-lg-12">
        <div class="row">
            <div class="col-md-3 col-lg-3">
                <!--Revenue today indicator-->
                <div class="p-2 bg-white rounded p-3 mb-3">
                    <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                        Employees
                    </div>
                    <div class="badge badge-success fs-26 text-monospace mx-auto">0</div>
                </div>
            </div>
            <div class="col-md-3 col-lg-3">
                <!--Revenue today indicator-->
                <div class="p-2 bg-white rounded p-3 mb-3">
                    <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                        Total Sales Balance
                    </div>
                    <div class="badge badge-success fs-26 text-monospace mx-auto">0</div>
                </div>
            </div>

            <div class="col-md-3 col-lg-3">
                <!--Balance indicator-->
                <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
                    <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                        Payables
                    </div>
                    <div class="text-muted">
                        <i class="fas fa fa-long-arrow-alt-up text-success"></i>
                        <span class="text-success text-size-2 text-monospace" style="font-size: 1.5rem">
                            0
                        </span>
                    </div>
                    <span class="small">Cashout requests pending:</span>
                    <span class="small text-monospace mx-auto text-danger">0
                    </span>

                </div>
            </div>
            <div class="col-md-3 col-lg-3">
                <!--Balance indicator-->
                <div class="p-2 bg-white rounded p-3 mb-3 shadow-sm">
                    <div class="header-pretitle text-muted fs-11 font-weight-bold text-uppercase mb-2">
                        Todays Sales
                    </div>
                    <div class="text-muted">
                        <i class="fas fa fa-long-arrow-alt-up text-success"></i>
                        <span class="text-success text-size-2 text-monospace" style="font-size: 1.5rem">
                            &#8358;0
                        </span>
                    </div>
                    <span class="small">This Week:</span>
                    <span class="small text-monospace mx-auto">&#8358;0
                    </span>
                </div>
            </div>



        </div>
    </div>
</div>
<div class="row mt-5">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fs-16 font-weight-700 mb-0">Transactions</h6>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class=table-responsive>
                    <table id="example" class="table table-striped table-bordered dt-responsive nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Customer</th>
                                <th>Amount(&#8358;)</th>
                                <th>Payable(&#8358;)</th>
                                <th>Commission(&#8358;)</th>
                                <th>Date & Time</th>
                                <th>Status</th>

                            </tr>
                        </thead>


                        <tbody>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>

</div>


    </div>
    <!--/.body content-->
@endsection
@section('scripts')
    @include('livewire.includes.datatable-js')
    <script src="{{ asset('js/sweetalert/dist/sweetalert.min.js') }}"></script>
    <!-- Third Party Scripts(used by this page)-->
    <script src="{{ asset('assets/dist/js/sidebar.js')}}"></script>

@endsection
