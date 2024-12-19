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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_leader_id')->nullable();

            $table->text('task')->nullable();
            $table->morphs('assigned');
            $table->morphs('fromable');
            $table->enum('status', ['Pending', 'Paid'])->default('Pending');
            $table->enum('is_approval', ['true', 'false'])->default('false');
            $table->timestamps();

            $table->foreign(columns: 'team_leader_id')->references('id')->on('users')->onDelete('set null');

        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
