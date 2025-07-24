<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('admins')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Pivot table for announcement departments
        Schema::create('announcement_department', function (Blueprint $table) {
            $table->foreignId('announcement_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->primary(['announcement_id', 'department_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('announcement_department');
        Schema::dropIfExists('announcements');
    }
};
