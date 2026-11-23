<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->foreignId('manager_id')->constrained();
            $table->timestampTz('starts_at', 6);
            $table->timestampTz('ends_at', 6);
            $table->timestampTz('original_starts_at', 6);
            $table->string('status')->default('scheduled');
            $table->timestampsTz(6);
            $table->unique(['id', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
