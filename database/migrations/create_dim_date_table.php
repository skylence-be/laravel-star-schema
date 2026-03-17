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

        Schema::connection($connection)->create($prefix.'dim_date', function (Blueprint $table): void {
            $table->integer('date_key')->primary(); // YYYYMMDD format
            $table->date('date')->unique();
            $table->tinyInteger('day_of_week');
            $table->tinyInteger('day_of_month');
            $table->smallInteger('day_of_year');
            $table->string('day_name', 10);
            $table->tinyInteger('week_of_year');
            $table->tinyInteger('month');
            $table->string('month_name', 10);
            $table->tinyInteger('quarter');
            $table->smallInteger('year');
            $table->tinyInteger('fiscal_quarter');
            $table->smallInteger('fiscal_year');
            $table->boolean('is_weekend')->default(false);
            $table->boolean('is_holiday')->default(false);

            $table->index(['year', 'month']);
            $table->index(['year', 'quarter']);
            $table->index('fiscal_year');
        });
    }

    public function down(): void
    {
        $prefix = config('star-schema.table_prefix', 'star_');
        $connection = config('star-schema.connection');

        Schema::connection($connection)->dropIfExists($prefix.'dim_date');
    }
};
