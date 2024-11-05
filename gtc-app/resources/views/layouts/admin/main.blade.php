<!doctype html>
<html lang="en">
<head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="Payroll System">
        <meta name="author" content="Brookes Professional Services Limited | Agbeniga Ambali">
        <title>Payroll System</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ asset('assets/dist/img/favicon.png')}}">
        <link href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
        <link href="{{ asset('assets/plugins/metisMenu/metisMenu.min.css')}}" rel="stylesheet">
        <link href="{{ asset('assets/plugins/fontawesome/css/all.min.css')}}" rel="stylesheet">
        <link href="{{ asset('assets/plugins/typicons/src/typicons.min.css')}}" rel="stylesheet">
        <link href="{{ asset('assets/plugins/themify-icons/themify-icons.min.css')}}" rel="stylesheet">
        <!--Third party Styles(used by this page)-->
        <link href="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
        <link href="{{ asset('assets/plugins/datatables/responsive.bootstrap4.min.css')}}" rel="stylesheet">
         <!--Third party Styles(used by this page)-->
        <link href="{{ asset('assets/plugins/select2/dist/css/select2.min.css')}}" rel="stylesheet">
        <link href="{{ asset('assets/plugins/select2-bootstrap4/dist/select2-bootstrap4.min.css')}}" rel="stylesheet">
        <link href="{{ asset('assets/plugins/jquery.sumoselect/sumoselect.min.css')}}" rel="stylesheet">
        <!--Start Your Custom Style Now-->
        <link href="{{ asset('assets/dist/css/style.css')}}" rel="stylesheet">
        <style>
            table.dataTable{
                width: 100% !important;
            }
            table.table-striped {
                width: 100%;
            }
        </style>
        @yield('styles')
        @livewireStyles
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
            @include('layouts.admin.sidebar')

            <!-- Page Content  -->

            <div class="content-wrapper">
                <div class="main-content">
                  @include('layouts.admin.header')
                  @yield('content')
                </div><!--/.main content-->
                <footer class="footer-content">
                    <div class="footer-text d-flex align-items-center justify-content-between">
                        <div class="copy">© 2023 BSRV Professionals, Providing Payment Solutions for Nigerians</div>
                        <div class="credit">Designed by: <a href="#">BrookesTechnologies</a></div>
                    </div>
                </footer><!--/.footer content-->
                <div class="overlay"></div>
            </div><!--/.wrapper-->
        </div>
        <!--Global script(used by all pages)-->
        @livewireScripts
        <script src="{{ asset('assets/plugins/jQuery/jquery-3.4.1.min.js')}}"></script>
        <script src="{{ asset('assets/dist/js/popper.min.js')}}"></script>
        <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.min.js')}}"></script>
        <script src="{{ asset('assets/plugins/metisMenu/metisMenu.min.js')}}"></script>
        <script src="{{ asset('assets/plugins/perfect-scrollbar/dist/perfect-scrollbar.min.js')}}"></script>

        <script src="{{ asset('assets/plugins/datatables/dataTables.min.js')}}"></script>
        <script src="{{ asset('assets/plugins/datatables/dataTables.bootstrap4.min.js')}}"></script>
        <!--Page Active Scripts(used by this page)-->
        <script src="{{ asset('assets/dist/js/pages/dashboard.js')}}"></script>
        <script src="https://js.pusher.com/7.2/pusher.min.js'"></script>

        <script src="{{ asset('js/formController.js') }}"></script>
        <script src="{{ asset('js/requestController.js') }}"></script>

        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            @if ($errors->any())
                Swal.fire('Oops...', "{!! implode('', $errors->all('<p>:message</p>')) !!}", 'error')
            @endif

            @if (session()->has('message'))
                Swal.fire(
                'Success!',
                "{{ session()->get('message') }}",
                'success'
                )
            @endif
            @if (session()->has('success'))
                Swal.fire(
                'Success!',
                "{{ session()->get('success') }}",
                'success'
                )
            @endif
            @if (session()->has('warning'))
                Swal.fire(
                'Oops!',
                "{{ session()->get('warning') }}",
                'warning'
                )
            @endif
            @if (session()->has('error'))
                Swal.fire(
                'Oops!',
                "{{ session()->get('error') }}",
                'error'
                )
            @endif
        </script>
        @yield('scripts')

        <!--Page Scripts(used by all page)-->
        <script src="{{ asset('assets/dist/js/sidebar.js')}}"></script>
        <script src="{{config('app.url')}}swal.js')}}"></script>

        {{-- @livewireScripts --}}

        <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        {{-- <x-livewire-alert::scripts /> --}}
    </body>
</html>
