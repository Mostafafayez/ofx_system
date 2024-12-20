<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('department_id');
            $table->decimal('target', 15, 2);
            $table->decimal('bonus_amount', 15, 2)->nullable();
            $table->decimal('bonus_percentage', 5, 2)->nullable();
            $table->enum('status', ['pending', 'achieved', 'missed'])->default('pending');
            $table->timestamp('valid_month')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonuses');
    }
};
