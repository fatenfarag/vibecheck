# Event Booking and QR Ticketing Platform — Implementation Plan

## 1. Project Overview

Build an API-first event booking and QR ticketing platform where organizers publish events, configure ticket types and inventory, and monitor bookings and sales. Attendees browse published events, place orders, complete test-mode payments, and receive a unique QR ticket by email. Gate staff scan tickets to verify their status and redeem each valid ticket once. Inventory allocation and ticket redemption must be concurrency-safe so overselling and duplicate entry are prevented.

## 2. Tech Stack

- **Backend:** Laravel 13, PHP 8.5.2, MySQL. Expose a versioned REST API under `/api/v1`.
- **Frontend:** Decoupled React application consuming the Laravel API. Build attendee, organizer, and gate-staff interfaces in React; do not use Laravel Blade as the frontend.
- **AI:** PrismPHP SDK, integrated as a standalone PHP SDK/service rather than a Laravel-specific Prism package. Keep AI calls behind an application service boundary and expose only explicitly permitted read-only context to it.
- **Auth:** Laravel Sanctum for API authentication; `spatie/laravel-permission` for role and permission management. Use token abilities only as an additional restriction, not as a replacement for server-side permission checks.
- **Agentic development tools:** Laravel Boost, GitHub Copilot, and OpenCode may assist development. They are development tools, not runtime dependencies or application agents.
- **Architecture:** Service Layer: `Controller -> Service -> Model`. Controllers validate/request-authorize and format responses; services implement use cases and transactions; Eloquent models own relationships, casts, and persistence behavior. Do not introduce DTOs. Use Laravel Form Requests for input validation and API Resources for response shaping.
- **Database and consistency:** MySQL transactions and row-level locking for inventory allocation, payment state changes, and ticket redemption. Add database constraints and unique indexes for invariants that must hold under concurrent requests.
- **QR and email:** Generate a cryptographically random, unguessable ticket verification token; encode a verification URL or opaque token in the QR code. Send ticket confirmation and QR details by email after the order reaches the paid state.
- **Payment:** Integrate a payment gateway in test/sandbox mode only for this project. Keep gateway interaction behind a payment service/provider adapter; never store raw card details.

## 3. User Roles & Permission Matrix

| Role | Can do | Cannot do |
|---|---|---|
| Guest | View published events and public event details; register or log in | Create events, view private dashboards, place an authenticated order, access tickets or scan tools |
| Attendee | Manage own basic profile; browse published events; create orders; pay for own pending orders; view own orders and tickets; display/download own QR tickets | Create or manage events; view other attendees' orders or tickets; access organizer reports; scan/redeem tickets; change ticket status |
| Organizer | Create and manage own events; upload event images; create and manage ticket types; view attendees, orders, ticket counts, and sales summaries for own events | Manage another organizer's events; assign platform roles; access platform-wide admin data; scan/redeem tickets unless separately granted gate-staff permission |
| Gate Staff | Access assigned event(s); scan a ticket; view the minimum ticket and attendee details needed for entry; redeem a valid unused ticket | Create/edit events or ticket types; view payment credentials or full financial records; access unrelated events; redeem an already-used, invalid, or cancelled ticket |
| Admin | Manage users, roles, permissions, events, and platform-level records; view platform-wide operational summaries; suspend or restore accounts/events | Read or expose raw payment-card data; bypass audit logging; use a ticket on behalf of an attendee without an explicit audited administrative process |

**Authorization rules**
- Every organizer-owned resource query must be scoped to the authenticated organizer's ownership; never rely on frontend filtering.
- Gate staff must have an explicit event assignment (or an explicit `scan-any-event` permission for trusted platform staff).
- Admin permissions are platform-wide and must be distinct from organizer ownership permissions.
- Role names are defaults; permissions are the enforcement units. A user may have more than one role where explicitly assigned.

## 4. Feature Breakdown

### Auth & Roles
- Register and log in attendees; support logout and current-user retrieval.
- Authenticate API requests with Sanctum.
- Seed roles and granular permissions for Guest, Attendee, Organizer, Gate Staff, and Admin.
- Provide admin-only user role assignment and gate-staff event assignment.
- Enforce authorization in Form Requests/controllers and again in service-level resource scoping where needed.
- Do not allow public self-registration as Organizer, Gate Staff, or Admin.

### Events
- Organizer creates, edits, publishes, unpublishes, and views their own events.
- Event fields: title, description, start/end date and time, venue, image, publication status, and organizer ownership. Title, date, venue, and image are required project fields; description and end time may be optional.
- Public API lists only published, upcoming events by default; event detail may show published event information only.
- Organizer can view their own event attendee list and sales summary.
- Admin can view and moderate all events.
- Validate event dates and image type/size. Prevent deletion or destructive edits when paid orders/tickets exist; use an explicit archive/unpublish flow instead.

### Tickets
- Organizer defines ticket types per event with name, description, price, total quantity, and sales availability window.
- Ticket type quantity is the total sellable inventory; remaining quantity is derived from total quantity minus successfully allocated tickets, not maintained as an independently editable client value.
- Do not permit reducing total quantity below the number already sold/allocated.
- A ticket is created for each purchased unit only after payment is confirmed, unless a clearly defined reservation/hold record is used during checkout.
- Ticket states: `valid` (paid and not redeemed), `used` (redeemed), and `cancelled` (invalidated by an authorized system/admin workflow). A pending/unpaid order must not produce an entry-valid ticket.
- Ticket ownership and event/ticket-type relationships are immutable after issuance.

### Orders
- Attendee creates an order for one or more ticket types and quantities.
- Validate that the event is published, sales are open, quantities are positive, and requested quantity is available.
- Calculate prices and totals on the server; never trust prices or totals submitted by React.
- Prevent overselling with a database transaction and row lock on the relevant ticket-type inventory row(s). Recheck stock inside the transaction before reserving/decrementing inventory.
- Use idempotency for order/payment initiation where practical so retries do not create duplicate orders or charges.
- Track order states such as `pending`, `paid`, `failed`, and `expired`. Only paid orders issue entry-valid tickets.
- Expire abandoned pending orders and release any inventory holds if a reservation/hold mechanism is implemented.
- Attendees can view only their own orders; organizers can view orders for their own events.

### QR Generation & QR Scanning
- Generate one unique, high-entropy token per issued ticket. Store the token securely (prefer a hash for lookup/verification) and enforce uniqueness in the database.
- QR content contains only an opaque token or signed verification URL; do not encode personal data, payment data, or predictable database IDs.
- Authenticated gate staff scan using the React scanner interface and submit the token to the API.
- API checks token existence, ticket status, paid order status, event assignment, and event validity.
- Scan result is one of: `valid`, `invalid`, or `used`. A valid scan must be redeemed atomically.
- Redeem with a transaction/conditional update so two concurrent scans cannot both succeed. The first successful redemption changes `valid` to `used`; later scans return `used`.
- Record scan timestamp, gate staff user, event, and outcome for audit purposes. Avoid returning unnecessary attendee or order information to the scanner.

### Payment
- Support one payment gateway in test/sandbox mode only.
- Create a payment attempt for a pending order and redirect/use the gateway's test checkout flow.
- Verify payment using the gateway's server-to-server callback/webhook or server-side verification API; do not trust the browser success redirect.
- Verify webhook signatures, amount, currency, order reference, and payment state. Process callbacks idempotently.
- On confirmed payment, mark the order paid, finalize inventory allocation, issue one ticket per purchased unit, generate QR tokens, and queue email delivery.
- On failed/expired payment, mark the attempt/order accordingly and release any held inventory.
- No live payment processing, refunds, chargebacks, split payments, or payout processing.

### Admin Dashboard
- React dashboard for platform administrators.
- Manage users, roles, permissions, event moderation, and gate-staff assignments.
- View platform-wide counts and operational summaries for users, events, orders, payments, and ticket redemptions.
- Provide audit views for administrative changes and scan activity.
- Do not expose card numbers, security codes, gateway secrets, or other payment credentials.

## 5. Data Visibility Rules

### What each role CAN see
- **Guest:** Published event title, image, date/time, venue, description, and public ticket-type names/prices/availability.
- **Attendee:** Guest-visible event data; own profile; own orders and payment status; own tickets and QR codes; ticket status for their own tickets.
- **Organizer:** All data for events they own; ticket types and inventory for those events; orders, attendee lists, ticket issuance/redemption counts, and sales summaries for those events. Show only attendee details needed to administer their event.
- **Gate Staff:** Assigned event details needed for entry; scan result (`valid`, `invalid`, `used`); for a valid/used ticket, only the minimum attendee/ticket details required for identity and entry checks.
- **Admin:** Platform-wide user, event, ticket, order, payment-status, assignment, and audit metadata needed for platform administration.

### What each role CANNOT see
- **Guest:** User profiles, attendee lists, orders, ticket QR tokens, scan logs, organizer/admin dashboards, unpublished events.
- **Attendee:** Other users' profiles, orders, tickets, QR tokens, payment attempts, attendee lists, organizer reports, scan logs, unpublished events.
- **Organizer:** Other organizers' private events, attendees, orders, ticket tokens, and sales data; platform-wide user/payment data; gateway secrets or card details.
- **Gate Staff:** Unassigned events; attendee account/profile data beyond the minimum entry-check fields; order totals, payment method details, sales reports, other events' attendee lists, QR token hashes, gateway data.
- **Admin:** Raw card data, card security codes, passwords, Sanctum token values, private gateway credentials, and AI/provider secrets. Admin access to business records must be auditable.

## 6. AI Scope

### What AI does
- Provide an optional, read-only organizer assistant for questions about the organizer's own event performance and operational data.
- Answer questions such as ticket sales by type, remaining inventory, order totals/status counts, attendance/redemption counts, and simple summaries of an event's performance.
- Explain platform usage or summarize event descriptions and organizer-visible reports when relevant data is supplied.
- Use PrismPHP SDK through a dedicated AI integration service. The AI service receives only the minimum structured context required for the current question.
- Treat model output as advisory text. The backend remains the source of truth for inventory, payments, permissions, ticket status, and redemption.

### What AI does not do
- Create, edit, publish, cancel, or delete events, ticket types, orders, or tickets.
- Set or change prices, inventory, roles, permissions, or event assignments.
- Initiate, confirm, reverse, or refund payments.
- Generate authoritative QR tokens, validate tickets, or redeem tickets.
- Make authorization decisions or bypass normal API permissions.
- Execute SQL, arbitrary code, shell commands, tools, or external actions based on model output.
- Access attendee private data unless a separately specified, permission-checked use case is added. No such use case is included in this plan.

### Data sources AI reads from
- Read-only, server-prepared aggregates for the authenticated organizer's own events: ticket-type sales and remaining inventory, order counts/totals by status, paid sales totals, and ticket redemption counts.
- Public event fields and event descriptions for events the requesting user is authorized to manage.
- No direct database credentials or unrestricted model access; backend services query and scope the data before it is sent to the model.

### Data sources AI does not access
- Other organizers' events, orders, sales, or attendee data.
- Attendee names, email addresses, phone numbers, account profiles, or other personal data.
- QR tokens, token hashes, ticket verification secrets, scan payloads, or security/audit secrets.
- Raw payment details, card data, gateway credentials, webhook secrets, passwords, Sanctum tokens, or application secrets.
- Unpublished/private data outside the authenticated organizer's ownership.
- Arbitrary external websites, user files, or connected services.

## 7. Out of Scope

- Seat maps, assigned seating, seat selection, and per-seat inventory.
- Multiple currencies, foreign-exchange conversion, tax automation, and complex invoicing.
- Live/production payment processing; this project uses test/sandbox payment mode only.
- Refunds, partial refunds, chargebacks, disputes, and organizer payouts.
- Multiple payment gateways or payment-method orchestration.
- Promo codes, coupons, discounts, bundles, subscriptions, and memberships.
- Waitlists, resale, ticket transfers, gifting, and attendee name changes after ticket issuance.
- Recurring events, event series, multi-day passes, and session-level ticket access.
- Dynamic pricing, early-bird pricing rules, and tiered pricing automation beyond manually configured ticket types.
- Native iOS/Android applications; the client is a web-based React application.
- Social login, multi-factor authentication, and enterprise SSO.
- Multi-tenant organizations with teams, branches, or complex organizer hierarchies.
- Offline QR scanning and later synchronization.
- Facial recognition, identity-document verification, and biometric entry.
- Public marketplace reviews, ratings, comments, and social features.
- SMS, WhatsApp, and push notifications; email delivery only.
- Advanced analytics, custom report builders, accounting integrations, and data warehouse exports.
- AI-driven actions or autonomous agents that modify platform data.
- AI access to personal attendee data or unrestricted database querying.

## 8. API Endpoints Overview

All endpoints are prefixed with `/api/v1`. JSON request/response bodies are assumed. `Auth` means a valid Sanctum-authenticated API token/session is required. Authorization is enforced server-side with Spatie permissions plus ownership/event-assignment checks. Public endpoints still validate input and expose only published/public data.

### Auth & Roles

| Method / path | Description | Auth required | Authorization required |
|---|---|---|---|
| POST `/auth/register` | Register an attendee account | No | Public registration; role is always Attendee |
| POST `/auth/login` | Authenticate and issue session/token | No | Public; account must be active |
| POST `/auth/logout` | Revoke current session/token | Yes | Authenticated user |
| GET `/auth/me` | Return current user's safe profile and roles | Yes | Authenticated user |
| GET `/admin/users` | List/search platform users | Yes | `users.view` |
| PATCH `/admin/users/{user}` | Update allowed user fields or account status | Yes | `users.update` |
| PUT `/admin/users/{user}/roles` | Assign/remove roles | Yes | `roles.assign` |
| GET `/admin/roles` | List roles and permissions | Yes | `roles.view` |
| PUT `/admin/gate-staff/{user}/events` | Assign gate staff to events | Yes | `gate-staff.assign` |

### Events

| Method / path | Description | Auth required | Authorization required |
|---|---|---|---|
| GET `/events` | List published events with public filters/pagination | No | Public-event visibility |
| GET `/events/{event}` | View published event details and public ticket types | No | Event must be published |
| POST `/organizer/events` | Create an event | Yes | `events.create` |
| GET `/organizer/events` | List current organizer's events | Yes | `events.view-own` |
| GET `/organizer/events/{event}` | View an owned event | Yes | `events.view-own` plus ownership |
| PATCH `/organizer/events/{event}` | Update an owned event | Yes | `events.update-own` plus ownership |
| POST `/organizer/events/{event}/publish` | Publish an owned event | Yes | `events.publish-own` plus ownership |
| POST `/organizer/events/{event}/unpublish` | Unpublish an owned event | Yes | `events.unpublish-own` plus ownership |
| POST `/admin/events/{event}/moderate` | Approve, reject, or suspend an event | Yes | `events.moderate` |

### Tickets & Ticket Types

| Method / path | Description | Auth required | Authorization required |
|---|---|---|---|
| GET `/events/{event}/ticket-types` | List public ticket types and availability | No | Event must be published; expose public fields only |
| POST `/organizer/events/{event}/ticket-types` | Create a ticket type | Yes | `ticket-types.create-own` plus event ownership |
| PATCH `/organizer/ticket-types/{ticketType}` | Update ticket type details/quantity | Yes | `ticket-types.update-own` plus event ownership; quantity cannot fall below allocated units |
| DELETE `/organizer/ticket-types/{ticketType}` | Disable/remove an unsold ticket type | Yes | `ticket-types.delete-own` plus event ownership; reject if tickets/orders make deletion unsafe |
| GET `/attendee/tickets` | List current attendee's tickets | Yes | `tickets.view-own` |
| GET `/attendee/tickets/{ticket}` | View one owned ticket and its QR payload | Yes | `tickets.view-own` plus ticket ownership |

### Orders

| Method / path | Description | Auth required | Authorization required |
|---|---|---|---|
| POST `/orders` | Create a pending order for requested ticket types/quantities | Yes | `orders.create-own`; attendee account; server validates event and stock |
| GET `/attendee/orders` | List current attendee's orders | Yes | `orders.view-own` |
| GET `/attendee/orders/{order}` | View one own order and its status | Yes | `orders.view-own` plus order ownership |
| GET `/organizer/events/{event}/orders` | List orders for an owned event | Yes | `orders.view-own-event` plus event ownership |
| GET `/organizer/events/{event}/attendees` | List attendees/tickets for an owned event | Yes | `attendees.view-own-event` plus event ownership |

### QR Generation & QR Scanning

| Method / path | Description | Auth required | Authorization required |
|---|---|---|---|
| GET `/attendee/tickets/{ticket}/qr` | Retrieve/render QR payload for an owned ticket | Yes | `tickets.view-own` plus ticket ownership |
| POST `/gate/scan` | Verify and atomically redeem a scanned opaque QR token; return `valid`, `invalid`, or `used` | Yes | `tickets.scan`; staff must be assigned to the ticket's event |
| GET `/gate/events` | List events assigned to current gate staff | Yes | `gate-events.view-assigned` |
| GET `/gate/events/{event}/scan-logs` | View scan outcomes for an assigned event | Yes | `scan-logs.view-assigned` |

### Payment

| Method / path | Description | Auth required | Authorization required |
|---|---|---|---|
| POST `/orders/{order}/payment` | Create a test-mode payment attempt for an own pending order | Yes | `payments.initiate-own` plus order ownership |
| GET `/orders/{order}/payment-status` | Retrieve own order payment status | Yes | `payments.view-own` plus order ownership |
| POST `/payments/webhook` | Receive and verify gateway payment notification | No (gateway-authenticated) | Valid gateway signature and verified order/amount; idempotent processing |
| GET `/organizer/events/{event}/sales-summary` | View sales totals/counts for an owned event | Yes | `reports.view-own-event` plus event ownership |

### Admin Dashboard

| Method / path | Description | Auth required | Authorization required |
|---|---|---|---|
| GET `/admin/dashboard/summary` | Platform-wide operational summary | Yes | `dashboard.view` |
| GET `/admin/events` | List all events for moderation | Yes | `events.view-all` |
| GET `/admin/orders` | Search platform orders and statuses | Yes | `orders.view-all` |
| GET `/admin/payments` | View payment attempt metadata/status (no card data) | Yes | `payments.view-all` |
| GET `/admin/audit-logs` | View administrative and ticket scan audit metadata | Yes | `audit-logs.view` |

### AI Assistant

| Method / path | Description | Auth required | Authorization required |
|---|---|---|---|
| POST `/organizer/ai/ask` | Ask a read-only question about the authenticated organizer's own event aggregates | Yes | `ai.ask-own-events`; context must be ownership-scoped and contain no attendee PII or secrets |

**Implementation invariants**
- Enforce all stock changes and ticket redemption in database transactions; add unique constraints for ticket QR token hashes and payment gateway references.
- A scan operation must use an atomic state transition from `valid` to `used`; never implement check-then-update as separate unprotected operations.
- Payment webhook processing and ticket issuance must be idempotent.
- Do not return internal QR token hashes, gateway secrets, password hashes, API tokens, or unrestricted model prompts in API responses.
- Use pagination for collection endpoints, consistent validation/error response shapes, rate limits on authentication/payment/scan/AI endpoints, and audit logs for privileged actions.
