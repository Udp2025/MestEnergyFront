<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceClientAssignmentsTable extends Migration
{
    public function up()
    {
        Schema::create('device_client_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('site_id');
            $table->unsignedInteger('device_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();

            // indexes
            $table->index(['site_id','device_id']);
            $table->index('client_id');

            // (Opcional) constraint si tu tabla clients tiene primary key id:
            // $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('device_client_assignments');
    }
}
