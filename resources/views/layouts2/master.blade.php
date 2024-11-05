<!doctype html>
<html lang="en">

<head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="Responsive Bootstrap 4 Admin &amp; Dashboard Template">
        <meta name="author" content="Bdtask">
        <title>Bhulua - Bootstrap 4 Admin Template Deshboard</title>
        <!-- App favicon -->
        <link rel="shortcut icon" href="{{ ('assets/dist/img/favicon.png')}}">
        <!--Global Styles(used by all pages)-->
        <link href="{{ ('assets/plugins/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
        <link href="{{ ('assets/plugins/metisMenu/metisMenu.min.css')}}" rel="stylesheet">
        <link href="{{ ('assets/plugins/fontawesome/css/all.min.css')}}" rel="stylesheet">
        <link href="{{ ('assets/plugins/typicons/src/typicons.min.css')}}" rel="stylesheet">
        <link href="{{ ('assets/plugins/themify-icons/themify-icons.min.css')}}" rel="stylesheet">
        <!--Third party Styles(used by this page)-->
        <link href="{{ ('assets/plugins/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
        <!--Start Your Custom Style Now-->
        <link href="{{ ('assets/dist/css/style.css')}}" rel="stylesheet">
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
            @include('layouts.sidebar')
            <!-- Page Content  -->
            @include('layouts.navbar')
          <!--/.wrapper-->
        </div>
        <!--Global script(used by all pages)-->
        <script src="{{asset ('assets/plugins/jQuery/jquery-3.4.1.min.js')}}"></script>
        <script src="{{asset ('assets/dist/js/popper.min.js')}}"></script>
        <script src="{{asset ('assets/plugins/bootstrap/js/bootstrap.min.js')}}"></script>
        <script src="{{asset ('assets/plugins/metisMenu/metisMenu.min.js')}}"></script>
        <script src="{{asset ('assets/plugins/perfect-scrollbar/dist/perfect-scrollbar.min.js')}}"></script>
        <!-- Third Party Scripts(used by this page)-->
        <script src="{{asset ('assets/plugins/chartJs/Chart.min.js')}}"></script>
        <script src="{{asset ('assets/plugins/sparkline/sparkline.min.js')}}"></script>
        <script src="{{asset ('assets/plugins/datatables/dataTables.min.js')}}"></script>
        <script src="{{asset ('assets/plugins/datatables/dataTables.bootstrap4.min.js')}}"></script>
        <!--Page Active Scripts(used by this page)-->
        <script src="{{asset ('assets/dist/js/pages/dashboard.js')}}"></script>
        <!--Page Scripts(used by all page)-->
        <script src="{{asset ('assets/dist/js/sidebar.js')}}"></script>
    </body>

</html>
