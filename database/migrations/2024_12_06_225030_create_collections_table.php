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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_service_id')->constrained('contract_services')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->text('invoice')->nullable();
            $table->enum('status', ['Pending', 'Paid'])->default('Pending');
            $table->enum('is_approval', ['true', 'false'])->default('false');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('collections');
    }
};
