# Kaah Fast Food — System review & fixes (summary)

## Database vs code alignment

- **`delivery.status`** MySQL ENUM uses **`On the way`** (lowercase *w*). PHP/SQL previously used **`On the Way`**, which could fail or store invalid values under strict SQL mode.
  - **Fixed in:** `php/delivery_dashboard.php`, `php/admin_dashboard.php` (busy-driver checks and stats).

## Order placement & addresses

- **`place_order.php`** had address saving **disabled**; checkout collected an address but it was never stored.
  - **Fixed:** Validate address (min 5 characters), insert into **`address`** with city **New Hargeisa**, `postal_code` NULL, within the same transaction as the order.

## Order items query

- **`get_order_details.php`** joined `orderitem` with `oi.item_id`. Portable join is **`oi.menu_item_id`** (matches `schema.sql` and inserts in `place_order.php`).
  - **Fixed:** Use `menu_item_id` for the join; validate query results; **restrict order view to deliveries assigned to the logged-in driver** (inner join on `delivery`).

## Delivery status updates

- Whitelist allowed statuses on update: `Assigned`, `Picked Up`, `On the way`, `Delivered`.
- Set **`delivered_at = NOW()`** when status becomes **Delivered** (`delivery_dashboard.php`, `update_delivery_status.php`).

## User table (`is_active`)

- **`signup.php`** inserted `is_active`; **`setup.php`** `CREATE TABLE user` had no `is_active` column → possible insert failures on fresh setup.
  - **Fixed:** Added `is_active` to `setup.php` user DDL, admin seed insert, optional **`ALTER TABLE user ADD COLUMN is_active ...`** for existing DBs, and **`ALTER`** run in setup when missing.

## Files touched

| File | Change |
|------|--------|
| `php/place_order.php` | Address validation + insert; transaction unchanged |
| `php/get_order_details.php` | Join fix, error checks, assignment-only access |
| `php/delivery_dashboard.php` | ENUM strings, status whitelist, `delivered_at` |
| `php/admin_dashboard.php` | Delivery status literals in SQL |
| `php/update_delivery_status.php` | Whitelist + `delivered_at` |
| `php/signup.php` | Explicit `is_active` |
| `php/setup.php` | `user.is_active` in DDL + ALTER + admin insert |

## Recommended manual check (existing databases)

If you already have bad `delivery.status` values, inspect and correct in phpMyAdmin:

```sql
SELECT delivery_id, status FROM delivery;
-- If needed, normalize to ENUM values exactly as in schema.sql
```

## Testing checklist

1. **Signup** → customer dashboard, DB row has `is_active = 1`.
2. **Login** → role redirect (Admin / Customer / Delivery).
3. **Place order** (customer) → row in `orders`, `orderitem`, **`address`**.
4. **Admin** → approve order, assign delivery (busy check uses correct statuses).
5. **Delivery** → Pick up → Start delivery → Complete; **`delivered_at`** set on complete.
6. **Delivery** → View order details → only for **assigned** orders.
