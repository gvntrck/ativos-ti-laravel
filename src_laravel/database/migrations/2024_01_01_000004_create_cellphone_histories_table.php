<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cellphone_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cellphone_id')->constrained()->onDelete('cascade');
            $table->string('event_type', 50);
            $table->text('description');
            $table->text('photos')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cellphone_histories');
    }
};
