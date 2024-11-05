<!doctype html>
<html lang="en">

<head>
        <!-- Required meta tags -->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="Brookes Professional Services Limited">
        <meta name="author" content="Brookes Professional Services Limited | Agbeniga Ambali">

        <title>Brookes Payroll System</title>
        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/dist/img/favicon.png">
        <!--Global Styles(used by all pages)-->
        <link href="assets/plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">
        <link href="assets/plugins/fontawesome/css/all.min.css" rel="stylesheet">
        <link href="assets/plugins/typicons/src/typicons.min.css" rel="stylesheet">
        <link href="asset   s/plugins/themify-icons/themify-icons.min.css" rel="stylesheet">
        <!--Third party Styles(used by this page)-->

        <!--Start Your Custom Style Now-->
        <link href="assets/dist/css/style.css" rel="stylesheet">
    </head>
    <body class="bg-white">
        <div class="d-flex align-items-center justify-content-center text-center h-100vh">
            <div class="form-wrapper m-auto">
                <div class="form-container my-4">
                    <div class="register-logo text-center mb-4">
                        <img src="assets/dist/img/logo2.png" alt="">
                    </div>
                    <div class="panel">
                        <div class="panel-header text-center mb-3">
                            <h3 class="fs-24">Sign into your account!</h3>
                            <p class="text-muted text-center mb-0">Nice to see you! Please log in with your account.</p>
                        </div>
                            <form class="register-form" action="{{ route('login') }}" method="POST">
                                @csrf
                            <div class="form-group">
                                <input type="email" class="form-control" id="email" placeholder="Enter email" name="email">
                                <div class="text-left">Enter your valid email</div>
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" id="pass" placeholder="Password" name="password">
                            </div>
                            <div class="custom-control custom-checkbox mb-3">
                                <input type="checkbox" class="custom-control-input" id="customCheck1">
                                <label class="custom-control-label" for="customCheck1">Remember me next time </label>
                            </div>
                            <button type="submit" class="btn btn-success btn-block">Sign in</button>
                        </form>
                    </div>
                    <div class="bottom-text text-center my-3">
                        Don't have an account? <a href="#" class="font-weight-500">Sign Up</a><br>
                        Remind <a href="#" class="font-weight-500">Password</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.End of form wrapper -->
        <!--Global script(used by all pages)-->
        <script src="assets/plugins/jQuery/jquery-3.4.1.min.js"></script>
        <script src="assets/dist/js/popper.min.js"></script>
        <script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>
        <script src="assets/plugins/metisMenu/metisMenu.min.js"></script>
        <script src="assets/plugins/perfect-scrollbar/dist/perfect-scrollbar.min.js"></script>
        <!-- Third Party Scripts(used by this page)-->

        <!--Page Active Scripts(used by this page)-->

        <!--Page Scripts(used by all page)-->
        <script src="assets/dist/js/sidebar.js"></script>
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
</script>

    </body>

</html>
