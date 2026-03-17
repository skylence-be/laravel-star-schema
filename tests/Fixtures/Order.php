<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

final class Order extends Model
{
    public $timestamps = false;

    protected $table = 'orders';

    protected $guarded = [];
}
