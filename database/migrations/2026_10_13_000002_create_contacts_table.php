<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuidv7()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
