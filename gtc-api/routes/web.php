<?php

use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */
Route::get('/test_db', function () {

    $user = DB::table('personal_access_tokens')->get();
    dd($user);

});


Auth::routes();
Route::group(['middleware' => 'auth'], function () {
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('admin_home');

    Route::get('/get_lga', [App\Http\Controllers\AgentController::class, 'getLGA'])->name("get_state_lga");

    Route::group(['prefix' => 'admin'], function () {
        Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
        Route::get('/download-database', [App\Http\Controllers\HomeController::class, 'download'])->name('download.database');
        //Roles and Permission Management
        Route::group(['prefix' => 'users'], function () {
            Route::get('/', [UserController::class, 'index'])->name('users_home');
            Route::post('/create', [UserController::class, 'create'])->name('create_new_user');
            Route::any('view_user_details/{id}', [UserController::class, 'viewDetails'])->name('view_user_details');
            Route::any('getUserInfo/{id}', [UserController::class, 'getUserInfo'])->name('getUserInfo');
            Route::post('/update', [UserController::class, 'update'])->name('admin_user_update');
            Route::get('/edit', [UserController::class, 'edit'])->name('user_edit');
            Route::get('/delete', [UserController::class, 'delete'])->name('user.destroy');

        });

        Route::group(['prefix' => 'company'], function () {
            Route::get('/', [App\Http\Controllers\CompanyController::class, 'index'])->name('company-home');
        });

        //Roles and Permission Management
        Route::group(['prefix' => 'role'], function () {
            Route::get('/', [RoleController::class, 'index'])->name('roles_home');
            Route::get('/create', [RoleController::class, 'create'])->name('create_new_role');
            Route::post('/store', [RoleController::class, 'store'])->name('roles.store');
            Route::post('/create_permission', [RoleController::class, 'create_permission'])->name('create_permission');
            Route::get('/roleList', [RoleController::class, 'getAccountRolesList'])->name('get_role_list');
            Route::get('/edit/{id}', [RoleController::class, 'edit'])->name('edit_role');
            Route::get('/show/{id}', [RoleController::class, 'show'])->name('show_role');
            Route::any('/update/{id}', [RoleController::class, 'update'])->name('update_role');
            Route::get('/delete', [RoleController::class, 'destroy'])->name('role.destroy');
            Route::get('/rolelist', [RoleController::class, 'getRolelist'])->name('role_list');
        });

        Route::group(['prefix' => 'department'], function () {
            Route::get('/', [App\Http\Controllers\DepartmentController::class, 'index'])->name('departments_home');
            Route::get('/load_data', [App\Http\Controllers\DepartmentController::class, 'index'])->name('load_department_data');
            Route::get('/edit_department', [App\Http\Controllers\DepartmentController::class, 'edit_department'])->name('update_department');
            Route::post('/store', [App\Http\Controllers\DepartmentController::class, 'store'])->name('add_new_department');
            Route::post('/update', [App\Http\Controllers\DepartmentController::class, 'update'])->name('update_department');
            Route::get('/destroy', [App\Http\Controllers\DepartmentController::class, 'delete'])->name('delete_department');
        });

        //manage grade
        Route::group(['prefix' => 'grade'], function () {
            Route::get('/', [App\Http\Controllers\GradeController::class, 'index'])->name('grades_home');
            Route::get('/load_grade_data', [App\Http\Controllers\GradeController::class, 'index'])->name('load_grade_data');
            Route::post('/create', [App\Http\Controllers\GradeController::class, 'create'])->name('add_new_grade');
            Route::post('/update', [App\Http\Controllers\GradeController::class, 'update'])->name('update_grade');
            Route::get('/delete', [App\Http\Controllers\GradeController::class, 'delete'])->name('delete_grade');

        });

        //manage level
        Route::group(['prefix' => 'level'], function () {
            Route::get('/', [App\Http\Controllers\LevelController::class, 'index'])->name('level_home');
            Route::get('/load_grade_data', [App\Http\Controllers\LevelController::class, 'index'])->name('load_level_data');
            Route::any('/create', [App\Http\Controllers\LevelController::class, 'create'])->name('add_new_level');
            Route::post('/update', [App\Http\Controllers\LevelController::class, 'update'])->name('update_level');
            Route::get('/delete', [App\Http\Controllers\LevelController::class, 'delete'])->name('delete_level');

        });
        //manage step
        Route::group(['prefix' => 'step'], function () {
            Route::get('/', [App\Http\Controllers\GradeController::class, 'stepIndex'])->name('step_home');
            Route::get('/load_step_data', [App\Http\Controllers\GradeController::class, 'stepIndex'])->name('load_step_data');
            Route::post('/create', [App\Http\Controllers\GradeController::class, 'createStep'])->name('add_new_step');
            Route::post('/update', [App\Http\Controllers\GradeController::class, 'updateStep'])->name('update_step');
            Route::get('/delete', [App\Http\Controllers\GradeController::class, 'deleteStep'])->name('delete_step');

        });
        //manage staff
        Route::group(['prefix' => 'staff'], function () {
            Route::get('/', [App\Http\Controllers\StaffController::class, 'staffIndex'])->name('staff_home');
            Route::get('/load_staff_data', [App\Http\Controllers\StaffController::class, 'staffIndex'])->name('load_staff_data');
            Route::any('/create', [App\Http\Controllers\StaffController::class, 'createstaff'])->name('add_new_staff');
            Route::any('/view/{id}', [App\Http\Controllers\StaffController::class, 'view_staff'])->name('view_staff');
            Route::any('/add_staff', [App\Http\Controllers\StaffController::class, 'createstaff'])->name('create_staff');
            Route::post('/update', [App\Http\Controllers\StaffController::class, 'updatestaff'])->name('update_staff');
            Route::get('/delete', [App\Http\Controllers\StaffController::class, 'deletestaff'])->name('delete_staff');

        });

        // manage allowance
        Route::group(['prefix' => 'allowance'], function () {
            Route::get('/', [App\Http\Controllers\AllowanceController::class, 'index'])->name('allowances_home');
            Route::post('/add', [App\Http\Controllers\AllowanceController::class, 'upload'])->name('upload_allowances');
            Route::get('/delete', [App\Http\Controllers\AllowanceController::class, 'delete'])->name('delete_allowance');
            Route::post('/update', [App\Http\Controllers\AllowanceController::class, 'update'])->name('update_allowance');
            //specification
            Route::get('/specification', [App\Http\Controllers\AllowanceController::class, 'specification'])->name('allowances_specification_home');
            Route::post('/specify_new_allowance', [App\Http\Controllers\AllowanceController::class, 'specifyNewAllowance'])->name('specify_new_allowance');
            Route::get('/del_specification', [App\Http\Controllers\AllowanceController::class, 'deleteSpec'])->name('delete_allowance_specification');
            //type
            Route::get('/type', [App\Http\Controllers\AllowanceController::class, 'type'])->name('allowances_type');
            Route::get('/loadtype', [App\Http\Controllers\AllowanceController::class, 'type'])->name('loadtypedata');
            Route::post('/addtype', [App\Http\Controllers\AllowanceController::class, 'addType'])->name('add_new_AllowanceType');
            Route::get('/delete_type', [App\Http\Controllers\AllowanceController::class, 'deleteType'])->name('delete_allowance_type');
            Route::post('/updateType', [App\Http\Controllers\AllowanceController::class, 'updateType'])->name('update_allowance_type');
            Route::get('/allowances_data', [App\Http\Controllers\AllowanceController::class, 'allowancesData'])->name('load_allowances_data');


        });
        // manage deduction
        Route::group(['prefix' => 'deduction'], function () {
            Route::get('/', [App\Http\Controllers\DeductionController::class, 'index'])->name('deductions_home');

            //specification
            Route::get('/specification', [App\Http\Controllers\DeductionController::class, 'specification'])->name('deductions_specification_home');
            Route::post('/specify_new_deduction', [App\Http\Controllers\DeductionController::class, 'specifyNewDeduction'])->name('specify_new_deduction');
            Route::get('/del_specification', [App\Http\Controllers\DeductionController::class, 'deleteSpec'])->name('delete_deduction_specification');

            Route::get('/type', [App\Http\Controllers\DeductionController::class, 'type'])->name('deductions_type');
            Route::get('/loadtype', [App\Http\Controllers\DeductionController::class, 'type'])->name('loadtypedata');
            Route::post('/addtype', [App\Http\Controllers\DeductionController::class, 'addType'])->name('add_new_DeductionType');
            Route::get('/delete_type', [App\Http\Controllers\DeductionController::class, 'deleteType'])->name('delete_deduction_type');
            Route::post('/updateType', [App\Http\Controllers\DeductionController::class, 'updateType'])->name('update_deduction_type');
            Route::post('/upload_deduction', [App\Http\Controllers\DeductionController::class, 'upload'])->name('upload_deduction');
            Route::get('/deductions_data', [App\Http\Controllers\DeductionController::class, 'deductionsData'])->name('load_deductions_data');
        });

        // manage payroll
        Route::group(['prefix' => 'payroll'], function () {
            Route::get('/', [App\Http\Controllers\PayrollController::class, 'index'])->name('payroll_home');
            Route::get('/salary-structure', [App\Http\Controllers\PayrollController::class, 'salary_structure'])->name('salary_structure');
            Route::get('/pay_slip/{id}', [App\Http\Controllers\PayrollController::class, 'pay_slip'])->name('pay_slip');
            Route::get('/payment_instruction', [App\Http\Controllers\PayrollController::class, 'paymentInstruction'])->name('generate_payment_instruction');
            Route::get('/send_payroll_via_mail/{id}', [App\Http\Controllers\PayrollController::class, 'send_payrollViaMail'])->name('send_payroll_via_mail');
            Route::post('/salary-structure', [App\Http\Controllers\PayrollController::class, 'addSalaryStructure'])->name('add_new_salary_structure');
            Route::post('/generate_monthly_payroll', [App\Http\Controllers\PayrollController::class, 'generatePayroll'])->name('generate_monthly_payroll');
        });



    });
});
Route::get('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name("logout");
Route::get('/download-template', [App\Http\Controllers\StaffController::class, 'downloadTemplate']);

