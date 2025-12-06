<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directorate_user', function (Blueprint $table) {
            $table->foreignId('directorate_id')->constrained();
            $table->foreignId('user_id')->constrained();
        });
    }
};
