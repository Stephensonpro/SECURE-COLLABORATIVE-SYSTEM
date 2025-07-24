<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('admins')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->boolean('is_confidential')->default(false);
            $table->timestamps();
        });

        // Pivot table for document permissions
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->boolean('can_view')->default(false);
            $table->boolean('can_download')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->primary(['document_id', 'role_id']);
        });

        // Document access logs
        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained('admins')->onDelete('cascade');
            $table->string('action'); // viewed, downloaded, etc.
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('document_permissions');
        Schema::dropIfExists('documents');
    }
};
