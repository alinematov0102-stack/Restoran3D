<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToTaomsTable extends Migration
{
    public function up()
    {
        Schema::table('taoms', function (Blueprint $table) {
            if (!Schema::hasColumn('taoms', 'kategoriya')) $table->string('kategoriya')->nullable();
            if (!Schema::hasColumn('taoms', 'tavsif')) $table->text('tavsif')->nullable();
            if (!Schema::hasColumn('taoms', 'rasm')) $table->string('rasm')->nullable();
        });
    }

    public function down()
    {
        Schema::table('taoms', function (Blueprint $table) {
            foreach (['kategoriya', 'tavsif', 'rasm'] as $column) {
                if (Schema::hasColumn('taoms', $column)) $table->dropColumn($column);
            }
        });
    }
}
