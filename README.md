# Order Cleanup

A Shopware 6 plugin for clearing all placed orders, customers and documents, and resetting number generators. Useful for resetting development or staging environments to a clean state.

## Features

**Orders**
- Deletes all orders and related data (line items, transactions, deliveries, addresses)
- Deletes all order documents (invoices, credit notes, delivery notes, cancellations)
- Deletes document media files (PDFs) from the media library
- Resets order-related number range counters (`order`, `document_invoice`, `document_storno`, `document_delivery_note`, `document_credit_note`) so numbering restarts from the configured start value

**Customers**
- Deletes all customers and their addresses
- Resets the customer number range counter (`customer`) so numbering restarts from the configured start value

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

Navigate to **Settings → System → Order Cleanup**.

- Click **Clear all orders** to delete all orders, documents and media files
- Click **Clear all customers** to delete all customers and addresses

Confirm the dialog before each action. A progress bar shows how many records have been processed during cleanup.

> **Warning:** These actions are irreversible and will permanently delete data.

### CLI

**Orders:**
```bash
bin/console order-cleanup:clear
```

**Customers:**
```bash
bin/console order-cleanup:clear-customers
```

To skip the confirmation prompt (e.g. in scripts):

```bash
bin/console order-cleanup:clear -n
bin/console order-cleanup:clear-customers -n
```

Both commands process records in batches of 500 and report progress per batch.

## What gets deleted

### Orders

| Data | Details |
|---|---|
| Orders | `order` and all sub-tables (line items, transactions, deliveries, addresses, etc.) |
| Documents | Invoices, credit notes, delivery notes, cancellations |
| Document media | PDF files from the media library |
| Number range states | `order`, `document_invoice`, `document_storno`, `document_delivery_note`, `document_credit_note` — reset to configured start value |

### Customers

| Data | Details |
|---|---|
| Customers | All customer accounts and login credentials |
| Addresses | All customer addresses |
| Number range states | `customer` — reset to configured start value |

`product` and any other number ranges are **never** affected.
