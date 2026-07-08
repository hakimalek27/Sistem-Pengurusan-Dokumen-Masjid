<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.6 registry_files (+mosque_id)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registry_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('classification_node_id')->constrained('classification_nodes')->cascadeOnDelete();
            $table->integer('transaction_no');
            $table->integer('volume')->default(1);
            $table->string('file_no'); // dijana §5.15
            $table->string('title');
            $table->string('sensitivity');
            $table->string('status')->default('terbuka'); // terbuka / tutup
            $table->integer('enclosure_count')->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['mosque_id', 'file_no']);
            $table->unique(['mosque_id', 'classification_node_id', 'transaction_no', 'volume'], 'reg_files_tenant_node_txn_vol_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_files');
    }
};
