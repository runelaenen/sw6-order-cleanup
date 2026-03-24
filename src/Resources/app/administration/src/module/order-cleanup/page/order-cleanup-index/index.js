import template from './order-cleanup-index.html.twig';
import './order-cleanup-index.scss';

const { Component, Mixin, Service } = Shopware;

Component.register('order-cleanup-index', {
    template,

    mixins: [
        Mixin.getByName('notification'),
    ],

    data() {
        return {
            isLoadingOrders: false,
            isLoadingCustomers: false,
            showOrderConfirmModal: false,
            showCustomerConfirmModal: false,
            orderTotal: 0,
            orderProcessed: 0,
            customerTotal: 0,
            customerProcessed: 0,
        };
    },

    computed: {
        orderProgress() {
            if (this.orderTotal === 0) {
                return 0;
            }
            return Math.min(Math.round((this.orderProcessed / this.orderTotal) * 100), 100);
        },

        customerProgress() {
            if (this.customerTotal === 0) {
                return 0;
            }
            return Math.min(Math.round((this.customerProcessed / this.customerTotal) * 100), 100);
        },
    },

    methods: {
        httpClient() {
            return Service('syncService').httpClient;
        },

        getHeaders() {
            return Service('syncService').getBasicHeaders();
        },

        async fetchCounts() {
            const response = await this.httpClient().get(
                '/_action/order-cleanup/count',
                { headers: this.getHeaders() }
            );
            return response.data;
        },

        async performOrderCleanup() {
            this.isLoadingOrders = true;
            this.showOrderConfirmModal = false;
            this.orderProcessed = 0;

            try {
                const { orders } = await this.fetchCounts();
                this.orderTotal = orders;

                let hasMore = true;
                while (hasMore) {
                    const response = await this.httpClient().post(
                        '/_action/order-cleanup/clear',
                        {},
                        { headers: this.getHeaders() }
                    );
                    hasMore = response.data.hasMore;
                    this.orderProcessed = hasMore
                        ? Math.min(this.orderProcessed + 500, this.orderTotal)
                        : this.orderTotal;
                }

                this.createNotificationSuccess({
                    title: this.$tc('order-cleanup.orders.notifications.successTitle'),
                    message: this.$tc('order-cleanup.orders.notifications.successMessage'),
                });
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('order-cleanup.notifications.errorTitle'),
                    message: this.$tc('order-cleanup.notifications.errorMessage'),
                });
            } finally {
                this.isLoadingOrders = false;
                this.orderTotal = 0;
                this.orderProcessed = 0;
            }
        },

        async performCustomerCleanup() {
            this.isLoadingCustomers = true;
            this.showCustomerConfirmModal = false;
            this.customerProcessed = 0;

            try {
                const { customers } = await this.fetchCounts();
                this.customerTotal = customers;

                let hasMore = true;
                while (hasMore) {
                    const response = await this.httpClient().post(
                        '/_action/order-cleanup/clear-customers',
                        {},
                        { headers: this.getHeaders() }
                    );
                    hasMore = response.data.hasMore;
                    this.customerProcessed = hasMore
                        ? Math.min(this.customerProcessed + 500, this.customerTotal)
                        : this.customerTotal;
                }

                this.createNotificationSuccess({
                    title: this.$tc('order-cleanup.customers.notifications.successTitle'),
                    message: this.$tc('order-cleanup.customers.notifications.successMessage'),
                });
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('order-cleanup.notifications.errorTitle'),
                    message: this.$tc('order-cleanup.notifications.errorMessage'),
                });
            } finally {
                this.isLoadingCustomers = false;
                this.customerTotal = 0;
                this.customerProcessed = 0;
            }
        },
    },
});
