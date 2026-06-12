<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Categorías de tickets
        Schema::create('help_topic', function (Blueprint $table) {
            $table->id('topic_id');
            $table->string('topic');
            $table->unsignedInteger('sla_id')->default(1);
            $table->unsignedInteger('priority_id')->default(1);
            $table->timestamps();
        });

        // Tickets (timestamps con nombre osTicket: created / updated)
        Schema::create('ticket', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->string('number', 20)->unique();
            $table->unsignedInteger('topic_id')->default(16);
            $table->unsignedInteger('sla_id')->default(1);
            $table->unsignedTinyInteger('status_id')->default(1);
            $table->timestamp('created')->useCurrent();
            $table->timestamp('updated')->useCurrent()->useCurrentOnUpdate();
        });

        // Hilo de conversación del ticket
        Schema::create('thread', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('object_id');
            $table->char('object_type', 1)->default('T');
            $table->timestamps();
        });

        // Entradas del hilo (body del ticket)
        Schema::create('thread_entry', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id');
            $table->char('type', 1)->default('M');
            $table->text('body')->nullable();
            $table->timestamps();
        });

        // Prioridad del ticket (sin timestamps — el modelo lo desactiva)
        Schema::create('ticket__cdata', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->primary();
            $table->unsignedInteger('priority')->default(1);
        });

        // Formularios asociados al ticket
        Schema::create('form_entry', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('object_id')->nullable();
            $table->char('object_type', 1)->default('T');
        });

        Schema::create('form_entry_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entry_id');
            $table->unsignedInteger('value_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_entry_values');
        Schema::dropIfExists('form_entry');
        Schema::dropIfExists('ticket__cdata');
        Schema::dropIfExists('thread_entry');
        Schema::dropIfExists('thread');
        Schema::dropIfExists('ticket');
        Schema::dropIfExists('help_topic');
    }
};
