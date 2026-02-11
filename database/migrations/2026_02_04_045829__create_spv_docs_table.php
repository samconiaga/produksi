<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpvDocsTable extends Migration
{
    public function up()
    {
        Schema::create('spv_docs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('doc_no', 64)->index();
            $table->date('doc_date')->nullable();
            $table->string('status', 32)->default('PENDING')->index(); // PENDING / APPROVED / REJECTED
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable()->index();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('spv_docs');
    }
}
