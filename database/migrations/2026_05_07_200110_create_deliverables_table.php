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
    Schema::create('deliverables', function (Blueprint $table) {
        $table->id();

        $table->foreignId('project_id')
              ->constrained('projects')
              ->cascadeOnDelete();

        $table->foreignId('task_id')
              ->nullable()
              ->constrained('tasks')
              ->nullOnDelete();

        $table->foreignId('submitted_by')
              ->constrained('users')
              ->cascadeOnDelete();

        $table->string('file_name');
        $table->string('file_path');

        $table->enum('status', ['en_attente', 'valide', 'refuse'])
              ->default('en_attente');

        $table->text('teacher_comment')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliverables');
    }
};
