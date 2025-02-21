# Zerifa - StockWise Plugin

## Overview

Zerifa's **StockWise Plugin** enhances your Shopware 6 store's shopping experience by providing
back-in-stock notifications and intelligent product alternatives for out of stock products to Product detail page / Offcanvas / Cart pages.

## Scope & Features

### Stock Notifications

- Back-in-stock notification system for customers
- Email notifications when products are available
- Admin panel for managing notifications
- Customer account section for managing subscriptions
- Multi-language support (EN, DE, RO)

### Smart Cart Management

- Automatic cart quantity adjustment based on available stock
- Configurable through admin settings
- Clear customer notifications for quantity adjustments
- Out-of-stock product handling in checkout
- Partial-Checkout support for mixed stock status carts
- Automatic back-in-stock notifications for out-of-stock items during split orders

### Alternative Products

- Smart product recommendations for out-of-stock items
- Configurable matching criteria:
  - Category matching
  - Manufacturer matching
  - Property matching
  - Custom field matching
  - Tag matching
- Price variance control
- Maximum recommendations limit
- Display options for product detail page

### Stock Status Display

- Enhanced stock status indicators
- Customizable low stock threshold
- Visual indicators for:
  - In stock
  - Low stock
  - Out of stock

## Administration

### Stock Notification Management

- Access through **Marketing > Stock Notifications**:
  - Overview of all Back-in-stock notifications
  - Product, Customer and current Status of the notification

## Installation

1. **Download the Plugin**: Obtain the latest version of the Zerifa Low Stock Alerts & Product Alternatives Plugin from
   the [Shopware Store](https://store.shopware.com/).
2. **Upload to Shopware**:

- Navigate to the Shopware Administration panel.
- Go to **Settings > My Extensions > Apps** and click on **Upload extension**.
- Select the plugin ZIP file and upload it.

3. **Install & Activate the Plugin**: Locate **Low Stock Alerts & Product Alternatives**, click **Install** then **Activate**.
4. **Configure Settings**: Access the plugin settings to customize the alerts, matching criteria or display type according to your preferences.
5. **Clear Cache**: Clear the cache to apply the changes and enable the plugin's features.

## Configuration

### Plugin Settings

Navigate to **Settings > System > Plugins > Low Stock Alerts & Product Alternatives > Configure**:

- Default Low Stock Threshold: Set the minimum stock level that triggers low stock alerts (default: 5)
- Enable Cart Quantity Adjustment: Automatically adjust cart quantities to match available stock
- Maximum Alternative Products: Set the maximum number of alternative products to display (default: 6) 
- Price Variance Percentage: Control the allowed price difference for alternative products (default: 20%)
- Enable Category Matching: Match alternative products from the same category
- Enable Manufacturer Matching: Match alternative products from the same manufacturer
- Enable Property Matching: Match alternative products with similar properties
- Product Detail Page Display Type: Choose how alternative products are displayed:
  - CrossSelling: Display alternative products in the cross-selling section
  - New Section: Show alternatives in a dedicated section below product details

### Stock Notification Management

Access via **Marketing > Stock Notifications**:

- List the status of each notification

## Benefits

- **Enhanced Stock Management**: Real-time stock monitoring and automated alerts for better inventory control
- **Smart Cart Handling**: Automatic cart quantity adjustments to prevent overselling and improve customer experience
- **Alternative Products**: Intelligent product recommendations when items are out of stock to maintain sales
- **Customer Notifications**: Back-in-stock notification system to keep customers engaged and increase sales
- **Flexible Configuration**: Highly customizable settings for thresholds, matching criteria and display options
- **Multilingual Support**: Full integration in German, English and Romanian for international shops

## Support

For technical support and inquiries:

- Visit [Zerifa Solutions](https://zerifa.com)
- Contact our support team
- Check documentation

## License

This plugin is proprietary software and requires a valid license for use.

---

© 2025 Zerifa Solutions. All rights reserved.
