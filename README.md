# Order Cleanup

A Shopware 6 plugin for cleaning up orders — from wiping an entire environment to deleting individual orders that Shopware normally blocks (e.g. orders with invoices or credit notes attached).

## Features

### Full environment cleanup

Wipe all order data at once — useful for resetting development or staging environments:

- Deletes all orders and related data (line items, transactions, deliveries, addresses)
- Deletes all order documents (invoices, credit notes, delivery notes, cancellations)
- Deletes document media files (PDFs) from the media library
- Resets order-related number range counters so numbering restarts from the configured start value


### Delete individual orders (even with documents)

Shopware prevents deleting orders once documents have been generated. This plugin removes that restriction:

- **Single delete** — the delete button in the order list context menu works for all orders, including those with documents
- **Bulk delete** — select multiple orders and use the "Delete selected" action in the bulk actions bar
- A confirmation modal warns when documents will be deleted along with the order
- Documents, their media files (PDFs), and the order are cleaned up in the correct order


### Access control

All actions (API routes and admin UI) require the `order.deleter` ACL privilege. Only admin users with this permission can see or use the cleanup features.

## Requirements

- Shopware 6.7

## Installation

```bash
composer require runelaenen/sw6-order-cleanup
bin/console plugin:install --activate OrderCleanup
bin/console cache:clear
bin/build-administration.sh # Or comparable command
```

## Usage

### Full cleanup (admin)

Navigate to **Settings → System → Order Cleanup**. Click **Clear all orders** and confirm the dialog.

> **Warning:** This action is irreversible and will permanently delete all order data and reset number range counters.

### Full cleanup (CLI)

```bash
bin/console order-cleanup:clear
```

To skip the confirmation prompt (e.g. in scripts):

```bash
bin/console order-cleanup:clear -n
```

### Deleting specific orders

Open the order list in the admin. Use the context menu on any order and click **Delete** — this works even for orders with documents. Confirm the deletion in the modal.

To delete multiple orders at once, select them using the checkboxes, then click **Delete selected** in the bulk actions bar.

### Deleting orders by ID (CLI)

```bash
bin/console order-cleanup:delete <order-id> [<order-id-2> ...]
```

To skip the confirmation prompt:

```bash
bin/console order-cleanup:delete <order-id> -n
```

This deletes the specified order(s) along with their documents and media files. Number range counters are not reset.

## What gets deleted

| Action | Orders | Documents & media | Number ranges reset |
|---|---|---|---|
| Single / bulk delete (admin) | Selected order(s) only | Only documents belonging to deleted orders | No |
| Delete by ID (CLI) | Specified order(s) only | Only documents belonging to deleted orders | No |
| Full cleanup | All orders | All documents | Yes |

Product and customer number ranges are **not** affected by any action.
