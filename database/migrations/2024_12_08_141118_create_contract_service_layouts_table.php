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
        Schema::create('contract_service_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_service_id')->constrained('contract_services')->cascadeOnDelete();
            $table->foreignId('layout_id')->constrained('layouts')->cascadeOnDelete();
            $table->text('answer');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_service_layouts');
    }
};
