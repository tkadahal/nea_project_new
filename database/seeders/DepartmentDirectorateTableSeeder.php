<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Directorate;
use Illuminate\Database\Seeder;

class DepartmentDirectorateTableSeeder extends Seeder
{
    public function run(): void
    {
        $pmit_departments = Department::whereIn('id', [1, 2, 3])->get();
        $bdd_departments = Department::whereIn('id', [4, 5, 6, 7])->get();
        $admisitrative_departments = Department::whereIn('id', [8, 9, 10, 11])->get();
        $finance_departments = Department::whereIn('id', [12, 13, 14, 15])->get();
        $generation_departments = Department::whereIn('id', [16, 17, 18])->get();
        $transmission_departments = Department::whereIn('id', [19, 20, 21, 22, 23, 24])->get();
        $engineering_departments = Department::whereIn('id', [25, 26, 27, 28, 29, 30])->get();
        $pmd_departments = Department::whereIn('id', [31, 32, 33])->get();
        $dcsd_departments = Department::whereIn('id', [34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46])->get();

        Directorate::findOrFail(1)->departments()->sync($dcsd_departments);
        Directorate::findOrFail(2)->departments()->sync($transmission_departments);
        Directorate::findOrFail(3)->departments()->sync($generation_departments);
        Directorate::findOrFail(4)->departments()->sync($pmd_departments);
        Directorate::findOrFail(5)->departments()->sync($pmit_departments);
        Directorate::findOrFail(6)->departments()->sync($finance_departments);
        Directorate::findOrFail(7)->departments()->sync($admisitrative_departments);
        Directorate::findOrFail(8)->departments()->sync($bdd_departments);
        Directorate::findOrFail(9)->departments()->sync($engineering_departments);
    }
}
