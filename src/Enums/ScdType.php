<?php

declare(strict_types=1);

namespace Skylence\StarSchema\Enums;

/**
 * Slowly Changing Dimension types (Kimball classification).
 */
enum ScdType: int
{
    /** Fixed attribute — never changes. */
    case Fixed = 0;

    /** Overwrite — update in place, no history. */
    case Overwrite = 1;

    /** Historical — track changes with effective date ranges. */
    case Historical = 2;
}
