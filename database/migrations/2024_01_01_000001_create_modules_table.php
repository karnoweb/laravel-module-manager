<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('module-manager.table_prefix', '');
        $tableName = $prefix . config('module-manager.tables.modules', 'modules');

        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('group')->default('general');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_system')->default(false);
            $table->json('metadata')->nullable();
            $table->enum('on_deactivate', ['cascade', 'restrict', 'none'])
                ->default('restrict');
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on($tableName)
                ->onDelete('cascade');

            $table->index(['group', 'is_active']);
            $table->index('parent_id');
            $table->index('sort_order');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        $prefix = config('module-manager.table_prefix', '');
        $tableName = $prefix . config('module-manager.tables.modules', 'modules');
        Schema::dropIfExists($tableName);
    }
};
