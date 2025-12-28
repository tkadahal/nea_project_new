<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TasksTableSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = [

            [

                'directorate_id' => 4,
                'department_id' => NULL,
                'parent_id' => NULL,
                'title' => 'Design, Supply, Installation and Commissioning of Underground Distribution Network under Kirtipur, Kuleshwor, Baneshwor, Balaju and Jorpati Distribution Center including Reinforcement Automation.',
                'description' => 'Construction of 296.5 k.m.11 kV and 284.5 k.m.400 V Underground network in the Kathmandu Valley.',
                'start_date' => '2020-06-24',
                'due_date' => '2026-06-30',
                'completion_date' => '2026-06-30',
                'status_id' => 2,
                'priority_id' => 2,
                'active' => false,
                'assigned_by' => 71,
                'created_at' => '2025-09-02 10:44:43',
                'updated_at' => '2025-09-02 10:44:43',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'department_id' => NULL,
                'parent_id' => 2,
                'title' => 'Construction of Underground Distribution network in Kathmandu Valley',
                'description' => NULL,
                'start_date' => '2020-06-24',
                'due_date' => '2026-06-30',
                'completion_date' => NULL,
                'status_id' => 1,
                'priority_id' => 2,
                'active' => false,
                'assigned_by' => 71,
                'created_at' => '2025-09-02 10:44:43',
                'updated_at' => '2025-09-02 10:44:43',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'department_id' => 32,
                'parent_id' => NULL,
                'title' => 'Capitalization of the Charging Stations',
                'description' => 'The committee is already formed for capitalization. The Team is working for the same.',
                'start_date' => '2025-09-03',
                'due_date' => '2025-09-03',
                'completion_date' => '2025-11-03',
                'status_id' => 2,
                'priority_id' => 2,
                'active' => false,
                'assigned_by' => 72,
                'created_at' => '2025-09-03 05:25:08',
                'updated_at' => '2025-09-03 05:25:08',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 1,
                'department_id' => 34,
                'parent_id' => NULL,
                'title' => 'Sindhuli Progresss',
                'description' => 'What is the progress?',
                'start_date' => '2025-09-03',
                'due_date' => '2025-09-10',
                'completion_date' => '2025-09-17',
                'status_id' => 2,
                'priority_id' => 2,
                'active' => false,
                'assigned_by' => 11,
                'created_at' => '2025-09-03 10:36:14',
                'updated_at' => '2025-09-03 10:36:14',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 5,
                'department_id' => NULL,
                'parent_id' => NULL,
                'title' => 'update all the fields',
                'description' => 'update all the fields',
                'start_date' => '2025-09-05',
                'due_date' => '2025-09-07',
                'completion_date' => '2025-09-09',
                'status_id' => 2,
                'priority_id' => 1,
                'active' => false,
                'assigned_by' => 98,
                'created_at' => '2025-09-05 02:17:31',
                'updated_at' => '2025-09-05 02:26:00',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'department_id' => 31,
                'parent_id' => NULL,
                'title' => 'Nikasha follow up',
                'description' => 'Please follow up disbursement from NEA budget',
                'start_date' => '2025-09-02',
                'due_date' => '2025-09-09',
                'completion_date' => NULL,
                'status_id' => 3,
                'priority_id' => 1,
                'active' => false,
                'assigned_by' => 38,
                'created_at' => '2025-09-02 07:17:45',
                'updated_at' => '2025-09-16 09:33:10',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 5,
                'department_id' => NULL,
                'parent_id' => NULL,
                'title' => 'Tippani tracking',
                'description' => 'Tippani tarcking',
                'start_date' => '2025-09-03',
                'due_date' => '2025-09-10',
                'completion_date' => '2025-09-11',
                'status_id' => 1,
                'priority_id' => 1,
                'active' => false,
                'assigned_by' => 6,
                'created_at' => '2025-09-03 09:52:38',
                'updated_at' => '2025-10-09 11:41:04',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'department_id' => 31,
                'parent_id' => NULL,
                'title' => '400 kV transmission line and substation',
                'description' => '1.Construction of double circuit 400 kV transmission line from New Khimti substation to Lapsiphedi substation and 132 kV transmission line from Lapsiphedi substation to Changunarayan substation and LILO.
2. Construction of 220/132 kV Barhabise GIS substation.',
                'start_date' => '2017-06-13',
                'due_date' => '2025-10-13',
                'completion_date' => '2025-12-31',
                'status_id' => 2,
                'priority_id' => 2,
                'active' => false,
                'assigned_by' => 90,
                'created_at' => '2025-10-13 08:39:04',
                'updated_at' => '2025-10-13 08:39:04',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'department_id' => 31,
                'parent_id' => NULL,
                'title' => '400 kV Khimti-Kathmandu 400 kV T.L. @ 220/132 kV Barhabise GIS substatio.',
                'description' => 'Construction of 240 numbers of DC 400 kV transmission line and 160MVA 220/132 kV Barhabise GIS substation',
                'start_date' => '2025-11-09',
                'due_date' => '2025-12-31',
                'completion_date' => '2025-12-31',
                'status_id' => 2,
                'priority_id' => 2,
                'active' => false,
                'assigned_by' => 90,
                'created_at' => '2025-11-09 06:07:07',
                'updated_at' => '2025-11-09 06:07:07',
                'deleted_at' => NULL,
            ],
        ];

        Task::insert($tasks);
    }
}
