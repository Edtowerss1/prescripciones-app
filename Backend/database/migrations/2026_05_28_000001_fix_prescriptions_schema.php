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
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->text('notes')->nullable()->after('status');
            $table->timestamp('consumed_at')->nullable()->after('notes');
            $table->dropIndex(['status']);
            $table->index(['status', 'created_at']);
        });

        Schema::table('prescription_items', function (Blueprint $table): void {
            $table->renameColumn('medication_name', 'name');
            $table->integer('quantity')->default(1)->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table): void {
            $table->dropIndex(['status', 'created_at']);
            $table->index(['status']);
            $table->dropColumn(['notes', 'consumed_at']);
        });

        Schema::table('prescription_items', function (Blueprint $table): void {
            $table->renameColumn('name', 'medication_name');
            $table->dropColumn('quantity');
        });
    }
};
