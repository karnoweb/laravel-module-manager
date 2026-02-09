<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('module-manager.table_prefix', '');
        $tableName = $prefix . config('module-manager.tables.dependencies', 'module_dependencies');
        $modulesTable = $prefix . config('module-manager.tables.modules', 'modules');

        Schema::create($tableName, function (Blueprint $table) use ($modulesTable) {
            $table->id();
            $table->unsignedBigInteger('module_id');
            $table->unsignedBigInteger('dependency_id');
            $table->enum('type', ['requires', 'conflicts', 'suggests'])
                ->default('requires');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('module_id')
                ->references('id')
                ->on($modulesTable)
                ->onDelete('cascade');

            $table->foreign('dependency_id')
                ->references('id')
                ->on($modulesTable)
                ->onDelete('cascade');

            $table->unique(['module_id', 'dependency_id', 'type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        $prefix = config('module-manager.table_prefix', '');
        $tableName = $prefix . config('module-manager.tables.dependencies', 'module_dependencies');
        Schema::dropIfExists($tableName);
    }
};
