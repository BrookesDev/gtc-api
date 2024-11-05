<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta name="description" content="Payroll System">
    <meta name="author" content="Brookes Professional Services Limited | Agbeniga Ambali">
    <title>Payroll System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/dist/img/favicon.png') }}">
    <link href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/metisMenu/metisMenu.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/typicons/src/typicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/themify-icons/themify-icons.min.css') }}" rel="stylesheet">
    <!--Third party Styles(used by this page)-->
    <link href="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/datatables/responsive.bootstrap4.min.css') }}" rel="stylesheet">
    <!--Third party Styles(used by this page)-->
    <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/select2-bootstrap4/dist/select2-bootstrap4.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/plugins/jquery.sumoselect/sumoselect.min.css') }}" rel="stylesheet">
    <!--Start Your Custom Style Now-->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('assets/dist/css/style2.css') }}" rel="stylesheet">
</head>

<body class="fixed">
    <!-- Page Loader -->
    <div class="page-loader-wrapper">
        <div class="loader">
            <div class="preloader">
                <div class="spinner-layer pl-green">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div>
                    <div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
            <p>Please wait...</p>
        </div>
    </div>
    <!-- #END# Page Loader -->
    <div class="wrapper">
        <!-- Sidebar  -->
        <nav class="sidebar sidebar-bunker">
            <div class="sidebar-header">
                <!--<a href="index.html" class="logo"><span>bd</span>task</a>-->
                <a href="index.html" class="logo"><img src="{{ asset('payrollk.png') }}"
                        alt=""></a>
            </div>
            <!--/.sidebar header-->
            <div class="profile-element d-flex align-items-center flex-shrink-0">
                <div class="avatar online">
                    <img src="{{ asset('assets/dist/img/avatar-1.jpg') }}" class="img-fluid rounded-circle"
                        alt="">
                </div>
                <div class="profile-text">
                    <h6 class="m-0">{{ Auth::user()->name }}</h6>
                    <span>{{ Auth::user()->email }}</span>
                </div>
            </div>
            <!--/.profile element-->
            <form class="search sidebar-form" action="#" method="get">
                <div class="search__inner">
                    <input type="text" class="search__text" placeholder="Search...">
                    <i class="typcn typcn-zoom-outline search__helper" data-sa-action="search-close"></i>
                </div>
            </form>
            <!--/.search-->
            <div class="sidebar-body">
                <nav class="sidebar-nav">
                    <ul class="metismenu">
                        <li class="nav-label">Main Menu</li>
                        <li class="mm-active">
                            <a class="has-arrow material-ripple" href="#">
                                <i class="typcn typcn-home-outline mr-2"></i>
                                Dashboard
                            </a>

                        </li>
                        <li>
                            <a class="has-arrow material-ripple" href="#">
                                <i class="typcn typcn-chart-pie-outline mr-2"></i>
                                Configuration
                            </a>
                            <ul class="nav-second-level">
                                <li><a href="{{ route('roles_home') }}">Manage Role</a></li>
                                <li><a href="{{ route('departments_home') }}">Manage Department</a></li>
                                <li><a href="{{ route('company-home') }}">Manage Company</a></li>
                                <li><a href="{{ route('users_home') }}">Manage User</a></li>
                                <li><a href="{{ route('step_home') }}">Manage Step</a></li>
                                <li><a href="{{ route('level_home') }}">Manage Level</a></li>
                                <li><a href="{{ route('grades_home') }}">Manage Grade</a></li>
                                <li><a href="{{ route('staff_home') }}">Manage Staff</a></li>

                            </ul>
                        </li>

                        <li>
                            <a class="has-arrow material-ripple" href="#">
                                <i class="typcn typcn-mail mr-2"></i>
                                Deductions
                            </a>
                            <ul class="nav-second-level">
                                <li><a href="{{ route('deductions_type') }}">Deduction Type</a></li>
                                {{-- <li><a href="{{ route('deductions_specification_home') }}">Specification</a></li> --}}
                                <li><a href="{{ route('deductions_home') }}">View Deductions</a></li>

                            </ul>
                        </li>
                        <li>
                            <a class="has-arrow material-ripple" href="#">
                                <i class="typcn typcn-mail mr-2"></i>
                                Allowances
                            </a>
                            <ul class="nav-second-level">
                                <li><a href="{{ route('allowances_type') }}">Allowance Type</a></li>
                                <li><a href="{{ route('allowances_specification_home') }}">Specification</a></li>
                                <li><a href="{{ route('allowances_home') }}">Allowances</a></li>

                            </ul>
                        </li>
                        <li>
                            <a class="has-arrow material-ripple" href="#">
                                <i class="typcn typcn-mail mr-2"></i>
                                Manage Payroll
                            </a>
                            <ul class="nav-second-level">
                                <li><a href="{{ route('salary_structure') }}">Salary Structure</a></li>
                                <li><a href="{{ route('payroll_home') }}">Payroll</a></li>
                                <li><a href="#">Allowances</a></li>

                            </ul>
                        </li>



                        <li><a href="{{ route('logout') }}"><i
                                    class="typcn typcn-attachment-outline mr-2"></i>Logout<span
                                    class="badge badge-success ml-auto">v1.1.0</span></a></li>
                        <li><a href="#"><i class="typcn typcn-support mr-2"></i>Documentation</a></li>
                    </ul>
                </nav>
            </div><!-- sidebar-body -->
        </nav>
        <!-- Page Content  -->
        <div class="content-wrapper">
            <div class="main-content">
                <nav class="navbar-custom-menu navbar navbar-expand-lg m-0">
                    <div class="sidebar-toggle-icon" id="sidebarCollapse">
                        sidebar toggle<span></span>
                    </div>
                    <!--/.sidebar toggle icon-->
                    <div class="d-flex flex-grow-1">
                        <ul class="navbar-nav flex-row align-items-center ml-auto">
                            <li class="nav-item dropdown quick-actions">
                                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                                    <i class="typcn typcn-th-large-outline"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div class="nav-grid-row row">
                                        <a href="#" class="icon-menu-item col-4">
                                            <i class="typcn typcn-cog-outline d-block"></i>
                                            <span>Settings</span>
                                        </a>
                                        <a href="#" class="icon-menu-item col-4">
                                            <i class="typcn typcn-group-outline d-block"></i>
                                            <span>Users</span>
                                        </a>
                                        <a href="#" class="icon-menu-item col-4">
                                            <i class="typcn typcn-puzzle-outline d-block"></i>
                                            <span>Components</span>
                                        </a>
                                        <a href="#" class="icon-menu-item col-4">
                                            <i class="typcn typcn-chart-bar-outline d-block"></i>
                                            <span>Profits</span>
                                        </a>
                                        <a href="#" class="icon-menu-item col-4">
                                            <i class="typcn typcn-time d-block"></i>
                                            <span>New Event</span>
                                        </a>
                                        <a href="#" class="icon-menu-item col-4">
                                            <i class="typcn typcn-edit d-block"></i>
                                            <span>Tasks</span>
                                        </a>
                                    </div>
                                </div>
                            </li>
                            <!--/.dropdown-->

                            <li class="nav-item dropdown notification">
                                <a class="nav-link dropdown-toggle badge-dot" href="#" data-toggle="dropdown">
                                    <i class="typcn typcn-bell"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <h6 class="notification-title">Notifications</h6>
                                    <p class="notification-text">You have 2 unread notification</p>
                                    <div class="notification-list">
                                        <div class="media new">
                                            <div class="img-user"><img
                                                    src="{{ asset('assets/dist/img/avatar.png') }}" alt="">
                                            </div>
                                            <div class="media-body">
                                                <h6>Congratulate <strong>Socrates Itumay</strong> for work anniversaries
                                                </h6>
                                                <span>Mar 15 12:32pm</span>
                                            </div>
                                        </div>
                                        <!--/.media -->
                                        <div class="media new">
                                            <div class="img-user online"><img
                                                    src="{{ asset('assets/dist/img/avatar2.png') }}" alt="">
                                            </div>
                                            <div class="media-body">
                                                <h6><strong>Joyce Chua</strong> just created a new blog post</h6>
                                                <span>Mar 13 04:16am</span>
                                            </div>
                                        </div>
                                        <!--/.media -->
                                        <div class="media">
                                            <div class="img-user"><img
                                                    src="{{ asset('assets/dist/img/avatar3.png') }}" alt="">
                                            </div>
                                            <div class="media-body">
                                                <h6><strong>Althea Cabardo</strong> just created a new blog post</h6>
                                                <span>Mar 13 02:56am</span>
                                            </div>
                                        </div>
                                        <!--/.media -->
                                        <div class="media">
                                            <div class="img-user"><img
                                                    src="{{ asset('assets/dist/img/avatar4.png') }}" alt="">
                                            </div>
                                            <div class="media-body">
                                                <h6><strong>Adrian Monino</strong> added new comment on your photo</h6>
                                                <span>Mar 12 10:40pm</span>
                                            </div>
                                        </div>
                                        <!--/.media -->
                                    </div>
                                    <!--/.notification -->
                                    <div class="dropdown-footer"><a href="#">View All Notifications</a></div>
                                </div>
                                <!--/.dropdown-menu -->
                            </li>
                            <!--/.dropdown-->
                            <li class="nav-item dropdown user-menu">
                                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                                    <!--<img src="{{ asset('assets/dist/img/user2-160x160.png') }}" alt="">-->
                                    <i class="typcn typcn-user-add-outline"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div class="dropdown-header d-sm-none">
                                        <a href="#" class="header-arrow"><i
                                                class="icon ion-md-arrow-back"></i></a>
                                    </div>
                                    <div class="user-header">
                                        <div class="img-user">
                                            <img src="{{ asset('assets/dist/img/avatar-1.jpg') }}" alt="">
                                        </div><!-- img-user -->
                                        <h6>Naeem Khan</h6>
                                        <span>example@gmail.com</span>
                                    </div><!-- user-header -->
                                    <a href="#" class="dropdown-item"><i class="typcn typcn-user-outline"></i>
                                        My Profile</a>
                                    <a href="#" class="dropdown-item"><i class="typcn typcn-edit"></i> Edit
                                        Profile</a>
                                    <a href="#" class="dropdown-item"><i class="typcn typcn-arrow-shuffle"></i>
                                        Activity Logs</a>
                                    <a href="#" class="dropdown-item"><i class="typcn typcn-cog-outline"></i>
                                        Account Settings</a>
                                    <a href="page-signin.html" class="dropdown-item"><i
                                            class="typcn typcn-key-outline"></i> Sign Out</a>
                                </div>
                                <!--/.dropdown-menu -->
                            </li>
                        </ul>
                        <!--/.navbar nav-->
                        <div class="nav-clock">
                            <div class="time">
                                <span class="time-hours"></span>
                                <span class="time-min"></span>
                                <span class="time-sec"></span>
                            </div>
                        </div><!-- nav-clock -->
                    </div>
                </nav>
                <!--/.navbar-->
                <!--Content Header (Page header)-->

                @yield('content')

            </div>
            <!--/.main content-->

            <footer class="footer-content">
                <div class="footer-text d-flex align-items-center justify-content-between">
                    <div class="copy">© 2023 BSRV Professionals, Providing Payment Solutions for Nigerians</div>
                    <div class="credit">Designed by: <a href="#">BrookesTechnologies</a></div>
                </div>
            </footer>
            <!--/.footer content-->
            <div class="overlay"></div>
        </div>
        <!--/.wrapper-->
    </div>
    <!--Global script(used by all pages)-->
    <script src="{{ asset('assets/plugins/jQuery/jquery-3.4.1.min.js') }}"></script>
    <script src="{{ asset('assets/dist/js/popper.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/metisMenu/metisMenu.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/perfect-scrollbar/dist/perfect-scrollbar.min.js') }}"></script>

    <script src="{{ asset('assets/plugins/datatables/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
    <!--Page Active Scripts(used by this page)-->
    <script src="{{ asset('assets/dist/js/pages/dashboard.js') }}"></script>

    <script src="{{ asset('js/formController.js') }}"></script>
    <script src="{{ asset('js/requestController.js') }}"></script>
    <script src="{{ asset('js/sweetalert/dist/sweetalert.min.js') }}"></script>


    <script>
        @if ($errors->any())
            new swal('Oops...', "{!! implode('', $errors->all(':message')) !!}", 'error')
        @endif

        @if (session()->has('message'))
            new swal(
                'Success!',
                "{{ session()->get('message') }}",
                'success'
            )
        @endif
        @if (session()->has('success'))
            new swal(
                'Success!',
                "{{ session()->get('success') }}",
                'success'
            )
        @endif
        @if (session()->has('warning'))
            new swal(
                'Oops!',
                "{{ session()->get('warning') }}",
                'warning'
            )
        @endif
        @if (session()->has('error'))
            new swal(
                'Oops!',
                "{{ session()->get('error') }}",
                'error'
            )
        @endif
    </script>
    @yield('scripts')

    <!--Page Scripts(used by all page)-->
    <script src="{{ asset('assets/dist/js/sidebar.js') }}"></script>
    <script src="{{ config('app.url') }}swal.js')}}"></script>

    {{-- @livewireScripts --}}



</body>

</html>
