<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('priority');
            $table->string('category');
            $table->string('status')->default('open');
            $table->text('summary')->nullable();
            $table->text('suggested_next_action')->nullable();
            $table->string('summary_status')->default('pending');
            $table->boolean('needs_attention')->default(false);
            $table->timestamps();

            $table->index(['status', 'category', 'priority']);
            $table->index('needs_attention');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
