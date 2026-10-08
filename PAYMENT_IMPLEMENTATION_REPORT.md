# Payment System Implementation Report

## Scope completed

The existing manual payment workflow was preserved and extended with a backward-compatible payment model. Payments now support explicit payment types, payment source (`gateway` or `manual`), gateway identifiers, currency, billing-period metadata, receipt numbers, refund fields, and gateway audit events.

A `PaymentGatewayInterface` isolates provider-specific behavior. `PayMongoPaymentGateway` implements PayMongo hosted checkout using the v2 Checkout Sessions API, server-calculated PHP amounts, supported payment methods, redirect URLs, metadata, and refund requests. `FakePaymentGateway` is the default local/test implementation so development never sends real money.

Tenant checkout now creates a `processing` payment and redirects to a hosted checkout URL. The application never treats a browser return as proof of payment. Payments are marked `paid` only after a signature-validated webhook. Webhook handling checks the payment identity and amount, records an audit event, and is idempotent for repeated deliveries. Invalid webhook signatures cannot update payments.

Manual cash, GCash, and bank-transfer submissions remain separate from gateway payments and continue through the existing owner-review workflow. Owner-recorded payments are explicitly stored as manual PHP payments. Paid manual payments receive a receipt number and payment timestamp.

The web and mobile APIs now expose payment details, checkout creation, manual submission, and the PayMongo webhook endpoint. Tenant payment UI includes a clear secure-checkout action, processing explanation, retry support for failed or expired checkouts, and expanded status badges.

## Main files added or changed

| Area | Files |
| --- | --- |
| Schema | `database/migrations/2026_09_20_000001_upgrade_payments_for_gateway_billing.php` |
| Models | `app/Models/Payment.php`, `app/Models/PaymentEvent.php` |
| Gateway | `app/Services/Payment/PaymentGatewayInterface.php`, `PayMongoPaymentGateway.php`, `FakePaymentGateway.php` |
| Payment service | `app/Services/PaymentService.php` |
| API | `app/Http/Controllers/Api/V1/PaymentController.php`, `app/Http/Resources/PaymentResource.php`, `routes/api.php` |
| Web | `app/Http/Controllers/Tenant/PaymentController.php`, `routes/web.php`, `resources/views/tenant/payments/show.blade.php` |
| Configuration | `config/services.php`, `.env.example`, `app/Providers/AppServiceProvider.php` |
| Tests | `tests/Feature/Payment/OnlinePaymentTest.php` |

## Configuration

Local development defaults to the fake gateway:

```env
PAYMENT_GATEWAY=fake
```

For PayMongo sandbox or production, set values in `.env` and never commit them:

```env
PAYMENT_GATEWAY=paymongo
PAYMONGO_PUBLIC_KEY=...
PAYMONGO_SECRET_KEY=...
PAYMONGO_WEBHOOK_SECRET=...
PAYMONGO_BASE_URL=https://api.paymongo.com
PAYMONGO_PAYMENT_METHODS=card,gcash,qrph
```

Register the webhook URL in PayMongo as:

```text
https://your-domain.example/api/v1/payments/webhook/paymongo
```

The integration follows PayMongo's hosted-checkout and webhook model: [Hosted Checkout](https://docs.paymongo.com/docs/payment-channels-hosted-checkout), [Webhook setup](https://docs.paymongo.com/docs/creating-a-webhook-endpoint), and [Refund API](https://docs.paymongo.com/reference/create-a-refund).

## Verification

The following checks passed:

- PHP syntax checks for all new and modified payment PHP files.
- Payment route inspection, including web checkout, mobile checkout, manual submission, and PayMongo webhook routes.
- Focused online-payment tests: 3 tests and 14 assertions.
- Full Laravel test suite: **95 tests and 206 assertions passed**.

## Important implementation boundary

This work implements the secure payment core and real PayMongo adapter, but it does not claim that live online payments have been tested against a real PayMongo account because no PayMongo credentials or public webhook endpoint were available in the development environment.

The larger prompt also requests additional product modules that are not yet wired into the existing project: administrator-configurable application/reservation fees, automatic contract initial-charge generation, recurring monthly billing jobs, configurable grace-period late fees, a full administrator payment-settings screen, email receipt delivery, and owner dashboard aggregates/refund controls. Those should be implemented as a separate phase rather than silently treating the current implementation as complete.
