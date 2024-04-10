<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('auth_tokens', function (Blueprint $table) {
            $table->id('tokens_id')->autoIncrement();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->text('google_access_token')->nullable();
            $table->text('google_refresh_token')->nullable();
            $table->text('shopify_token')->nullable();

            $table->timestamps();
        });

        Schema::table('auth_tokens', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('auth_tokens');
    }
};
