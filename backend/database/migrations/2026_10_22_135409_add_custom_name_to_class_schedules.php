<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->string('custom_name')->nullable()->after('subject_id');
            $table->unsignedBigInteger('subject_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('custom_name');
            $table->unsignedBigInteger('subject_id')->nullable(false)->change();
        });
    }
};
