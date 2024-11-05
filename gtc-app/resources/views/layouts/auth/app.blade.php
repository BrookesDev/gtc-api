<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
<link href="{{ config('app.url') }}needs-auth/images/gicon.png" rel="icon" />
<title>Payroll System | Authentication</title>
<meta name="description" content="Sign In to your Needspay Account">
<meta name="author" content="harnishdesign.net">

<!-- Web Fonts
============================================= -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap">

<!-- Stylesheet
============================================= -->
<link rel="stylesheet" type="text/css" href="{{ config('app.url') }}needs-auth/vendor/bootstrap/css/bootstrap.min.css" />
<link rel="stylesheet" type="text/css" href="{{ config('app.url') }}needs-auth/vendor/font-awesome/css/all.min.css" />
<link rel="stylesheet" type="text/css" href="{{ config('app.url') }}needs-auth/css/stylesheet.css"/>
@livewireStyles
<style>
.otp-input {
  display: flex;
  justify-content: space-between;
  width: 100%;
}

.otp-input input {
  width: 60px;
  height:60px;
  text-align: center;
  /* margin-right: 20px; */
}

.otp-input input:last-of-type {
    margin-right: 0px;
}

@media screen and (max-width: 760px) {
    .otp-input input {
        width: 40px;
        height: 40px !important;
        text-align: center;
    /* margin-right: 20px; */
    }
}

@media screen and (max-width: 300px) {
    .otp-input input {
        width: 30px;
        height: 30px !important;
        text-align: center;
    /* margin-right: 20px; */
    }
}

.phone-input {
  display: flex;
  align-items: center;
}

.prefix {
  padding: 0 5px;
  font-weight: bold;
}

</style>
</head>
<body>


<div id="main-wrapper">
  <div class="container-fluid px-0">
    @yield('content')
  </div>
</div>

<!-- Back to Top
============================================= -->
<a id="back-to-top" data-bs-toggle="tooltip" title="Back to Top" href="javascript:void(0)"><i class="fa fa-chevron-up"></i></a>

<!-- Script -->
    <script src="{{ config('app.url') }}needs-auth/vendor/jquery/jquery.min.js"></script>
    <script src="{{ config('app.url') }}needs-auth/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="{{ config('app.url') }}needs-auth/js/theme.js"></script>
    <script>
    function moveToNextInput(event, currentInput) {
        const input = event.target;

        if (input.value.length === input.maxLength) {
        if (currentInput < 6) {
            const nextInputId = "digit" + (currentInput + 1);
            document.getElementById(nextInputId).focus();
        } else {
            input.blur(); // Unfocus on the last input
        }
        } else if (input.value.length === 0 && currentInput > 1) {
        const previousInputId = "digit" + (currentInput - 1);
        document.getElementById(previousInputId).focus();
        }
    }
    </script>
    @livewireScripts
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <x-livewire-alert::scripts />

</body>
</html>
