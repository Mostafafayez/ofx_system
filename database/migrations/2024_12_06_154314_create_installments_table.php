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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('liability_id');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['pending', 'paid']);
            $table->softDeletes();
            $table->timestamps();



            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('liability_id')->references('id')->on('liabilities')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('installments');
    }
};
