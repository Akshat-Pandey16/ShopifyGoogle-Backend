<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spreadsheet_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('spreadsheet_id');
            $table->string('spreadsheet_name');
            $table->timestamps();
        });

        Schema::table('spreadsheet_data', function (Blueprint $table) {
            $table->unique(['spreadsheet_name', 'spreadsheet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_data');
    }
};
