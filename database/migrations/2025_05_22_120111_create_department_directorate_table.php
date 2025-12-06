<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_directorate', function (Blueprint $table) {
            $table->foreignId('directorate_id')->constrained();
            $table->foreignId('department_id')->constrained();
        });
    }
};
