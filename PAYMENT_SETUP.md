# Payment Gateway & Enrollment Integration Guide

This document explains the payment gateway integration, backend course price enforcement, environment variable configuration, and callback handling for the **Online Learning Platform**.

---

## 🔒 Security Principles

1. **Authoritative Backend Pricing**:
   - The frontend checkout interface (`payment.html`) **never dictates the purchase price**.
   - Upon payment initiation, `process_payment.php` queries the authoritative course price directly from PostgreSQL:
     ```sql
     SELECT id, title, price FROM courses WHERE id = :course_id;
     ```
   - Client-side modifications to `amount` inputs or URL query parameters are rejected.

2. **Idempotency & Replay Protection**:
   - Every transaction is assigned a unique `transaction_id` (`TXN...`).
   - Duplicate callback processing is prevented by PostgreSQL unique constraints on `payments.transaction_id` and idempotency checks in `process_payment.php`.

3. **Atomic Enrollment**:
   - Course access (`enrollments` record) is created **only after** the payment status transitions to `COMPLETED` following gateway verification.

---

## ⚙️ Payment Environment Variables

Configure these variables in your `.env` file or Railway project environment:

| Environment Variable | Description | Example |
| :--- | :--- | :--- |
| `PAYMENT_PROVIDER` | Selected payment provider gateway | `bKash` / `Nagad` / `SSLCommerz` / `Stripe` |
| `PAYMENT_STORE_ID` | Merchant Store ID / Public API Key | `your_store_id` |
| `PAYMENT_STORE_PASSWORD` | Merchant Store Password / Secret Key | `your_store_password` |
| `PAYMENT_API_KEY` | API Authorization Key | `your_api_key` |
| `PAYMENT_SANDBOX` | Set `true` for development sandbox, `false` for live production | `true` |

---

## 🔄 Payment Lifecycle Architecture

```
┌──────────────┐     1. Initiate Checkout     ┌────────────────────────┐
│   Customer   ├────────────────────────────►│  process_payment.php   │
└──────┬───────┘                              │  (?action=initiate)    │
       │                                      └───────────┬────────────┘
       │                                                  │ 2. Fetch True Price
       │                                                  ▼
       │                                      ┌────────────────────────┐
       │                                      │     PostgreSQL DB      │
       │                                      │   (courses.price)      │
       │                                      └───────────┬────────────┘
       │                                                  │
       │ 3. Dispatch to Gateway / Confirm                 │ 4. Insert PENDING payment
       ▼                                                  ▼
┌──────────────┐      5. Gateway IPN Callback ┌────────────────────────┐
│   Payment    ├────────────────────────────►│  process_payment.php   │
│   Gateway    │                              │  (?action=confirm)     │
└──────────────┘                              └───────────┬────────────┘
                                                          │ 6. Verify Amount Match
                                                          ▼
                                              ┌────────────────────────┐
                                              │ Update Status -> PAID  │
                                              │ Create Enrollment      │
                                              └────────────────────────┘
```

---

## 🧪 Gateway Verification & Webhook Endpoints

- **Initiate Endpoint**: `POST /CONTROLLAR/process/process_payment.php?action=initiate`
- **Confirm / IPN Endpoint**: `POST /CONTROLLAR/process/process_payment.php?action=confirm`
- **Cancel Endpoint**: `POST /CONTROLLAR/process/process_payment.php?action=cancel`
