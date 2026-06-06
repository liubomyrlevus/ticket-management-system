<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            // Прив'язка до тікета
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            // Прив'язка до автора
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Текст коментаря
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
