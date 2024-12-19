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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('serial_num')->unique();
            $table->text('details')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->unsignedBigInteger('sales_employee_id');
            $table->unsignedBigInteger('client_id');
            $table->softDeletes(); 
            $table->timestamps();

            $table->foreign('sales_employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
};
