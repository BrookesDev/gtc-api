@extends('layouts.master')

@section('content')
    <div class="content-header row align-items-center m-0">
        <nav aria-label="breadcrumb" class="col-sm-4 order-sm-last mb-3 mb-sm-0 p-0 ">
            <ol class="breadcrumb d-inline-flex font-weight-600 fs-13 bg-white mb-0 float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Manage Staff</li>
            </ol>
        </nav>
        <div class="col-sm-8 header-title p-0">
            <div class="media">
                <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div>
                <div class="media-body">
                    <h1 class="font-weight-bold">Staff </h1>
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
                            <h6 class="fs-17 font-weight-600 mb-0 textInput">All Staff</h6>
                        </div>
                        <div class="text-right modal-effect ">
                            <a href="{{ route('add_new_staff') }}"
                                class="btn btn-success rounded-pill w-100p btn-sm mr-1">
                                <i class="fas fa-plus"></i> Create New Staff
                            </a>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <i id="loaderPage" class="fa fa-spinner fa-spin fa-2x"></i>
                    <div class="table-responsive">

                        <p style="display: none" id="recordURL">{{ route('load_staff_data') }}</p>
                        <p style="display: none" id="deleteURL">{{ route('delete_staff') }}</p>
                        <table class="table table-striped table-bordered nowrap bootstrap4-modal">
                            <thead>
                                <tr class="ligth">
                                    <th>S/N</th>
                                    <th>Staff ID</th>
                                    <th>Title</th>
                                    <th>Name</th>
                                    <th>Phone Number</th>
                                    <th>Email</th>
                                    <th>Address</th>
                                    <th>Qualification</th>
                                    <th>Grade</th>
                                    <th>Step</th>
                                    <th>Level</th>
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



@endsection
@section('scripts')
    @include('livewire.includes.datatable-js')

    @include('includes.staff-record-js')
    @include('includes.delete-js')

@endsection
