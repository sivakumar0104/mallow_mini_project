# Subscription Billing & Usage Metering System

A high-performance, multi-tenant billing engine designed to handle 5M+ monthly usage events with atomic proration and robust idempotency.

## 1. Architecture & Scaling Strategy

This system is built to scale while maintaining strict financial integrity.

### Data Strategy for 50L+ (5M+) Rows
*   **Table Partitioning**: The `usage_events` table is designed to be range-partitioned by `usage_date` (monthly). This keeps indexes compact and allows for instantaneous removal of stale historical data.
*   **Denormalization / Rollup**: We maintain `daily_usage_aggregates` via atomic database upserts during the ingestion phase. This allows dashboard and billing queries to bypass the raw 5M+ event table, targeting a significantly smaller, daily-rollup dataset.
*   **Buffer Layer (Scalability Path)**: Currently, ingestion is handled via atomic DB transactions. To handle bursts exceeding database write IOPS, the system is designed to drop in a decoupled ingestion pipeline (e.g., Redis Streams or Kafka) with consumer workers buffering writes to the DB.

## 2. Setup & Installation

1.  **Clone the repository**:
    `git clone <repository-url> && cd <project-name>`
2.  **Install dependencies**:
    `composer install`
3.  **Environment Setup**:
    `cp .env.example .env`
4.  **Application Config**:
    `php artisan key:generate`
5.  **Database Migration**:
    `php artisan migrate`
6.  **Seeding (Optional)**:
    `php artisan db:seed`
7.  **Testing**:
    `php artisan test`

## 3. Key Design Decisions

*   **Currency**: Stored as integers (cents/paise) across all models to prevent floating-point rounding errors common in financial calculations.
*   **Caching**: Plan lookups are cached in Redis (TTL: 6h). An `Observer` pattern (`PlanObserver`) ensures invalidation occurs immediately upon any update or deletion.
*   **Idempotency**: All ingestion requests require an `idempotency_key`. The `usage_events` table enforces a unique constraint on `(customer_id, idempotency_key)` to prevent double-billing.

## 4. Rollout & Monitoring

*   **Rollout Strategy**: Deploy behind feature flags. We recommend a "shadow-write" phase where usage events are ingested into both the new system and legacy systems, with automated metric-parity verification before cutting over the invoice engine.
*   **Monitoring (Anomaly Alerting)**: A **Zero-Write Anomaly Alert** is configured to trigger PagerDuty if the 15-minute rolling ingestion rate drops >80% below the trailing 3-day average during business hours.

## 5. Handing This Off (Team Lead Perspective)

### To the next engineer:
1.  **Buffer Scaling**: If ingestion spikes exceed single-instance DB IOPS, move the `UsageController` logic into an asynchronous Redis-backed queue.
2.  **Time-Zone Complexity**: Currently assumes system-wide UTC. Ensure you handle tenant-specific time zones for mid-cycle cutoffs if the client base becomes global.
3.  **Late Events**: Retroactive ingestion logic is currently manual. If client syncing is unreliable, implement a "correction" process to re-calculate past invoices.

### Corners intentionally cut (3-day timebox):
*   **Ingestion Pipeline**: Bypassed a distributed queue (Kafka/SQS) in favor of atomic DB upserts for lower operational complexity.
*   **Payment Integration**: Skipped external payment gateway webhooks (Stripe/Razorpay) to focus strictly on internal ledger and metering accuracy.
