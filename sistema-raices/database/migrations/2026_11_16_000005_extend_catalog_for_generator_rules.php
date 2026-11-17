<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('selection_group', 80)->nullable()->after('category');
        });

        Schema::table('package_services', function (Blueprint $table) {
            $table->boolean('is_optional')->default(false)->after('default_quantity');
            $table->string('quantity_rule', 40)->default('fixed')->after('is_optional');
            $table->decimal('quantity_rule_value', 10, 2)->nullable()->after('quantity_rule');
            $table->json('event_type_names')->nullable()->after('quantity_rule_value');
            $table->foreignId('replaces_service_id')->nullable()->after('event_type_names')->constrained('services')->nullOnDelete();
            $table->smallInteger('sort_order')->default(0)->after('replaces_service_id');
            $table->boolean('display_quantity')->default(true)->after('sort_order');
        });

        Schema::table('service_rates', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('service_id')->constrained('packages')->nullOnDelete();
            $table->foreignId('event_type_id')->nullable()->after('package_id')->constrained('event_types')->nullOnDelete();
            $table->index(['service_id', 'package_id', 'event_type_id', 'valid_from'], 'service_rates_context_index');
        });
    }

    public function down(): void
    {
        Schema::table('service_rates', function (Blueprint $table) {
            $table->dropIndex('service_rates_context_index');
            $table->dropForeign(['package_id']);
            $table->dropForeign(['event_type_id']);
            $table->dropColumn(['package_id', 'event_type_id']);
        });

        Schema::table('package_services', function (Blueprint $table) {
            $table->dropForeign(['replaces_service_id']);
            $table->dropColumn(['is_optional', 'quantity_rule', 'quantity_rule_value', 'event_type_names', 'replaces_service_id', 'sort_order', 'display_quantity']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('selection_group');
        });
    }
};
