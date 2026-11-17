<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('venue_configuration_id')->nullable()->constrained()->nullOnDelete();
            $table->date('event_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('adult_count')->default(0);
            $table->unsignedSmallInteger('child_count')->default(0);
            $table->enum('status', ['pending_advance', 'reserved', 'in_preparation', 'completed', 'cancelled', 'released'])->default('pending_advance');
            $table->dateTime('hold_expires_at')->nullable()->comment('Vence a los 15 días si no se paga adelanto');
            $table->dateTime('advance_paid_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('active_event_date')->storedAs("CASE WHEN status IN ('cancelled','released','completed') THEN NULL ELSE event_date END");
            $table->unique('active_event_date', 'events_one_active_reservation_per_date');
            $table->index(['status', 'event_date']);
            $table->index(['status', 'hold_expires_at', 'advance_paid_at']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('version_number')->default(1);
            $table->enum('status', ['borrador', 'vigente', 'reemplazado', 'cancelado'])->default('borrador');
            $table->json('terms_snapshot');
            $table->decimal('total_amount', 12, 2);
            $table->string('document_path', 500)->nullable();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['event_id', 'version_number']);
        });

        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pendiente', 'parcial', 'pagada', 'renegociada', 'cancelada'])->default('pendiente');
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['due_date', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('payment_type', ['adelanto', 'cuota', 'saldo', 'otro']);
            $table->decimal('amount', 12, 2);
            $table->char('currency_code', 3);
            $table->string('method', 40)->comment('Método real a configurar');
            $table->string('receipt_path', 500)->nullable();
            $table->dateTime('paid_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['registrado', 'anulado', 'reversado'])->default('registrado');
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->index(['event_id', 'paid_at']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->enum('status', ['solicitada', 'aprobada', 'rechazada', 'pagada'])->default('solicitada');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('refunded_at')->nullable();
            $table->string('receipt_path', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('service_type', 120);
            $table->string('contact_name', 160)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('name');
        });

        Schema::create('supplier_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('item_or_service', 200);
            $table->text('specification')->nullable()->comment('Color, cantidades, modelos, letras iluminadas, etc.');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->dateTime('order_deadline')->nullable();
            $table->dateTime('delivery_at')->nullable();
            $table->string('work_schedule', 250)->nullable();
            $table->enum('status', ['pendiente', 'solicitado', 'confirmado', 'entregado', 'cancelado'])->default('pendiente');
            $table->text('change_notes')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['order_deadline', 'status']);
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 160);
            $table->string('function_name', 120)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('staff_id')->constrained()->restrictOnDelete();
            $table->string('event_role', 120);
            $table->string('notes', 500)->nullable();
            $table->enum('status', ['propuesto', 'confirmado', 'reemplazado', 'cancelado', 'completado'])->default('propuesto');
            $table->foreignId('replacement_for_assignment_id')->nullable()->constrained('staff_assignments')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('event_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->enum('task_type', ['pedido_proveedor', 'especificacion', 'preparacion', 'cambio_cliente', 'otro']);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('due_at')->nullable();
            $table->enum('status', ['pendiente', 'en_proceso', 'completada', 'cancelada'])->default('pendiente');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['due_at', 'status']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 120);
            $table->json('setting_value');
            $table->dateTime('valid_from');
            $table->dateTime('valid_until')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['setting_key', 'valid_from', 'valid_until'], 'system_settings_validity_index');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['create', 'update', 'soft_delete', 'restore', 'status_change', 'price_adjustment', 'login', 'other']);
            $table->string('entity_type', 120);
            $table->unsignedBigInteger('entity_id');
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('reason', 500)->nullable();
            $table->dateTime('occurred_at');
            $table->index(['entity_type', 'entity_id', 'occurred_at'], 'audit_entity_index');
            $table->index(['user_id', 'occurred_at'], 'audit_user_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('event_tasks');
        Schema::dropIfExists('staff_assignments');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('supplier_assignments');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('installments');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('events');
    }
};
