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
                <li class="breadcrumb-item active">Allowance</li>
            </ol>
        </nav>
        <div class="col-sm-8 header-title p-0">
            <div class="media">
                <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div>
                <div class="media-body">
                    <h1 class="font-weight-bold">Manage Allowance </h1>
                    <small>Manage Allowance</small>
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
                            <h6 class="fs-17 font-weight-600 mb-0 textInput">Manage Allowance</h6>
                        </div>
                        <div class="text-right">
                            
                          
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p style="display: none" id="deleteURL">{{ route('delete_allowance') }}</p>
                    <div class="table-responsive">
                        <table id="example" class="table table-striped table-bordered nowrap  dt-responsive">
                            <thead>
                                <tr class="ligth">
                                    <th>SN</th>
                                    <th>Staff</th>
                                    <th>Allowance</th>
                                    <th>Year</th>
                                    <th>Month</th>
                                    <th>Amount</th>
                                    <th>Created At</th>
                
                                    <th style="min-width: 100px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                
                                @foreach ($allowances as $type)
                                    <tr>
                                        <td>{{ $loop->iteration }}  </td>
                                        <td>
                                            {{ $type->Staff->lastname.' '.$type->Staff->firstname. ' '.$type->Staff->middlename }}
                                        </td>
                                        <td>
                                            {{ $type->Allowance->description}}
                                        </td>
                                     
                                        <td>
                                            {{ $type->year }}
                                        </td>
                                     
                                        <td>
                                            {{ $type->month }}
                                        </td>
                                     
                                        <td class="text-end">
                                            {{ number_format($type->amount,2) }}
                                        </td>
                                     
                                        <td>
                                      
                                            <a href="#" class="btn btn-danger-soft btn-sm" id="deleteRecord" data-id="{{$type->id }}"><i
                                                    class="far fa-trash-alt"></i></a>
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
