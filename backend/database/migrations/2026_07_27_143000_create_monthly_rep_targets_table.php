<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_rep_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id');
            $table->string('rep_name');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->double('target');
            $table->timestamps();

            $table->unique(['erp_id', 'year', 'month']);
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_rep_targets');
    }
};
