<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_corrections', function (Blueprint $table) {
            $table->id();
            // 元の勤怠データへの紐付け
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // 修正後の時間（日付も含めて保存できるようtimestamp型）
            $table->timestamp('updated_clock_in')->nullable();
            $table->timestamp('updated_clock_out')->nullable();

            $table->text('remarks'); // 備考（必須にするのが一般的）

            // 0: 承認待ち, 1: 承認済み, 2: 却下
            $table->integer('status')->default(0);

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
        Schema::dropIfExists('attendance_corrections');
    }
}
