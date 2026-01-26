<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\DigitalMarketingLeadController;
use App\Http\Controllers\Api\Users\LoginController;
use App\Http\Controllers\Api\Users\AttendanceController;
use App\Http\Controllers\Api\Fabricator\LoginController as FabricatorLoginController;
use App\Http\Controllers\Api\Users\FabricatorController;
use App\Http\Controllers\Api\Users\FabricatorProfileController;
use App\Http\Controllers\Api\Users\ReportController;
use App\Http\Controllers\Api\Users\BrandController;
use App\Http\Controllers\Api\FabricatorProjectionController;
use App\Http\Controllers\Api\BdmController;
use App\Http\Controllers\Api\BdmCallController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\FabricatorStockController;
use App\Http\Controllers\Api\FabricatorStockManagementController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\S3Controller;

use App\Http\Controllers\Api\InvoiceResponseController;

Route::post('/upload-image', [S3Controller::class, 'upload']);
Route::get('/files', [S3Controller::class, 'list']);

Route::prefix('users')->group(function () {
    Route::post('send-otp', [LoginController::class, 'sendOtp']);
    Route::post('verify-otp', [LoginController::class, 'verifyOtp']);
});

    Route::prefix('fabricator')->group(function () {
    Route::post('login', [FabricatorLoginController::class, 'login']);


        Route::post('logout', [FabricatorLoginController::class, 'logout']);
        Route::post('update/{id}', [FabricatorLoginController::class, 'update']);
        Route::get(
            'dashboard',
            [FabricatorController::class, 'dashboard']
        );

});

Route::post('/digital-marketing-leads', [DigitalMarketingLeadController::class, 'store']);




Route::middleware('auth:sanctum')->group(function () {

Route::post('/leads/site-identification', [LeadController::class, 'storeSiteIdentification']);
Route::get('/leads/view', [LeadController::class, 'getLeadsByUser']);
Route::post('/leads/new', [LeadController::class, 'storeOrConvertToNewLead']);
Route::post('/leads/{id}/followup', [LeadController::class, 'addFollowUp']);

Route::post('/leads/schedule', [LeadController::class, 'storeSchedule']);
Route::get('/leads/schedule-list', [LeadController::class, 'getScheduleList']);
Route::post('/leads/check-in', [LeadController::class, 'leadCheckIn']);
Route::post('/leads/check-out', [LeadController::class, 'leadCheckOut']);
Route::get('/leads/details', [LeadController::class, 'getLeadDetails']);


Route::post('/leads/unplanned-schedule', [LeadController::class, 'storeUnplannedSchedule']);
Route::get('/leads/unplanned-schedule-list', [LeadController::class, 'getUnplannedScheduleList']);
Route::post('/leads/unplanned-check-in', [LeadController::class, 'unplannedCheckIn']);

// bdm leads schedule routes
Route::post('/leads/bdm/schedule', [BdmController::class, 'storeBdmSchedule']);
Route::get('/leads/bdm/schedule-list', [BdmController::class, 'getBdmScheduleList']);
Route::post('/leads/bdm/check-in', [BdmController::class, 'leadBdmCheckIn']);
Route::post('/leads/bdm/check-out', [BdmController::class, 'leadBdmCheckOut']);

Route::post('/leads/bdm/unplanned-schedule', [BdmController::class, 'storeUnplannedBdmSchedule']);
Route::get('/leads/bdm/unplanned-schedule-list', [BdmController::class, 'getUnplannedBdmScheduleList']);
Route::post('/leads/bdm/unplanned-check-in', [BdmController::class, 'unplannedBdmCheckIn']);
Route::post('/leads/bdm/unplanned-check-out', [BdmController::class, 'unplannedBdmCheckOut']);

Route::post('/bdm/assignedbdolist', [BdmController::class, 'getAssignedBdoList']);
Route::post('/bdm/lead-list', [BdmController::class, 'getBdmTeamLeadList']);
Route::post('bdm/lead-details', [BdmController::class, 'getLeadDetailsForBdm']);
Route::post('bdm/team-details', [BdmController::class, 'getBdmTeamReport']);
Route::post('bdm/km-coverage', [BdmController::class, 'getKmCoverage']);
Route::post('bdm/dashboard', [BdmController::class, 'getBdmDashboard']);
Route::post('bdm/daily-report', [BdmController::class, 'getBdmDailyReport']);






// Route for Follow-up Meeting (Stage 3)
Route::post('/leads/followup-meeting', [LeadController::class, 'storeFollowupMeeting']);

Route::post('/leads/measurements', [LeadController::class, 'storeMeasurements']);

// Route to view measurements for a specific lead
Route::get('/leads/measurements/view', [LeadController::class, 'getMeasurementsByLead']);

Route::post('/leads/send-to-fabricator', [LeadController::class, 'sendToFabricator']);

Route::get('/fabricator/assignments', [LeadController::class, 'getFabricatorAssignments']);
// Route to get all measurements for a specific lead ID

Route::post('/fabricator/upload-quote', [LeadController::class, 'uploadFabricationDetails']);

Route::post('/leads/final-status', [LeadController::class, 'updateLeadFinalStatus']);

Route::post('/leads/handover', [LeadController::class, 'completeSiteHandover']);

Route::post('/expenses', [App\Http\Controllers\Api\ExpenseController::class, 'store']);
Route::get('/expenses', [App\Http\Controllers\Api\ExpenseController::class, 'getExpenses']);

Route::get('/expenses-type', [App\Http\Controllers\Api\ExpenseController::class, 'mobileIndex']);


Route::prefix('users')->group(function () {




        Route::post('punch-in', [AttendanceController::class, 'punchIn']);
        Route::post('punch-out', [AttendanceController::class, 'punchOut']);
        Route::get('consolidated-report', [ReportController::class, 'getConsolidatedReport']);
        Route::post('fabricator/create', [FabricatorController::class, 'store']);

    Route::post('/fabricator/list', [FabricatorController::class, 'getFabricatorList']);
    Route::post('/fabricator/details', [FabricatorController::class, 'getFabricatorDetails']);
    Route::post('/tourplan', [BdmController::class, 'getTourPlanCalendar']);
    Route::post('view/tourplan', [BdmController::class, 'viewTourPlan']);
    Route::post('/joint-work-requests', [BdmController::class, 'getJointWorkRequests']);
    Route::post('/update-joint-work-status', [BdmController::class, 'updateJointWorkStatus']);
    Route::post('/profile', [BdmController::class, 'getBdmProfile']);
    Route::post('/brands', [BrandController::class, 'index']);

    Route::post('bdo/tour-plan-calendar', [LeadController::class, 'getBdoTourPlanCalendar']);
    Route::post('bdo/view-tour-plan', [LeadController::class, 'viewBdoTourPlan']);


    
    Route::get(
        'fabricator/profile/{id}',
        [FabricatorProfileController::class, 'profile']
    );
});




Route::get('/accounts', [App\Http\Controllers\Api\AccountController::class, 'index']);
Route::get('/accounts/details', [App\Http\Controllers\Api\AccountController::class, 'getaccountDetails']);
Route::post('/accounts', [App\Http\Controllers\Api\AccountController::class, 'store']);
Route::get('/account-types', [App\Http\Controllers\Api\AccountController::class, 'getTypes']);
Route::get('/locations', [App\Http\Controllers\Api\AccountController::class, 'getLocations']);


Route::prefix('fabricator-projections')->group(function () {
    Route::get('/', [FabricatorProjectionController::class, 'index']);
    Route::post('/', [FabricatorProjectionController::class, 'store']);
});





Route::post('/bdm-calls/store', [BdmCallController::class, 'store']);
Route::get('/bdm-calls/list', [BdmCallController::class, 'list']); // Pass ?user_id=12

Route::post('fabricator-stock-management/store', [FabricatorStockManagementController::class, 'store']);
Route::get('fabricator-stock-management/list', [FabricatorStockManagementController::class, 'list']);

Route::prefix('locations')->group(function () {
    // 1. Get States (Can handle ?zone_id=X inside the controller)
    Route::get('/states', [LocationController::class, 'getStates']);

    // 2. Get Districts (Pass ?state_id=X)
    Route::get('/districts', [LocationController::class, 'getDistricts']);

    // 3. Get Cities (Pass ?district_id=X)
    Route::get('/cities', [LocationController::class, 'getCities']);

    // 4. Get Areas (Pass ?city_id=X)
    Route::get('/areas', [LocationController::class, 'getAreas']);

    // 5. Get Pincodes (Pass ?area_id=X)
    Route::get('/pincodes', [LocationController::class, 'getPincodes']);
});
Route::get('/zones/list', [LocationController::class, 'getZoneList']);


Route::apiResource('invoice-responses', InvoiceResponseController::class);

Route::get('categories', [ProductController::class, 'getCategories']);
Route::post('sub-categories', [ProductController::class, 'getSubCategories']); // POST allows body params
Route::post('products', [ProductController::class, 'getProducts']);

});