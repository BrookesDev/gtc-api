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
                            <a href="window.history.back();" class="btn btn-success rounded-pill w-100p btn-sm mr-1">
                                <i class="fas fa-plus"></i> Back
                            </a>

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-body">
                        <form id="frm_main" method="post">
                            @csrf

                            <h4>Section A:</h4>


                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Title</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->title ?? '' }}" name="title">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Surname</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->lastname ?? '' }}" name="lastname">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">First Name</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->firstname ?? '' }}" name="firstname">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Middle Name</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->middlename ?? '' }}" name="middlename">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-3 col-form-label font-weight-600">Date
                                            of Birth</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="date"
                                                value="{{ $staff->dob ?? '' }}" name="dob">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Gender</label>
                                        <div class="col-sm-9">
                                            <select name="gender" class="form-control" required>
                                                <option value="">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Marital Status</label>
                                        <div class="col-sm-9">
                                            <select name="marital_status" class="form-control" required>
                                                <option value="{{ $staff->marital_status ?? '' }}">
                                                    {{ $staff->marital_status ?? '' }}</option>
                                                <option value="Single">Single</option>
                                                <option value="Married">Married</option>
                                                <option value="Divorce">Divorce</option>
                                                <option value="Widow">Widow</option>
                                            </select>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Phone Number</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->phone_number ?? '' }}" name="phone_number">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Email</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="email"
                                                value="{{ $staff->email ?? '' }}" name="email">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Staff ID</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->staff_id ?? '' }}" name="staff_id">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">RSA Number</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->rsa_number ?? '' }}" name="rsa_number">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h4>Section B:</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Qualification</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" required name="qualification">
                                                {{-- <option value="" selected>Select Qualification</option> --}}
                                                @if ((($staff->qualification??'') !=''))
                                                selected  <option value="{{ $staff->qualification?? '' }}" 
                                                >{{ $staff->qualification?? '' }}</option>
                                                @endif
                                              
                                                <option value="First School Leaving Certificate">First School Leaving
                                                    Certificate</option>
                                                <option value="ND">ND</option>
                                                <option value="NCE">NCE</option>
                                                <option value="HND">HND</option>
                                                <option value="BSc">BSc</option>
                                                <option value="B.ed">B.ed</option>
                                                <option value="BSc.ed">BSc.ed</option>
                                                <option value="M.ed">M.ed</option>
                                                <option value="MSc">MSc</option>
                                                <option value="phd">PhD</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Step</label>
                                        <div class="col-sm-9">
                                            <select name="step" class="form-control">
                                                <option value="">Select Step</option>
                                                @foreach ($step as $step)
                                                    <option value="{{ $step->id }}" 
                                                        @if ($step->id==($staff->step??''))
                                                        selected
                                                    @endif>{{ $step->description }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Grade</label>
                                        <div class="col-sm-9">
                                            <select name="grade" class="form-control">
                                                <option value="">Select Grade</option>
                                                @foreach ($grades as $grade)
                                                    <option value="{{ $grade->id }}" @if ($grade->id==($staff->grade??''))
                                                        selected
                                                    @endif>{{ $grade->description }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Level</label>
                                        <div class="col-sm-9">
                                            <select name="level" class="form-control" required name="level">
                                                <option value="">Select Level</option>
                                                @foreach ($levels as $level)
                                                    <option value="{{ $level->id }}" @if ($level->id==($staff->level??''))
                                                        selected
                                                    @endif>{{ $level->description }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Department</label>
                                        <div class="col-sm-9">

                                            <select name="dept_id" class="form-control" required>
                                                <option value="">Select Department</option>
                                                @foreach ($departments as $dept)
                                                    <option value="{{ $dept->id }}"  @if ($dept->id==($staff->dept_id??''))
                                                        selected
                                                    @endif>{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>


                            <h4>Section C</h4>
                            <br>
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Country</label>
                                        <div class="col-sm-9">

                                            <select name="country" class="form-control" required id="country">
                                                <option value="">Select Country</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}"
                                                        @if ($country->id==($staff->country??''))
                                                        selected
                                                    @endif
                                                        >{{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">City</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->city ?? '' }}" name="city">
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class="row" id="homeAdressNationalityDiv">

                            </div>
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Address</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->address ?? '' }}" name="address">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Employment
                                            Date</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="date"
                                                value="{{ $staff->employment_date ?? '' }}" name="employment_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Resignation/Retirement
                                            Date</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="date"
                                                value="{{ $staff->res_retir_date ?? '' }}" name="res_retir_date">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Account
                                            Number</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->account_number ?? '' }}" name="account_number">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Account
                                            Bank</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->account_bank ?? '' }}" name="account_bank">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label for="example-text-input"
                                            class="col-sm-3 col-form-label font-weight-600">Medical
                                            Condition</label>
                                        <div class="col-sm-9">
                                            <input class="form-control" required type="text"
                                                value="{{ $staff->medical_condition ?? '' }}" name="medical_condition">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h3>Statory Deductions</h3>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group form-check">
                                        <input type="checkbox" name="pension"
                                        @if (($staff->pension??'')==1)
                                        checked
                                    @endif class="form-check-input" value="1" id="checkPension">
                                        <label class="form-check-label" for="checkPension">Pension</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group form-check">
                                        <input type="checkbox" name="nhis" class="form-check-input" value="1"
                                        @if (($staff->nhis??'')==1)
                                        checked
                                    @endif id="checkNHIS">
                                        <label class="form-check-label" for="checkNHIS">NHIS</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group form-check">
                                        <input type="checkbox" name="nhfund"  @if (($staff->nhfund??'')==1)
                                        checked
                                    @endif class="form-check-input" value="1" id="checkNHFUND">
                                        <label class="form-check-label" for="checkNHFUND">NHFUND</label>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">

                                <button type="submit" class="btn btn-success"><span id="loaderg"
                                        class="spinner-border spinner-border-sm me-2" role="status"
                                        style="display: none"></span>Save changes</button>
                            </div>
                        </form>
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

            @if(Route::currentRouteName() === 'create_staff')
            $("#frm_main").on('submit', async function(e) {
                e.preventDefault();
                const serializedData = $("#frm_main").serializeArray();

                try {
                    var loader = $("#loaderg");
                    loader.show();
                    const postRequest = await request("{{ route('create_staff') }}", processFormInputs(
                        serializedData), 'post');

                    swal("Good Job", "Staff added successfully!.", "success");
                    $('#frm_main').trigger("reset");
                    $("#frm_main .close").click();
                    loader.hide();
                    window.location.reload();
                } catch (e) {
                    if ('message' in e) {
                        console.log('e.message', e.message);
                        loader.hide();
                        swal("Opss", e.message, "error");
                        //   loader.hide();
                    }
                }
            })
            @else
            $("#frm_main").on('submit', async function(e) {
                e.preventDefault();
                const serializedData = $("#frm_main").serializeArray();
                // alert('here');
                try {
                    var loader = $("#loaderg");
                    loader.show();
                    const postRequest = await request("{{ route('update_staff') }}", processFormInputs(
                        serializedData), 'post');

                    swal("Good Job", "Staff added successfully!.", "success");
                    $('#frm_main').trigger("reset");
                    $("#frm_main .close").click();
                    loader.hide();
                    window.location.reload();
                } catch (e) {
                    if ('message' in e) {
                        console.log('e.message', e.message);
                        loader.hide();
                        swal("Opss", e.message, "error");
                        //   loader.hide();
                    }
                }
            })
            @endif
        })
    </script>



    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $("#country").on("change click", function(e) {
                var id = $(this).val(); // $(this).data('id');
                console.log('here');
                var parentDIv = $('#homeAdressNationalityDiv');

                if (id == 156) {
                    //first empty the element
                    parentDIv.empty();
                    var StateNLGA =
                        `  <div class="col-md-6 homeAddIfNig"
                                    >

                                    <div class="form-group row">
                                            <label for="example-text-input"
                                                class="col-sm-3 col-form-label font-weight-600">State</label>
                                            <div class="col-sm-9">

                                                <select name="state" class="form-control" required id="mystate2">
                                                    <option value="">Select State</option>
                                                    @foreach ($states as $state)
                                                <option value="{{ $state->id_no }}"
                                                    {{ $state->id_no == ($staff->state ?? '') ? 'selected' : '' }}>
                                                    {{ $state->state }}</option>
                                            @endforeach
                                                </select>
                                            </div>
                                        </div>


                                </div>
                                <div class="col-md-6 homeAddIfNig"
                                    >
                                    <div class="form-group row">


                                        <label for="example-text-input"
                                                class="col-sm-3 col-form-label font-weight-600">LGA</label>
                                            <div class="col-sm-9">
                                                <select name="lga" class="form-control lgaa2" required id="homLga" >
                                                    <option value="">Select Local Government</option>
                                                    @foreach ($localgovt->where('state_id', $staff->hom_state ?? '') as $lga)
                                                <option value="{{ $lga->id_no }}"
                                                    {{ $lga->id_no == ($staff->hom_lga_id ?? '') ? 'selected' : '' }}>
                                                    {{ $lga->local_govt }}</option>
                                            @endforeach
                                                </select>



                                    </div>
                                </div>

                            `
                    //append the element in the parentDIv
                    parentDIv.append(StateNLGA);
                } else {
                    parentDIv.empty();
                }


                $("#mystate2").on("change click", function(e) {
                    $(".lgaa2").empty()
                    var id = $(this).val(); // $(this).data('id');
                    //    alert(id);
                    $.ajax({
                        url: '{{ route('get_state_lga') }}?id=' + id,
                        type: "GET",
                        dataType: "json",
                        success: function(response) {
                            var len = 0;
                            len = response['data'].length;
                            if (len > 0) {
                                for (var i = 0; i < len; i++) {
                                    // console.log(response);
                                    var id = response['data'][i].id_no;
                                    var descr = response['data'][i].local_govt;
                                    var option = "<option value='" + id + "'>" + descr +
                                        "</option>";
                                    $(".lgaa2").append(option);
                                }
                            }
                        }
                    });
                });
            });



        });
    </script>
@endsection
