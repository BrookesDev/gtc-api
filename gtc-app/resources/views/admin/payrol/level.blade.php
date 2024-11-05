@extends('layouts.master')

@section('content')
    <div class="content-header row align-items-center m-0">
        <nav aria-label="breadcrumb" class="col-sm-4 order-sm-last mb-3 mb-sm-0 p-0 ">
            <ol class="breadcrumb d-inline-flex font-weight-600 fs-13 bg-white mb-0 float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Manage Level</li>
            </ol>
        </nav>
        <div class="col-sm-8 header-title p-0">
            <div class="media">
                <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div>
                <div class="media-body">
                    <h1 class="font-weight-bold">Levels </h1>
                    <small></small>
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
                            <h6 class="fs-17 font-weight-600 mb-0 textInput">All Levels</h6>
                        </div>
                        <div class="text-right modal-effect ">
                            <button data-effect="effect-super-scaled" data-toggle="modal" data-target="#myModal"
                                class="btn btn-success rounded-pill w-100p btn-sm mr-1">
                                <i class="fas fa-plus"></i> Create New Level
                            </button>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <i id="loaderPage" class="fa fa-spinner fa-spin fa-2x"></i>
                    <div class="table-responsive">
                        <p style="display: none" id="recordURL">{{ route('load_level_data') }}</p>
                        <p style="display: none" id="deleteURL">{{ route('delete_level') }}</p>

                        <table data-page-length="1000" class="table table-striped table-bordered nowrap bootstrap4-modal">
                            <thead>
                                <tr class="ligth">
                                    <th>S/N</th>
                                    <th>Description</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th style="min-width: 100px">Action</th>
                                </tr>
                            </thead>

                            <tbody id="dataBody">



                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!--/.body content-->



    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Add New Level</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('add_new_level') }}" onsubmit="$('#loaderg').show()" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-body">

                        <div class="form-group">
                            <label class="font-weight-600">Description</label>
                            <input class="form-control" placeholder="description" type="text" required
                            name="description">
                        </div>


                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger " data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success"><span id="loaderg"
                                class="spinner-border spinner-border-sm me-2" role="status"
                                style="display: none"></span>Submit</button>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Edit Level</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('update_level') }}" onsubmit="$('#loaderTest').show()" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-body">
                            <input type="hidden" id="recordID" name="id">
                        <div class="form-group">
                            <label class="font-weight-600">Level:</label>
                            <input type="text" class="form-control" id="description" name="description"
                            placeholder="description" required />
                        </div>


                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger " data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success"><span id="loaderTest"
                                class="spinner-border spinner-border-sm me-2" role="status"
                                style="display: none"></span>Submit</button>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection
@section('scripts')
    @include('livewire.includes.datatable-js')


    @include('includes.record-js')
    @include('includes.delete-js')

@endsection
