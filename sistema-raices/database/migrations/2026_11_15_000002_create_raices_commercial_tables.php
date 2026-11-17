<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 180);
            $table->string('mobile', 30)->index();
            $table->text('dni_encrypted')->nullable()->comment('Cifrar desde Laravel; no guardar DNI plano');
            $table->text('address_encrypted')->nullable()->comment('Cifrar desde Laravel');
            $table->string('district', 120)->nullable();
            $table->enum('client_type', ['nuevo', 'recurrente'])->default('nuevo');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('full_name');
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 120);
            $table->enum('venue_type', ['area_verde', 'salon_principal', 'medio_salon', 'mezanine', 'escenario', 'otro']);
            $table->unsignedInteger('capacity')->nullable();
            $table->text('access_restrictions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('venue_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 140)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('venue_configuration_items', function (Blueprint $table) {
            $table->foreignId('venue_configuration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->primary(['venue_configuration_id', 'venue_id']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('category', 100);
            $table->text('description')->nullable();
            $table->string('unit_label', 50)->default('unidad');
            $table->boolean('is_optional')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->enum('name', ['basico', 'estandar', 'premium'])->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('package_services', function (Blueprint $table) {
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->decimal('default_quantity', 10, 2)->default(1);
            $table->string('notes', 500)->nullable();
            $table->primary(['package_id', 'service_id']);
        });

        Schema::create('service_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->char('currency_code', 3)->comment('Código ISO; definir moneda antes de cargar tarifas');
            $table->decimal('amount', 12, 2);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['service_id', 'valid_from', 'valid_until'], 'service_rates_validity_index');
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->enum('rule_type', ['temporada', 'dia_semana', 'horario', 'cantidad_personas', 'grupo_edad', 'residencia', 'promocion', 'otro']);
            $table->json('criteria');
            $table->enum('adjustment_type', ['percentage', 'fixed_amount']);
            $table->decimal('adjustment_value', 8, 2);
            $table->smallInteger('priority')->default(100);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'rule_type', 'priority'], 'pricing_rules_lookup_index');
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('event_type_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source_channel', ['whatsapp', 'facebook', 'instagram', 'tiktok', 'llamada', 'presencial', 'otro']);
            $table->date('event_date_requested')->nullable();
            $table->decimal('budget_reference', 12, 2)->nullable();
            $table->text('summary')->nullable();
            $table->enum('status', ['nueva', 'en_atencion', 'cotizada', 'reservada', 'ganada', 'perdida', 'cerrada'])->default('nueva');
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['status', 'created_at']);
            $table->index('event_date_requested');
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->string('quote_number', 40)->unique();
            $table->foreignId('event_type_id')->nullable()->constrained()->nullOnDelete();
            $table->date('event_date');
            $table->foreignId('venue_configuration_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('adult_count')->default(0);
            $table->unsignedSmallInteger('child_count')->default(0);
            $table->enum('quote_status', ['borrador', 'enviada', 'aceptada', 'rechazada', 'vencida', 'convertida', 'cancelada'])->default('borrador');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('event_date');
            $table->index('quote_status');
        });

        Schema::create('quote_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500)->nullable();
            $table->char('currency_code', 3);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('price_per_adult', 12, 2)->nullable();
            $table->decimal('price_per_child', 12, 2)->nullable();
            $table->decimal('advance_percent', 5, 2);
            $table->decimal('advance_amount', 12, 2);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('valid_until')->comment('15 días desde emisión en V1');
            $table->dateTime('customer_accepted_at')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
            $table->unique(['quote_id', 'version_number']);
            $table->index(['valid_until', 'is_current']);
        });

        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description_snapshot', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('internal_unit_price', 12, 2);
            $table->decimal('line_discount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->boolean('display_quantity_to_customer')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['quote_version_id', 'sort_order']);
        });

        Schema::create('quote_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('pricing_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('adjustment_type', ['descuento', 'recargo', 'correccion']);
            $table->string('reason', 500);
            $table->decimal('amount', 12, 2);
            $table->foreignId('authorized_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('applied_at');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('occurred_at');
            $table->string('channel', 40);
            $table->enum('outcome', ['sin_respuesta', 'pendiente', 'interesado', 'acepta', 'rechaza', 'solicita_cambios', 'otro']);
            $table->text('notes')->nullable();
            $table->string('next_action', 255)->nullable();
            $table->dateTime('next_action_at')->nullable();
            $table->dateTime('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->index(['next_action_at', 'reminder_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
        Schema::dropIfExists('quote_adjustments');
        Schema::dropIfExists('quote_lines');
        Schema::dropIfExists('quote_versions');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('service_rates');
        Schema::dropIfExists('package_services');
        Schema::dropIfExists('packages');
        Schema::dropIfExists('services');
        Schema::dropIfExists('venue_configuration_items');
        Schema::dropIfExists('venue_configurations');
        Schema::dropIfExists('venues');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('event_types');
    }
};
