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
                <li class="breadcrumb-item active">User Management</li>
            </ol>
        </nav>
        <div class="col-sm-8 header-title p-0">
            <div class="media">
                <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div>
                <div class="media-body">
                    <h1 class="font-weight-bold">Users </h1>
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
                            <h6 class="fs-17 font-weight-600 mb-0 textInput">Admin Users</h6>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-success rounded-pill w-100p btn-sm mr-1"
                                data-toggle="modal" data-target="#exampleModal1">Add New User</button>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @livewire('userlist')
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
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Add New User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('create_new_user') }}" method="post" onsubmit="$('#loaderg').show()">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="user_type" value="admin">
                        <div class="form-group">
                            <label class="font-weight-600">Full Name</label>
                            <input type="text" name="name" required class="form-control"
                                placeholder="Enter full name">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-600">Email address</label>
                            <input type="email" class="form-control" required name="email" placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label required class="font-weight-600">Phone Number </label>
                            <input type="number" name="phone_no" class="form-control" placeholder="Enter phone_no number">
                        </div>
                        <div class="form-group">
                            <label required class="font-weight-600">Role </label>
                            <select name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
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
    {{-- edit user  --}}
    <div class="modal fade" id="modal-edit" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel4"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-600" id="exampleModalLabel4">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin_user_update') }}" method="post" id="editUserform"
                    onsubmit="$('#loaderu').show()">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="user_type" value="admin">
                        <div class="form-group">
                            <label class="font-weight-600">Name</label>
                            <input type="text" name="name" required class="form-control" id="firstName"
                                placeholder="Enter full name">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-600">Email address</label>
                            <input type="email" class="form-control" required name="email" id="email"
                                placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label required class="font-weight-600">Phone Number </label>
                            <input type="number" name="phone_no" class="form-control" id="PhoneNumber"
                                placeholder="Enter phone_no number">
                        </div>
                        <div class="form-group">
                            <label required class="font-weight-600">Role </label>
                            <select name="role" class="form-control" required id="role">
                                <option value="">Select Role</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>

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

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            /* When click delete button */
            $('body').on('click', '#deleteRecord', function() {
                var user_id = $(this).data('id');
                var user_name = $(this).data('username');
                var token = $("meta[name='csrf-token']").attr("content");
                var el = this;

                resetAccount(el, user_id, user_name);
            });


            async function resetAccount(el, user_id, user_name) {
                const willUpdate = await swal({
                    title: "Confirm User Delete",
                    text: `Are you sure you want to delete this user (${user_name})?`,
                    icon: "warning",
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes!",
                    showCancelButton: true,
                    buttons: ["Cancel", "Yes, Delete"]
                });
                if (willUpdate) {
                    //performReset()
                    performDelete(el, user_id);
                } else {
                    swal("User record will not be deleted  :)");
                }
            }


            function performDelete(el, user_id) {
                //alert(user_id);
                try {
                    $.get('{{ route('user.destroy') }}?id=' + user_id,
                        function(data, status) {
                            console.log(status);
                            console.table(data);
                            if (status === "success") {
                                let alert = swal("User successfully deleted!.");
                                location.reload()
                            }
                        }
                    );
                } catch (e) {
                    let alert = swal(e.message);
                }
            }


            $('body').on('click', '#edit-user', function() {

                var user_id = $(this).data('id');
                // alert('here');

                $.get('{{ route('user_edit') }}?id=' + user_id, function(data) {


                    var userID =
                        `<input name="id" value="${data.id}" id="userid" type="hidden" class="form-control">`;

                    $('#editUserform').append(userID);
                    // $('#userid').val(data.id);
                    $('#firstName').val(data.name)
                    $('#email').val(data.email);
                    $('#PhoneNumber').val(data.phone_no)
                    $('#role').val(data.roles.length != 0 ? data.roles[0].id : '')


                })
            });

        })
    </script>
@endsection
