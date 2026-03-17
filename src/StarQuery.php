<?php

declare(strict_types=1);

namespace Skylence\StarSchema;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Skylence\StarSchema\Adapters\DateAdapter;
use Skylence\StarSchema\Adapters\MySqlAdapter;
use Skylence\StarSchema\Adapters\PgsqlAdapter;
use Skylence\StarSchema\Adapters\SqliteAdapter;
use Skylence\StarSchema\Contracts\FactDefinition;
use Skylence\StarSchema\Enums\AggregationType;
use Skylence\StarSchema\Enums\Range;
use Skylence\StarSchema\Enums\TimeGrain;
use Skylence\StarSchema\Services\StarSchemaRegistry;

final class StarQuery
{
    private CarbonImmutable $from;

    private CarbonImmutable $to;

    private TimeGrain $grain = TimeGrain::Daily;

    /** @var array<string, mixed> */
    private array $filters = [];

    /** @var array<string> */
    private array $groupByDimensions = [];

    private bool $fillGaps = true;

    private function __construct(private readonly FactDefinition $fact) {}

    public static function fact(FactDefinition|string $fact): self
    {
        if (is_string($fact)) {
            $fact = app(StarSchemaRegistry::class)->fact($fact);
        }

        return new self($fact);
    }

    public static function range(FactDefinition|string $fact, Range $range, ?CarbonImmutable $now = null): self
    {
        [$from, $to] = $range->dates($now);

        return self::fact($fact)->between($from, $to);
    }

    public static function adapterFor(string $driver): DateAdapter
    {
        return match ($driver) {
            'pgsql' => new PgsqlAdapter,
            'sqlite' => new SqliteAdapter,
            default => new MySqlAdapter,
        };
    }

    public function between(CarbonImmutable $from, CarbonImmutable $to): self
    {
        $this->from = $from;
        $this->to = $to;

        return $this;
    }

    public function perDay(): self
    {
        $this->grain = TimeGrain::Daily;

        return $this;
    }

    public function perWeek(): self
    {
        $this->grain = TimeGrain::Weekly;

        return $this;
    }

    public function perMonth(): self
    {
        $this->grain = TimeGrain::Monthly;

        return $this;
    }

    public function perQuarter(): self
    {
        $this->grain = TimeGrain::Quarterly;

        return $this;
    }

    public function perYear(): self
    {
        $this->grain = TimeGrain::Yearly;

        return $this;
    }

    public function grain(TimeGrain $grain): self
    {
        $this->grain = $grain;

        return $this;
    }

    /**
     * @param  mixed  $value  Single value or array of values
     */
    public function where(string $column, mixed $value): self
    {
        $this->filters[$column] = $value;

        return $this;
    }

    public function groupBy(string ...$dimensions): self
    {
        $this->groupByDimensions = array_merge($this->groupByDimensions, $dimensions);

        return $this;
    }

    public function withoutGapFilling(): self
    {
        $this->fillGaps = false;

        return $this;
    }

    /**
     * @return Collection<int, TrendValue>
     */
    public function sum(string $measure): Collection
    {
        return $this->aggregate($measure, AggregationType::Sum);
    }

    /**
     * @return Collection<int, TrendValue>
     */
    public function avg(string $measure): Collection
    {
        return $this->aggregate($measure, AggregationType::Avg);
    }

    /**
     * @return Collection<int, TrendValue>
     */
    public function count(string $measure = '*'): Collection
    {
        return $this->aggregate($measure, AggregationType::Count);
    }

    /**
     * @return Collection<int, TrendValue>
     */
    public function min(string $measure): Collection
    {
        return $this->aggregate($measure, AggregationType::Min);
    }

    /**
     * @return Collection<int, TrendValue>
     */
    public function max(string $measure): Collection
    {
        return $this->aggregate($measure, AggregationType::Max);
    }

    /**
     * @return Collection<int, TrendValue>
     */
    public function aggregate(string $measure, AggregationType $type): Collection
    {
        $query = $this->fact->query();
        $driver = $query->getConnection()->getDriverName();
        $adapter = self::adapterFor($driver);
        $dateColumn = $this->fact->dateColumn();

        $truncExpr = $adapter->truncate($dateColumn, $this->grain);

        $query->whereBetween($dateColumn, [$this->from->toDateString(), $this->to->toDateString()]);

        foreach ($this->filters as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        $selects = [DB::raw($truncExpr.' as period')];
        $groups = [DB::raw($truncExpr)];

        foreach ($this->groupByDimensions as $dim) {
            $selects[] = $dim;
            $groups[] = $dim;
        }

        $selects[] = DB::raw($type->expression($measure).' as value');

        $results = $query->select($selects)->groupBy($groups)->get();

        $format = $adapter->carbonFormat($this->grain);

        $mapped = $results->mapWithKeys(fn ($row): array => [
            $row->period => new TrendValue(
                date: $row->period,
                value: $row->value ?? 0,
            ),
        ]);

        if (! $this->fillGaps || $this->groupByDimensions !== []) {
            return $mapped->values();
        }

        return $this->fillGaps($mapped, $format);
    }

    /**
     * Calculate growth rate comparing current period to previous period.
     *
     * @return array{current: float|int, previous: float|int, growth: float|null}
     */
    public function growthRate(string $measure, AggregationType $type = AggregationType::Sum): array
    {
        $currentTotal = $this->scalar($measure, $type);

        $days = $this->from->diffInDays($this->to);
        $prevFrom = $this->from->subDays($days + 1);
        $prevTo = $this->from->subDay();

        $prevQuery = self::fact($this->fact)
            ->between($prevFrom, $prevTo)
            ->grain($this->grain);

        foreach ($this->filters as $column => $value) {
            $prevQuery->where($column, $value);
        }

        $previousTotal = $prevQuery->scalar($measure, $type);

        $growth = $previousTotal !== 0
            ? round(($currentTotal - $previousTotal) / $previousTotal * 100, 2)
            : null;

        return [
            'current' => $currentTotal,
            'previous' => $previousTotal,
            'growth' => $growth,
        ];
    }

    /**
     * Get a single aggregated value (no time grouping).
     */
    public function scalar(string $measure, AggregationType $type = AggregationType::Sum): float|int
    {
        $query = $this->fact->query();
        $dateColumn = $this->fact->dateColumn();

        $query->whereBetween($dateColumn, [$this->from->toDateString(), $this->to->toDateString()]);

        foreach ($this->filters as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query->selectRaw($type->expression($measure).' as value')->value('value') ?? 0;
    }

    /**
     * @param  Collection<string, TrendValue>  $data
     * @return Collection<int, TrendValue>
     */
    private function fillGaps(Collection $data, string $format): Collection
    {
        $period = $this->carbonPeriod();

        return collect($period)->map(function (CarbonInterface $date) use ($data, $format) {
            $key = $date->format($format);

            return $data->get($key, new TrendValue(date: $key, value: 0));
        });
    }

    private function carbonPeriod(): CarbonPeriod
    {
        return match ($this->grain) {
            TimeGrain::Daily => CarbonPeriod::create($this->from->startOfDay(), '1 day', $this->to->startOfDay()),
            TimeGrain::Weekly => CarbonPeriod::create($this->from->startOfWeek(), '1 week', $this->to->startOfDay()),
            TimeGrain::Monthly => CarbonPeriod::create($this->from->startOfMonth(), '1 month', $this->to->startOfMonth()),
            TimeGrain::Quarterly => CarbonPeriod::create($this->from->startOfQuarter(), '3 months', $this->to->startOfQuarter()),
            TimeGrain::Yearly => CarbonPeriod::create($this->from->startOfYear(), '1 year', $this->to->startOfYear()),
        };
    }
}
