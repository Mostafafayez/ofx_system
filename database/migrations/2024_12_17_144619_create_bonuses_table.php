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
            $table->decimal('target', 15, 2); // Target sales amount
            $table->decimal('bonus_amount', 15, 2)->nullable(); // Fixed bonus amount
            $table->decimal('bonus_percentage', 5, 2)->nullable(); // Bonus as a percentage
            $table->string('status')->default(['pending','achieved', 'missed']); // Status: pending, achieved, missed
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->timestamp('valid_month')->nullable(); // Month-Year for validation
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
