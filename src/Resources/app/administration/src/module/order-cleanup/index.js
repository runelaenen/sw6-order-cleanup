import './page/order-cleanup-index';

Shopware.Module.register('order-cleanup', {
    type: 'plugin',
    name: 'order-cleanup.general.name',
    title: 'order-cleanup.general.title',
    description: 'order-cleanup.general.description',
    color: '#e53935',
    icon: 'regular-trash',

    routes: {
        index: {
            component: 'order-cleanup-index',
            path: 'index',
        },
    },

    settingsItem: {
        group: 'system',
        to: 'order.cleanup.index',
        icon: 'regular-trash',
        privilege: 'system.core_update',
    },
});
