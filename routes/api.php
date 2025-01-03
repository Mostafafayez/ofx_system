<?php

use App\Http\Controllers\BonusController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LayoutController;
use App\Http\Controllers\Leads_adminController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\userDashboardController;
use App\Http\Controllers\UserFilterationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\TeamController;
use Spatie\Permission\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\LiabilityController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ManagerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [EmployeeController::class, 'login']);
//Manage-Employee
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/users/{id}', [EmployeeController::class, 'updateUser']);
    Route::get('/users', [EmployeeController::class, 'getallusers']);
    Route::post('/add_employee', [EmployeeController::class, 'register']);
    Route::delete('/user/{id}', [EmployeeController::class, 'deleteUser']);
    Route::post('/user/{id}/password', [EmployeeController::class, 'updatePassword']);
    Route::get('/teamleaders', [EmployeeController::class, 'index']);
    Route::get('/roles', [EmployeeController::class, 'getAllRoles']);
    Route::get('/Permissions', [EmployeeController::class, 'getAllPermissions']);
});




Route::middleware('auth:sanctum')->prefix('managers')->group(function () {
    Route::post('/add', [ManagerController::class, 'addManagerWithTeams']);
    Route::get('/', [ManagerController::class, 'getAllManagersWithTeams']);
    Route::get('/{id}', [ManagerController::class, 'getManagerById']);
    Route::delete('/{id}', [ManagerController::class, 'deleteManager']);
});
Route::get('/contracts', [ManagerController::class, 'getTeamContracts']);
Route::get('/collection', [ManagerController::class, 'getTeamcollection']);

Route::middleware('auth:sanctum')->group(function () {
    Route::delete('/users/{id}/delete', [EmployeeController::class, 'softDeleteUser']);
    Route::patch('/users/{id}/restore', [EmployeeController::class, 'restoreUser']);
    Route::get('/users/birth-month', [EmployeeController::class, 'getUsersByBirthMonth']);
});

//add role"owner"
Route::middleware('auth:sanctum')->group(function () {
    Route::post('add/role', [EmployeeController::class, 'addRole']);
});
//sales //leads
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/leads/create', [LeadsController::class, 'create']); //with check
    Route::get('/leads/{id}/check-followup', [LeadsController::class, 'checkFollowUp']);
    Route::post('/leads/{id}/status', [LeadsController::class, 'updateStatus']);

    Route::get('leads/user', [LeadsController::class, 'getLeadsWithDetails']);
    Route::get('leads/filter_status/{status}', [LeadsController::class, 'filterLeadsByStatus']);
    Route::get('leads/all/filter_status/{status}', [LeadsController::class, 'filterallLeadsByStatus']);
});

Route::middleware('auth:sanctum')->prefix('offers')->group(function () {
    Route::post('/', [LeadsController::class, 'addOffer']);
    Route::get('/{leadid}', [LeadsController::class, 'alloffers']);
});


//followups
Route::middleware('auth:sanctum')->prefix('followups')->group(function () {
    Route::post('/', [LeadsController::class, 'addFollowUp']);
    Route::post('/filter/{leadid}', [LeadsController::class, 'filterfollowupsByStatus']);
    Route::get('/{leadid}', [LeadsController::class, 'allfollowups']);
    Route::get('/all/filter', [LeadsController::class, 'filterallfollowupsByStatus']);
});

//leads for each team
Route::middleware('auth:sanctum')->group(function () {
    // Route::get('/allfollowups/filter', [Leads_adminController::class, 'filterfollowupsByStatus']);
});


///manage_Department
Route::middleware(['auth:sanctum'])->prefix('departments')->group(function () {
    Route::get('/', [DepartmentController::class, 'index']);
    Route::get('/{id}', [DepartmentController::class, 'show']);
    Route::post('/', [DepartmentController::class, 'store']);
    Route::post('/{id}', [DepartmentController::class, 'update']);
    Route::delete('/{id}', [DepartmentController::class, 'destroy']);
});

//manage_services
Route::middleware(['auth:sanctum'])->prefix('services')->group(function () {
    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/all', [ServiceController::class, 'getall']);
    Route::get('/layouts', [ServiceController::class, 'show_layouts']);
    Route::get('/team/{id}', [ServiceController::class, 'show_team']);
    Route::get('/layouts/{id}', [ServiceController::class, 'show_layouts_id']);
    Route::post('/', [ServiceController::class, 'store']);
    Route::post('/{id}', [ServiceController::class, 'update']);
    Route::delete('/{id}', [ServiceController::class, 'destroy']);
});



Route::middleware(['auth:sanctum'])->group(function () {

    // Contract
    Route::prefix('contracts')->group(function () {

        Route::post('/', [ContractController::class, 'createContract']);
        Route::get('/', [ContractController::class, 'getContract']);
        Route::get('/user', [ContractController::class, 'getContracts']);
        Route::get('/{id}', [ContractController::class, 'getContractDetails']);
        Route::post('/{id}', [ContractController::class, 'updateContract']);
        Route::delete('/{id}', [ContractController::class, 'deleteContract']);
        Route::post('/status/{id}', [TaskController::class, 'updateStatus']);
        //team-leader

        // Services and Layouts getTeamcollection
        Route::get('/{contractId}/services', [ContractController::class, 'getServicesByContract']);
        Route::get('/sales', [ContractController::class, 'getAllCollectionsBySales']);


    });
    // Collection
 Route::prefix('collections')->middleware('auth:sanctum')->group(function () {

        Route::post('/', [ContractController::class, 'handleCollections']);
        Route::get('/service/{serviceId}', [ContractController::class, 'getCollectionsByService']);
        Route::get('/user', [ContractController::class, 'getCollectionsByUser']);
        Route::get('/sales/{salesId}', [ContractController::class, 'getCollectionsBySales']);
        Route::get('/sales', [CollectionController::class, 'getAllCollectionsBySales']);
        Route::post('/{collectionId}/approval', [CollectionController::class, 'updateApproval']);
        Route::post('/{collectionId}/status', [CollectionController::class, 'updateStatus']);
        Route::get('/team/{contract_id}', [CollectionController::class, 'getCollectionPercentageByContract']); // Percentage of collection for team
        Route::post('/{id}', [CollectionController::class, 'updateCollectionWithAdjustments']);
    });
});


Route::prefix('myteam')->middleware(['auth:sanctum'])->group(function () {

    Route::get('/contracts', action: [CollectionController::class, 'getTeamContracts']);
    Route::get('/collections/{contract_id}', action: [CollectionController::class, 'getTeamcollections']);
});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('layouts', LayoutController::class);
    Route::get('/contracts/{contractId}/layouts', [TaskController::class, 'getContractLayouts']);
});


//Teams
Route::middleware(['auth:sanctum'])->group(function () {

    Route::middleware('role:owner')->group(function () {
        Route::get('/allteams', [TeamController::class, 'getAllTeams']);
        Route::get('/all', [TeamController::class, 'getAllTeamsWithLeaders']);
        Route::post('team/department', [TeamController::class, 'filterAllTeamsWithdepartment']);
        Route::post('/teams', [TeamController::class, 'store']);
    });

        Route::get('/teamleader/members', [TeamController::class, 'getTeamLeaderMembers']);
        Route::get('/my-team', [TeamController::class, 'getMyTeamandLeader']);

     Route::middleware('role:manager')->get('/teams/members', [TeamController::class, 'getAllTeamsByType']);

});



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tasks/user', [TaskController::class, 'getUserTasks']);//get task by user_id
    Route::post('/task/assign', [TaskController::class, 'assignTasksToTeamMember']);

    Route::get('/tasks/team', [TaskController::class, 'getTeamTasks']);
    Route::get('/tasks', [TaskController::class, 'getAllTasksv2']);

    Route::get('/tasks/status/{task_id}', [TaskController::class, 'updateTaskStatus']);//for user
    Route::get('/tasks/approval/{task_id}', [TaskController::class, 'approveTask']); //for teamleader

    Route::get('/tasks/admin', [TaskController::class, 'getAllTasks']); // not used yet


});



//notes
Route::prefix('notes')->middleware('auth:sanctum')->group(function () {

    Route::get('/sender', [NoteController::class, 'getNotesByUser']);
    Route::post('/create', [NoteController::class, 'createNote']);
    Route::get('/{notable_id}', [NoteController::class, 'getNote']);
    Route::get('/', [NoteController::class, 'getAllNotes']);
    Route::delete('/{id}', [NoteController::class, 'deleteNote']);;
});

Route::prefix('notes/user')->middleware('auth:sanctum')->group(function () {
    Route::get('/private', [NoteController::class, 'getNotes_user']);
    Route::get('/leads', [NoteController::class, 'getNotes_leads']);
    Route::get('/contract', [NoteController::class, 'getNotes_Contracts']);
    Route::get('/task', [NoteController::class, 'getNotes_tasks']);
});

Route::middleware(['auth:sanctum', 'role:owner'])->group(function () {

    Route::get('/liabilities', [LiabilityController::class, 'index']);
    Route::post('/liabilities', [LiabilityController::class, 'store']);
    Route::put('/liabilities/{id}', [LiabilityController::class, 'update']);
    Route::delete('/liabilities/{id}', [LiabilityController::class, 'destroy']);
});


// price_list
Route::prefix('price-list')->middleware('auth:sanctum')->group(function () {

         Route::get('/', [PriceListController::class, 'getAll']);

  Route::middleware('role:owner')->group(function () {

        Route::post('/', [PriceListController::class, 'store']);
        Route::put('/{id}', [PriceListController::class, 'update']);
        Route::delete('/{id}', [PriceListController::class, 'delete']);
    });
});

   //owner
Route::prefix('dashboard')->middleware(['auth:sanctum', 'role:owner'])->group(function () {

    Route::get('/grouped-by-date', [OwnerDashboardController::class, 'getCollectionsGroupedByMonthAndYear']);
    Route::get('/grouped-by-sales-employee', [OwnerDashboardController::class, 'getCollectionsGroupedBySalesEmployeeUsingRelation']);
    Route::get('/grouped-by-Service', [OwnerDashboardController::class, 'getCollectionsGroupedBySalesEmployeeAndService']);
    Route::post('/report', [BonusController::class, 'getMonthlyReportv2']);
    Route::get('/sales-by-employee', [OwnerDashboardController::class, 'getTotalSalesByEmployee']);
    Route::get('/Services', [OwnerDashboardController::class, 'getTotalServicePrices']);
    Route::get('/totaly-report', [BonusController::class, 'gettotalyReport']);
});


Route::prefix('user')->middleware('auth:sanctum')->group(function () {

    Route::get('/collection/filter', [UserFilterationController::class, 'getCollectionsGroupedByAuthUser']);
});


//salary
Route::prefix('salary')->middleware('auth:sanctum')->group(function () {
    Route::post('/sales', [SalaryController::class, 'calculateAllSalesSalaries']);
    Route::post('/technical', [SalaryController::class, 'calculateTechnicalSalaries']);
    Route::post('/employee', [SalaryController::class, 'addSalary']);
    Route::post('/deduction/{id}', [SalaryController::class, 'addDeduction']);
    Route::delete('/{id}', [SalaryController::class, 'deletesalary']);
});


//bonuses
Route::prefix('bonuses')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [BonusController::class, 'createBonus']);
    Route::get('/', [BonusController::class, 'getAllBonuses']);
    Route::get('/{id}/check', [BonusController::class, 'checkBonusStatus']);
    Route::delete('/{id}', [BonusController::class, 'deleteBonus']);
});



Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user/team', [userDashboardController::class, 'getTeamId']);
    Route::get('/sales', [CollectionController::class, 'getCollectionsByAuthUser']);
    Route::get('/collections', [CollectionController::class, 'getCollectionss']);
    Route::get('/sales-employees', [CollectionController::class, 'getAllSalesEmployeesWithCollections']);

});




