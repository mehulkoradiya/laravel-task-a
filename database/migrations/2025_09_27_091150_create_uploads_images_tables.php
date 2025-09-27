<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('filename');
            $table->bigInteger('size')->default(0);
            $table->string('checksum')->nullable();
            $table->string('storage_disk')->default('public');
            $table->enum('status', ['pending','uploading','assembling','completed','failed'])->default('pending');
            $table->integer('total_chunks')->nullable();
            $table->integer('received_chunks')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('uploads')->cascadeOnDelete();
            $table->string('path');
            $table->string('checksum')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('image_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained('images')->cascadeOnDelete();
            $table->string('variant')->index();
            $table->string('path');
            $table->integer('width');
            $table->integer('height');
            $table->bigInteger('size_bytes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_variants');
        Schema::dropIfExists('images');
        Schema::dropIfExists('uploads');
    }
};
