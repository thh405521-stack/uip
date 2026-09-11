<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The full platform's dashboard reads from ~8 tables (universities,
 * faculties, departments, students, projects, categories, analytics
 * events...). This standalone build keeps just the two tables that
 * actually drive every chart on the Data Analysis dashboard:
 * universities (for the "verified universities" KPI + leaderboard) and
 * projects (for growth/category/trend charts). Everything the dashboard
 * shows is computed live from real rows in these tables — nothing is
 * hardcoded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('verification_status')->default('pending'); // pending | verified
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('category');
            $table->string('status')->default('pending'); // pending | published | rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('universities');
    }
};
