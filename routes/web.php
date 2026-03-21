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

    UserController,
    RoleController,
    PermissionController,

    DirectorateController,
    DepartmentController,
    StatusController,
    PriorityController,
    FiscalYearController,
    BudgetHeadingController,

    ProjectActivityController,

    EventController,

    NotificationController,
    ChartController,
    LibraryController,
    ProjectTypeController,
};

use App\Http\Controllers\Settings\{
    ProfileController,
    PasswordController,
    AppearanceController
};

use App\Http\Controllers\Admin\Charts\ProjectChartController;
use App\Http\Controllers\Admin\PreBudgetController;

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

        // Pre Budget
        Route::get('/preBudget/export', [PreBudgetController::class, 'export'])
            ->name('preBudget.export');
        Route::resource('preBudget', PreBudgetController::class);

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
        Route::resource('library', LibraryController::class);
        Route::resource('projectType', ProjectTypeController::class);

        // Project Activities
        Route::controller(ProjectActivityController::class)->prefix('projectActivity')->name('projectActivity.')->group(function () {

            // Standard CRUD
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');

            // AJAX endpoints
            Route::post('add-row', 'addRow')->name('addRow');
            Route::delete('delete-row/{id}', 'deleteRow')->name('deleteRow');
            Route::post('update-field', 'updateField')->name('updateField');
            Route::get('get-activities', 'getActivities')->name('getActivities');
            Route::get('rows', 'getRows')->name('getRows');
            Route::get('budgetData', 'getBudgetData')->name('budgetData');

            // Excel
            Route::get('template', 'downloadTemplate')->name('template');
            Route::get('upload-form', 'showUploadForm')->name('uploadForm');
            Route::post('upload', 'uploadExcel')->name('upload');
            Route::post('upload/confirm', 'confirmExcelUpload')
                ->name('upload.confirm');

            Route::post('upload/cancel', 'cancelExcelUpload')
                ->name('upload.cancel');

            // Show / Edit / Update / Download
            Route::get('show/{projectId}/{fiscalYearId}/{version?}', 'show')->name('show');
            Route::get('edit/{projectId}/{fiscalYearId}', 'edit')->name('edit');
            Route::put('{projectId}/{fiscalYearId}', 'update')->name('update');
            Route::get('{projectId}/{fiscalYearId}/download', 'downloadActivities')
                ->name('downloadActivities');

            Route::delete('{id}', 'destroy')->name('destroy');

            // === WORKFLOW ACTIONS ===
            Route::get('/project-activities/{projectId}/{fiscalYearId}/log', 'showLog')
                ->name('log');

            Route::post('{projectId}/{fiscalYearId}/send-for-review', 'sendForReview')
                ->name('sendForReview');

            Route::post('{projectId}/{fiscalYearId}/review', 'review')
                ->name('review');

            Route::post('{projectId}/{fiscalYearId}/approve', 'approve')
                ->name('approve');

            Route::post('{projectId}/{fiscalYearId}/reject', 'reject')
                ->name('reject');

            Route::post('{projectId}/{fiscalYearId}/returnToDraft', 'returnToDraft')
                ->name('returnToDraft');
        });

        // Comments


        // Events
        Route::resource('event', EventController::class);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');

        require __DIR__ . '/admin/analytics.php';
        require __DIR__ . '/admin/schedule.php';
        require __DIR__ . '/admin/project.php';
        require __DIR__ . '/admin/contract.php';
        require __DIR__ . '/admin/task.php';
        require __DIR__ . '/admin/file.php';
        require __DIR__ . '/admin/reports.php';
        require __DIR__ . '/admin/projectExpense.php';
        require __DIR__ . '/admin/expense.php';
        require __DIR__ . '/admin/comment.php';
        require __DIR__ . '/admin/budget.php';
    });
});

require __DIR__ . '/auth.php';
