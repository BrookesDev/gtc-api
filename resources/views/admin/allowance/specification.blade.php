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
                    <h1 class="font-weight-bold">Manage Allowance Specification</h1>
                    <small>Manage Allowances for all levels</small>
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
                            <button type="button" class="btn btn-info rounded-pill w-100p btn-sm mr-1"
                                data-toggle="modal" data-target="#exampleModal1">Add New Allowance</button>
                            
                            

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p style="display: none" id="deleteURL">{{ route('delete_allowance_specification') }}</p>
                    <div class="table-responsive">
                        <table id="example" class="table table-striped table-bordered nowrap  dt-responsive">
                            <thead>
                                <tr class="ligth">
                                    <th>SN</th>
                                    <th>Allowance</th>
                                    <th>Lower Level</th>
                                    <th>Upper Level</th>
                                    <th>Percent</th>
                                    <th>Fixed Amount</th>
                                    <th>Created At</th>
                
                                    <th style="min-width: 100px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                
                                @foreach ($allowances as $spec)
                                    <tr>
                                        <td>{{ $loop->iteration }}  </td>
                                        <td> {{ $spec->Allowance->description }} </td>
                                        <td> {{ $spec->LowerLevel->description }} </td>
                                        <td> {{ $spec->UpperLevel->description }} </td>
                                        <td> {{ $spec->percentage==0? '-':$spec->percentage }} </td>
                                        <td class="text-right"> {{ number_format($spec->fixed_amount)}} </td>
                                        <td> {{ $spec->created_at}} </td>
                                        
                                        <td>
                                      
                                            <a href="#" class="btn btn-danger-soft btn-sm" id="deleteRecord" data-id="{{$spec->id }}"><i
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
                <form action="{{ route('specify_new_allowance') }}" method="post" onsubmit="$('#loaderg').show()">
                    @csrf
                    <div class="modal-body">
                        
                        <div class="form-group">
                            <label class="font-weight-600">Allowance Type</label>
                            <select name="allowance_type" id="" class="form-control">
                                <option value="">Select Type</option>
                                @foreach ($allowanceTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->description }}</option>                                    
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-600">Lower Level</label>
                            <select name="lower_level" id="" class="form-control" required>
                                <option value="">Select Type</option>
                                @foreach ($levels as $level)
                                <option value="{{ $level->id }}">{{ $level->description }}</option>                                    
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-600">Upper Level</label>
                            <select name="upper_level" id="" class="form-control" required>
                                <option value="">Select Level</option>
                                @foreach ($levels as $level)
                                <option value="{{ $level->id }}">{{ $level->description }}</option>                                    
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-600">Spec. Type</label>
                            <select name="spec_type" id="" class="form-control" required>
                                <option value="">Select Spec. Type</option>
                                <option value="Amount">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                              
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-600">Spec. Value</label>
                            <input  class="form-control" name="spec_value" required />                            
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
