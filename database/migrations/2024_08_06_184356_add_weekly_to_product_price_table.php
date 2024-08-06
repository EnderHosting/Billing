<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeeklyToProductPriceTable extends Migration
{
    public function up()
    {
        Schema::table('product_price', function (Blueprint $table) {
            $table->decimal('weekly', 8, 2)->nullable()->after('type');  // Añadir columna 'weekly'
            $table->decimal('weekly_setup', 8, 2)->nullable()->after('weekly');  // Añadir columna 'weekly_setup'
        });
    }

    public function down()
    {
        Schema::table('product_price', function (Blueprint $table) {
            $table->dropColumn('weekly');
            $table->dropColumn('weekly_setup');
        });
    }
}