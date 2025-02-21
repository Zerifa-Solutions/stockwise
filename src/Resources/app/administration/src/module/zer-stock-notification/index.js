import './acl';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
import roRO from './snippet/ro-RO.json';

const {Module, Component} = Shopware;

Component.register('zer-stock-notification-list', () => import('./page/stock-notification-list'));

Module.register('zer-stock-notification', {
    type: 'plugin',
    name: 'zer-stock-notification',
    title: 'zer-stock-notification.general.mainMenuTitle',
    description: 'zer-stock-notification.general.descriptionTextModule',
    color: '#24e54b',
    icon: 'regular-bell',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
        'ro-RO': roRO
    },

    routes: {
        list: {
            component: 'zer-stock-notification-list',
            path: '',
            meta: {
                privilege: 'zer_stock_notification.viewer'
            }
        }
    },

    navigation: [
        {
            id: 'zer-stock-notification',
            label: 'zer-stock-notification.general.mainMenuTitle',
            color: '#24e54b',
            path: 'zer.stock.notification.list',
            icon: 'regular-bell',
            position: 100,
            parent: 'sw-marketing'
        }
    ],
});