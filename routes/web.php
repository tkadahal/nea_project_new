<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    FileController,
    RoleController,
    TaskController,
    UserController,
    EventController,
    BudgetController,
    StatusController,
    CommentController,
    ExpenseController,
    ProjectController,
    ContractController,
    PriorityController,
    DashboardController,
    DefinitionController,
    DepartmentController,
    FiscalYearController,
    PermissionController,
    DirectorateController,
    NotificationController,
    ProjectExpenseController,
    ProjectActivityController,
    ContractExtensionController,
    AnalyticalDashboardController,
    BudgetQuaterAllocationController,
    ProjectExpenseFundingAllocationController
};
use App\Http\Controllers\Settings\{
    ProfileController,
    PasswordController,
    AppearanceController
};
use App\Http\Middleware\AuthGates;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::permanentRedirect('/', '/login')->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', AuthGates::class])->group(function () {

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Settings Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {
        // Profile
        Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });

        // Password
        Route::controller(PasswordController::class)->prefix('password')->name('password.')->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
        });

        // Appearance
        Route::get('appearance', [AppearanceController::class, 'edit'])->name('appearance.edit');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {

        // Permissions & Roles
        Route::resource('permission', PermissionController::class);
        Route::resource('role', RoleController::class);

        // Analytics & Summary
        Route::controller(AnalyticalDashboardController::class)->group(function () {
            Route::get('summary', 'summary')->name('summary');
            Route::get('analytics/task', 'taskAnalytics')->name('analytics.task');
            Route::get('analytics/project', 'projectAnalytics')->name('analytics.project');
            Route::get('tasks/analytics/export', 'exportTaskAnalytics')->name('tasks.analytics.export');
            Route::get('projects/analytics/export', 'exportProjectAnalytics')->name('projects.analytics.export');
        });

        // Users
        Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
            Route::get('projects/{directorate_id}', 'getProjects')->name('projects');
        });
        Route::resource('user', UserController::class);

        // Master Data
        Route::resource('directorate', DirectorateController::class);
        Route::resource('department', DepartmentController::class);
        Route::resource('status', StatusController::class);
        Route::resource('priority', PriorityController::class);
        Route::resource('fiscalYear', FiscalYearController::class);

        // Projects
        Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
            Route::get('analytics', 'analytics')->name('analytics');
            Route::get('users/{directorate_id}', 'getUsers')->name('users');
            Route::get('departments/{directorate_id}', 'getDepartments')->name('departments');
            Route::get('budget/create', 'createBudget')->name('budget.create');
            Route::get('{project}/progress/chart', 'progressChart')->name('progress.chart');
        });
        Route::resource('project', ProjectController::class);

        // Project Activities
        Route::controller(ProjectActivityController::class)->prefix('project-activities')->name('projectActivity.')->group(function () {
            Route::get('template', 'downloadTemplate')->name('template');
            Route::get('upload', 'showUploadForm')->name('uploadForm');
            Route::post('upload', 'uploadExcel')->name('upload');
            Route::get('{projectId}/{fiscalYearId}/download-activities', 'downloadActivities')->name('download-activities');
        });
        Route::prefix('projectActivity')->name('projectActivity.')->group(function () {
            Route::get('rows', [ProjectActivityController::class, 'getRows'])->name('getRows');
            Route::get('budgetData', [ProjectActivityController::class, 'getBudgetData'])->name('budgetData');
            Route::get('show/{projectId}/{fiscalYearId}', [ProjectActivityController::class, 'show'])->name('show');
            Route::get('edit/{projectId}/{fiscalYearId}', [ProjectActivityController::class, 'edit'])->name('edit');
            Route::put('{projectId}/{fiscalYearId}', [ProjectActivityController::class, 'update'])->name('update');
            // Route::get('definitions', [ProjectActivityController::class, 'getDefinitions'])->name('getDefinitions');
        });
        Route::resource('projectActivity', ProjectActivityController::class)->except(['show', 'edit', 'update']);

        // Contracts
        Route::controller(ContractController::class)->prefix('contracts')->name('contracts.')->group(function () {
            Route::get('projects/{directorate_id}', 'getProjects')->name('projects');
        });
        Route::resource('contract', ContractController::class);

        // Contract Extensions
        Route::prefix('contract/{contract}/extensions')->name('contract.extensions.')->controller(ContractExtensionController::class)->group(function () {
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{extension}/edit', 'edit')->name('edit');
            Route::put('{extension}', 'update')->name('update');
            Route::delete('{extension}', 'destroy')->name('destroy');
        });

        // Tasks
        Route::controller(TaskController::class)->prefix('tasks')->name('tasks.')->group(function () {
            Route::post('filter', 'filter')->name('filter');
            Route::post('set-view', 'setViewPreference')->name('set-view');
            Route::get('gantt-chart', 'getGanttChart')->name('ganttChart');
            Route::get('users-by-projects', 'getUsersByProjects')->name('users_by_projects');
            Route::get('users-by-directorate-or-department', 'getUsersByDirectorateOrDepartment')->name('users_by_directorate_or_department');
            Route::get('projects/{directorate_id}', 'getProjects')->name('projects');
            Route::get('departments/{directorate_id}', 'getDepartments')->name('departments');
        });
        Route::prefix('task')->name('task.')->controller(TaskController::class)->group(function () {
            Route::post('load-more', 'loadMore')->name('loadMore');
            Route::post('updateStatus', 'updateStatus')->name('updateStatus');
            Route::get('{task}/{project?}', 'show')->name('show')->where(['task' => '[0-9]+', 'project' => '[0-9]+']);
            Route::get('{task}/edit/{project?}', 'edit')->name('edit')->where(['task' => '[0-9]+', 'project' => '[0-9]+']);
            Route::put('{task}/update/{project?}', 'update')->name('update')->where(['task' => '[0-9]+', 'project' => '[0-9]+']);
        });
        Route::resource('task', TaskController::class)->except(['show', 'edit', 'update']);

        // Comments
        Route::controller(CommentController::class)->group(function () {
            Route::post('projects/{project}/comments', 'storeForProject')->name('projects.comments.store');
            Route::post('tasks/{task}/comments', 'storeForTask')->name('tasks.comments.store');
        });

        // Events
        Route::resource('event', EventController::class);

        // Budgets
        Route::controller(BudgetController::class)->prefix('budget')->name('budget.')->group(function () {
            Route::get('download-template', 'downloadTemplate')->name('download-template');
            Route::get('upload', 'uploadIndex')->name('upload.index');
            Route::post('upload', 'upload')->name('upload');
            Route::get('{budget}/remaining', 'remaining')->name('remaining');
        });
        Route::prefix('budgets')->name('budget.')->group(function () {
            Route::get('duplicates', [BudgetController::class, 'listDuplicates'])->name('duplicates');
            Route::post('clean-duplicates', [BudgetController::class, 'cleanDuplicates'])->name('cleanDuplicates');
        });
        Route::resource('budget', BudgetController::class);

        // Budget Quarter Allocations
        Route::post('budget-quater-allocations/load-budgets', [BudgetQuaterAllocationController::class, 'loadBudgets'])->name('budgetQuaterAllocations.loadBudgets');
        Route::resource('budgetQuaterAllocation', BudgetQuaterAllocationController::class);

        // Project Expense Funding Allocations
        Route::get('project-expense-funding-allocation/create', [ProjectExpenseFundingAllocationController::class, 'create'])->name('projectExpenseFundingAllocation.create');
        Route::post('project-expense-funding-allocation', [ProjectExpenseFundingAllocationController::class, 'store'])->name('projectExpenseFundingAllocation.store');
        Route::post('project-expense-funding-allocations/load-data', [ProjectExpenseFundingAllocationController::class, 'loadData'])->name('projectExpenseFundingAllocations.loadData');

        // Expenses
        Route::controller(ExpenseController::class)->group(function () {
            Route::get('fiscal-years/by-date', 'byDate')->name('fiscal-years.by-date');
            Route::get('budgets/available', 'availableBudget')->name('budgets.available');
            Route::get('expenses/0', 'testShow')->name('expense.testShow');
        });
        Route::resource('expense', ExpenseController::class);

        // Project Expenses
        Route::controller(ProjectExpenseController::class)->prefix('projectExpense')->name('projectExpense.')->group(function () {
            Route::get('download/excel/{projectId}/{fiscalYearId}', 'downloadExcel')->name('excel.download');
            Route::get('show/{projectId}/{fiscalyearId}', 'show')->name('show');

            Route::get('{projectId}/{fiscalYearId}', 'getForProject')->name('getForProject');
        });
        Route::resource('projectExpense', ProjectExpenseController::class)->except(['show', 'edit', 'update']);

        // Files
        Route::controller(FileController::class)->group(function () {
            Route::get('files', 'index')->name('file.index');
            Route::post('{model}/{id}/files', 'store')->name('files.store');
            Route::get('files/{file}/download', 'download')->name('files.download');
            Route::delete('files/{file}', 'destroy')->name('files.destroy');
        });

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    });
});

require __DIR__ . '/auth.php';
