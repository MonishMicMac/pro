<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalMarketing\DigitalMarketingLeadController;
use App\Http\Controllers\Master\ProductController;

// Public Routes
Route::get('login', [LoginController::class, 'index'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::get('/ui-components', function () {
        return view('ui-components');
    });

    // Masters Group
    Route::group(['prefix' => 'masters', 'as' => 'masters.'], function () {

        Route::get('products/export', [ProductController::class, 'export'])->name('products.export');

        // --- Product Master (Order: Static before Resource) ---
        Route::get('products/import', [ProductController::class, 'importView'])->name('products.importView');
        Route::post('products/upload-preview', [ProductController::class, 'uploadPreview'])->name('products.uploadPreview');
        Route::post('products/import', [ProductController::class, 'importProducts'])->name('products.import');
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulkDelete');
        Route::resource('products', ProductController::class);

        // --- Other Masters ---
        Route::post('states/bulk-delete', [App\Http\Controllers\Master\StateController::class, 'bulkDelete'])->name('states.bulkDelete');
        Route::resource('states', App\Http\Controllers\Master\StateController::class);

        Route::post('product-category/bulk-delete', [App\Http\Controllers\Master\ProductCategoryController::class, 'bulkDelete'])->name('product-category.bulkDelete');
        Route::resource('product-category', App\Http\Controllers\Master\ProductCategoryController::class);

        Route::post('branches/bulk-delete', [App\Http\Controllers\Master\BranchController::class, 'bulkDelete'])->name('branches.bulkDelete');
        Route::resource('branches', App\Http\Controllers\Master\BranchController::class);

        Route::post('brands/bulk-delete', [App\Http\Controllers\Master\BrandController::class, 'bulkDelete'])->name('brands.bulkDelete');
        Route::resource('brands', App\Http\Controllers\Master\BrandController::class);

        Route::post('lead-types/bulk-delete', [App\Http\Controllers\Master\LeadTypeController::class, 'bulkDelete'])->name('lead-types.bulkDelete');
        Route::resource('lead-types', App\Http\Controllers\Master\LeadTypeController::class);

        Route::post('expense-types/bulk-delete', [App\Http\Controllers\Master\ExpenseTypeController::class, 'bulkDelete'])->name('expense-types.bulkDelete');
        Route::resource('expense-types', App\Http\Controllers\Master\ExpenseTypeController::class);

        Route::post('competitors/bulk-delete', [App\Http\Controllers\Master\CompetitorController::class, 'bulkDelete'])->name('competitors.bulkDelete');
        Route::resource('competitors', App\Http\Controllers\Master\CompetitorController::class);

        Route::post('accounts/bulk-delete', [App\Http\Controllers\Master\AccountController::class, 'bulkDelete'])->name('accounts.bulkDelete');
        Route::resource('accounts', App\Http\Controllers\Master\AccountController::class);

        Route::post('sales-stages/bulk-delete', [App\Http\Controllers\Master\SalesStageController::class, 'bulkDelete'])->name('sales-stages.bulkDelete');
        Route::resource('sales-stages', App\Http\Controllers\Master\SalesStageController::class);

        Route::post('travel-types/bulk-delete', [App\Http\Controllers\Master\TravelTypeController::class, 'bulkDelete'])->name('travel-types.bulkDelete');
        Route::resource('travel-types', App\Http\Controllers\Master\TravelTypeController::class);

        Route::post('property-types/bulk-delete', [App\Http\Controllers\Master\PropertyTypeController::class, 'bulkDelete'])->name('property-types.bulkDelete');
        Route::resource('property-types', App\Http\Controllers\Master\PropertyTypeController::class);

        Route::post('categories/bulk-delete', [App\Http\Controllers\Master\CategoryController::class, 'bulkDelete'])->name('categories.bulkDelete');
        Route::resource('categories', App\Http\Controllers\Master\CategoryController::class);

        Route::post('sub-categories/bulk-delete', [App\Http\Controllers\Master\SubCategoryController::class, 'bulkDelete'])->name('sub-categories.bulkDelete');
        Route::resource('sub-categories', App\Http\Controllers\Master\SubCategoryController::class);

        Route::post('item-types/bulk-delete', [App\Http\Controllers\Master\ItemTypeController::class, 'bulkDelete'])->name('item-types.bulkDelete');
        Route::resource('item-types', App\Http\Controllers\Master\ItemTypeController::class);

        // Existing bulk delete (this works because you manually named it)
        Route::post('account-types/bulk-delete', [App\Http\Controllers\Master\AccountTypeController::class, 'bulkDelete'])
            ->name('account_types.bulkDelete');

        // FIX: Add ->names('account_types') to the resource
        Route::resource('account-types', App\Http\Controllers\Master\AccountTypeController::class)
            ->names('account_types');


        // Travel Allowance Master
        Route::post('travel-allowance/bulk-delete', [App\Http\Controllers\Master\TravelAllowanceMasterController::class, 'bulkDelete'])->name('travel-allowance.bulkDelete');
        Route::resource('travel-allowance', App\Http\Controllers\Master\TravelAllowanceMasterController::class);

        // Station Allowance Master
        Route::post('station-allowance/bulk-delete', [App\Http\Controllers\Master\StationAllowanceMasterController::class, 'bulkDelete'])->name('station-allowance.bulkDelete');
        Route::resource('station-allowance', App\Http\Controllers\Master\StationAllowanceMasterController::class);


        Route::post('fabricators/bulk-delete', [App\Http\Controllers\Master\FabricatorController::class, 'bulkDelete'])->name('fabricators.bulkDelete');
        Route::get('fabricators-status/{id}', [App\Http\Controllers\Master\FabricatorController::class, 'status'])->name('fabricators.status');
        Route::resource('fabricators', App\Http\Controllers\Master\FabricatorController::class);
        Route::post(
            'fabricators/bulk-status',
            [App\Http\Controllers\Master\FabricatorController::class, 'bulkStatus']
        )
            ->name('fabricators.bulkStatus');
        // web.php
        Route::get(
            'fabricators/{id}/show',
            [App\Http\Controllers\Master\FabricatorController::class, 'show']
        )->name('fabricators.show');


        Route::post('zones/bulk-delete', [App\Http\Controllers\Master\ZoneController::class, 'bulkDelete'])->name('zones.bulkDelete');
        Route::get('zones/data', [App\Http\Controllers\Master\ZoneController::class, 'getData'])->name('zones.data');
        Route::resource('zones', App\Http\Controllers\Master\ZoneController::class);

        Route::prefix('user-mappings')->name('user-mappings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Master\UserMappingController::class, 'index'])->name('index');
            Route::get('data', [App\Http\Controllers\Master\UserMappingController::class, 'getData'])->name('data');
            Route::post('store', [App\Http\Controllers\Master\UserMappingController::class, 'store'])->name('store');
            Route::post('update-field', [App\Http\Controllers\Master\UserMappingController::class, 'updateField'])->name('updateField');
            Route::post('clear-field', [App\Http\Controllers\Master\UserMappingController::class, 'clearField'])->name('clearField');
            Route::post('bulk-delete', [App\Http\Controllers\Master\UserMappingController::class, 'bulkDelete'])->name('bulkDelete');
            Route::get('{id}/edit', [App\Http\Controllers\Master\UserMappingController::class, 'edit'])->name('edit');
            Route::delete('{id}', [App\Http\Controllers\Master\UserMappingController::class, 'destroy'])->name('destroy');
        });
    });

    // Dependent Dropdowns
    Route::get('get-districts/{state_id}', [App\Http\Controllers\Master\FabricatorController::class, 'getDistricts'])->name('dropdown.districts');
    Route::get('get-cities/{district_id}', [App\Http\Controllers\Master\FabricatorController::class, 'getCities'])->name('dropdown.cities');
    Route::get('get-areas/{city_id}', [App\Http\Controllers\Master\FabricatorController::class, 'getAreas'])->name('dropdown.areas');
    Route::get('get-pincodes/{area_id}', [App\Http\Controllers\Master\FabricatorController::class, 'getPincodes'])->name('dropdown.pincodes');

    // Role & User Management
    Route::get('/roles/data', [RoleController::class, 'getData'])->name('roles.data');
    Route::post('roles/bulk-delete', [RoleController::class, 'bulkDelete'])->name('roles.bulkDelete');
    Route::resource('roles', RoleController::class);

    Route::get('/users/data', [UserController::class, 'getData'])->name('users.data');
    Route::get('users-geodata', [UserController::class, 'getGeoData'])->name('users.geodata');
    Route::post('users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
    Route::resource('users', UserController::class);

    Route::get('userroles', [UserRoleController::class, 'index'])->name('userroles.index');
    Route::post('userroles/update', [UserRoleController::class, 'updatePermissions'])->name('userroles.update');

    // Marketing
    Route::group(['prefix' => 'marketing', 'as' => 'marketing.'], function () {
        Route::post('leads/bulk-delete', [DigitalMarketingLeadController::class, 'bulkDelete'])->name('leads.bulkDelete');
        Route::resource('leads', DigitalMarketingLeadController::class);

        Route::get('regional-footprint', [App\Http\Controllers\Marketing\RegionalFootprintController::class, 'index'])->name('regional.footprint');
        Route::get('regional-footprint-data', [App\Http\Controllers\Marketing\RegionalFootprintController::class, 'getData'])->name('regional.footprint.data');
    });



    Route::get('marketing/leads/{id}/history', [DigitalMarketingLeadController::class, 'history'])->name('marketing.leads.history');

    Route::get('field-activity', [App\Http\Controllers\LeadController::class, 'fieldActivity'])->name('leads.field-activity');

    // Leads & Visits
    Route::get('site-visits', [App\Http\Controllers\SiteVisitController::class, 'index'])->name('site-visits.index');
    Route::get('site-visits/data', [App\Http\Controllers\SiteVisitController::class, 'data'])->name('site-visits.data');
    Route::get('site-visit-report', [App\Http\Controllers\SiteVisitController::class, 'report'])->name('site-visits.report');
    Route::get('site-visit-report/data', [App\Http\Controllers\SiteVisitController::class, 'reportData'])->name('site-visits.report.data');


    Route::get('leads/data', [App\Http\Controllers\LeadController::class, 'data'])->name('leads.data');
    Route::get('get-location-data', [App\Http\Controllers\LeadController::class, 'getLocationData'])->name('get.location.data');
    Route::resource('leads', App\Http\Controllers\LeadController::class);


    Route::get('expenses/data', [App\Http\Controllers\ExpenseController::class, 'data'])->name('expenses.data');
    Route::resource('expenses', App\Http\Controllers\ExpenseController::class)->only(['index']);


    Route::get('attendance', [App\Http\Controllers\AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('attendance/data', [App\Http\Controllers\AttendanceController::class, 'data'])->name('attendance.data');
});
