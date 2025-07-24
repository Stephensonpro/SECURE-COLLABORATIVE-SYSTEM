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
        Schema::table('message_recipient', function (Blueprint $table) {
            $table->timestamps(); // This adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('message_recipient', function (Blueprint $table) {
            $table->dropTimestamps(); // This removes both columns if you rollback
        });
    }
};
