<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending_payment')->index();

            // Recipient details
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('destination_address');
            $table->string('destination_postal_code')->nullable();
            $table->string('destination_area_id');
            $table->string('destination_area_name')->nullable();

            // Package details
            $table->integer('weight')->default(1000);
            $table->text('item_description')->nullable();
            $table->string('item_category')->nullable();
            $table->unsignedBigInteger('item_value')->default(0);

            // Courier selection
            $table->string('courier_code');
            $table->string('courier_service_code');
            $table->string('courier_service_name');
            $table->unsignedBigInteger('shipping_fee');
            $table->unsignedBigInteger('total_amount');
            $table->string('estimated_delivery')->nullable();

            // Biteship tracking & dispatch
            $table->string('biteship_order_id')->nullable()->index();
            $table->string('waybill_number')->nullable()->index();
            $table->json('tracking_history')->nullable();

            // Midtrans payments
            $table->string('snap_token')->nullable();
            $table->string('payment_method')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
