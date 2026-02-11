<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holding_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('produksi_batch_id');
            $table->unsignedInteger('hold_no')->default(1);

            $table->string('holding_stage', 50)->nullable();
            $table->string('holding_reason', 191)->nullable();
            $table->text('holding_note')->nullable();

            $table->dateTime('held_at')->nullable();
            $table->unsignedBigInteger('held_by')->nullable();

            // outcome: null = OPEN, RELEASE/REJECT = closed
            $table->string('outcome', 20)->nullable(); // RELEASE | REJECT
            $table->string('return_to', 50)->nullable();

            $table->string('resolve_reason', 191)->nullable();
            $table->text('resolve_note')->nullable();

            $table->dateTime('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();

            $table->unsignedInteger('duration_seconds')->nullable(); // durasi session hold

            $table->timestamps();

            $table->index(['produksi_batch_id', 'resolved_at']);
            $table->index(['produksi_batch_id', 'hold_no']);

            $table->foreign('produksi_batch_id')
                ->references('id')->on('produksi_batches')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holding_logs');
    }
};
