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
                <li class="breadcrumb-item active">Salary Structure</li>
            </ol>
        </nav>
        <div class="col-sm-8 header-title p-0">
            <div class="media">
                <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div>
                <div class="media-body">
                    <h1 class="font-weight-bold">Manage Salary Structure</h1>
                    <small>Manage Salary Structures </small>
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
                            <h6 class="fs-17 font-weight-600 mb-0 textInput">Manage Salary Structure</h6>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-info rounded-pill w-100p btn-sm mr-1"
                                data-toggle="modal" data-target="#exampleModal1">Add New Salary</button>



                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table id="example" data-page-length="1000" class="table table-striped table-bordered nowrap  dt-responsive">
                            <thead>
                                <tr class="ligth">
                                    <th>SN</th>
                                    <th>Level</th>
                                    <th>Step</th>
                                    <th>Annual Basic</th>

                                    <th>Created At</th>

                                    {{-- <th style="min-width: 100px">Action</th> --}}
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($SalaryStructure as $salary)
                                    <tr>
                                        <td>{{ $loop->iteration }}  </td>
                                        <td> {{ $salary->Level->description}} </td>
                                        <td> {{ $salary->Step->description}} </td>
                                        <td class="text-right"> {{ number_format($salary->annual_basic,2)}} </td>
                                        <td> {{date('d-M-Y', strtotime($salary->created_at))}} </td>


                                    </tr>
                                @endforeach


                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- add new user  --}}
    <div class="modal fade" id="exampleModal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Add Salary Structure</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('add_new_salary_structure') }}" method="post" onsubmit="$('#loaderg').show()" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">

                        <div class="form-group">
                            <label class="font-weight-600">Level</label>
                            <select name="level" id="" class="form-control">
                                <option value="">Select Level</option>
                                @foreach ($levels as $level)
                                <option value="{{ $level->id}}">{{ $level->description }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-600">Step</label>
                            <select name="step"  class="form-control">
                                <option value="">Select Step</option>
                                @foreach ($steps as $step)
                                <option value="{{ $step->id}}">{{ $step->description }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-600">Annual Basic</label>
                            <input type="number" accept="0.01" class="form-control" name="annual_basic">
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

    <!--/.body content-->
@endsection
@section('scripts')
    @include('includes.admin-datatable-scripts')
    @include('includes.delete-js')

    <script>
        $(document).ready(function() {


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
