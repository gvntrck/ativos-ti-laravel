<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('computers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['desktop', 'notebook'])->default('desktop');
            $table->string('hostname', 100)->index();
            $table->string('status', 20)->default('active');
            $table->boolean('deleted')->default(false);
            $table->string('user_name', 100)->nullable();
            $table->string('location', 255)->nullable();
            $table->string('property', 20)->nullable();
            $table->text('specs')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_url', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};
