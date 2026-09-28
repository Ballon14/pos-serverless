<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('harga_grosir', 15, 2)->default(0)->after('harga_jual');
            $table->unsignedInteger('minimal_grosir')->default(0)->after('harga_grosir');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['harga_grosir', 'minimal_grosir']);
        });
    }
};
