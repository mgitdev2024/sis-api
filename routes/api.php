<?php
use App\Http\Controllers\Auth\CredentialController;
use App\Http\Controllers\UserProfile\PersonalInformationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::get('/run-migrations-and-seed', function () {
    // Artisan::call('migrate', ["--force" => true]);
    Artisan::call('migrate:fresh', ["--force" => true]);
    Artisan::call('db:seed', ["--force" => true]);
    return 'Migrations and Seed completed successfully!';
});
Route::post('login', [CredentialController::class, 'onLogin']);

Route::get('signed-url/check/{token}', [App\Http\Controllers\Auth\SignedUrlController::class, 'onCheckSignedURL']);
Route::post('signed-url/create', [App\Http\Controllers\Auth\SignedUrlController::class, 'onSendLoginURL']);
Route::post('password/reset', [App\Http\Controllers\Auth\CredentialController::class, 'onResetPassword']);

Route::post('otp/send', [App\Http\Controllers\Auth\SignedUrlController::class, 'onSendOtp']);
Route::post('otp/validate', [App\Http\Controllers\Auth\SignedUrlController::class, 'onValidateOtp']);

Route::get('token/check', [App\Http\Controllers\Auth\SignedUrlController::class, 'onCheckToken']);

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::post('bulk/user', [App\Http\Controllers\Bulk\BulkController::class, 'onBulkUploadEmployee']);
    // Logout
    Route::get('logout', [CredentialController::class, 'onLogout']);
    Route::get('user/{personal_id}', [App\Http\Controllers\User\UserController::class, 'onGetDataById']);
    Route::post('user/list', [App\Http\Controllers\User\UserController::class, 'onGetPaginatedList']);

    #region Announcement & Feeds
    Route::post('announcement/test', [App\Http\Controllers\Portal\AnnouncementController::class, 'onTest']);
    Route::post('announcement/create', [App\Http\Controllers\Portal\AnnouncementController::class, 'onCreate']);
    Route::post('announcement/update/{id}', [App\Http\Controllers\Portal\AnnouncementController::class, 'onUpdateById']);
    Route::post('announcement/get', [App\Http\Controllers\Portal\AnnouncementController::class, 'onGetPaginatedList']);
    Route::get('announcement/get/{id}', [App\Http\Controllers\Portal\AnnouncementController::class, 'onGetById']);
    Route::delete('announcement/delete/{id}', [App\Http\Controllers\Portal\AnnouncementController::class, 'onDeleteById']);
    #endregion

    #region Memoranda
    Route::post('memoranda/create', [App\Http\Controllers\Portal\MemorandaController::class, 'onCreate']);
    Route::post('memoranda/update/{id}', [App\Http\Controllers\Portal\MemorandaController::class, 'onUpdateById']);
    Route::post('memoranda/get', [App\Http\Controllers\Portal\MemorandaController::class, 'onGetPaginatedList']);
    Route::get('memoranda/get/{id}', [App\Http\Controllers\Portal\MemorandaController::class, 'onGetById']);
    Route::delete('memoranda/delete/{id}', [App\Http\Controllers\Portal\MemorandaController::class, 'onDeleteById']);
    #endregion

    #region Holiday
    Route::post('holiday/create', [App\Http\Controllers\Portal\HolidayController::class, 'onCreate']);
    Route::post('holiday/update/{id}', [App\Http\Controllers\Portal\HolidayController::class, 'onUpdateById']);
    Route::post('holiday/get', [App\Http\Controllers\Portal\HolidayController::class, 'onGetPaginatedList']);
    Route::get('holiday/get/{id}', [App\Http\Controllers\Portal\HolidayController::class, 'onGetById']);
    Route::delete('holiday/delete/{id}', [App\Http\Controllers\Portal\HolidayController::class, 'onDeleteById']);
    #endregion

    #region Event
    Route::post('event/create', [App\Http\Controllers\Portal\EventController::class, 'onCreate']);
    Route::post('event/update/{id}', [App\Http\Controllers\Portal\EventController::class, 'onUpdateById']);
    Route::post('event/get', [App\Http\Controllers\Portal\EventController::class, 'onGetPaginatedList']);
    Route::get('event/get/{id}', [App\Http\Controllers\Portal\EventController::class, 'onGetById']);
    Route::delete('event/delete/{id}', [App\Http\Controllers\Portal\EventController::class, 'onDeleteById']);
    #endregion

    #region Area
    Route::post('org/area/create', [App\Http\Controllers\OrganizationalStructure\AreaController::class, 'onCreate']);
    Route::post('org/area/update/{id}', [App\Http\Controllers\OrganizationalStructure\AreaController::class, 'onUpdateById']);
    Route::post('org/area/get', [App\Http\Controllers\OrganizationalStructure\AreaController::class, 'onGetPaginatedList']);
    Route::get('org/area/get/all', [App\Http\Controllers\OrganizationalStructure\AreaController::class, 'onGetAll']);
    Route::get('org/area/get/{id}', [App\Http\Controllers\OrganizationalStructure\AreaController::class, 'onGetById']);
    Route::delete('org/area/delete/{id}', [App\Http\Controllers\OrganizationalStructure\AreaController::class, 'onDeleteById']);
    #endregion

    #region Branch
    Route::post('org/branch/create', [App\Http\Controllers\OrganizationalStructure\BranchController::class, 'onCreate']);
    Route::post('org/branch/update/{id}', [App\Http\Controllers\OrganizationalStructure\BranchController::class, 'onUpdateById']);
    Route::post('org/branch/get', [App\Http\Controllers\OrganizationalStructure\BranchController::class, 'onGetPaginatedList']);
    Route::get('org/branch/get/all', [App\Http\Controllers\OrganizationalStructure\BranchController::class, 'onGetAll']);
    Route::get('org/branch/get/{id}', [App\Http\Controllers\OrganizationalStructure\BranchController::class, 'onGetById']);
    Route::delete('org/branch/delete/{id}', [App\Http\Controllers\OrganizationalStructure\BranchController::class, 'onDeleteById']);
    #endregion

    #region Company
    Route::post('org/company/create', [App\Http\Controllers\OrganizationalStructure\CompanyController::class, 'onCreate']);
    Route::post('org/company/update/{id}', [App\Http\Controllers\OrganizationalStructure\CompanyController::class, 'onUpdateById']);
    Route::post('org/company/get', [App\Http\Controllers\OrganizationalStructure\CompanyController::class, 'onGetPaginatedList']);
    Route::get('org/company/get/all', [App\Http\Controllers\OrganizationalStructure\CompanyController::class, 'onGetAll']);
    Route::get('org/company/get/{id}', [App\Http\Controllers\OrganizationalStructure\CompanyController::class, 'onGetById']);
    Route::delete('org/company/delete/{id}', [App\Http\Controllers\OrganizationalStructure\CompanyController::class, 'onDeleteById']);
    #endregion

    #region Workforce Division
    Route::post('org/workforce/create', [App\Http\Controllers\OrganizationalStructure\WorkforceDivisionController::class, 'onCreate']);
    Route::post('org/workforce/update/{id}', [App\Http\Controllers\OrganizationalStructure\WorkforceDivisionController::class, 'onUpdateById']);
    Route::post('org/workforce/get', [App\Http\Controllers\OrganizationalStructure\WorkforceDivisionController::class, 'onGetPaginatedList']);
    Route::get('org/workforce/get/all', [App\Http\Controllers\OrganizationalStructure\WorkforceDivisionController::class, 'onGetAll']);
    Route::get('org/workforce/get/{id}', [App\Http\Controllers\OrganizationalStructure\WorkforceDivisionController::class, 'onGetById']);
    Route::delete('org/workforce/delete/{id}', [App\Http\Controllers\OrganizationalStructure\WorkforceDivisionController::class, 'onDeleteById']);
    #endregion

    #region Division
    Route::post('org/division/bulk', [App\Http\Controllers\OrganizationalStructure\DivisionController::class, 'onBulkUpload']);
    Route::post('org/division/create', [App\Http\Controllers\OrganizationalStructure\DivisionController::class, 'onCreate']);
    Route::post('org/division/update/{id}', [App\Http\Controllers\OrganizationalStructure\DivisionController::class, 'onUpdateById']);
    Route::put('org/division/status/{id}', [App\Http\Controllers\OrganizationalStructure\DivisionController::class, 'onChangeStatus']);
    Route::post('org/division/get', [App\Http\Controllers\OrganizationalStructure\DivisionController::class, 'onGetPaginatedList']);
    Route::get('org/division/get/all', [App\Http\Controllers\OrganizationalStructure\DivisionController::class, 'onGetAll']);
    Route::get('org/division/get/{id}', [App\Http\Controllers\OrganizationalStructure\DivisionController::class, 'onGetById']);
    Route::delete('org/division/delete/{id}', [App\Http\Controllers\OrganizationalStructure\DivisionController::class, 'onDeleteById']);
    #endregion

    #region Department
    Route::post('org/department/bulk', [App\Http\Controllers\OrganizationalStructure\DepartmentController::class, 'onBulkUpload']);
    Route::post('org/department/create', [App\Http\Controllers\OrganizationalStructure\DepartmentController::class, 'onCreate']);
    Route::post('org/department/update/{id}', [App\Http\Controllers\OrganizationalStructure\DepartmentController::class, 'onUpdateById']);
    Route::put('org/department/status/{id}', [App\Http\Controllers\OrganizationalStructure\DepartmentController::class, 'onChangeStatus']);
    Route::post('org/department/get', [App\Http\Controllers\OrganizationalStructure\DepartmentController::class, 'onGetPaginatedList']);
    Route::get('org/department/get/all', [App\Http\Controllers\OrganizationalStructure\DepartmentController::class, 'onGetAll']);
    Route::get('org/department/get/{id}', [App\Http\Controllers\OrganizationalStructure\DepartmentController::class, 'onGetById']);
    Route::delete('org/department/delete/{id}', [App\Http\Controllers\OrganizationalStructure\DepartmentController::class, 'onDeleteById']);
    #endregion

    #region Section
    Route::post('org/section/bulk', [App\Http\Controllers\OrganizationalStructure\SectionController::class, 'onBulkUpload']);
    Route::post('org/section/create', [App\Http\Controllers\OrganizationalStructure\SectionController::class, 'onCreate']);
    Route::post('org/section/update/{id}', [App\Http\Controllers\OrganizationalStructure\SectionController::class, 'onUpdateById']);
    Route::put('org/section/status/{id}', [App\Http\Controllers\OrganizationalStructure\SectionController::class, 'onChangeStatus']);
    Route::post('org/section/get', [App\Http\Controllers\OrganizationalStructure\SectionController::class, 'onGetPaginatedList']);
    Route::get('org/section/get/all', [App\Http\Controllers\OrganizationalStructure\SectionController::class, 'onGetAll']);
    Route::get('org/section/get/{id}', [App\Http\Controllers\OrganizationalStructure\SectionController::class, 'onGetById']);
    Route::delete('org/section/delete/{id}', [App\Http\Controllers\OrganizationalStructure\SectionController::class, 'onDeleteById']);
    #endregion

    #region Job Title
    Route::post('org/job/bulk', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onBulkUpload']);
    Route::post('org/job/create', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onCreate']);
    Route::post('org/job/update/{id}', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onUpdateById']);
    Route::put('org/job/status/{id}', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onChangeStatus']);
    Route::post('org/job/get', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onGetPaginatedList']);
    Route::get('org/job/get/all', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onGetAll']);
    Route::get('org/job/get/all/vacant', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onGetVacantSlot']);
    Route::get('org/job/get/{id}', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onGetById']);
    Route::delete('org/job/delete/{id}', [App\Http\Controllers\OrganizationalStructure\JobTitleController::class, 'onDeleteById']);
    #endregion

    #region structure Title
    Route::post('org/structure/create', [App\Http\Controllers\OrganizationalStructure\OrganizationalStructureController::class, 'onCreate']);
    Route::post('org/structure/update/{id}', [App\Http\Controllers\OrganizationalStructure\OrganizationalStructureController::class, 'onUpdateById']);
    Route::put('org/structure/status/{id}', [App\Http\Controllers\OrganizationalStructure\OrganizationalStructureController::class, 'onChangeStatus']);
    Route::get('org/structure/all', [App\Http\Controllers\OrganizationalStructure\OrganizationalStructureController::class, 'onGetAll']);
    Route::get('org/structure/get/{id}', [App\Http\Controllers\OrganizationalStructure\OrganizationalStructureController::class, 'onGetById']);
    Route::delete('org/structure/delete/{id}', [App\Http\Controllers\OrganizationalStructure\OrganizationalStructureController::class, 'onDeleteById']);

    #endregion

    #region Employment Information
    Route::post('employment-information/create', [App\Http\Controllers\User\EmploymentInformationController::class, 'onCreate']);
    Route::get('employment-information/{personal_information_id}', [App\Http\Controllers\User\EmploymentInformationController::class, 'onGetDataById']);
    Route::post('employment-information/upload_id', [App\Http\Controllers\User\EmploymentInformationController::class, 'onUploadID']);
    Route::post('employment-information/update/{personal_information_id}', [App\Http\Controllers\User\EmploymentInformationController::class, 'onUpdate']);
    #endregion

    #region Store Management
    Route::post('store/create', [App\Http\Controllers\Settings\StoreManagemenController::class, 'onCreate']);
    Route::post('store/update/{id}', [App\Http\Controllers\Settings\StoreManagemenController::class, 'onUpdateById']);
    Route::post('store/get', [App\Http\Controllers\Settings\StoreManagemenController::class, 'onGetPaginatedList']);
    Route::get('store/get/{id}', [App\Http\Controllers\Settings\StoreManagemenController::class, 'onGetById']);
    Route::delete('store/delete/{id}', [App\Http\Controllers\Settings\StoreManagemenController::class, 'onDeleteById']);
    #endregion

    #region Internal System
    Route::post('configuration/system/create', [App\Http\Controllers\SystemConfiguration\InternalSystemController::class, 'onCreate']);
    Route::post('configuration/system/update/{id}', [App\Http\Controllers\SystemConfiguration\InternalSystemController::class, 'onUpdateById']);
    Route::put('configuration/system/status/{id}', [App\Http\Controllers\SystemConfiguration\InternalSystemController::class, 'onChangeStatus']);
    Route::get('configuration/system/all', [App\Http\Controllers\SystemConfiguration\InternalSystemController::class, 'onGetAll']);
    Route::get('configuration/system/get/{id}', [App\Http\Controllers\SystemConfiguration\InternalSystemController::class, 'onGetById']);
    Route::delete('configuration/system/delete/{id}', [App\Http\Controllers\SystemConfiguration\InternalSystemController::class, 'onDeleteById']);
    #endregion

    #region Permission
    Route::post('configuration/permission/create', [App\Http\Controllers\SystemConfiguration\ModulePermissionController::class, 'onCreate']);
    Route::post('configuration/permission/update/{id}', [App\Http\Controllers\SystemConfiguration\ModulePermissionController::class, 'onUpdateById']);
    Route::put('configuration/permission/status/{id}', [App\Http\Controllers\SystemConfiguration\ModulePermissionController::class, 'onChangeStatus']);
    Route::get('configuration/permission/all', [App\Http\Controllers\SystemConfiguration\ModulePermissionController::class, 'onGetAll']);
    Route::get('configuration/permission/toggle/{id}', [App\Http\Controllers\SystemConfiguration\ModulePermissionController::class, 'onGetByToggledPermission']);
    Route::get('configuration/permission/get/{id}', [App\Http\Controllers\SystemConfiguration\ModulePermissionController::class, 'onGetById']);
    Route::delete('configuration/permission/delete/{id}', [App\Http\Controllers\SystemConfiguration\ModulePermissionController::class, 'onDeleteById']);
    #endregion

    #region Function
    Route::post('configuration/function/create', [App\Http\Controllers\SystemConfiguration\ModuleFunctionController::class, 'onCreate']);
    Route::post('configuration/function/update/{id}', [App\Http\Controllers\SystemConfiguration\ModuleFunctionController::class, 'onUpdateById']);
    Route::put('configuration/function/status/{id}', [App\Http\Controllers\SystemConfiguration\ModuleFunctionController::class, 'onChangeStatus']);
    Route::get('configuration/function/all', [App\Http\Controllers\SystemConfiguration\ModuleFunctionController::class, 'onGetAll']);
    Route::get('configuration/function/distinct', [App\Http\Controllers\SystemConfiguration\ModuleFunctionController::class, 'onGetDistinct']);
    Route::get('configuration/function/get/{id}', [App\Http\Controllers\SystemConfiguration\ModuleFunctionController::class, 'onGetById']);
    Route::delete('configuration/function/delete/{id}', [App\Http\Controllers\SystemConfiguration\ModuleFunctionController::class, 'onDeleteById']);
    #endregion

    #region Access Management
    Route::post('configuration/access/create', [App\Http\Controllers\SystemConfiguration\AccessManagementController::class, 'onCreate']);
    Route::post('configuration/access/update/{id}', [App\Http\Controllers\SystemConfiguration\AccessManagementController::class, 'onUpdateById']);
    Route::put('configuration/access/status/{id}', [App\Http\Controllers\SystemConfiguration\AccessManagementController::class, 'onChangeStatus']);
    Route::get('configuration/access/all', [App\Http\Controllers\SystemConfiguration\AccessManagementController::class, 'onGetAll']);
    Route::get('configuration/access/get/{id}', [App\Http\Controllers\SystemConfiguration\AccessManagementController::class, 'onGetById']);
    Route::get('configuration/access/settings', [App\Http\Controllers\SystemConfiguration\AccessManagementController::class, 'onGetAllConfiguration']);
    Route::delete('configuration/access/delete/{id}', [App\Http\Controllers\SystemConfiguration\AccessManagementController::class, 'onDeleteById']);
    #endregion

    #region Modules
    Route::post('configuration/module/create', [App\Http\Controllers\SystemConfiguration\ModuleController::class, 'onCreate']);
    Route::post('configuration/module/update/{id}', [App\Http\Controllers\SystemConfiguration\ModuleController::class, 'onUpdateById']);
    Route::put('configuration/module/status/{id}', [App\Http\Controllers\SystemConfiguration\ModuleController::class, 'onChangeStatus']);
    Route::get('configuration/module/all', [App\Http\Controllers\SystemConfiguration\ModuleController::class, 'onGetAll']);
    Route::get('configuration/module/get/{id}', [App\Http\Controllers\SystemConfiguration\ModuleController::class, 'onGetById']);
    Route::delete('configuration/module/delete/{id}', [App\Http\Controllers\SystemConfiguration\ModuleController::class, 'onDeleteById']);
    #endregion

    #region Sub Modules
    Route::post('configuration/sub-module/create', [App\Http\Controllers\SystemConfiguration\SubModuleController::class, 'onCreate']);
    Route::post('configuration/sub-module/update/{id}', [App\Http\Controllers\SystemConfiguration\SubModuleController::class, 'onUpdateById']);
    Route::put('configuration/sub-module/status/{id}', [App\Http\Controllers\SystemConfiguration\SubModuleController::class, 'onChangeStatus']);
    Route::get('configuration/sub-module/all', [App\Http\Controllers\SystemConfiguration\SubModuleController::class, 'onGetAll']);
    Route::get('configuration/sub-module/get/{id}', [App\Http\Controllers\SystemConfiguration\SubModuleController::class, 'onGetById']);
    Route::delete('configuration/sub-module/delete/{id}', [App\Http\Controllers\SystemConfiguration\SubModuleController::class, 'onDeleteById']);
    #endregion

    #region Approval Level
    Route::post('approval/level/create', [App\Http\Controllers\Approvals\ApprovalLevelController::class, 'onCreate']);
    Route::post('approval/level/update/{id}', [App\Http\Controllers\Approvals\ApprovalLevelController::class, 'onUpdateById']);
    Route::put('approval/level/status/{id}', [App\Http\Controllers\Approvals\ApprovalLevelController::class, 'onChangeStatus']);
    Route::get('approval/level/all', [App\Http\Controllers\Approvals\ApprovalLevelController::class, 'onGetAll']);
    Route::get('approval/level/get/{id}', [App\Http\Controllers\Approvals\ApprovalLevelController::class, 'onGetById']);
    Route::delete('approval/level/delete/{id}', [App\Http\Controllers\Approvals\ApprovalLevelController::class, 'onDeleteById']);
    #endregion

    #region Approval Workflow
    Route::post('approval/workflow/create', [App\Http\Controllers\Approvals\ApprovalWorkflowController::class, 'onCreate']);
    Route::post('approval/workflow/update/{id}', [App\Http\Controllers\Approvals\ApprovalWorkflowController::class, 'onUpdateById']);
    Route::put('approval/workflow/status/{id}', [App\Http\Controllers\Approvals\ApprovalWorkflowController::class, 'onChangeStatus']);
    Route::get('approval/workflow/all', [App\Http\Controllers\Approvals\ApprovalWorkflowController::class, 'onGetAll']);
    Route::get('approval/workflow/get/{id}', [App\Http\Controllers\Approvals\ApprovalWorkflowController::class, 'onGetById']);
    Route::delete('approval/workflow/delete/{id}', [App\Http\Controllers\Approvals\ApprovalWorkflowController::class, 'onDeleteById']);
    #endregion

    #region Approval Configuration
    Route::post('approval/configuration/create', [App\Http\Controllers\Approvals\ApprovalConfigurationController::class, 'onCreate']);
    Route::post('approval/configuration/update/{id}', [App\Http\Controllers\Approvals\ApprovalConfigurationController::class, 'onUpdateById']);
    Route::put('approval/configuration/status/{id}', [App\Http\Controllers\Approvals\ApprovalConfigurationController::class, 'onChangeStatus']);
    Route::get('approval/configuration/all', [App\Http\Controllers\Approvals\ApprovalConfigurationController::class, 'onGetAll']);
    Route::get('approval/configuration/get/{id}', [App\Http\Controllers\Approvals\ApprovalConfigurationController::class, 'onGetById']);
    Route::get('approval/configuration/category/{id}', [App\Http\Controllers\Approvals\ApprovalConfigurationController::class, 'onGetByCategory']);
    Route::delete('approval/configuration/delete/{id}', [App\Http\Controllers\Approvals\ApprovalConfigurationController::class, 'onDeleteById']);
    #endregion

    #region Approval Ticket
    Route::post('approval/ticket/create', [App\Http\Controllers\Approvals\ApprovalTicketController::class, 'onCreate']);
    Route::post('approval/ticket/update/{id}', [App\Http\Controllers\Approvals\ApprovalTicketController::class, 'onUpdateById']);
    Route::put('approval/ticket/status/{id}', [App\Http\Controllers\Approvals\ApprovalTicketController::class, 'onChangeStatus']);
    Route::get('approval/ticket/all', [App\Http\Controllers\Approvals\ApprovalTicketController::class, 'onGetAll']);
    Route::get('approval/ticket/get/{id}', [App\Http\Controllers\Approvals\ApprovalTicketController::class, 'onGetById']);
    Route::delete('approval/ticket/delete/{id}', [App\Http\Controllers\Approvals\ApprovalTicketController::class, 'onDeleteById']);
    #endregion

    #region Approval History
    Route::post('approval/history/action/{id}', [App\Http\Controllers\Approvals\ApprovalHistoryController::class, 'onAction']);
    Route::post('approval/history/update/{id}', [App\Http\Controllers\Approvals\ApprovalHistoryController::class, 'onUpdateById']);
    Route::put('approval/history/status/{id}', [App\Http\Controllers\Approvals\ApprovalHistoryController::class, 'onChangeStatus']);
    Route::get('approval/history/all', [App\Http\Controllers\Approvals\ApprovalHistoryController::class, 'onGetAll']);
    Route::get('approval/history/category/{id}', [App\Http\Controllers\Approvals\ApprovalHistoryController::class, 'onGetByCategory']);
    Route::get('approval/history/get/{id}', [App\Http\Controllers\Approvals\ApprovalHistoryController::class, 'onGetById']);
    Route::delete('approval/history/delete/{id}', [App\Http\Controllers\Approvals\ApprovalHistoryController::class, 'onDeleteById']);
    #endregion

    #region User Access
    Route::post('configuration/user/create', [App\Http\Controllers\SystemConfiguration\UserAccessController::class, 'onCreate']);
    Route::post('configuration/user/update/{id}', [App\Http\Controllers\SystemConfiguration\UserAccessController::class, 'onUpdateById']);
    Route::put('configuration/user/status/{id}', [App\Http\Controllers\SystemConfiguration\UserAccessController::class, 'onChangeStatus']);
    Route::get('configuration/user/all', [App\Http\Controllers\SystemConfiguration\UserAccessController::class, 'onGetAll']);
    Route::get('configuration/user/category/{id}', [App\Http\Controllers\SystemConfiguration\UserAccessController::class, 'onGetByCategory']);
    Route::get('configuration/user/get/{id}', [App\Http\Controllers\SystemConfiguration\UserAccessController::class, 'onGetById']);
    Route::delete('configuration/user/delete/{id}', [App\Http\Controllers\SystemConfiguration\UserAccessController::class, 'onDeleteById']);
    #endregion
});
