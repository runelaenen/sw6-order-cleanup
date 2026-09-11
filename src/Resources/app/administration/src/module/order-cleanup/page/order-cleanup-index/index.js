import template from './order-cleanup-index.html.twig';

Shopware.Component.register('order-cleanup-index', {
    template,

    mixins: [
        Shopware.Mixin.getByName('notification'),
    ],

    data() {
        return {
            isLoading: false,
            showConfirmModal: false,
        };
    },

    computed: {
        canDelete() {
            return Shopware.Service('acl').can('order.deleter');
        },
    },

    methods: {
        openConfirmModal() {
            this.showConfirmModal = true;
        },

        closeConfirmModal() {
            this.showConfirmModal = false;
        },

        async performCleanup() {
            this.isLoading = true;
            this.showConfirmModal = false;

            try {
                await Shopware.Service('syncService').httpClient.post(
                    '/_action/order-cleanup/clear',
                    {},
                    { headers: Shopware.Service('syncService').getBasicHeaders() }
                );

                this.createNotificationSuccess({
                    title: this.$tc('order-cleanup.notifications.successTitle'),
                    message: this.$tc('order-cleanup.notifications.successMessage'),
                });
            } catch (e) {
                this.createNotificationError({
                    title: this.$tc('order-cleanup.notifications.errorTitle'),
                    message: this.$tc('order-cleanup.notifications.errorMessage'),
                });
            } finally {
                this.isLoading = false;
            }
        },
    },
});
