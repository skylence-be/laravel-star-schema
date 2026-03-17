<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('star-schema.table_prefix', 'star_');
        $connection = config('star-schema.connection');

        Schema::connection($connection)->create($prefix.'fact_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('fact_name');
            $table->string('grain', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->json('measures');
            $table->json('dimensions');
            $table->timestamp('aggregated_at');

            $table->index(['fact_name', 'grain', 'period_start']);
            $table->index('aggregated_at');
        });
    }

    public function down(): void
    {
        $prefix = config('star-schema.table_prefix', 'star_');
        $connection = config('star-schema.connection');

        Schema::connection($connection)->dropIfExists($prefix.'fact_snapshots');
    }
};
