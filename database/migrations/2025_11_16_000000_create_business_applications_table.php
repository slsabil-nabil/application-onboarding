<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // لو الجدول موجود (في نظام قديم) لا تعيد إنشائه
        if (Schema::hasTable('business_applications')) {
            return;
        }

        Schema::create('business_applications', function (Blueprint $table) {
            $table->id();

            $table->string('business_name');
            $table->string('industry_type')->nullable();
            $table->string('address')->nullable();

            $table->string('owner_name');
            $table->string('owner_email');
            $table->string('owner_phone', 40)->nullable();

            $table->string('license_path')->nullable();

            $table->string('status')->default('pending');

            $table->uuid('resubmit_token')->nullable()->index();
            $table->timestamp('resubmit_expires_at')->nullable();

            $table->text('rejection_reason')->nullable();

            $table->json('licenses_paths')->nullable();
            $table->json('supporting_documents_paths')->nullable();
            $table->json('form_data')->nullable();

            // حقول الاستيفاء
            $table->string('interpolation')->nullable(); // مثل: pending / completed ...
            $table->json('interpolation_required_docs')->nullable();
            $table->text('interpolation_note')->nullable();
            $table->json('interpolation_contact_corrections')->nullable();
            $table->json('interpolation_uploaded_files')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_applications');
    }
};
