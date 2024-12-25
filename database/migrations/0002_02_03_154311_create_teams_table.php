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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('branch');
            $table->unsignedBigInteger('service_id')->nullable();
             $table->unsignedBigInteger('teamleader_id')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign( 'teamleader_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('teams');
    }
};
