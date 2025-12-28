<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaskUserTableSeeder extends Seeder
{
    public function run(): void
    {


        $task_users = [

            [
                'task_id' => 1,
                'user_id' => 78,
            ],

            [
                'task_id' => 1,
                'user_id' => 79,
            ],

            [
                'task_id' => 2,
                'user_id' => 71,
            ],

            [
                'task_id' => 3,
                'user_id' => 71,
            ],

            [
                'task_id' => 4,
                'user_id' => 72,
            ],

            [
                'task_id' => 5,
                'user_id' => 103,
            ],

            [
                'task_id' => 6,
                'user_id' => 19,
            ],

            [
                'task_id' => 7,
                'user_id' => 103,
            ],

            [
                'task_id' => 8,
                'user_id' => 62,
            ],

            [
                'task_id' => 8,
                'user_id' => 90,
            ],

            [
                'task_id' => 9,
                'user_id' => 62,
            ],

            [
                'task_id' => 9,
                'user_id' => 90,
            ],
        ];

        DB::table('task_user')->insert($task_users);
    }
}
