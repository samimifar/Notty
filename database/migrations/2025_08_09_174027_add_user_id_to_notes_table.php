<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
    Schema::table('notes', function (Blueprint $table) {
        $table->foreignId('user_id')->after('id')->constrained('users')->cascadeOnDelete();
        $table->unique(['user_id','date']);
    });
}
public function down(): void {
    Schema::table('notes', function (Blueprint $table) {
        $table->dropUnique(['notes_user_id_date_unique']);
        $table->dropConstrainedForeignId('user_id');
    });
}
   
};
