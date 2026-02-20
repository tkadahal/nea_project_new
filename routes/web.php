<?php

declare(strict_types=1);

use App\Http\Middleware\AuthGates;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\{
    DashboardController,
    AnalyticalDashboardController,

    UserController,
    RoleController,
    PermissionController,

    DirectorateController,
    DepartmentController,
    StatusController,
    PriorityController,
    FiscalYearController,
    BudgetHeadingController,

    ProjectController,
    ContractController,
    ProjectActivityController,
    ProjectActivityScheduleController,
    ProjectExpenseController,
    ContractExtensionController,

    BudgetController,
    BudgetQuaterAllocationController,
    ProjectExpenseFundingAllocationController,

    TaskController,
    CommentController,
    EventController,

    ExpenseController,
    FileController,
    ReportController,
    NotificationController,
    ChartController,
};

use App\Http\Controllers\Settings\{
    ProfileController,
    PasswordController,
    AppearanceController
};

use App\Http\Controllers\Admin\Charts\ProjectChartController;


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

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('settings.')->group(function () {

        Route::prefix('profile')->name('profile.')
            ->controller(ProfileController::class)
            ->group(function () {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
                Route::delete('/', 'destroy')->name('destroy');
            });

        Route::prefix('password')->name('password.')
            ->controller(PasswordController::class)
            ->group(function () {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->name('update');
            });

        Route::get('appearance', [AppearanceController::class, 'edit'])
            ->name('appearance.edit');
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

        Route::prefix('analytics')->name('analytics.')->group(function () {

            // Main charts dashboard (portfolio view)
            Route::get('/project-charts', [ProjectChartController::class, 'index'])
                ->name('project-charts');

            // Single project detail view with all charts
            Route::get('/project-charts/{project}', [ProjectChartController::class, 'show'])
                ->name('project-charts.show');
        });

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

            // Load users by directorate (for left panel)
            Route::get('load-users/{directorate_id}', 'loadUsers')->name('loadUsers');

            // Load projects by directorate (for right panel)
            Route::get('load-projects/{directorate_id}', 'loadProjects')->name('loadProjects');

            // Show assignment page
            Route::get('assign-user-to-project', 'assignUserToProject')
                ->name('assignUserToProject');

            // Handle the actual assignment (POST)
            Route::post('assign-user-to-project', 'storeAssignment')
                ->name('assignUserToProject.store');
        });

        Route::resource('user', UserController::class);

        Route::get('/online-users', function () {
            return view('admin.users.online-users');
        })->name('online-users.index');

        // Master Data
        Route::resource('directorate', DirectorateController::class);
        Route::resource('department', DepartmentController::class);
        Route::resource('status', StatusController::class);
        Route::resource('priority', PriorityController::class);
        Route::resource('fiscalYear', FiscalYearController::class);
        Route::resource('budgetHeading', BudgetHeadingController::class);

        // Projects
        Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
            Route::get('analytics', 'analytics')->name('analytics');
            Route::get('users/{directorate_id}', 'getUsers')->name('users');
            Route::get('departments/{directorate_id}', 'getDepartments')->name('departments');
            Route::get('budget/create', 'createBudget')->name('budget.create');
            Route::get('{project}/progress/chart', 'progressChart')->name('progress.chart');

            Route::get('{project}/chart', [ChartController::class, 'activityTree'])->name('chart');

            /*
            |--------------------------------------------------------------------------
            | PROJECT ACTIVITY SCHEDULES - Complete Routes
            |--------------------------------------------------------------------------
            */

            // Main schedule management page
            Route::get('{project}/schedules', [ProjectActivityScheduleController::class, 'index'])
                ->name('schedules.index');

            // Schedule tree view
            Route::get('{project}/schedules/tree', [ProjectActivityScheduleController::class, 'tree'])
                ->name('schedules.tree');

            // Progress dashboard
            Route::get('{project}/schedules/dashboard', [ProjectActivityScheduleController::class, 'dashboard'])
                ->name('schedules.dashboard');

            // Quick update page (for leaf schedules only)
            Route::get('{project}/schedules/quick-update', [ProjectActivityScheduleController::class, 'quickUpdate'])
                ->name('schedules.quick-update');

            // ══════════════════════════════════════════════════════════
            // CHARTS - View Analytics (NEW)
            // ══════════════════════════════════════════════════════════

            // Charts page (Burn Chart, S-Curve, Activity Chart)
            Route::get('{project}/schedules/charts', [ProjectActivityScheduleController::class, 'charts'])
                ->name('schedules.charts');

            // ══════════════════════════════════════════════════════════
            // SCHEDULE CRUD - Create, Edit, Delete (NEW)
            // ══════════════════════════════════════════════════════════

            // Create custom schedule
            Route::get('{project}/schedules/create-schedule', [ProjectActivityScheduleController::class, 'createSchedule'])
                ->name('schedules.create-schedule');

            Route::post('{project}/schedules/store-schedule', [ProjectActivityScheduleController::class, 'storeSchedule'])
                ->name('schedules.store-schedule');

            // Edit schedule definition
            Route::get('{project}/schedules/{schedule}/edit-schedule', [ProjectActivityScheduleController::class, 'editSchedule'])
                ->name('schedules.edit-schedule');

            Route::put('{project}/schedules/{schedule}/update-schedule', [ProjectActivityScheduleController::class, 'updateSchedule'])
                ->name('schedules.update-schedule');

            // Delete schedule
            Route::delete('{project}/schedules/{schedule}/destroy-schedule', [ProjectActivityScheduleController::class, 'destroySchedule'])
                ->name('schedules.destroy-schedule');

            // ══════════════════════════════════════════════════════════
            // ASSIGNMENT - Assign Schedules to Project
            // ══════════════════════════════════════════════════════════

            // Assign schedules form
            Route::get('{project}/schedules/assign', [ProjectActivityScheduleController::class, 'assignForm'])
                ->name('schedules.assign-form');

            // Assign schedules (POST)
            Route::post('{project}/schedules/assign', [ProjectActivityScheduleController::class, 'assign'])
                ->name('schedules.assign');

            // ══════════════════════════════════════════════════════════
            // PROGRESS UPDATE - Edit & Update Progress
            // ══════════════════════════════════════════════════════════

            // Edit single schedule progress
            Route::get('{project}/schedules/{schedule}/edit', [ProjectActivityScheduleController::class, 'edit'])
                ->name('schedules.edit');

            // Update single schedule progress
            Route::put('{project}/schedules/{schedule}', [ProjectActivityScheduleController::class, 'update'])
                ->name('schedules.update');

            // Bulk update schedules
            Route::post('{project}/schedules/bulk-update', [ProjectActivityScheduleController::class, 'bulkUpdate'])
                ->name('schedules.bulk-update');

            // ══════════════════════════════════════════════════════════
            // DATE REVISIONS - Track Multiple Actual Dates (NEW)
            // ══════════════════════════════════════════════════════════

            // Add date revision
            Route::post('{project}/schedules/{schedule}/date-revision', [ProjectActivityScheduleController::class, 'addDateRevision'])
                ->name('schedules.add-date-revision');

            // Delete date revision
            Route::delete('{project}/schedules/date-revision/{revision}', [ProjectActivityScheduleController::class, 'deleteDateRevision'])
                ->name('schedules.delete-date-revision');


            // ══════════════════════════════════════════════════════════
            // REFERENCE FILES - Separate Page for File Management (NEW)
            // ══════════════════════════════════════════════════════════

            // Files management page
            Route::get('{project}/schedules/files', [ProjectActivityScheduleController::class, 'filesPage'])
                ->name('schedules.files');

            // Upload file (from files page)
            Route::post('{project}/schedules/upload-file', [ProjectActivityScheduleController::class, 'uploadFile'])
                ->name('schedules.upload-file');

            // Download file
            Route::get('{project}/schedules/files/{file}/download', [ProjectActivityScheduleController::class, 'downloadFile'])
                ->name('schedules.download-file');

            // Delete file
            Route::delete('{project}/schedules/files/{file}', [ProjectActivityScheduleController::class, 'deleteFile'])
                ->name('schedules.delete-file');
        });
        Route::resource('project', ProjectController::class);

        // Project Activities
        Route::prefix('projectActivity')->name('projectActivity.')->group(function () {

            // Standard CRUD
            Route::get('/', [ProjectActivityController::class, 'index'])->name('index');
            Route::get('create', [ProjectActivityController::class, 'create'])->name('create');
            Route::post('/', [ProjectActivityController::class, 'store'])->name('store');

            // AJAX endpoints
            Route::post('add-row', [ProjectActivityController::class, 'addRow'])->name('addRow');
            Route::delete('delete-row/{id}', [ProjectActivityController::class, 'deleteRow'])->name('deleteRow');
            Route::post('update-field', [ProjectActivityController::class, 'updateField'])->name('updateField');
            Route::get('get-activities', [ProjectActivityController::class, 'getActivities'])->name('getActivities');
            Route::get('rows', [ProjectActivityController::class, 'getRows'])->name('getRows');
            Route::get('budgetData', [ProjectActivityController::class, 'getBudgetData'])->name('budgetData');

            // Excel
            Route::get('template', [ProjectActivityController::class, 'downloadTemplate'])->name('template');
            Route::get('upload-form', [ProjectActivityController::class, 'showUploadForm'])->name('uploadForm');
            Route::post('upload', [ProjectActivityController::class, 'uploadExcel'])->name('upload');
            Route::post('upload/confirm', [ProjectActivityController::class, 'confirmExcelUpload'])
                ->name('upload.confirm');

            Route::post('upload/cancel', [ProjectActivityController::class, 'cancelExcelUpload'])
                ->name('upload.cancel');

            // Show / Edit / Update / Download
            Route::get('show/{projectId}/{fiscalYearId}/{version?}', [ProjectActivityController::class, 'show'])->name('show');
            Route::get('edit/{projectId}/{fiscalYearId}', [ProjectActivityController::class, 'edit'])->name('edit');
            Route::put('{projectId}/{fiscalYearId}', [ProjectActivityController::class, 'update'])->name('update');
            Route::get('{projectId}/{fiscalYearId}/download', [ProjectActivityController::class, 'downloadActivities'])
                ->name('downloadActivities');

            Route::delete('{id}', [ProjectActivityController::class, 'destroy'])->name('destroy');

            // === WORKFLOW ACTIONS ===
            Route::get('/project-activities/{projectId}/{fiscalYearId}/log', [ProjectActivityController::class, 'showLog'])
                ->name('log');

            Route::post('{projectId}/{fiscalYearId}/send-for-review', [ProjectActivityController::class, 'sendForReview'])
                ->name('sendForReview');

            Route::post('{projectId}/{fiscalYearId}/review', [ProjectActivityController::class, 'review'])
                ->name('review');

            Route::post('{projectId}/{fiscalYearId}/approve', [ProjectActivityController::class, 'approve'])
                ->name('approve');

            Route::post('{projectId}/{fiscalYearId}/reject', [ProjectActivityController::class, 'reject'])
                ->name('reject');

            Route::post('{projectId}/{fiscalYearId}/returnToDraft', [ProjectActivityController::class, 'returnToDraft'])
                ->name('returnToDraft');
        });

        // Contracts
        Route::controller(ContractController::class)->prefix('contracts')->name('contracts.')->group(function () {
            Route::get('projects/{directorate_id}', 'getProjects')->name('projects');
            Route::get('get-project-budget/{projectId}', [ContractController::class, 'getProjectBudget'])
                ->name('get-project-budget');
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
            Route::get('filter-projects', [BudgetController::class, 'filterProjects'])
                ->name('filter-projects');
        });
        Route::prefix('budgets')->name('budget.')->group(function () {
            Route::get('duplicates', [BudgetController::class, 'listDuplicates'])->name('duplicates');
            Route::post('clean-duplicates', [BudgetController::class, 'cleanDuplicates'])->name('cleanDuplicates');
        });
        Route::resource('budget', BudgetController::class);

        // Budget Quarter Allocations
        Route::controller(BudgetQuaterAllocationController::class)
            ->prefix('budget-quater-allocation')
            ->name('budgetQuaterAllocation.')
            ->group(function () {
                Route::get('download-template', 'downloadTemplate')
                    ->name('download-template');

                Route::get('upload-template', 'uploadIndex')
                    ->name('uploadIndex');

                Route::post('upload-template', 'uploadTemplate')
                    ->name('uploadTemplate');
            });

        // Separate route for AJAX load budgets
        Route::post(
            'budget-quater-allocations/load-budgets',
            [BudgetQuaterAllocationController::class, 'loadBudgets']
        )->name('budgetQuaterAllocations.loadBudgets');

        // Resource routes
        Route::resource('budgetQuaterAllocation', BudgetQuaterAllocationController::class);

        // Project Expense Funding Allocations
        Route::post('project-expense-funding-allocations/load-data', [ProjectExpenseFundingAllocationController::class, 'loadData'])->name('projectExpenseFundingAllocations.loadData');
        Route::resource('project-expense-funding-allocation', ProjectExpenseFundingAllocationController::class)
            ->names('projectExpenseFundingAllocation')
            ->only(['index', 'create', 'store']);

        // Expenses
        Route::controller(ExpenseController::class)->group(function () {
            Route::get('fiscal-years/by-date', 'byDate')->name('fiscal-years.by-date');
            Route::get('budgets/available', 'availableBudget')->name('budgets.available');
            Route::get('expenses/0', 'testShow')->name('expense.testShow');
        });
        Route::resource('expense', ExpenseController::class);

        // Project Expenses
        Route::controller(ProjectExpenseController::class)->prefix('projectExpense')->name('projectExpense.')->group(function () {
            Route::get('downloadExcel/{projectId}/{fiscalYearId}', 'downloadTemplate')->name('template.download');
            Route::get('{project}/{fiscalYear}/upload', 'uploadView')->name('upload.view');
            Route::post('{project}/{fiscalYear}/upload', 'upload')->name('upload');
            Route::get('download/excel/{projectId}/{fiscalYearId}', 'downloadExcel')->name('excel.download');
            Route::get('show/{projectId}/{fiscalYearId}', 'show')->name('show');

            Route::get(
                'next-quarter/{project}/{fiscalYear}',
                [ProjectExpenseController::class, 'nextQuarterAjax']
            )
                ->name('nextQuarter');

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

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            // Show progress report generation view
            Route::get('consolidated-annual', [ReportController::class, 'showConsolidatedAnnualReport'])
                ->name('consolidatedAnnual.view');

            // Generate and download progress report
            Route::get('consolidated-annual/download', [ReportController::class, 'consolidatedAnnualReport'])
                ->name('consolidatedAnnual');

            // Show budget report generation view
            Route::get('budgetReport', [ReportController::class, 'showBudgetReportView'])
                ->name('budgetReport.view');

            // Generate and download budget report
            Route::get('budgetReport/download', [ReportController::class, 'budgetReport'])
                ->name('budgetReport');

            // Get project count for preview
            Route::get('project-count', [ReportController::class, 'getProjectCount'])
                ->name('projectCount');
        });

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    });
});

require __DIR__ . '/auth.php';
