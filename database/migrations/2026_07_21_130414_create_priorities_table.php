<?php

use App\Enums\Project\ErasmusPriority;
use App\Models\Project\ErasmusPriority as ErasmusPriorityModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        foreach (ErasmusPriority::cases() as $priority) {
            ErasmusPriorityModel::create(['name' => $priority->getLabel()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('priorities');
    }
};
