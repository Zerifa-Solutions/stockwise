import template from './stock-notification-list.html.twig';

const {Mixin, Data: {Criteria}} = Shopware;

export default {
    template,

    inject: [
        'repositoryFactory',
        'filterFactory',
        'acl'
    ],

    mixins: [
        Mixin.getByName('listing'),
        Mixin.getByName('notification')
    ],

    data() {
        return {
            notifications: null,
            isLoading: false,
            total: 0,
            page: 1,
            limit: 25,
            sortBy: 'createdAt',
            sortDirection: 'DESC',
            naturalSorting: false
        };
    },

    computed: {
        notificationRepository() {
            return this.repositoryFactory.create('zer_stock_notification');
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        notificationCriteria() {
            const criteria = new Criteria(this.page, this.limit);

            criteria
                .addAssociation('product')
                .addAssociation('customer')
                .addAssociation('salesChannel');

            this.sortBy.split(',').forEach((sortBy) => {
                criteria.addSorting(Criteria.sort(sortBy, this.sortDirection, this.naturalSorting));
            });

            return criteria;
        },

        columns() {
            return [
                {
                    property: 'product.name',
                    label: this.$tc('zer-stock-notification.list.columnProduct'),
                    routerLink: 'sw.product.detail',
                    primary: true
                },
                {
                    property: 'customer.firstName',
                    label: this.$tc('zer-stock-notification.list.columnCustomer')
                },
                {
                    property: 'salesChannel.name',
                    label: this.$tc('zer-stock-notification.list.columnSalesChannel'),
                    routerLink: 'sw.salesChannel.detail'
                },
                {
                    property: 'status',
                    label: this.$tc('zer-stock-notification.list.columnStatus')
                },
                {
                    property: 'sentAt',
                    label: this.$tc('zer-stock-notification.list.columnSentAt')
                },
                {
                    property: 'createdAt',
                    label: this.$tc('zer-stock-notification.list.columnCreatedAt')
                }
            ];
        },

        dateFilter() {
            return Shopware.Filter.getByName('date');
        },
    },

    methods: {
        getList() {
            this.isLoading = true;

            return this.notificationRepository.search(this.notificationCriteria)
                .then((result) => {
                    this.notifications = result;
                    this.total = result.total;

                    const parentIds = this.getParentIds(result);

                    if (parentIds.length > 0) {
                        return this.loadParentProducts(parentIds);
                    }

                    return Promise.resolve();
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        getParentIds(notifications) {
            const parentIds = [];

            notifications.forEach((notification) => {
                if (notification.product && notification.product.translated?.name === null && notification.product.parentId) {
                    parentIds.push(notification.product.parentId);
                }
            });

            return [...new Set(parentIds)];
        },

        loadParentProducts(parentIds) {
            const criteria = new Criteria(1, parentIds.length);
            criteria.addFilter(Criteria.equalsAny('id', parentIds));

            return this.productRepository.search(criteria)
                .then((parents) => {
                    this.notifications.forEach((notification) => {
                        if (notification.product && notification.product.translated?.name === null && notification.product.parentId) {
                            notification.product.parent = parents.get(notification.product.parentId);
                        }
                    });
                });
        },

        updateTotal({total}) {
            this.total = total;
        },

        onChangeLanguage() {
            this.getList();
        },

        onColumnSort(column) {
            this.onSortColumn(column);
        },
    }
};