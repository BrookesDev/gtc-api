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
                    <small>Manage Allowance Types</small>
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
                            <button type="button" class="btn btn-success rounded-pill w-100p btn-sm mr-1"
                                data-toggle="modal" data-target="#exampleModal1">Add New Allowance</button>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p style="display: none" id="deleteURL">{{ route('delete_allowance_type') }}</p>
                    <div class="table-responsive">
                        <table id="example" class="table table-striped table-bordered nowrap  dt-responsive">
                            <thead>
                                <tr class="ligth">
                                    <th>SN</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                
                                    <th style="min-width: 100px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                
                                @foreach ($allowanceTypes as $type)
                                    <tr>
                                        <td>{{ $loop->iteration }}  </td>
                                        <td>
                                            {{ $type->description }}
                                        </td>
                                        <td>
                                            {{ $type->CreatedBy->name }}
                                        </td>
                                     
                                        <td>
                                            {{ $type->created_at }}
                                        </td>
                                     
                                        <td>
                                            <a href="#" class="btn btn-success-soft btn-sm mr-1" data-id="{{$type->id }}" data-description="{{$type->description }}" data-toggle="modal" data-target="#modal-edit" id="editButton"><i
                                                    class="far fa-eye"></i></a>
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

    {{-- add new user  --}}
    <div class="modal fade" id="exampleModal1" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Add New Allowance Type</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('add_new_AllowanceType') }}" method="post" onsubmit="$('#loaderg').show()">
                    @csrf
                    <div class="modal-body">
                        
                        <div class="form-group">
                            <label class="font-weight-600">Type</label>
                            <input type="text" name="description" required class="form-control"
                                >
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
    {{-- edit user  --}}
    <div class="modal fade" id="modal-edit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Edit Allowance Type</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('update_allowance_type') }}" method="post" id="editUserform"
                    onsubmit="$('#loaderu').show()">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="recordID" name="id">
                        <div class="form-group">
                            <label class="font-weight-600">Level:</label>
                            <input type="text" class="form-control" id="description" name="description"
                            placeholder="description" required />
                        </div>
                     
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger " data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success"><span id="loaderu"
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
