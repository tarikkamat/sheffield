<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('strength')->default(70);
            $table->unsignedTinyInteger('attack_power')->default(70);
            $table->unsignedTinyInteger('defense_power')->default(70);
            $table->unsignedTinyInteger('goalkeeper_power')->default(70);
            $table->unsignedTinyInteger('supporter_power')->default(70);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
