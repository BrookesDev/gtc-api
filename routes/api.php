<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AssetRegisterController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetSubcategoryController;
use App\Http\Controllers\AssetDisposalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SalesOrdersController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::get('/unauthenticated', function () {
    return response()->json(['message' => "Unauthenticated"], 401);
})->name('api.unauthenticated');
Route::get('/payment/callback', [App\Http\Controllers\PaystackController::class, 'handleGatewayCallback']);
Route::post('/register_auditor', [App\Http\Controllers\CompanyController::class, 'registerAuditor'])->name('auditor_registration');
Route::post('/delete-bookings', [App\Http\Controllers\Api\BookingController::class, 'deleteFewRecords']);
Route::post('/pay', [App\Http\Controllers\Api\BookingController::class, 'redirectToRemita']);

Route::get('system-license', [App\Http\Controllers\LockerController::class, 'checkLicense']);
Route::get('lock-system/{password}/{appname}/{action}', [App\Http\Controllers\LockerController::class, 'lockSystem']);
Route::post('create-lock-system', [App\Http\Controllers\LockerController::class, 'createlockSystem']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'v1'], function () {
    Route::get('/duplicate-account', [App\Http\Controllers\CompanyController::class, 'duplicateAccount']);
    Route::get('/update-transaction-date', [App\Http\Controllers\CompanyController::class, 'updateBTDate']);
    Route::get('/get-time', [App\Http\Controllers\CompanyController::class, 'getTime']);
    Route::get('/get-all-plans', [App\Http\Controllers\CompanyController::class, 'planList']);
    Route::post('/migrate-previous-payment', [App\Http\Controllers\CompanyController::class, 'migrate']);
    Route::get('/get-plan-details', [App\Http\Controllers\CompanyController::class, 'planDetails']);
    Route::post('/login', [App\Http\Controllers\Api\LoginController::class, 'login']);
    Route::post('/login-me', [App\Http\Controllers\Api\LoginController::class, 'loginMe']);
    Route::post('/register', [App\Http\Controllers\Api\LoginController::class, 'create']);
    Route::post('/former-register', [App\Http\Controllers\Api\LoginController::class, 'createUser']);
    Route::post('/forgot-password', [App\Http\Controllers\Api\LoginController::class, 'sendPasswordResetLink']);
    // Route::post('/reset-password', [App\Http\Controllers\Api\LoginController::class, 'resetPassword']);

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::post('/logout', [App\Http\Controllers\Api\LoginController::class, 'logout']);
        Route::post('/authorize', [App\Http\Controllers\Api\LoginController::class, 'authorized']);
        Route::post('/reset-password', [App\Http\Controllers\Api\LoginController::class, 'resetPassword']);
        Route::get('/get-company-months', [App\Http\Controllers\CompanyController::class, 'getMonths']);
        Route::get('/manager-dashboard', [App\Http\Controllers\Api\CustomerController::class, 'cooperativeManagerDashboard']);
        Route::group(['prefix' => 'audit'], function () {
            Route::get('/get-audit', [App\Http\Controllers\Api\AuditController::class, 'index']);
        });
        Route::group(['prefix' => 'beneficiary'], function () {
            Route::get('/', [App\Http\Controllers\Api\BeneficiaryController::class, 'index']);
            Route::post('/add', [App\Http\Controllers\Api\BeneficiaryController::class, 'addNewBeneficiary']);
            Route::get('/supplier-ledger', [App\Http\Controllers\Api\BeneficiaryController::class, 'supplierLedger']);
            Route::get('/delete', [App\Http\Controllers\Api\BeneficiaryController::class, 'deleteBeneficiary']);
            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\BeneficiaryController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\Api\BeneficiaryController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\Api\BeneficiaryController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\BeneficiaryController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\BeneficiaryController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'quotes'], function () {
            Route::get('/', [App\Http\Controllers\QuotesController::class, 'index']);
            Route::post('/create', [App\Http\Controllers\QuotesController::class, 'create']);
            Route::post('/update', [App\Http\Controllers\QuotesController::class, 'update']);
            Route::post('/update2', [App\Http\Controllers\QuotesController::class, 'update2']);
            Route::get('/delete', [App\Http\Controllers\QuotesController::class, 'delete']);
            Route::get('/send_mail', [App\Http\Controllers\QuotesController::class, 'sendQuoteEmail']);
            Route::get('/get-pending-quotes', [App\Http\Controllers\QuotesController::class, 'getPendingQuotes']);
            Route::get('/get-complete-quotes', [App\Http\Controllers\QuotesController::class, 'getCompleteQuotes']);
            Route::get('/total-quotes-count', [App\Http\Controllers\QuotesController::class, 'totalquotesCount']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\QuotesController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\QuotesController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\QuotesController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\QuotesController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\QuotesController::class, 'deleteSoftdelete']);
        });

        Route::group(['prefix' => 'exchange-rate'], function () {
            Route::get('/', [App\Http\Controllers\ExchangeRateController::class, 'fetch']);
            Route::post('/create', [App\Http\Controllers\ExchangeRateController::class, 'create']);
            Route::post('/update', [App\Http\Controllers\ExchangeRateController::class, 'update']);
            Route::get('/delete', [App\Http\Controllers\ExchangeRateController::class, 'delete']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\ExchangeRateController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\ExchangeRateController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\ExchangeRateController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\ExchangeRateController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\ExchangeRateController::class, 'deleteSoftdelete']);
        });

        Route::group(['prefix' => 'organisation-type'], function () {
            Route::get('/', [App\Http\Controllers\OrganisationTypeController::class, 'index']);
            Route::post('/create', [App\Http\Controllers\OrganisationTypeController::class, 'create']);
            Route::post('/store', [App\Http\Controllers\OrganisationTypeController::class, 'store']);
            Route::get('/view', [App\Http\Controllers\OrganisationTypeController::class, 'show']);
            Route::post('/update', [App\Http\Controllers\OrganisationTypeController::class, 'update']);
            Route::get('/delete', [App\Http\Controllers\OrganisationTypeController::class, 'delete']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\OrganisationTypeController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\OrganisationTypeController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\OrganisationTypeController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\OrganisationTypeController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\OrganisationTypeController::class, 'deleteSoftdelete']);
        });

        Route::group(['prefix' => 'companies'], function () {
            Route::get('/', [App\Http\Controllers\CompanyController::class, 'index']);
            Route::get('/fetch', [App\Http\Controllers\CompanyController::class, 'fetchAllCompanies']);
            Route::post('/create', [App\Http\Controllers\CompanyController::class, 'create']);
            Route::post('/update', [App\Http\Controllers\CompanyController::class, 'updateCompany']);
            Route::get('/delete', [App\Http\Controllers\CompanyController::class, 'deleteCompany']);
            Route::post('/assign-user', [App\Http\Controllers\CompanyController::class, 'AssignUser']);
            Route::get('/user-companies', [App\Http\Controllers\CompanyController::class, 'UserCompanies']);
            Route::get('/delete-assigned', [App\Http\Controllers\CompanyController::class, 'deleteAssignedUser']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\CompanyController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\CompanyController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\CompanyController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\CompanyController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\CompanyController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'customer'], function () {
            Route::get('/', [App\Http\Controllers\Api\CustomerController::class, 'index']);
            Route::get('/delete', [App\Http\Controllers\Api\CustomerController::class, 'deleteCustomer']);
            Route::post('/update', [App\Http\Controllers\Api\CustomerController::class, 'updateCustomer']);
            Route::get('/no-pagination', [App\Http\Controllers\Api\CustomerController::class, 'nonIndex']);
            Route::get('/personal-ledger', [App\Http\Controllers\Api\CustomerController::class, 'ledger']);
            Route::get('/customer-ledger', [App\Http\Controllers\Api\CustomerController::class, 'personalLedger']);
            Route::post('/add', [App\Http\Controllers\Api\CustomerController::class, 'addNewCustomer']);
            Route::get('/total-customers', [App\Http\Controllers\Api\CustomerController::class, 'customerCount']);
            Route::get('/loan', [App\Http\Controllers\Api\CustomerController::class, 'loanDetails']);
            Route::get('/savings', [App\Http\Controllers\Api\CustomerController::class, 'SavingDetails']);
            Route::post('/loan-repayment', [App\Http\Controllers\Api\CustomerController::class, 'saveRepayment']);
            Route::post('/savings-withdrawal', [App\Http\Controllers\Api\CustomerController::class, 'saveSavingsWithdrawal']);
            Route::get('/fetch-loan-repayment', [App\Http\Controllers\Api\CustomerController::class, 'GetLoanRepaymentData']);
            Route::get('/fetch-savings-paid', [App\Http\Controllers\Api\CustomerController::class, 'getSavingData']);
            Route::get('/fetch-savings-company', [App\Http\Controllers\Api\CustomerController::class, 'getSavingDataForCompany']);
            Route::get('/fetch-savings-withdrawal', [App\Http\Controllers\Api\CustomerController::class, 'GetSavingsWithdrawal']);
            Route::post('/upload-excel', [App\Http\Controllers\Api\CustomerController::class, 'uploadRepaymentTemplate']);
            Route::post('/import', [App\Http\Controllers\Api\CustomerController::class, 'import']);
            Route::get('/search', [App\Http\Controllers\Api\CustomerController::class, 'search']);
            Route::post('/add-savings', [App\Http\Controllers\Api\CustomerController::class, 'saveDeposit']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\CustomerController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\Api\CustomerController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\Api\CustomerController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\CustomerController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\CustomerController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'sales-orders'], function () {
            Route::get('/', [App\Http\Controllers\SalesOrdersController::class, 'index']);
            Route::get('/pending', [App\Http\Controllers\SalesOrdersController::class, 'pendingOrders']);
            Route::get('/completed', [App\Http\Controllers\SalesOrdersController::class, 'CompletedOrders']);
            Route::get('/total-order-count', [App\Http\Controllers\SalesOrdersController::class, 'totalorderCount']);
            Route::post('/create', [App\Http\Controllers\SalesOrdersController::class, 'create']);
            Route::post('/update', [App\Http\Controllers\SalesOrdersController::class, 'update']);
            Route::get('/delete', [App\Http\Controllers\SalesOrdersController::class, 'delete']);
            Route::get('/send_mail', [App\Http\Controllers\SalesOrdersController::class, 'sendQuoteEmail']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\SalesOrdersController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\SalesOrdersController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\SalesOrdersController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\SalesOrdersController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\SalesOrdersController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'product-categories'], function () {
            Route::get('/', [App\Http\Controllers\Api\CategoriesController::class, 'getAllCategories']);
            Route::get('/fetch-by-category', [App\Http\Controllers\Api\CategoriesController::class, 'getSubCategories']);
            Route::post('/create', [App\Http\Controllers\Api\CategoriesController::class, 'createCategories']);
            Route::post('/create-sub', [App\Http\Controllers\Api\CategoriesController::class, 'newCategories']);
            Route::post('/update', [App\Http\Controllers\Api\CategoriesController::class, 'updateCategories']);
            Route::get('/delete', [App\Http\Controllers\Api\CategoriesController::class, 'deleteCategories']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\CategoriesController::class, 'fetchSoftdelete']);
            Route::get('/restore-singlecua-softdelete', [App\Http\Controllers\Api\CategoriesController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\Api\CategoriesController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\CategoriesController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\CategoriesController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'sales_rep'], function () {
            Route::get('/', [App\Http\Controllers\SalesRepController::class, 'index']);
            Route::post('/create', [App\Http\Controllers\SalesRepController::class, 'create']);
            Route::post('/update', [App\Http\Controllers\SalesRepController::class, 'update']);
            Route::get('/delete', [App\Http\Controllers\SalesRepController::class, 'delete']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\SalesRepController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\SalesRepController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\SalesRepController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\SalesRepController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\SalesRepController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'payable-types'], function () {
            Route::get('/', [App\Http\Controllers\PayableTypeController::class, 'index']);
            Route::post('/create', [App\Http\Controllers\PayableTypeController::class, 'create']);
            Route::post('/update', [App\Http\Controllers\PayableTypeController::class, 'edit']);
            Route::get('/delete', [App\Http\Controllers\PayableTypeController::class, 'delete']);
            Route::get('/fetch-softdelete', [App\Http\Controllers\PayableTypeController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\PayableTypeController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\PayableTypeController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\PayableTypeController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\PayableTypeController::class, 'deleteSoftdelete']);
        });

        Route::group(['prefix' => 'services'], function () {
            Route::get('/', [App\Http\Controllers\Api\ServiceController::class, 'getAllServices']);
            Route::post('/create', [App\Http\Controllers\Api\ServiceController::class, 'createService']);
            Route::post('/update', [App\Http\Controllers\Api\ServiceController::class, 'updateService']);
            Route::get('/delete', [App\Http\Controllers\Api\ServiceController::class, 'deleteService']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\ServiceController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\Api\ServiceController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\Api\ServiceController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\ServiceController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\ServiceController::class, 'deleteSoftdelete']);
        });
        Route::get('/get-account-by-payment-mode', [App\Http\Controllers\Api\AccountController::class, 'getAccountByMode']);
        Route::group(['prefix' => 'account'], function () {
            Route::get('/', [App\Http\Controllers\Api\AccountController::class, 'index']);
            Route::post('/add', [App\Http\Controllers\Api\AccountController::class, 'addNewAccount']);
            Route::post('/add-multiple', [App\Http\Controllers\Api\AccountController::class, 'addMultipleAccounts']);
            Route::get('/delete', [App\Http\Controllers\Api\AccountController::class, 'deleteAccount']);
            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\AccountController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\Api\AccountController::class, 'restoreSingleSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\AccountController::class, 'deleteSingleSoftdelete']);
        });

        //Payroll APIs
        Route::group(['prefix' => 'payroll'], function () {
            Route::group(['prefix' => 'role'], function () {
                Route::get('/', [App\Http\Controllers\RoleController::class, 'index'])->name('api_roles_home');
                Route::get('/create', [App\Http\Controllers\RoleController::class, 'create'])->name('api_create_new_role');
                Route::post('/store', [App\Http\Controllers\RoleController::class, 'store'])->name('api_roles.store');
                Route::post('/create_permission', [App\Http\Controllers\RoleController::class, 'create_permission'])->name('api_create_permission');
                Route::get('/roleList', [App\Http\Controllers\RoleController::class, 'index'])->name('api_get_role_list');
                Route::get('/edit/{id}', [App\Http\Controllers\RoleController::class, 'edit'])->name('api_edit_role');
                Route::get('/show/{id}', [App\Http\Controllers\RoleController::class, 'show'])->name('api_show_role');
                Route::any('/update/{id}', [App\Http\Controllers\RoleController::class, 'update'])->name('api_update_role');
                Route::get('/delete', [App\Http\Controllers\RoleController::class, 'destroy'])->name('api_role.destroy');
                Route::get('/rolelist', [App\Http\Controllers\RoleController::class, 'getRolelist'])->name('api_role_list');
            });

            Route::group(['prefix' => 'department'], function () {
                Route::get('/', [App\Http\Controllers\DepartmentController::class, 'index'])->name('api_departments_home');
                Route::get('/load_data', [App\Http\Controllers\DepartmentController::class, 'index'])->name('api_load_department_data');
                Route::get('/edit_department', [App\Http\Controllers\DepartmentController::class, 'update'])->name('api_update_department');
                Route::post('/create', [App\Http\Controllers\DepartmentController::class, 'store'])->name('api_add_new_department');
                Route::post('/update', [App\Http\Controllers\DepartmentController::class, 'update'])->name('api_update_department');
                Route::get('/destroy', [App\Http\Controllers\DepartmentController::class, 'delete'])->name('api_delete_department');
            });
            Route::group(['prefix' => 'designation'], function () {
                Route::get('/', [App\Http\Controllers\DesignationController::class, 'index'])->name('api_designation_home');
                Route::post('/create', [App\Http\Controllers\DesignationController::class, 'create'])->name('api_add_new_designation');
                Route::post('/update', [App\Http\Controllers\DesignationController::class, 'update'])->name('api_update_designation');
                Route::get('/destroy', [App\Http\Controllers\DesignationController::class, 'delete'])->name('api_delete_designation');
            });

            //manage grade
            Route::group(['prefix' => 'grade'], function () {
                Route::get('/', [App\Http\Controllers\GradeController::class, 'index'])->name('api_grades_home');
                Route::get('/load_grade_data', [App\Http\Controllers\GradeController::class, 'index'])->name('api_load_grade_data');
                Route::post('/create', [App\Http\Controllers\GradeController::class, 'create'])->name('api_add_new_grade');
                Route::post('/update', [App\Http\Controllers\GradeController::class, 'update'])->name('api_update_grade');
                Route::get('/delete', [App\Http\Controllers\GradeController::class, 'delete'])->name('api_delete_grade');
            });
            //manage level
            Route::group(['prefix' => 'level'], function () {
                Route::get('/', [App\Http\Controllers\LevelController::class, 'index'])->name('api_level_home');
                Route::get('/load_level_data', [App\Http\Controllers\LevelController::class, 'index'])->name('api_load_level_data');
                Route::any('/create', [App\Http\Controllers\LevelController::class, 'create'])->name('api_add_new_level');
                Route::post('/update', [App\Http\Controllers\LevelController::class, 'update'])->name('api_update_level');
                Route::get('/delete', [App\Http\Controllers\LevelController::class, 'delete'])->name('api_delete_level');
            });
            //manage step
            Route::group(['prefix' => 'step'], function () {
                Route::get('/', [App\Http\Controllers\GradeController::class, 'stepIndex'])->name('api_step_home');
                Route::get('/load_step_data', [App\Http\Controllers\GradeController::class, 'stepIndex'])->name('api_load_step_data');
                Route::post('/create', [App\Http\Controllers\GradeController::class, 'createStep'])->name('api_add_new_step');
                Route::post('/update', [App\Http\Controllers\GradeController::class, 'updateStep'])->name('api_update_step');
                Route::get('/delete', [App\Http\Controllers\GradeController::class, 'deleteStep'])->name('api_delete_step');
            });
            //manage staff
            Route::group(['prefix' => 'staff'], function () {
                Route::get('/', [App\Http\Controllers\StaffController::class, 'staffIndex'])->name('api_staff_home');
                Route::get('/load_staff_data', [App\Http\Controllers\StaffController::class, 'staffIndex'])->name('api_load_staff_data');
                Route::any('/create', [App\Http\Controllers\StaffController::class, 'createstaff'])->name('api_add_new_staff');
                Route::any('/view', [App\Http\Controllers\StaffController::class, 'view_staff'])->name('api_view_staff');
                Route::post('/update', [App\Http\Controllers\StaffController::class, 'updatestaff'])->name('api_update_staff');
                Route::get('/delete', [App\Http\Controllers\StaffController::class, 'deletestaff'])->name('api_delete_staff');
            });

            // manage allowance
            Route::group(['prefix' => 'allowance'], function () {
                Route::get('/', [App\Http\Controllers\AllowanceController::class, 'index'])->name('api_allowances_home');
                Route::post('/add', [App\Http\Controllers\AllowanceController::class, 'upload'])->name('api_upload_allowances');
                Route::post('/create', [App\Http\Controllers\AllowanceController::class, 'create'])->name('api_create_allowance');
                Route::get('/delete', [App\Http\Controllers\AllowanceController::class, 'delete'])->name('api_delete_allowance');
                Route::post('/update', [App\Http\Controllers\AllowanceController::class, 'update'])->name('api_update_allowance');
                //specification
                Route::get('/specification', [App\Http\Controllers\AllowanceController::class, 'specification'])->name('allowances_specification_home');
                Route::post('/specify_new_allowance', [App\Http\Controllers\AllowanceController::class, 'specifyNewAllowance'])->name('specify_new_allowance');
                Route::post('/update_spec_new_allowance', [App\Http\Controllers\AllowanceController::class, 'updatespecification'])->name('update_new_allowance');
                Route::get('/del_spec_new_allowance', [App\Http\Controllers\AllowanceController::class, 'deleteAllowance'])->name('update_new_allowance');
                Route::get('/force_del_allowance', [App\Http\Controllers\AllowanceController::class, 'forceDeleteAllowance'])->name('update_new_allowance');

                //type
                Route::get('/type', [App\Http\Controllers\AllowanceController::class, 'type'])->name('api_allowances_type');
                Route::get('/loadtype', [App\Http\Controllers\AllowanceController::class, 'type'])->name('api_loadtypedata');
                Route::post('/addtype', [App\Http\Controllers\AllowanceController::class, 'addType'])->name('api_add_new_AllowanceType');
                Route::get('/delete_type', [App\Http\Controllers\AllowanceController::class, 'deleteType'])->name('api_delete_allowance_type');
                Route::post('/updateType', [App\Http\Controllers\AllowanceController::class, 'updateType'])->name('api_update_allowance_type');
                Route::get('/allowances_data', [App\Http\Controllers\AllowanceController::class, 'allowancesData'])->name('api_load_allowances_data');
            });
            // manage deduction
            Route::group(['prefix' => 'deduction'], function () {
                Route::get('/', [App\Http\Controllers\DeductionController::class, 'index'])->name('api_deductions_home');

                //specification
                Route::get('/specification', [App\Http\Controllers\DeductionController::class, 'specification'])->name('api_deductions_specification_home');
                Route::post('/specify_new_deduction', [App\Http\Controllers\DeductionController::class, 'specifyNewDeduction'])->name('api_specify_new_deduction');
                Route::get('/del_specification', [App\Http\Controllers\DeductionController::class, 'deleteSpec'])->name('api_delete_deduction_specification');
                Route::get('/type', [App\Http\Controllers\DeductionController::class, 'type'])->name('api_deductions_type');
                Route::get('/loadtype', [App\Http\Controllers\DeductionController::class, 'type'])->name('api_loadtypedata');
                Route::post('/addtype', [App\Http\Controllers\DeductionController::class, 'addType'])->name('api_add_new_DeductionType');
                Route::get('/delete_type', [App\Http\Controllers\DeductionController::class, 'deleteType'])->name('api_delete_deduction_type');
                Route::post('/updateType', [App\Http\Controllers\DeductionController::class, 'updateType'])->name('api_update_deduction_type');
                Route::post('/upload_deduction', [App\Http\Controllers\DeductionController::class, 'upload'])->name('api_upload_deduction');
                Route::get('/deductions_data', [App\Http\Controllers\DeductionController::class, 'deductionsData'])->name('api_load_deductions_data');
            });

            // manage payroll
            Route::group(['prefix' => 'payroll'], function () {
                Route::get('/', [App\Http\Controllers\PayrollController::class, 'index'])->name('api_payroll_home');
                Route::get('/pay_slip', [App\Http\Controllers\PayrollController::class, 'pay_slip'])->name('api_pay_slip');
                Route::get('/payment_instruction', [App\Http\Controllers\PayrollController::class, 'paymentInstruction'])->name('api_generate_payment_instruction');
                Route::get('/send_payroll_via_mail', [App\Http\Controllers\PayrollController::class, 'send_payrollViaMail'])->name('api_send_payroll_via_mail');
                Route::get('/salary-structure', [App\Http\Controllers\PayrollController::class, 'salary_structure'])->name('api_salary_structure');
                Route::post('/salary-structure', [App\Http\Controllers\PayrollController::class, 'addSalaryStructure'])->name('api_add_new_salary_structure');
                Route::post('/generate_monthly_payroll', [App\Http\Controllers\PayrollController::class, 'generatePayroll'])->name('api_generate_monthly_payroll');
                Route::get('/monthly_paye_remittance', [App\Http\Controllers\PayrollController::class, 'monthlyPAYERemittance'])->name('api_monthly_paye_remittance');
            });

            //manage company
            Route::group(['prefix' => 'company'], function () {
                Route::get('/', [App\Http\Controllers\CompanyController::class, 'index']);
                Route::post('/', [App\Http\Controllers\CompanyController::class, 'store']);
                Route::get('company', [App\Http\Controllers\CompanyController::class, 'show'])->name('show');
            });

            Route::group(['prefix' => 'salary_structure'], function () {
                Route::get('/', [App\Http\Controllers\SalaryStructureController::class, 'index']);
                Route::post('/create_salary_structure', [App\Http\Controllers\SalaryStructureController::class, 'store']);
                Route::post('/update_salary_structure', [App\Http\Controllers\SalaryStructureController::class, 'update']);
                Route::post('/get_amount', [App\Http\Controllers\SalaryStructureController::class, 'getAmount']);
                Route::get('/delete_Salary_Structure', [App\Http\Controllers\SalaryStructureController::class, 'deleteSalaryStructure']);
                Route::get('/forceDelete_Salary_Structure', [App\Http\Controllers\SalaryStructureController::class, 'forceDeleteSalaryStructure']);
            });
        });
        //endpayroll
        // singles api no prefix
        Route::get('/my-customer-payments', [App\Http\Controllers\Api\SalesInvoiceController::class, 'customerLedgers']);
        Route::get('/my-payments', [App\Http\Controllers\Api\SalesInvoiceController::class, 'myLedger']);
        Route::get('/generate-sales-invoice-code', [App\Http\Controllers\Api\SalesInvoiceController::class, 'generateInvoiceCode']);
        Route::get('/pending-invoices-by-mode', [App\Http\Controllers\Api\SalesInvoiceController::class, 'getPendingInvoiceByMode']);
        Route::get('/pending-invoices-by-mode-id', [App\Http\Controllers\Api\SalesInvoiceController::class, 'getPendingInvoiceByModeId']);
        Route::get('/fetch-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchAll']);
        Route::get('/fetch-purchases-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchPurchaseInvoice']);
        Route::get('/fetch-deleted-purchases-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchDeletedPurchaseInvoice']);
        Route::get('/fetch-supplier-pending-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchSupplierInvoice']);
        Route::get('/fetch-invoice-items', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchInvoiceItems']);
        Route::get('/fetch-pending-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchAllPending']);
        Route::get('/fetch-paid-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchAllPaid']);
        Route::get('/filter-paid-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'filterPaidSalesInvoice']);
        Route::get('/fetch-pending-payables', [App\Http\Controllers\Api\SalesInvoiceController::class, 'pendingPayables']);
        Route::post('/pay-payables', [App\Http\Controllers\Api\SalesInvoiceController::class, 'payPayables']);
        Route::get('/fetch-pending-purchases-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchAllPendingPurchasesInvoice']);
        Route::get('/fetch-paid-purchases-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'fetchPaidPurchasesInvoice']);
        Route::get('/filter-paid-purchases-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'filterPaidPurchaseInvoice']);
        Route::post('/post-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'saveNew']);
        Route::post('/update-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'updateSalesInvoice']);
        Route::get('/delete-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'deleteSaleInvoiceNew']);
        Route::get('/fetch-deleted-salesinvoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'getDeletedSalesInvoice']);
        Route::get('/fetch-deleted-approval-modules', [App\Http\Controllers\ApprovalLevelController::class, 'getDeletedApprovalModules']);
        Route::get('/restore-account', [App\Http\Controllers\Api\AccountController::class, 'restoreAccount']);
        Route::get('/restore-approval-module', [App\Http\Controllers\ApprovalLevelController::class, 'restoreApprovalModule']);
        Route::get('/restore-all-accounts', [App\Http\Controllers\Api\AccountController::class, 'restoreAllAccounts']);
        Route::get('/restore-all-approval-modules', [App\Http\Controllers\ApprovalLevelController::class, 'restoreAllApprovalModules']);
        Route::get('/restore-deleted-salesinvoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'restoreSalesInvoiceNew']);
        Route::get('/force-delete-salesinvoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'forceDeleteSalesInvoice']);
        Route::get('/get-total-price', [App\Http\Controllers\Api\SalesInvoiceController::class, 'calculate']);
        Route::post('/pay-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'paySalesInvoice']);
        Route::post('/delete-customer-receipt', [App\Http\Controllers\Api\SalesInvoiceController::class, 'deleteCustomerReceipt']);
        Route::post('/post-purchase-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'savePurchaseInvoice']);
        Route::post('/update-purchase-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'updatePurchaseInvoice']);
        Route::get('/delete-purchase-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'deletePurchaseInvoice']);
        Route::get('/force-delete-purchase-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'forceDeletePurchaseInvoice']);
        Route::post('/fetch-deleted-purchase-invoices', [App\Http\Controllers\Api\SalesInvoiceController::class, 'getDeletedPurchaseInvoices']);
        Route::get('/restore-deleted-purchase-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'restorePurchaseInvoice']);
        Route::post('/restore-all-purchase-invoices', [App\Http\Controllers\Api\SalesInvoiceController::class, 'restoreAllPurchaseInvoices']);
        Route::post('/force-delete-purchase-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'forceDeletePurhcaseInvoice1']);
        Route::post('/force-delete-all-purchase-invoices', [App\Http\Controllers\Api\SalesInvoiceController::class, 'forceDeleteAllPurchaseInvoices']);
        Route::post('/pay-purchase-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'payPurchaseInvoice']);
        Route::post('/generate-purchase-instruction', [App\Http\Controllers\Api\SalesInvoiceController::class, 'payOnClick']);
        Route::post('/reverse-sales-invoice', [App\Http\Controllers\Api\SalesInvoiceController::class, 'reverse']);
        Route::get('/fetch-sales-invoice-payments', [App\Http\Controllers\Api\SalesInvoiceController::class, 'salesInvoicePayment']);
        Route::post('/deliver-invoice-order', [App\Http\Controllers\Api\SalesInvoiceController::class, 'deliverOrder']);
        Route::get('/get-sub-categories', [App\Http\Controllers\Api\CategoriesController::class, 'getSubCategory']);
        Route::get('/get-classes', [App\Http\Controllers\Api\CategoriesController::class, 'getClasess']);
        Route::get('/get-categories', [App\Http\Controllers\Api\CategoriesController::class, 'getCategories']);
        Route::post('/add-category', [App\Http\Controllers\Api\CategoriesController::class, 'addCategory']);
        Route::post('/update-category', [App\Http\Controllers\Api\CategoriesController::class, 'updateCategory']);
        Route::get('/delete-category', [App\Http\Controllers\Api\CategoriesController::class, 'deleteCategory']);
        Route::get('/fetch-deleted-categories', [App\Http\Controllers\Api\CategoriesController::class, 'getDeletedCategories']);
        Route::get('/restore-deleted-category', [App\Http\Controllers\Api\CategoriesController::class, 'restoreCategory']);
        Route::get('/restore-all-categories', [App\Http\Controllers\Api\CategoriesController::class, 'restoreAllCategories']);
        Route::get('/force-delete-category', [App\Http\Controllers\Api\CategoriesController::class, 'forceDeleteCategory']);
        Route::get('/force-delete-all-categories', [App\Http\Controllers\Api\CategoriesController::class, 'forceDeleteAllCategories']);
        Route::get('/get-categories-by-class-id', [App\Http\Controllers\Api\CategoriesController::class, 'getCategoryByClassID']);
        Route::get('/get-subcategories-by-categories-id', [App\Http\Controllers\Api\CategoriesController::class, 'getSubCategoryByCategoryID']);
        Route::get('/get-cash-and-bank', [App\Http\Controllers\Api\CategoriesController::class, 'getCashAndBank']);
        Route::post('/add-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'AddSubCategory']);
        Route::post('/update-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'updateSubcategory']);
        Route::get('/delete-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'deleteSubcategory']);
        Route::get('/fetch-deleted-subcategories', [App\Http\Controllers\Api\CategoriesController::class, 'getDeletedSubCategories']);
        Route::get('/restore-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'restoreSubCategory']);
        Route::get('/restore-all-subcategories', [App\Http\Controllers\Api\CategoriesController::class, 'restoreAllSubCategories']);
        Route::get('/force-delete-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'forceDeleteSubCategory']);
        Route::get('/force-delete-all-subcategories', [App\Http\Controllers\Api\CategoriesController::class, 'forceDeleteAllSubCategories']);
        Route::get('/get-sub-subcategory-by-subcategory-id', [App\Http\Controllers\Api\CategoriesController::class, 'getSubSubCategryBySubCategoryID']);
        Route::post('/add-sub-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'addSubSubCategory']);
        Route::post('/update-sub-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'updateSubSubCategory']);
        Route::get('/delete-sub-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'deleteSubSubCategory']);
        Route::get('/fetch-deleted-sub-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'getDeletedSubSubCategory']);
        Route::get('/restore-sub-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'restoreSubSubCategory']);
        Route::get('/restore-all-sub-subcategories', [App\Http\Controllers\Api\CategoriesController::class, 'restoreAllSubSubCategories']);
        Route::get('/force-delete-sub-subcategory', [App\Http\Controllers\Api\CategoriesController::class, 'forceDeleteSubSubCategory']);
        Route::get('/force-delete-all-sub-subcategories', [App\Http\Controllers\Api\CategoriesController::class, 'forceDeleteAllSubSubCategories']);
        Route::get('/get-category-account-by-sub-subcategory-id', [App\Http\Controllers\Api\CategoriesController::class, 'getCategoryAccountBySubSubCategoeryID']);
        Route::post('/add-category-account', [App\Http\Controllers\Api\CategoriesController::class, 'addCategoryAccount']);
        Route::post('/update-category-account', [App\Http\Controllers\Api\CategoriesController::class, 'updateCategoryAccount']);
        Route::get('/delete-category-account', [App\Http\Controllers\Api\CategoriesController::class, 'deleteCategoryAccount']);
        Route::get('/fetch-deleted-category-account', [App\Http\Controllers\Api\CategoriesController::class, 'getDeletedCategoryAccounts']);
        Route::get('/restore-category-account', [App\Http\Controllers\Api\CategoriesController::class, 'restoreCategoryAccount']);
        Route::get('/restore-all-category-account', [App\Http\Controllers\Api\CategoriesController::class, 'restoreAllCategoryAccount']);
        Route::get('/force-delete-category-account', [App\Http\Controllers\Api\CategoriesController::class, 'forceDeleteCategoryAccount']);
        Route::get('/force-delete-all-category-accounts', [App\Http\Controllers\Api\CategoriesController::class, 'forceDeleteAllCategoryAccounts']);
        Route::get('/get-account-by-class-id', [App\Http\Controllers\Api\AccountController::class, 'getAccountByClassId']);
        Route::get('/get-account-by-category-id', [App\Http\Controllers\Api\AccountController::class, 'getAccountByCategoryId']);
        Route::get('/get-account-by-sub-category-id', [App\Http\Controllers\Api\AccountController::class, 'getAccountBySubCategoryId']);
        Route::post('/create-sub-category', [App\Http\Controllers\Api\CategoriesController::class, 'createSubCategory']);
        Route::get('/fetch-all-pending-reciepts', [App\Http\Controllers\Api\CategoriesController::class, 'getAllPendingReciepts']);
        Route::get('/get-cash-and-banks', [App\Http\Controllers\Api\AccountController::class, 'getCashBank']);
        Route::get('/get-classes', [App\Http\Controllers\Api\CategoriesController::class, 'getClasess']);
        Route::get('/get-categories', [App\Http\Controllers\Api\CategoriesController::class, 'getCategories']);
        Route::get('/get-categories-by-class', [App\Http\Controllers\Api\CategoriesController::class, 'getCategoriesByID']);
        Route::post('/post-school-transactions', [App\Http\Controllers\Api\CategoriesController::class, 'doubleEntry']);
        Route::get('/get-account-by-class-id', [App\Http\Controllers\Api\AccountController::class, 'getAccountByClassId']);
        Route::get('/get-account-by-category-id', [App\Http\Controllers\Api\AccountController::class, 'getAccountByCategoryId']);
        Route::get('/get-account-by-sub-category-id', [App\Http\Controllers\Api\AccountController::class, 'getAccountBySubCategoryId']);
        Route::post('/purchase-order', [StockController::class, 'createPurchaseOrder']);
        Route::post('/create-purchase-order', [StockController::class, 'createPurchaseOrderNew']);
        Route::get('/delete-purchase-order', [StockController::class, 'deletePurchaseOrder']);
        Route::post('/update-purchase-order', [StockController::class, 'updatePurchaseOrder']);
        Route::get('/fetch-purchase-order', [StockController::class, 'index']);
        Route::get('/pending-purchase-order', [StockController::class, 'pendingPurchaseOrders']);
        Route::get('/completed-purchase-order', [StockController::class, 'completedPurchaseOrders']);
        Route::get('/approve-purchase-order', [StockController::class, 'approvePurchaseOrder']);
        Route::get('/customer-pendingorder', [StockController::class, 'pendingOrders']);
        Route::get('/order-details', [StockController::class, 'OrderDetails']);
        Route::post('/stock-delivery', [StockController::class, 'stockDelivery']);
        Route::get('/delete-order', [StockController::class, 'deleteOrder']);
        Route::get('/get-deleted-orders', [StockController::class, 'getDeletedPurchaseOrder']);
        Route::get('/restore-deleted-order', [StockController::class, 'restorePurchaseOrder']);
        Route::get('/restore-all-deleted-orders', [StockController::class, 'restoreAllPurchaseOrders']);
        Route::get('/force-delete-purchase-order', [StockController::class, 'forceDeletePurchaseOrder']);
        Route::get('/force-delete-all-purchase-orders', [StockController::class, 'forceDeleteAllPurchaseOrders']);
        Route::get('/fetch-distinct-journal-entries', [App\Http\Controllers\TaxController::class, 'fetchDistinctUUID']);
        Route::get('/delete-journal-entries', [App\Http\Controllers\TaxController::class, 'deleteJournalEntries']);
        Route::get('/fetch-deleted-journal-entries', [App\Http\Controllers\TaxController::class, 'getDeletedJournalEntries']);
        Route::get('/restore-journal-entries', [App\Http\Controllers\TaxController::class, 'restoreDeletedJournalEntries']);
        Route::get('/restore-all-journal-entries', [App\Http\Controllers\TaxController::class, 'restoreAllJournalEntries']);
        Route::get('/force-delete-journal-entries', [App\Http\Controllers\TaxController::class, 'forceDeleteJournalEntry']);
        Route::get('/force-delete-all-journal-entries', [App\Http\Controllers\TaxController::class, 'forceDeleteAllJournalEntries']);
        Route::post('/update-working-month', [App\Http\Controllers\TaxController::class, 'updateProvinceMonth']);
        Route::post('/reverse-working-month', [App\Http\Controllers\TaxController::class, 'reverseProvinceMonth']);
        Route::get('/get-journal-transaction', [App\Http\Controllers\TaxController::class, 'getJournalTrans']);
        Route::get('/pending-purchase-orders', [StockController::class, 'pendingPurchaseOrders']);
        Route::get('/pending-purchase-orders-id', [StockController::class, 'pendingPurchaseOrdersID']);
        Route::get('/delivered-purchase-order', [StockController::class, 'deliveredPurchaseOrder']);
        Route::post('/post-account-payable', [App\Http\Controllers\Api\PaymentVoucherController::class, 'saveAccountPayable']);
        Route::get('/delete-account-payable', [App\Http\Controllers\Api\PaymentVoucherController::class, 'deleteAccountPayable']);
        Route::get('/force-delete-account-payable', [App\Http\Controllers\Api\PaymentVoucherController::class, 'forceDeleteAccountPayable']);
        Route::get('/restore-account-payable', [App\Http\Controllers\Api\PaymentVoucherController::class, 'restoreAccountPayable']);
        Route::get('/fetch-paid-payables', [App\Http\Controllers\Api\PaymentVoucherController::class, 'fetchPaidPayables']);
        Route::get('/fetch-all-archived-payables', [App\Http\Controllers\Api\PaymentVoucherController::class, 'fetchSoftdelete']);
        Route::get('/fetch-all-payables', [App\Http\Controllers\Api\PaymentVoucherController::class, 'fetchPayables']);
        Route::get('/fetch-pending-payables', [App\Http\Controllers\Api\PaymentVoucherController::class, 'pendingPayables']);
        Route::get('/pincard-details', [StockController::class, 'pincardDetails']);
        Route::post('/create-depreciation-method', [AssetSubcategoryController::class, 'createDepreciationMethods']);
        Route::post('/update-depreciation-method', [AssetSubcategoryController::class, 'updateDepreciationMethods']);
        Route::get('/fetch-depreciation-method', [AssetSubcategoryController::class, 'fetchDepreciationMethods']);
        Route::get('/delete-depreciation-method', [AssetSubcategoryController::class, 'deleteDepreciationMethods']);
        Route::post('/post-loan-application', [App\Http\Controllers\Api\UnitController::class, 'saveLoanApplication']);
        Route::group(['prefix' => 'category'], function () {
            Route::get('/', [App\Http\Controllers\Api\CategoriesController::class, 'index']);
            // Route::get('/get-sub-category', [App\Http\Controllers\Api\CategoriesController::class, 'getSubCategory']);
        });
        Route::get('/fetch-all-currencies', [App\Http\Controllers\Api\IncomeController::class, 'allCurrencies']);
        Route::post('/create-new', [App\Http\Controllers\Api\IncomeController::class, 'create']);
        Route::get('/fetch-all-expenses', [App\Http\Controllers\Api\IncomeController::class, 'listExpenses']);
        Route::get('/total-expenses', [App\Http\Controllers\Api\IncomeController::class, 'totalExpenses']);
        Route::group(['prefix' => 'income'], function () {
            Route::get('/', [App\Http\Controllers\Api\IncomeController::class, 'index']);
            Route::get('/total-income', [App\Http\Controllers\Api\IncomeController::class, 'totalIncome']);
            Route::get('/total-lodged', [App\Http\Controllers\Api\IncomeController::class, 'totalLodged']);
            Route::get('/total-pending-lodge', [App\Http\Controllers\Api\IncomeController::class, 'totalPending']);
            Route::get('/get-payment-method', [App\Http\Controllers\Api\IncomeController::class, 'getPaymentMethod']);
            Route::get('/fetch-all', [App\Http\Controllers\Api\IncomeController::class, 'list']);
            Route::get('/fetch-all-pending', [App\Http\Controllers\Api\IncomeController::class, 'listPending']);
            Route::post('/lodge-to-bank', [App\Http\Controllers\Api\IncomeController::class, 'lodgeToBank']);
            Route::get('/download-upload-template', [App\Http\Controllers\Api\IncomeController::class, 'download']);
            Route::post('/post-bulk-upload', [App\Http\Controllers\Api\IncomeController::class, 'postUpload']);
            Route::get('/test', [App\Http\Controllers\Api\IncomeController::class, 'TestingPlan']);
        });

        //Fixed_asset_Register API
        Route::group(['prefix' => 'fixedassets'], function () {
            Route::get('/', [AssetRegisterController::class, 'getAllFixedAssets']);
            Route::get('/statistics', [AssetRegisterController::class, 'statistics'])->middleware('admin');
            ;
            Route::get('/assets', [AssetRegisterController::class, 'ggetAllFixedAssets']);
            Route::get('/get-assets-by-company', [AssetRegisterController::class, 'getAssetByParish']);
            Route::get('/getcompanyassets', [AssetRegisterController::class, 'getAssetForParish']);
            Route::get('/all-approved-assets', [AssetRegisterController::class, 'getallApprovedFixedAssets']);
            Route::get('/all-approved-parish-assets', [AssetRegisterController::class, 'getSuperAdminParishFixedAssets']);
            Route::get('/company-approved-assets', [AssetRegisterController::class, 'getApprovedFixedAssetsForProvince']);
            Route::post('/create_fixed_asset', [AssetRegisterController::class, 'saveFixedAsset']);
            Route::post('/update_fixed_asset', [AssetRegisterController::class, 'updateFixedAsset']);
            Route::get('/delete_fixed_asset', [AssetRegisterController::class, 'deleteFixedAsset']);
            Route::post('/upload_document', [AssetRegisterController::class, 'assetregisterDocument']);
            Route::post('/import', [AssetRegisterController::class, 'import']);
            Route::post('/transfer', [AssetRegisterController::class, 'transfer']);
            Route::get('/transfer-report', [AssetRegisterController::class, 'transferReport']);
            Route::post('/approve-assets', [AssetRegisterController::class, 'approveFixedAssetRegister']);
            Route::post('/disapprove-assets', [AssetRegisterController::class, 'disapproveFixedAssetRegister']);
            Route::get('/fetch-softdelete', [AssetRegisterController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [AssetRegisterController::class, 'restoreSingleSoftdelete']);
            Route::get('/delete-single-softdelete', [AssetRegisterController::class, 'deleteSingleSoftdelete']);
        });

        // Asset Disposal routes
        Route::group(['prefix' => 'assetdisposed'], function () {
            Route::get('/', [AssetDisposalController::class, 'getAssetDisposalList']);
            Route::get('/fetch_assetdisposed', [AssetDisposalController::class, 'getAssetDisposal']);
            Route::post('/create_assetdisposal', [AssetDisposalController::class, 'createAssetDisposal']);
        });
        Route::group(['prefix' => 'settings'], function () {
            Route::post('/activate-working-month', [App\Http\Controllers\SettingController::class, 'activateWorkingMonth']);
            Route::post('/deactivate-working-month', [App\Http\Controllers\SettingController::class, 'deactivateWorkingMonth']);
        });

        Route::group(['prefix' => 'assetcategories'], function () {
            Route::get('/', [AssetCategoryController::class, 'getAllAssetCategories']);
            Route::get('/fetch_asset_category', [AssetCategoryController::class, 'showAssetCategory']);
            Route::post('/create_asset_category', [AssetCategoryController::class, 'saveAssetCategory']);
            Route::post('/update_asset_category', [AssetCategoryController::class, 'updateAssetCategory']);
            Route::get('/delete_asset_category', [AssetCategoryController::class, 'deleteAssetCategory']);
            Route::get('/fetch-softdelete', [AssetCategoryController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [AssetCategoryController::class, 'restoreSingleSoftdelete']);
            Route::get('/delete-single-softdelete', [AssetCategoryController::class, 'deleteSingleSoftdelete']);
        });

        Route::group(['prefix' => 'assetsubcategories'], function () {
            Route::get('/', [AssetSubcategoryController::class, 'getAssetSubcategoryList']);
            Route::get('/fetch_asset_subcategory', [AssetSubcategoryController::class, 'getAssetSubcategory']);
            Route::post('/create_asset_subcategory', [AssetSubcategoryController::class, 'createAssetSubcategory']);
            Route::post('/update_asset_subcategory', [AssetSubcategoryController::class, 'updateAssetSubcategory']);
            Route::get('/delete_asset_subcategory', [AssetSubcategoryController::class, 'deleteAssetSubcategory']);
        });


        Route::group(['prefix' => 'booking'], function () {
            Route::get('/', [App\Http\Controllers\Api\BookingController::class, 'index']);
            Route::get('/get-payments', [App\Http\Controllers\Api\BookingController::class, 'getPayments']);
            Route::get('/get-details', [App\Http\Controllers\Api\BookingController::class, 'details']);
            Route::get('/add', [App\Http\Controllers\Api\BookingController::class, 'add']);
            Route::post('/create', [App\Http\Controllers\Api\BookingController::class, 'createNew']);
            Route::post('/create-booking', [App\Http\Controllers\Api\BookingController::class, 'createNew']);
            Route::post('/update_old', [App\Http\Controllers\Api\BookingController::class, 'update']);
            Route::post('/update', [App\Http\Controllers\Api\BookingController::class, 'updateNew']);
            Route::get('/delete', [App\Http\Controllers\Api\BookingController::class, 'deleteBooking']);
            Route::post('/add-labor-expenses', [App\Http\Controllers\Api\BookingController::class, 'addLaborExpenses']);
            Route::get('/pending-payment', [App\Http\Controllers\Api\BookingController::class, 'pending']);
            Route::post('/make-payment', [App\Http\Controllers\Api\BookingController::class, 'makePayment']);
            Route::post('/add-expenses', [App\Http\Controllers\Api\BookingController::class, 'addNewExpenses']);



            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\BookingController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\Api\BookingController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\Api\BookingController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\BookingController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\BookingController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'journal'], function () {
            Route::post('/post-entries', [App\Http\Controllers\Api\JournalController::class, 'postJournalEntries']);
            Route::post('/opening-balance-bulk', [App\Http\Controllers\Api\JournalController::class, 'openingBalanceBulk']);
            Route::post('/create', [App\Http\Controllers\Api\BookingController::class, 'create']);
        });
        Route::group(['prefix' => 'sales'], function () {
            Route::get('/', [App\Http\Controllers\Api\SaleController::class, 'index']);
            Route::get('/transactions', [App\Http\Controllers\Api\SaleController::class, 'getSaleTransactons']);
            Route::post('/create', [App\Http\Controllers\Api\SaleController::class, 'create']);
        });
        Route::group(['prefix' => 'users'], function () {
            Route::get('/fetch-all', [App\Http\Controllers\Api\UserController::class, 'index']);
            Route::post('/create-new', [App\Http\Controllers\Api\UserController::class, 'create']);
            Route::post('/create-with-module', [App\Http\Controllers\Api\UserController::class, 'createWithModule']);
            Route::post('/assign-module', [App\Http\Controllers\Api\UserController::class, 'assignModule']);
            Route::post('/assign-module-is-admin', [App\Http\Controllers\Api\UserController::class, 'assignModuleIsAdmin']);
            Route::post('/update-user', [App\Http\Controllers\Api\UserController::class, 'updateUser']);
            Route::get('/delete', [App\Http\Controllers\Api\UserController::class, 'deleteUser']);
            Route::get('/search', [App\Http\Controllers\Api\UserController::class, 'search']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\UserController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\Api\UserController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\Api\UserController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\UserController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\UserController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'role'], function () {
            Route::get('/', [App\Http\Controllers\RoleController::class, 'show']);
            Route::post('/create', [App\Http\Controllers\RoleController::class, 'store']);
            Route::post('/update', [App\Http\Controllers\RoleController::class, 'update']);
            Route::get('/delete-role', [App\Http\Controllers\RoleController::class, 'deleteRole']);
            Route::get('/permissions', [App\Http\Controllers\RoleController::class, 'getPermissions']);
            Route::get('/get-roles', [App\Http\Controllers\RoleController::class, 'getRoles']);
            Route::get('/get-role-list', [App\Http\Controllers\RoleController::class, 'getRolelist']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\RoleController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\RoleController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\RoleController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\RoleController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\RoleController::class, 'deleteSoftdelete']);
        });

        Route::group(['prefix' => 'beneficiaryaccounts'], function () {
            Route::get('/', [App\Http\Controllers\BeneficiaryAccountController::class, 'getAllbeneficiaryAccounts']);
            Route::post('/create', [App\Http\Controllers\BeneficiaryAccountController::class, 'createBeneficiaryAccount']);
            Route::post('/update', [App\Http\Controllers\BeneficiaryAccountController::class, 'updateBeneficiaryAccount']);
            Route::any('/getaccount', [App\Http\Controllers\BeneficiaryAccountController::class, 'getBeneficiaryAccount']);
            Route::get('/delete', [App\Http\Controllers\BeneficiaryAccountController::class, 'deleteBeneficiaryAccount']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\BeneficiaryAccountController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\BeneficiaryAccountController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\BeneficiaryAccountController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\BeneficiaryAccountController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\BeneficiaryAccountController::class, 'deleteSoftdelete']);
        });

        Route::group(['prefix' => 'rider'], function () {
            Route::get('/', [App\Http\Controllers\UserController::class, 'getRiders']);
            Route::post('/add', [App\Http\Controllers\UserController::class, 'createRider']);
            Route::get('/edit', [App\Http\Controllers\UserController::class, 'editRider']);
            Route::get('/delete', [App\Http\Controllers\UserController::class, 'deleteRider']);
            Route::post('/store', [App\Http\Controllers\UserController::class, 'createRider']);
        });

        Route::group(['prefix' => 'taxes'], function () {
            Route::get('/', [App\Http\Controllers\TaxController::class, 'getallTaxes']);
            Route::get('/get-tax', [App\Http\Controllers\TaxController::class, 'getTax']);
            Route::get('/company-tax', [App\Http\Controllers\TaxController::class, 'getCompanyTax']);
            Route::post('/create', [App\Http\Controllers\TaxController::class, 'createTax']);
            Route::post('/update', [App\Http\Controllers\TaxController::class, 'updateTax']);
            Route::get('/delete', [App\Http\Controllers\TaxController::class, 'deleteTax']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\TaxController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\TaxController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\TaxController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\TaxController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\TaxController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'payment_voucher'], function () {
            Route::post('/post-payment', [App\Http\Controllers\Api\PaymentVoucherController::class, 'postPayment']);
            Route::post('/post-bulk-journal', [App\Http\Controllers\Api\PaymentVoucherController::class, 'postBulkJournal']);
            Route::post('/post-bulk-journal-excel', [App\Http\Controllers\Api\PaymentVoucherController::class, 'postBulkJournalExcel']);
            Route::get('/fetch-all', [App\Http\Controllers\Api\PaymentVoucherController::class, 'indexVoucher']);
            Route::get('/list', [App\Http\Controllers\Api\PaymentVoucherController::class, 'index']);
            Route::get('/add', [App\Http\Controllers\Api\PaymentVoucherController::class, 'add']);
            Route::get('/get-details', [App\Http\Controllers\Api\PaymentVoucherController::class, 'getDetails']);
            Route::post('/create-new', [App\Http\Controllers\Api\PaymentVoucherController::class, 'create']);
            Route::get('/delete-payment-voucher', [App\Http\Controllers\Api\PaymentVoucherController::class, 'deletePaymentVoucher']);
            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\PaymentVoucherController::class, 'getDeletedPaymentVouchers']);
            Route::post('/restore-single-softdelete', [App\Http\Controllers\Api\PaymentVoucherController::class, 'restorePaymentVouchers']);
            Route::post('/restore-softdelete', [App\Http\Controllers\Api\PaymentVoucherController::class, 'restoreAllPaymentVouchers']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\PaymentVoucherController::class, 'forceDeletePaymentVoucher']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\PaymentVoucherController::class, 'forceDeleteAllPaymentVouchers']);
            Route::post('/generate-payment', [App\Http\Controllers\Api\PaymentVoucherController::class, 'save_beneficiary_payment']);
            Route::post('/make-voucher-payment', [App\Http\Controllers\Api\PaymentVoucherController::class, 'payment']);
            Route::get('/approved-paid-voucher', [App\Http\Controllers\Api\PaymentVoucherController::class, 'approvedPaidVouchers']);
            Route::get('/approved_payment_list', [App\Http\Controllers\Api\PaymentVoucherController::class, 'approved_payment_list']);
            Route::get('/pending_payment_list', [App\Http\Controllers\Api\PaymentVoucherController::class, 'pending_payment_list']);
            Route::get('/approve_voucher', [App\Http\Controllers\Api\PaymentVoucherController::class, 'approve_voucher']);
            Route::get('/disapprove_voucher', [App\Http\Controllers\Api\PaymentVoucherController::class, 'disapprove_voucher']);
        });


        Route::group(['prefix' => 'reports'], function () {
            Route::get('/profit-loss-report', [App\Http\Controllers\ReportController::class, 'profitAndLoss']);
            Route::get('/summary-income-report', [App\Http\Controllers\ReportController::class, 'summaryIncomeReport']);
            Route::get('/summary-profit-loss-exclusive', [App\Http\Controllers\ReportController::class, 'summaryProfitLoss']);
            Route::get('/income-detailed-report', [App\Http\Controllers\ReportController::class, 'incomeDetailReport']);
            Route::get('/profit-loss-exclusive', [App\Http\Controllers\ReportController::class, 'profitLossReport']);
            Route::get('/profit-loss-build', [App\Http\Controllers\ReportController::class, 'profitLossReportBuild']);
            Route::get('/activity-report', [App\Http\Controllers\ReportController::class, 'activityReportNew']);
            Route::get('/balance-sheet', [App\Http\Controllers\ReportController::class, 'balanceSheet']);
            Route::get('/inflow', [App\Http\Controllers\ReportController::class, 'inflow'])->name('inflow-report');
            Route::get('/financial-position', [App\Http\Controllers\ReportController::class, 'financialPositionTest']);
            Route::get('/financial-position-summary', [App\Http\Controllers\ReportController::class, 'financialPositionSummaryNew']);
            Route::get('/continent-inflow', [App\Http\Controllers\ReportController::class, 'continentInflow'])->name('continent-inflow-report');
            Route::get('/region-inflow', [App\Http\Controllers\ReportController::class, 'regionInflow'])->name('region-inflow-report');
            Route::get('/province-inflow', [App\Http\Controllers\ReportController::class, 'provinceInflow'])->name('province-inflow-report');
            Route::get('/currency-inflow', [App\Http\Controllers\ReportController::class, 'currencyInflow'])->name('currency-inflow-report');
            Route::get('/monthly-income-summary-report', [App\Http\Controllers\ReportController::class, 'monthlyIncomeSummary'])->name('monthly-income-summary-report');
            Route::get('/print-inflow/{start}/{end}', [App\Http\Controllers\ReportController::class, 'printInflow'])->name('print-cash-inflow');
            Route::get('/download-inflow/{start}/{end}', [App\Http\Controllers\ReportController::class, 'downloadInflow'])->name('download-cash-inflow-report');
            Route::get('/charts-of-account', [App\Http\Controllers\ReportController::class, 'chartOfpAccount'])->name('charts_of_account');
            Route::get('/general-ledger', [App\Http\Controllers\ReportController::class, 'generalLedger'])->name('general_ledger');
            Route::get('/account-report', [App\Http\Controllers\ReportController::class, 'accountReport'])->name('account-report');
            Route::get('/cashbook', [App\Http\Controllers\ReportController::class, 'searchLedger'])->name('cashbook');
            Route::get('/schedule-of-receivable', [App\Http\Controllers\ReportController::class, 'scheduleOfReceivable']);
            Route::get('/schedule-of-payable', [App\Http\Controllers\ReportController::class, 'scheduleOfPayable']);
            Route::get('/trial-balance', [App\Http\Controllers\ReportController::class, 'trialBalance'])->name('trial_balance');
            Route::any('/get-trial-balance', [App\Http\Controllers\ReportController::class, 'getTrialBalance'])->name('get_trial_balance');
            Route::any('/get-trial-balance-summary', [App\Http\Controllers\ReportController::class, 'getSummaryTrialBalance']);
            Route::any('/print-trial-balance/{start}/{end}', [App\Http\Controllers\ReportController::class, 'printTrialBalance'])->name('print-trial-balnace');
            // Route::get('/balance-sheet', [App\Http\Controllers\ReportController::class, 'balanceSheet'])->name('balance_sheet');
            Route::get('/searchCashbook', [App\Http\Controllers\ReportController::class, 'searchLedger'])->name('searchAndFilter_cashbook');
            Route::get('/searchContinentCashbook', [App\Http\Controllers\ReportController::class, 'searchContinentLedger'])->name('searchAndFilterContinentCashbook');
            Route::get('/general-ledger-filter', [App\Http\Controllers\ReportController::class, 'searchJournal'])->name('searchAndFilter_journal');
            Route::get('/searchAccount', [App\Http\Controllers\ReportController::class, 'searchAccount'])->name('searchAndFilter_account');
            Route::get('/searchCustomer', [App\Http\Controllers\ReportController::class, 'searchCustomer'])->name('searchAndFilter_customer');
            Route::get('/searchSupplier', [App\Http\Controllers\ReportController::class, 'searchSupplier'])->name('searchAndFilter_supplier');
            Route::get('/search-continental-Journal', [App\Http\Controllers\ReportController::class, 'searchContinentalJournal'])->name('searchAndFilterContinentaljournal');
            Route::get('/searchReceipt', [App\Http\Controllers\ReportController::class, 'searchReceipt'])->name('searchAndFilterReceipts');
            Route::get('/searchReceiptByCode', [App\Http\Controllers\ReportController::class, 'searchReceiptByCode'])->name('searchAndFilterReceiptsByGlcode');
            Route::get('/pdf', [App\Http\Controllers\ReportController::class, 'pdfview'])->name('pdfview');
            Route::get('/print_cashbook', [App\Http\Controllers\ReportController::class, 'printCashbook'])->name('print_cashbook');
            Route::get('/print_account_report', [App\Http\Controllers\ReportController::class, 'printAccountReport'])->name('print_account_report');
            Route::get('/print_customer_report', [App\Http\Controllers\ReportController::class, 'printCustomerlLedger'])->name('print_customer_report');
            Route::get('/print_supplier_report', [App\Http\Controllers\ReportController::class, 'printSupplierlLedger'])->name('print_supplier_report');
            Route::get('/session_save', [App\Http\Controllers\ReportController::class, 'sessionSave'])->name('session_save');
            Route::get('/income-and-expenditure', [App\Http\Controllers\ReportController::class, 'incomeExpenditure'])->name('income_and_expenditure');
            Route::get('/continent-income-and-expenditure', [App\Http\Controllers\ReportController::class, 'continentIncomeExpenditure'])->name('continent_income_and_expenditure');
            Route::get('/region-income-and-expenditure', [App\Http\Controllers\ReportController::class, 'regionIncomeExpenditure'])->name('region_income_and_expenditure');
            Route::get('/province-income-and-expenditure', [App\Http\Controllers\ReportController::class, 'provinceIncomeExpenditure'])->name('province_income_and_expenditure');
            Route::any('/get-income-and-expenditure', [App\Http\Controllers\ReportController::class, 'getIncomeExpenditure'])->name('get_income_expenditure');
            Route::any('/get-monthly-income-and-expenditure', [App\Http\Controllers\ReportController::class, 'getMonthlyIncomeExpenditure']);
            Route::any('/print-income-and-expenditure/{start}/{end}/{id}', [App\Http\Controllers\ReportController::class, 'printIncomeExpenditure'])->name('print-income-expenditure');
            Route::any('/print-monthly-income-summary/{month}', [App\Http\Controllers\ReportController::class, 'printMonthlyIncomeSummary'])->name('print-monthly-income-summary-report');
            Route::any('/download-monthly-income-summary/{month}', [App\Http\Controllers\ReportController::class, 'downloadMonthlyIncomeSummary'])->name('download-monthly-income-summary-report');

            // Route::get('pdfview',array('as'=>'pdfview','uses'=>'ItemController@pdfview'));
            Route::get('/fetch-all', [App\Http\Controllers\Api\PaymentVoucherController::class, 'index']);
            Route::get('/add', [App\Http\Controllers\Api\PaymentVoucherController::class, 'add']);
            Route::post('/create-new', [App\Http\Controllers\Api\PaymentVoucherController::class, 'create']);
            Route::get('/download-journal-posting', [App\Http\Controllers\Api\PaymentVoucherController::class, 'downloadJournal']);
        });

        Route::get('/age-report-payables', [App\Http\Controllers\PayableTypeController::class, 'getUnpaidPayables']);
        Route::get('/age-report-receivables', [App\Http\Controllers\PayableTypeController::class, 'getUnpaidReceivables']);
        Route::group(['prefix' => 'stocks'], function () {
            Route::get('/', [StockController::class, 'getStockList']);
            Route::get('/fetch_stock', [StockController::class, 'getStock']);
            Route::post('/add_stock', [StockController::class, 'addStock']);
            Route::post('/update_stock', [StockController::class, 'updateStock']);
            Route::get('/delete_stock', [StockController::class, 'deleteStock']);
            Route::get('/fetch-softdelete', [StockController::class, 'getDeletedStocks']);
            Route::get('/restore-single-softdelete', [StockController::class, 'restoreDeletedStocks']);
            Route::get('/restore-softdelete', [StockController::class, 'restoreAllDeletedStocks']);
            Route::get('/delete-single-softdelete', [StockController::class, 'forceDeleteStock']);
            Route::get('/delete-softdelete', [StockController::class, 'forceDeleteAllStocks']);
            Route::post('/submit-request', [StockController::class, 'CreatestockRequest']);
            Route::post('/create-request', [StockController::class, 'createRequest']);
            Route::post('/release-requisition', [StockController::class, 'releaseRequisition']);
            Route::get('/approve-requisition', [StockController::class, 'approveRequisition']);
            Route::get('/disapprove-requisition', [StockController::class, 'disapproveRequisition']);
            Route::get('/fetch-all-request', [StockController::class, 'getStockRequests']);
            Route::get('/fetch-request', [StockController::class, 'getArequisition']);
            Route::post('/update-request', [StockController::class, 'updateStockRequest']);
            Route::post('/delete-requisition', [StockController::class, 'deleteRequisition']);
        });

        Route::group(['prefix' => 'units'], function () {
            Route::get('/fetch-all', [App\Http\Controllers\Api\ItemController::class, 'unit']);
            Route::get('/get-unit', [App\Http\Controllers\Api\ItemController::class, 'getunit']);
            Route::post('/create', [App\Http\Controllers\Api\ItemController::class, 'create']);
            Route::post('/update-unit', [App\Http\Controllers\Api\ItemController::class, 'updateUnit']);
            Route::get('/delete-unit', [App\Http\Controllers\Api\ItemController::class, 'deleteUnit']);
            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\ItemController::class, 'getDeletedUnits']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\Api\ItemController::class, 'restoreDeletedUnit']);
            Route::get('/restore-softdelete', [App\Http\Controllers\Api\ItemController::class, 'restoreAllDeletedUnits']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\ItemController::class, 'forceDeleteUnits']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\ItemController::class, 'forceDeleteAllUnits']);
        });
        Route::group(['prefix' => 'items'], function () {
            Route::get('/fetch-all', [App\Http\Controllers\Api\ItemController::class, 'index']);
            Route::get('/fetch-stocks', [App\Http\Controllers\Api\ItemController::class, 'stocks']);
            Route::get('/fetch-products', [App\Http\Controllers\Api\ItemController::class, 'fetchProducts']);
            Route::get('/fetch-services', [App\Http\Controllers\Api\ItemController::class, 'fetchServices']);
            Route::get('/get-stock', [App\Http\Controllers\Api\ItemController::class, 'getStock']);
            Route::get('/get-item', [App\Http\Controllers\Api\ItemController::class, 'getItem']);
            Route::post('/add-new-item', [App\Http\Controllers\Api\ItemController::class, 'addNewItem']);
            Route::post('/create-new-stock', [App\Http\Controllers\Api\ItemController::class, 'createStockItem']);
            Route::post('/update-stock-item', [App\Http\Controllers\Api\ItemController::class, 'updateStockItem']);
            Route::get('/item-count', [App\Http\Controllers\Api\ItemController::class, 'itemCount']);
            Route::post('/update-item', [App\Http\Controllers\Api\ItemController::class, 'updateItem']);
            Route::get('/delete-item', [App\Http\Controllers\Api\ItemController::class, 'deleteItem']);
            Route::get('/fetch-softdelete', [App\Http\Controllers\Api\ItemController::class, 'getDeletedItems']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\Api\ItemController::class, 'restoreDeletedItem']);
            Route::get('/restore-softdelete', [App\Http\Controllers\Api\ItemController::class, 'restoreAllDeletedItems']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\Api\ItemController::class, 'forceDeleteItem']);
            Route::get('/delete-softdelete', [App\Http\Controllers\Api\ItemController::class, 'forceDeleteAllItems']);
        });


        Route::group(['prefix' => 'departments'], function () {
            Route::get('/', [App\Http\Controllers\DepartmentController::class, 'getallDepartment']);
            Route::post('/create', [App\Http\Controllers\DepartmentController::class, 'createDepartment']);
            Route::post('/update', [App\Http\Controllers\DepartmentController::class, 'updateDepartment']);
            Route::get('/find-department', [App\Http\Controllers\DepartmentController::class, 'findDepartment']);
            Route::get('/delete', [App\Http\Controllers\DepartmentController::class, 'deleteDepartment']);

            Route::get('/fetch-softdelete', [App\Http\Controllers\DepartmentController::class, 'fetchSoftdelete']);
            Route::get('/restore-single-softdelete', [App\Http\Controllers\DepartmentController::class, 'restoreSingleSoftdelete']);
            Route::get('/restore-softdelete', [App\Http\Controllers\DepartmentController::class, 'restoreSoftdelete']);
            Route::get('/delete-single-softdelete', [App\Http\Controllers\DepartmentController::class, 'deleteSingleSoftdelete']);
            Route::get('/delete-softdelete', [App\Http\Controllers\DepartmentController::class, 'deleteSoftdelete']);
        });
        Route::group(['prefix' => 'account'], function () {
            Route::post('/create-loan-account', [App\Http\Controllers\NominalLedgerController::class, 'createLoan']);
            Route::post('/update-loan-account', [App\Http\Controllers\NominalLedgerController::class, 'updateLoan']);
            Route::post('/upload-opening-balance', [App\Http\Controllers\Api\AccountController::class, 'uploadOpeningBalance']);
            Route::post('/upload-journal-entries', [App\Http\Controllers\Api\AccountController::class, 'uploadJournalEntries']);
            Route::get('/get-by-last-category-id', [App\Http\Controllers\Api\AccountController::class, 'getAccountsByLastCategory']);
            Route::get('/fetch-loans', [App\Http\Controllers\NominalLedgerController::class, 'fetchLoans']);
            Route::post('/create-savings-account', [App\Http\Controllers\NominalLedgerController::class, 'createSavings']);
            Route::post('/update-account', [App\Http\Controllers\NominalLedgerController::class, 'updateAccount']);
            Route::post('/staff-savings', [App\Http\Controllers\NominalLedgerController::class, 'staffSavings']);
            Route::get('/fetch-savings', [App\Http\Controllers\NominalLedgerController::class, 'fetchSavings']);
            Route::get('/fetch-savings-type', [App\Http\Controllers\NominalLedgerController::class, 'StaffSavingsType']);
            Route::get('/fetch-new-savings', [App\Http\Controllers\NominalLedgerController::class, 'StaffSavingsTypeNew']);
            Route::get('/fetch-savings-prefix', [App\Http\Controllers\NominalLedgerController::class, 'StaffSavingsTypeByPrefix']);
            Route::get('/fetch-savings-data', [App\Http\Controllers\NominalLedgerController::class, 'StaffSavingsTypeWithData']);
            Route::get('/fetch-savings-transaction', [App\Http\Controllers\NominalLedgerController::class, 'fetchStaffSavingsNew']);
            Route::get('/fetch-loan-transactions', [App\Http\Controllers\NominalLedgerController::class, 'fetchStaffLoanTransactions']);
            Route::get('/delete-loan', [App\Http\Controllers\NominalLedgerController::class, 'deleteLoans']);
            Route::get('/delete-savings', [App\Http\Controllers\NominalLedgerController::class, 'deleteSavings']);
            Route::post('/staff-loan', [App\Http\Controllers\NominalLedgerController::class, 'staffLoan']);
            Route::post('/new-staff-loan', [App\Http\Controllers\NominalLedgerController::class, 'newStaffLoan']);
            Route::get('/fetch-staff-loan', [App\Http\Controllers\NominalLedgerController::class, 'fetchStaffLoans']);
            Route::get('/fetch-repayment-details', [App\Http\Controllers\NominalLedgerController::class, 'fetchMemberLoanWithTotal']);
            Route::get('/fetch-approved-staff-loan', [App\Http\Controllers\NominalLedgerController::class, 'fetchApprovedStaffLoans']);
            Route::post('/approve-staff-loan', [App\Http\Controllers\NominalLedgerController::class, 'approveMemberLoan']);
            Route::post('/disburse-staff-loan', [App\Http\Controllers\NominalLedgerController::class, 'disburseMemberLoan']);
            Route::post('/disapprove-staff-loan', [App\Http\Controllers\NominalLedgerController::class, 'disapproveMemberLoan']);
            Route::get('/fetch-staff-savings', [App\Http\Controllers\NominalLedgerController::class, 'fetchStaffSavings']);
            Route::get('/fetch-bank-savings', [App\Http\Controllers\NominalLedgerController::class, 'fetchStaffBankSavings']);
            Route::get('/fetch-member-savings', [App\Http\Controllers\NominalLedgerController::class, 'fetchMemberSavings']);
            Route::get('/fetch-member-loans', [App\Http\Controllers\NominalLedgerController::class, 'fetchMemberLoan']);
            Route::get('/fetch-member-ledger-by-saving', [App\Http\Controllers\NominalLedgerController::class, 'fetchMemberSavingLedger']);
            Route::get('/fetch-member-ledger-by-loan', [App\Http\Controllers\NominalLedgerController::class, 'fetchMemberLoanLedger']);
            Route::get('/fetch-member-ledger-by-account', [App\Http\Controllers\NominalLedgerController::class, 'fetchMemberLedgerByAccount']);
            Route::post('/save-opening-balance', [App\Http\Controllers\ReportNew::class, 'saveReviewedOpeningBalance']);
            Route::post('/save-journal-entries', [App\Http\Controllers\ReportNew::class, 'saveReviewedJournalEntries']);
        });

        Route::group(['prefix' => 'units'], function () {
            Route::get('/', [App\Http\Controllers\Api\UnitController::class, 'index']);
            Route::post('/add-unit', [App\Http\Controllers\Api\UnitController::class, 'addNewUnit']);
        });
        Route::group(['prefix' => 'approval_level'], function () {
            Route::post('/create_app', [App\Http\Controllers\ApprovalLevelController::class, 'create']);
            Route::get('/', [App\Http\Controllers\ApprovalLevelController::class, 'getAll']);
            Route::post('/update_app', [App\Http\Controllers\ApprovalLevelController::class, 'update']);
            Route::get('/get_app_level', [App\Http\Controllers\ApprovalLevelController::class, 'fetchApprovalLevelById']);
            Route::get('/delete', [App\Http\Controllers\ApprovalLevelController::class, 'deleteApprovalLevel']);
            Route::get('/delete', [App\Http\Controllers\ApprovalLevelController::class, 'deleteApprovalLevel']);
        });
        Route::group(['prefix' => 'module'], function () {
            Route::get('/', [App\Http\Controllers\ApprovalLevelController::class, 'index']);
            Route::post('/update', [App\Http\Controllers\PurchaseInvoiceController::class, 'updateSalesInvoiceNew']);
        });
        Route::group(['prefix' => 'dashboard'], function () {
            Route::get('/', [App\Http\Controllers\DashBoardController::class, 'board']);
            Route::get('/total-payables', [App\Http\Controllers\DashBoardController::class, 'totalPayables']);
            Route::get('/total-receivables', [App\Http\Controllers\DashBoardController::class, 'totalReceivables']);
            Route::get('/total-incomes', [App\Http\Controllers\DashBoardController::class, 'totalIncomes']);
            Route::get('/total-expenses', [App\Http\Controllers\DashBoardController::class, 'totalExpenses']);
            Route::get('/top-customers', [App\Http\Controllers\DashBoardController::class, 'topCustomer']);
        });
        Route::get('/get-monthly-deduction', [App\Http\Controllers\Api\JournalController::class, 'getMonthlyDeduction']);
        Route::get('/get-loan-monthly-deduction', [App\Http\Controllers\Api\JournalController::class, 'getLoanMonthlyDeduction']);
        Route::post('/import-monthly-deduction-template', [App\Http\Controllers\Api\JournalController::class, 'importTemplate']);
        Route::get('/get-deduction-import', [App\Http\Controllers\Api\JournalController::class, 'deductionImport']);
        Route::post('/reverse-deduction', [App\Http\Controllers\Api\JournalController::class, 'reverseDeduction']);
        Route::get('/ledger-postings', [App\Http\Controllers\Api\JournalController::class, 'ledgerPostings']);
        Route::post('/post-receivables', [App\Http\Controllers\Api\JournalController::class, 'postReceivables']);
        Route::post('/post-receipt', [App\Http\Controllers\Api\JournalController::class, 'postReceipt']);
        Route::get('/delete-receipt', [App\Http\Controllers\Api\JournalController::class, 'deleteReceipt']);
        Route::get('/force-delete-receipt', [App\Http\Controllers\Api\JournalController::class, 'forceDeleteReceipt']);
        Route::get('/restore-receipt', [App\Http\Controllers\Api\JournalController::class, 'restoreReceipt']);
        Route::post('/post-expenses', [App\Http\Controllers\Api\JournalController::class, 'postExpenses']);
        Route::get('/fetch-receivables', [App\Http\Controllers\Api\JournalController::class, 'fetchReceivables']);
        Route::get('/fetch-pending-receivables', [App\Http\Controllers\Api\JournalController::class, 'fetchPendingReceivables']);
        Route::get('/fetch-receipts', [App\Http\Controllers\Api\JournalController::class, 'fetchReceipts']);
        Route::get('/fetch-archived-receipts', [App\Http\Controllers\Api\JournalController::class, 'receiptFetchSoftdelete']);
        Route::get('/fetch-invoice-sum', [App\Http\Controllers\Api\JournalController::class, 'fetchAmountReceivables']);
        Route::get('/fetch-expenses', [App\Http\Controllers\Api\JournalController::class, 'fetchExpenses']);
    });

    Route::group(['prefix' => 'store'], function () {
        Route::get('/', [App\Http\Controllers\StoreController::class, 'index'])->name('store-home');
        Route::get('/user', [App\Http\Controllers\StoreController::class, 'user'])->name('store-user');
        Route::get('/destroy-assign-data', [App\Http\Controllers\StoreController::class, 'destroyData'])->name('destroy-assign-data');
        Route::post('/assign-user', [App\Http\Controllers\StoreController::class, 'assignUser'])->name('assign-user-to-store');
        Route::get('/assign-user-details', [App\Http\Controllers\StoreController::class, 'assignDetails'])->name('assign-user-details');
        Route::get('/details', [App\Http\Controllers\StoreController::class, 'details'])->name('store-details');
        Route::get('/requisition/details-{id}', [App\Http\Controllers\StoreController::class, 'requisitionDetails'])->name('requisition-details');
        Route::get('/outlet', [App\Http\Controllers\StoreController::class, 'outlet'])->name('store-outlet-home');
        Route::get('/outlet/products', [App\Http\Controllers\StoreController::class, 'outletProducts'])->name('store-outlet-product');
        Route::get('/main-to-outlet', [App\Http\Controllers\StoreController::class, 'mainToOutlet'])->name('transfer-main-outlet');
        Route::get('/transfer-stock', [App\Http\Controllers\StoreController::class, 'transferStock'])->name('transfer-stock');
        Route::get('/get-outlet', [App\Http\Controllers\StoreController::class, 'getStoreOutlet'])->name('get-store-outlet');
        Route::get('/get-store-by-type', [App\Http\Controllers\StoreController::class, 'getStoreByType'])->name('get-store-by-type');
        Route::get('/get-product-by-store-id', [App\Http\Controllers\StoreController::class, 'getProductByStore'])->name('get-product-by-store-id');
        Route::get('/stocks', [App\Http\Controllers\StoreController::class, 'mainProducts'])->name('store-main-product');
        Route::get('/store-stocks', [App\Http\Controllers\StoreController::class, 'mainProductsTores'])->name('store-list-product');
        Route::get('/delete', [App\Http\Controllers\StoreController::class, 'destroy'])->name('delete-store');
        Route::post('/create', [App\Http\Controllers\StoreController::class, 'create'])->name('create-store');
        Route::get('/pending-store-requisition', [App\Http\Controllers\StoreController::class, 'pendingStoreRequisiton'])->name('pendingstore-requisition');
        Route::get('/requisition', [App\Http\Controllers\StoreController::class, 'storeRequestIndex'])->name('store-requisition');
        Route::get('/stocks/{id}', [App\Http\Controllers\StoreController::class, 'mainProducts'])->name('view-products');
        Route::get('/make-requisition', [App\Http\Controllers\StoreController::class, 'storeRequest'])->name('make-store-requisition');
        Route::post('/make-requisition', [App\Http\Controllers\StoreController::class, 'createNewOrder'])->name('create-new-store-order');
        Route::get('/pending-requisition', [App\Http\Controllers\StoreController::class, 'pendingRequisition'])->name('pending-requisition');
        Route::get('/approve-requisition', [App\Http\Controllers\StoreController::class, 'approvePurchaseOrder'])->name('approve-requisition');
        Route::post('/save-received-requisition', [App\Http\Controllers\StoreController::class, 'saveReceivedRequisition'])->name('save-received-requisition');
        Route::get('/get-pending-requisition-details-by-reference', [App\Http\Controllers\StoreController::class, 'pendingRequisitionByReference'])->name('get-pending-requisition-details-by-reference');
        Route::get('/stock-management', [App\Http\Controllers\StoreController::class, 'stockDeliverable'])->name('stock-movement');
        Route::get('/stock-deliverable', [App\Http\Controllers\StoreController::class, 'stockDeliverable'])->name('stock-deliverable');
    });
    Route::group(['prefix' => 'payment_voucher'], function () {
        Route::get('/download-excel', [App\Http\Controllers\Api\PaymentVoucherController::class, 'download']);
    });
    Route::group(['prefix' => 'customer'], function () {
        Route::get('/download-excel', [App\Http\Controllers\Api\CustomerController::class, 'RepaymentExcel']);
        Route::get('/update-customer-ledger', [App\Http\Controllers\Api\CustomerController::class, 'updatePrefixInLedger']);
    });
    Route::get('/download-deduction-template', [App\Http\Controllers\Api\JournalController::class, 'downloadTemplate']);
    Route::get('/upload-excel', [App\Http\Controllers\Api\JournalController::class, 'uploadexcel']);
    Route::get('/download-member-template', [App\Http\Controllers\Api\CustomerController::class, 'downloadMemberTemplate']);
    Route::get('/download-opening-balance', [App\Http\Controllers\ReportNew::class, 'downloadOpeningbalance']);
    Route::get('/download-journal-entries', [App\Http\Controllers\ReportNew::class, 'downloadJournalEntries']);
    Route::get('/donwload-province-excel-statistics', [AssetRegisterController::class, 'getStatisticByProvince']);
    Route::get('/download-template', [AssetRegisterController::class, 'downloadTemplate']);
    Route::post('/upload-excel', [AssetRegisterController::class, 'uploadexcel']);
    Route::get('/download-province-parish-statistics', [AssetRegisterController::class, 'getParishStatisticByProvinceExcel']);
});

//GTC mobile APIs

Route::post('/login', [App\Http\Controllers\Api\LoginController::class, 'login'])->name('login');
Route::post('/set-pin', [App\Http\Controllers\Api\LoginController::class, 'setPin']);
Route::get('/verify-pin', [App\Http\Controllers\Api\LoginController::class, 'verifyPin']);
Route::post('/register', [App\Http\Controllers\Api\LoginController::class, 'create']);
Route::get('/fetch-staff-record', [App\Http\Controllers\CustomerController::class, 'fetchStaffRecord']);
Route::get('/get-all-categories', [App\Http\Controllers\Api\RequestController::class, 'category']);
Route::get('/get-all-brands', [App\Http\Controllers\Api\RequestController::class, 'brand']);
Route::get('/get-item-details', [App\Http\Controllers\Api\RequestController::class, 'getItemDetails']);
Route::get('/get-all-items', [App\Http\Controllers\Api\RequestController::class, 'item']);
Route::get('/get-all-items-by-category', [App\Http\Controllers\Api\RequestController::class, 'getCategoryItems']);
Route::get('/get-all-brands-with-product', [App\Http\Controllers\Api\RequestController::class, 'getBrandWithItems']);
Route::get('/get-products-by-brand', [App\Http\Controllers\Api\RequestController::class, 'getProductsByBrand']);
Route::post('/make-order', [App\Http\Controllers\Api\RequestController::class, 'makeOrder']);
Route::get('/stores', [App\Http\Controllers\Api\RequestController::class, 'getStores']);
Route::get('/get-category-by-store', [App\Http\Controllers\Api\RequestController::class, 'getCategoryByStore']);
Route::get('/get-products-by-store', [App\Http\Controllers\Api\RequestController::class, 'getProductsByStore']);
Route::get('/get-products-by-category-store', [App\Http\Controllers\Api\RequestController::class, 'getProductsByCategoryStore']);
Route::get('/get-category-products', [App\Http\Controllers\Api\RequestController::class, 'getCategoryProducts']);
Route::get('/local-govt', [App\Http\Controllers\Api\RequestController::class, 'localGovt']);
Route::get('/nig-state', [App\Http\Controllers\Api\RequestController::class, 'nigState']);
Route::post('/forgot-password', [App\Http\Controllers\Api\RequestController::class, 'sendOtp']);
Route::post('/verify-otp', [App\Http\Controllers\Api\RequestController::class, 'verifyOtp']);
Route::post('/create-new-password', [App\Http\Controllers\Api\RequestController::class, 'changePassword']);
Route::post('/create-new-password-with-otp', [App\Http\Controllers\Api\RequestController::class, 'changePasswordOtp']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/profile', [App\Http\Controllers\Api\RequestController::class, 'profile']);
    Route::post('/update-profile', [App\Http\Controllers\Api\RequestController::class, 'profileUpdate']);
    Route::post('/add-to-cart', [App\Http\Controllers\Api\RequestController::class, 'addToCart']);
    Route::post('/add-to-cart-from-wish-list', [App\Http\Controllers\Api\RequestController::class, 'addToCartWishList']);
    Route::post('/add-to-wish-list', [App\Http\Controllers\Api\RequestController::class, 'addToWishList']);
    Route::post('/generate-invoice', [App\Http\Controllers\Api\RequestController::class, 'generateInvoice']);
    Route::post('/change-default-address', [App\Http\Controllers\Api\RequestController::class, 'changeDefaultAddress']);
    Route::get('/customer-cart', [App\Http\Controllers\Api\RequestController::class, 'customerCart']);
    Route::get('/customer-wish-list', [App\Http\Controllers\Api\RequestController::class, 'customerWishList']);
    Route::get('/clear-cart', [App\Http\Controllers\Api\RequestController::class, 'clearCart']);
    Route::get('/customer-paid-orders', [App\Http\Controllers\Api\RequestController::class, 'customerPaidOrders']);
    Route::get('/customer-delivered-orders', [App\Http\Controllers\Api\RequestController::class, 'customerDeliveredOrders']);
    Route::get('/delete-customer-cart', [App\Http\Controllers\Api\RequestController::class, 'deleteCartItem']);
    Route::get('/delete-customer-wish-list', [App\Http\Controllers\Api\RequestController::class, 'deleteWishList']);
    Route::get('/paid-pending-orders', [App\Http\Controllers\Api\RequestController::class, 'paidPendingOrders']);
    Route::get('/paid-delivered-orders', [App\Http\Controllers\Api\RequestController::class, 'paidDeliveredOrders']);
    Route::post('/change-password', [App\Http\Controllers\Api\LoginController::class, 'changeCustomerPassword']);
    Route::post('/review-product', [App\Http\Controllers\Api\RequestController::class, 'reviewProduct']);
    Route::post('/logout', [App\Http\Controllers\Api\LoginController::class, 'logout']);
});

