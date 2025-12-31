<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
};
