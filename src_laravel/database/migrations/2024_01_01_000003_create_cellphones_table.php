<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cellphones', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 20)->unique()->nullable();
            $table->string('phone_number', 30)->index()->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('deleted')->default(false);
            $table->string('user_name', 100)->nullable();
            $table->string('brand_model', 150)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('property', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_url', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cellphones');
    }
};
