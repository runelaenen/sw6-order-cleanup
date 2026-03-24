# Order Cleanup

A Shopware 6 plugin for clearing all placed orders, documents and resetting number generators. Useful for resetting development or staging environments to a clean state.

## Features

- Deletes all orders and related data (line items, transactions, deliveries, addresses)
- Deletes all order documents (invoices, credit notes, delivery notes, cancellations)
- Deletes document media files (PDFs) from the media library
- Resets order-related number range counters (`order`, `document_invoice`, `document_storno`, `document_delivery_note`, `document_credit_note`) so numbering restarts from the configured start value
- Available via a dedicated admin page and a CLI command

## Requirements

- Shopware 6.6

## Installation

```bash
composer require runelaenen/sw6-order-cleanup
bin/console plugin:install --activate OrderCleanup
bin/console cache:clear
bin/build-administration.sh # Or comparable command
```

## Usage

### Admin

Navigate to **Settings → System → Order Cleanup**. Click **Clear all orders** and confirm the dialog.

> **Warning:** This action is irreversible and will permanently delete all order data.

### CLI

```bash
bin/console order-cleanup:clear
```

To skip the confirmation prompt (e.g. in scripts):

```bash
bin/console order-cleanup:clear -n
```

## What gets deleted

| Data | Details |
|---|---|
| Orders | `order` and all sub-tables (line items, transactions, deliveries, addresses, etc.) |
| Documents | Invoices, credit notes, delivery notes, cancellations |
| Document media | PDF files from the media library |
| Number range states | Reset to configured start value for order and document types only |

Product and customer number ranges are **not** affected.
