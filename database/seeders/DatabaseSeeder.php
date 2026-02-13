<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
            PermissionRoleTableSeeder::class,
            DirectoratesTableSeeder::class,
            UsersTableSeeder::class,
            RoleUserTableSeeder::class,
            DepartmentsTableSeeder::class,
            DepartmentDirectorateTableSeeder::class,
            StatusesTableSeeder::class,
            BudgetHeadingsTableSeeder::class,
            PrioritiesTableSeeder::class,
            ProjectsTableSeeder::class,
            FiscalYearTableSeeder::class,
            // BudgetsTableSeeder::class,
            ContractsTableSeeder::class,
            ProjectActivityScheduleSeeder::class,
            // TasksTableSeeder::class,
            // ProjectUserTableSeeder::class,
            // TaskUserTableSeeder::class
        ]);
    }
}
