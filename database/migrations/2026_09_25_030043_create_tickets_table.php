<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_line_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 24); // fault | provisioning | number_port
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('priority', 16)->default('normal');
            $table->string('status', 24)->default('open');
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
