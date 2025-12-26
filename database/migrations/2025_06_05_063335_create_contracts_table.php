<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directorate_id')->index()->constrained();
            $table->foreignId('project_id')->index()->constrained();
            $table->foreignId('status_id')->index()->constrained();
            $table->foreignId('priority_id')->index()->constrained();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('contractor')->nullable();
            $table->decimal('contract_amount', 18, 2)->nullable();
            $table->decimal('contract_variation_amount', 18, 2)->nullable();
            $table->string('contract_agreement_date')->nullable();
            $table->string('agreement_effective_date')->nullable();
            $table->string('agreement_completion_date')->nullable();
            $table->string('initial_contract_period')->nullable();

            $table->integer('progress');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['deleted_at']);
        });
    }
};
