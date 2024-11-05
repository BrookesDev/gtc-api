@extends('layouts.master')

@section('content')
<div class="content-header row align-items-center m-0">
    <nav aria-label="breadcrumb" class="col-sm-4 order-sm-last mb-3 mb-sm-0 p-0 ">
        <ol class="breadcrumb d-inline-flex font-weight-600 fs-13 bg-white mb-0 float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Companies</li>
        </ol>
    </nav>
    <div class="col-sm-8 header-title p-0">
        <div class="media">
            <div class="header-icon text-success mr-3"><i class="typcn typcn-spiral"></i></div>
            <div class="media-body">
                <h1 class="font-weight-bold">Companies </h1>
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
                            <h6 class="fs-17 font-weight-600 mb-0 textInput">All Companies</h6>
                        </div>
                        <div class="text-right">
                            <a href="{{route('create_new_role')}}"  class="btn btn-success rounded-pill w-100p btn-sm mr-1">
                                <i class="fas fa-plus"></i> Create New Company
                            </a>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered nowrap bootstrap4-modal">
                            <thead>
                                <tr class="ligth">
                                    <th>SN</th>
                                    <th> Name</th>
                                    <th>Address</th>
                                    <th>Email</th>

                                    <th style="min-width: 100px">Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                @foreach ($companies as $company)
                                    <tr>
                                        <td>
                                            {{ $loop->iteration }}
                                        </td>
                                        <td>
                                            {{ $company->company_name }}
                                        </td>
                                        <td>{{ $company->company_address }}</td>
                                        <td>{{ $company->company_email }}</td>
                                        <td class="text-end">

                                            <a  href="" class="btn btn-success-soft btn-sm mr-1"><i class="far fa-eye"></i></a>

                                            <a  href="javascript:void(0);" class="btn btn-danger-soft btn-sm" id="deleteRecord" data-rolename="{{ $company->name }}" data-id="{{ $company->id }}">
                                                <i class="far fa-trash-alt"></i></i></a>
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
    @include('livewire.includes.datatable-js')
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
@endsection
