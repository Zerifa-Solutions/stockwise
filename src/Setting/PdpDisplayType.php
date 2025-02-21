<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Setting;

enum PdpDisplayType: string
{
    use EnumTrait;

    case CROSSSELLING = 'crossselling';

    case SECTION = 'section';
}
