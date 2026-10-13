<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('uuidv7()'));
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->uuid('contact_id')->nullable();
            $table->string('title');
            $table->timestamps();

            $table->unique(['tenant_id', 'id']);

            // A project and its contact share tenant_id, so a contact from another studio cannot be attached.
            $table->foreign(['tenant_id', 'contact_id'])
                ->references(['tenant_id', 'id'])
                ->on('contacts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
