<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('role')->nullable()->comment('Papel estratégico da categoria (B2B, B2C, Funil)');
            $table->text('role_description')->nullable()->comment('Descrição em PT-BR do papel para consulta interna');
            $table->string('funnel_stage', 10)
                ->default('TOFU')
                ->comment('Estágio do funil: TOFU, MOFU ou BOFU');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('funnel_stage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
