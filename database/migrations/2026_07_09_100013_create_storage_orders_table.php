<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.13 storage_orders
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('ordered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('gb');
            $table->integer('unit_price_cents'); // salinan kadar semasa
            $table->integer('amount_cents');
            $table->integer('period_months')->default(12); // 0 = kekal
            $table->string('status')->default('menunggu_bayaran'); // menunggu_bayaran/dibayar/dibatalkan
            $table->string('invoice_no')->unique(); // INV-{YYYY}-{0001}
            $table->string('invoice_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['mosque_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_orders');
    }
};
