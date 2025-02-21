<?php

declare(strict_types=1);

namespace Zerifa\StockWise\Setting;

enum Config: string
{
    use EnumTrait;

    case ENABLE_CART_QUANTITY_ADJUSTMENT = 'ZerStockWise.config.enableCartQuantityAdjustment';

    case MAX_RECOMMENDATIONS = 'ZerStockWise.config.maxRecommendations';

    case VARIANCE_PERCENTAGE = 'ZerStockWise.config.variancePercentage';

    case ENABLE_CATEGORY_MATCHING = 'ZerStockWise.config.enableCategoryMatching';

    case ENABLE_MANUFACTURER_MATCHING = 'ZerStockWise.config.enableManufacturerMatching';

    case ENABLE_PROPERTY_MATCHING = 'ZerStockWise.config.enablePropertyMatching';

    case ENABLE_CUSTOM_FIELD_MATCHING = 'ZerStockWise.config.enableCustomFieldMatching';

    case ENABLE_TAG_MATCHING = 'ZerStockWise.config.enableTagMatching';

    case PDP_DISPLAY_TYPE = 'ZerStockWise.config.pdpDisplayType';
}
