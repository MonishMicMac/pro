<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\DigitalMarketingLeadController;
use App\Http\Controllers\Api\Users\LoginController;
use App\Http\Controllers\Api\Users\AttendanceController;
use App\Http\Controllers\Api\Fabricator\LoginController as FabricatorLoginController;
use App\Http\Controllers\Api\Users\FabricatorController;
use App\Http\Controllers\Api\Users\ReportController;



Route::post('/leads/site-identification', [LeadController::class, 'storeSiteIdentification']);
Route::get('/leads/view', [LeadController::class, 'getLeadsByUser']);
Route::post('/leads/new', [LeadController::class, 'storeOrConvertToNewLead']);
Route::post('/leads/{id}/followup', [LeadController::class, 'addFollowUp']);

Route::post('/leads/schedule', [LeadController::class, 'storeSchedule']);
Route::get('/leads/schedule-list', [LeadController::class, 'getScheduleList']);
Route::post('/leads/check-in', [LeadController::class, 'leadCheckIn']);
Route::post('/leads/check-out', [LeadController::class, 'leadCheckOut']);

Route::post('/leads/unplanned-schedule', [LeadController::class, 'storeUnplannedSchedule']);
Route::get('/leads/unplanned-schedule-list', [LeadController::class, 'getUnplannedScheduleList']);
Route::post('/leads/unplanned-check-in', [LeadController::class, 'unplannedCheckIn']);


Route::post('/digital-marketing-leads', [DigitalMarketingLeadController::class, 'store']);

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

Route::prefix('users')->group(function () {
    Route::post('send-otp', [LoginController::class, 'sendOtp']);
    Route::post('verify-otp', [LoginController::class, 'verifyOtp']);

    Route::post('fabricator/create', [FabricatorController::class, 'store']);

    // Protected User Routes (Attendance)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('punch-in', [AttendanceController::class, 'punchIn']);
        Route::post('punch-out', [AttendanceController::class, 'punchOut']);
        Route::get('consolidated-report', [ReportController::class, 'getConsolidatedReport']);
    });
});

Route::prefix('fabricator')->group(function () {
    Route::post('login', [FabricatorLoginController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [FabricatorLoginController::class, 'logout']);
        Route::post('update/{id}', [FabricatorLoginController::class, 'update']);
        Route::get(
            'dashboard',
            [FabricatorController::class, 'dashboard']
        );
    });
});


Route::get('/accounts', [App\Http\Controllers\Api\AccountController::class, 'index']);
Route::post('/accounts', [App\Http\Controllers\Api\AccountController::class, 'store']);
Route::get('/account-types', [App\Http\Controllers\Api\AccountController::class, 'getTypes']);
Route::get('/locations', [App\Http\Controllers\Api\AccountController::class, 'getLocations']);
