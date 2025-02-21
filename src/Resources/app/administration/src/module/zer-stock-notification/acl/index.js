Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'marketing',
        key: 'zer_stock_notification',
        roles: {
            viewer: {
                privileges: [
                    'zer_stock_notification:read',
                    'sales_channel:read',
                    'product:read',
                    'customer:read',
                    'language:read'
                ],
                dependencies: []
            }
        }
    });
