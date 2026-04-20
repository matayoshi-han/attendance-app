<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestCorrectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rest_corrections', function (Blueprint $table) {
            $table->id();
            // どの修正申請に属するか
            $table->foreignId('attendance_correction_id')->constrained()->cascadeOnDelete();

            $table->timestamp('updated_break_start')->nullable();
            $table->timestamp('updated_break_end')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rest_corrections');
    }
}
