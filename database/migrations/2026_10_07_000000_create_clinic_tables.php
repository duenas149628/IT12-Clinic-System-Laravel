<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table): void {
            $table->bigIncrements('patient_id');
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('patient_number', 20)->unique();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->date('birth_date')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('contact_number', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id', 'fk_patients_user')
                ->references('user_id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->bigIncrements('appointment_id');
            $table->unsignedBigInteger('patient_id');
            $table->date('preferred_date');
            $table->time('preferred_start_time');
            $table->time('preferred_end_time');
            $table->date('confirmed_date')->nullable();
            $table->time('confirmed_start_time')->nullable();
            $table->time('confirmed_end_time')->nullable();
            $table->string('reason', 255)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->index('patient_id', 'fk_appointments_patient');

            $table->foreign('patient_id', 'fk_appointments_patient')
                ->references('patient_id')->on('patients')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('visit_records', function (Blueprint $table): void {
            $table->bigIncrements('visit_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->unique();
            $table->date('visit_date');
            $table->text('chief_complaint')->nullable();
            $table->text('findings')->nullable();
            $table->text('treatment')->nullable();
            $table->text('notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->index('patient_id', 'fk_visit_records_patient');

            $table->foreign('appointment_id', 'fk_visit_records_appointment')
                ->references('appointment_id')->on('appointments')
                ->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('patient_id', 'fk_visit_records_patient')
                ->references('patient_id')->on('patients')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('appointment_reminders', function (Blueprint $table): void {
            $table->bigIncrements('reminder_id');
            $table->unsignedBigInteger('appointment_id');
            $table->date('reminder_date');
            $table->string('reminder_type', 32);
            $table->dateTime('created_at')->useCurrent();
            $table->unique(
                ['appointment_id', 'reminder_date', 'reminder_type'],
                'uq_appointment_reminder'
            );
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->bigIncrements('notification_id');
            $table->unsignedBigInteger('recipient_user_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('notification_type', 40);
            $table->string('title', 160);
            $table->string('body', 500);
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('read_at')->nullable();
            $table->index(
                ['recipient_user_id', 'read_at', 'created_at'],
                'idx_notifications_recipient_unread'
            );
            $table->index('appointment_id', 'idx_notifications_appointment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('appointment_reminders');
        Schema::dropIfExists('visit_records');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
    }
};
