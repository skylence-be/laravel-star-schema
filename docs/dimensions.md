# Defining Dimensions

Dimensions provide descriptive context for your facts — they answer *who*, *what*, *where*, and *when*.

## Creating a Dimension

Implement the `DimensionDefinition` interface:

```php
namespace App\StarSchema\Dimensions;

use App\Models\Customer;
use Skylence\StarSchema\Contracts\DimensionDefinition;
use Skylence\StarSchema\Enums\ScdType;

class CustomerDimension implements DimensionDefinition
{
    public function name(): string
    {
        return 'customer';
    }

    public function sourceModel(): string
    {
        return Customer::class;
    }

    public function table(): string
    {
        return 'dim_customers';
    }

    public function attributes(): array
    {
        return [
            'name'    => 'Customer Name',
            'email'   => 'Email Address',
            'country' => 'Country',
            'segment' => 'Customer Segment',
            'tier'    => 'Pricing Tier',
        ];
    }

    public function hierarchies(): array
    {
        return [
            'country' => ['region', 'city'],
        ];
    }

    public function scdTypes(): array
    {
        return [
            'name'    => ScdType::Overwrite->value,  // Update in place
            'email'   => ScdType::Overwrite->value,
            'country' => ScdType::Overwrite->value,
            'segment' => ScdType::Overwrite->value,
            'tier'    => ScdType::Fixed->value,       // Never changes
        ];
    }
}
```

Register it in `config/star-schema.php`:

```php
'dimensions' => [
    App\StarSchema\Dimensions\CustomerDimension::class,
    App\StarSchema\Dimensions\ProductDimension::class,
],
```

## Slowly Changing Dimensions (SCD)

The package supports Kimball's SCD classification:

| Type | Enum | Behavior |
|---|---|---|
| **Type 0 — Fixed** | `ScdType::Fixed` | Attribute never changes after initial load |
| **Type 1 — Overwrite** | `ScdType::Overwrite` | Update in place, no history kept |
| **Type 2 — Historical** | `ScdType::Historical` | Track changes with effective date ranges (planned) |

When syncing dimensions, the `SyncDimension` action uses `scdTypes()` to determine which columns to update on conflict:

- **Fixed** columns are set on insert but never updated
- **Overwrite** columns are upserted (updated if the row already exists)

```php
public function scdTypes(): array
{
    return [
        'name'         => ScdType::Overwrite->value,
        'date_of_birth' => ScdType::Fixed->value,
    ];
}
```

## Syncing Dimensions

Sync all dimensions from their source models:

```bash
php artisan star-schema:sync-dimensions
```

Sync a specific dimension:

```bash
php artisan star-schema:sync-dimensions --dimension=customer
```

The sync process:

1. Reads all records from the source model in chunks of 1,000
2. Selects only the `id` + configured attribute columns
3. Upserts into the dimension table
4. Only updates columns marked as `ScdType::Overwrite`

## Using the DefinesDimension Trait

For simpler cases, add the `DefinesDimension` trait directly to your Eloquent model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Skylence\StarSchema\Concerns\DefinesDimension;

class Product extends Model
{
    use DefinesDimension;

    public function dimensionAttributes(): array
    {
        return [
            'name'     => 'Product Name',
            'sku'      => 'SKU',
            'category' => 'Category',
        ];
    }

    public function dimensionHierarchies(): array
    {
        return [
            'category' => ['subcategory'],
        ];
    }
}
```

## Hierarchies

Hierarchies define drill-down paths within a dimension. For example, a geography dimension might have:

```
Country → Region → City
```

Defined as:

```php
public function hierarchies(): array
{
    return [
        'country' => ['region', 'city'],
    ];
}
```

This metadata is available for query builders and reporting tools to offer drill-down navigation.

## Linking Dimensions to Facts

In your `FactDefinition`, reference dimensions by their foreign key column:

```php
public function dimensions(): array
{
    return [
        'customer_id' => CustomerDimension::class,
        'product_id'  => ProductDimension::class,
    ];
}
```

The foreign key columns are automatically included when aggregating facts into snapshots, and can be used for grouping in queries:

```php
StarQuery::fact('sales_orders')
    ->between($from, $to)
    ->perMonth()
    ->groupBy('customer_id')
    ->sum('total_amount');
```
