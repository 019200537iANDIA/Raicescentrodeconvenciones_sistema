<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_services', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropForeign(['service_id']);
            $table->dropPrimary();
            $table->id()->first();
            $table->unique(['package_id', 'service_id']);
            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('package_services', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropForeign(['service_id']);
            $table->dropUnique(['package_id', 'service_id']);
            $table->dropColumn('id');
            $table->primary(['package_id', 'service_id']);
            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
            $table->foreign('service_id')->references('id')->on('services')->restrictOnDelete();
        });
    }
};
