import template from './sw-order-list.html.twig';

export default {
    template,

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    data() {
        return {
            showBulkDeleteModal: false,
        };
    },

    computed: {
        selectionCount() {
            return Object.keys(this.$refs.orderGrid?.selection ?? {}).length;
        },
    },

    methods: {
        disableDeletion(order) {
            return !this.acl.can('order.deleter');
        },

        async onConfirmDelete(id) {
            this.showDeleteModal = false;

            try {
                await Shopware.Service('syncService').httpClient.post(
                    '/_action/order-cleanup/delete-orders',
                    { ids: [id] },
                    { headers: Shopware.Service('syncService').getBasicHeaders() }
                );

                this.$refs.orderGrid.resetSelection();
                this.getList();
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('order-cleanup.orderList.notifications.errorTitle'),
                    message: this.$tc('order-cleanup.orderList.notifications.errorMessage'),
                });
            }
        },

        openBulkDeleteModal() {
            this.showBulkDeleteModal = true;
        },

        closeBulkDeleteModal() {
            this.showBulkDeleteModal = false;
        },

        async onConfirmBulkDelete() {
            this.showBulkDeleteModal = false;

            const ids = Object.keys(this.$refs.orderGrid.selection);

            if (ids.length === 0) {
                return;
            }

            try {
                await Shopware.Service('syncService').httpClient.post(
                    '/_action/order-cleanup/delete-orders',
                    { ids },
                    { headers: Shopware.Service('syncService').getBasicHeaders() }
                );

                this.createNotificationSuccess({
                    title: this.$tc('order-cleanup.orderList.notifications.bulkSuccessTitle'),
                    message: this.$tc('order-cleanup.orderList.notifications.bulkSuccessMessage', ids.length, { count: ids.length }),
                });

                this.$refs.orderGrid.resetSelection();
                this.getList();
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('order-cleanup.orderList.notifications.errorTitle'),
                    message: this.$tc('order-cleanup.orderList.notifications.errorMessage'),
                });
            }
        },
    },
};
