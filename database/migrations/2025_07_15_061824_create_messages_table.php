<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('admins')->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
        });

        // Pivot table for message recipients
        Schema::create('message_recipient', function (Blueprint $table) {
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipient_id')->constrained('admins')->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->primary(['message_id', 'recipient_id']);
        });

        // Message attachments
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('message_recipient');
        Schema::dropIfExists('messages');
    }
};
