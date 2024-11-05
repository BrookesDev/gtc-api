@extends('layouts.admin.main')


@section('content')

    <div class="content-header row align-items-center m-0">
        <nav aria-label="breadcrumb" class="col-sm-4 order-sm-last mb-3 mb-sm-0 p-0 ">
            <ol class="breadcrumb d-inline-flex font-weight-600 fs-13 bg-white mb-0 float-sm-right">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item active">Roles and Permission</li>
            </ol>
        </nav>
        <div class="col-sm-8 header-title p-0">
            <div class="media">
                <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div>
                <div class="media-body">
                    <h1 class="font-weight-bold">Vendors</h1>
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
                            <h6 class="fs-17 font-weight-600 mb-0 textInput">Create New Role</h6>
                        </div>
                        <div class="text-right">
                            <a href="{{ route('create_new_role') }}"
                                class="btn btn-success rounded-pill w-100p btn-sm mr-1">
                                <i class="fas fa-arrow"></i> Back
                            </a>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="post" onsubmit="$('#loaderg').show()">
                        @csrf
                        <div class="row">

                            <div class="col-lg-4">
                                <div class="card">
                                    <div class="card-body pt-0">
                                        <div class="card-header mb-4">
                                            <h5 class="card-title">Input Role Permission</h5>
                                        </div>

                                        <div class="form-group">
                                            <label>Role Name</label>

                                            <input type="text" class="form-control" name="name" value=""
                                                required>
                                        </div>
                                        @can('role-create')
                                            <button type="submit" class="btn btn-primary btn-block"><span id="loaderg"
                                                    class="spinner-border spinner-border-sm me-2" role="status"
                                                    style="display: none"></span>Submit</button>
                                        @endcan

                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="box-body">



                                </div>
                                <div class="card">
                                    <div class="card-body pt-0">
                                        <div class="card-header mb-4">
                                            <h5 class="card-title">Permissions</h5>
                                            <div class="input-row">

                                                <div class="checkbox">
                                                    <input type="checkbox">
                                                    <span class="check"><i class="checked-icon"
                                                            data-feather="check"></i></span>
                                                    <span class="label">Off</span>
                                                </div>


                                            </div>
                                        </div>
                                        <div class="row  demo-checkbox">


                                            @foreach ($permission as $value)
                                                <div class="col-md-3 mb-3 d-flex align-center">
                                                    <div class="card flex-fill">
                                                        <label for="md_checkbox_{{ $value->id }}">
                                                            <div class="card-body p-3 text-center">
                                                                <p class="card-text f-12">{{ $value->name }}</p>
                                                            </div>
                                                            <div class="card-footer align-center">
                                                                {{-- <input type="checkbox" id="notification_switch{{ $value->id }}" class="filled-in chk-col-info" value="{{ $value->id }}"> --}}
                                                                <span
                                                                    class="toggle-switch-label text-center demo-checkbox align-center">

                                                                    <input type="checkbox" style="height: 40px"
                                                                        id="md_checkbox_{{ $value->id }}"
                                                                        name="permission[]" value="{{ $value->id }}"
                                                                        class="filled-in chk-col-primary">
                                                                </span>
                                                            </div>
                                                    </div>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
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
                var user_name = $(this).data('rolename');
                var token = $("meta[name='csrf-token']").attr("content");
                var el = this;

                resetAccount(el, user_id, user_name);
            });


            async function resetAccount(el, user_id, user_name) {
                const willUpdate = await swal({
                    title: "Confirm Role Delete",
                    text: `Are you sure you want to delete this role (${user_name})?`,
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
                    swal("Role will not be deleted  :)");
                }
            }


            function performDelete(el, user_id) {
                //alert(user_id);
                try {
                    $.get('{{ route('role.destroy') }}?id=' + user_id,
                        function(data, status) {
                            console.log(status);
                            console.table(data);
                            if (status === "success") {
                                let alert = swal("Role successfully deleted!.");
                                location.reload()
                            }
                        }
                    );
                } catch (e) {
                    let alert = swal(e.message);
                }
            }

        })
    </script>

    <script>
        $(document).ready(function() {
            $('.toggle input[type="checkbox"]').click(function() {
                $(this).parent().toggleClass('on');

                if ($(this).parent().hasClass('on')) {
                    $(this).parent().children('.label').text('On')
                } else {
                    $(this).parent().children('.label').text('Off')
                }
            });

            $('.checkbox input[type="checkbox"]').click(function() {
                $(this).parent().toggleClass('on');

                if ($(this).parent().hasClass('on')) {
                    $(this).parent().children('.label').text('On')
                } else {
                    $(this).parent().children('.label').text('Off')
                }
            });

            $('.radio input[type="radio"]').click(function() {
                $(this).parent().addClass('on');

                if ($(this).parent().hasClass('on')) {
                    $(this).parent().children('.label').text('On')
                }
            });
            $('input').focusin(function() {
                $(this).parent().addClass('focus');
            });
            $('input').focusout(function() {
                $(this).parent().removeClass('focus');
            });
        });
    </script>
@endsection
