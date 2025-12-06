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
            UsersTableSeeder::class,
            RoleUserTableSeeder::class,
            DirectoratesTableSeeder::class,
            DepartmentsTableSeeder::class,
            DepartmentDirectorateTableSeeder::class,
            StatusesTableSeeder::class,
            PrioritiesTableSeeder::class,
            ProjectsTableSeeder::class,
            FiscalYearTableSeeder::class,
        ]);
    }
}
