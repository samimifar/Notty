<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // nullable تا رکوردهای قدیمی خطا ندن
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()   // users(id)
                ->nullOnDelete()  // حذف یوزر => user_id = null
                ->after('id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // برای نسخه‌های قدیمی لاراول:
            // $table->dropForeign(['user_id']);
            // $table->dropColumn('user_id');
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
