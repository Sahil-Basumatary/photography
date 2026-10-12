# Photography platform

Public portfolio, private client galleries, and studio records for `photography.sahilbzy.com`.

## Application

One Laravel 13 app on PHP 8.5.

Blade renders the public site. Livewire handles gallery and studio interaction. Filament is the private console, with its own templates kept off the public pages.

Modules:

- tenancy
- identity
- crm
- studio
- contracts
- billing
- galleries
- media
- notifications
- audit

A module calls another module through its actions. Architecture tests fail if a module queries another module's tables.

Browser JavaScript covers multipart upload, the lightbox, and drag ordering. An upload uses a presigned request for one quarantine key.

## Production

Region `eu-west-2`.

| Piece | Choice |
| --- | --- |
| App | One encrypted EC2 instance |
| Database | PostgreSQL 18 on private Single-AZ RDS |
| Photographs | Private S3, CloudFront origin access control |
| Jobs | SQS |
| Mail | SES |
| Shell | SSM |
| Deploy | GitHub Actions through OIDC |

Standing cost is about £33–£45 per month including VAT. Multi-AZ RDS waits until the availability number or the revenue justifies it.

RDS has no public address. Port 5432 accepts the application security group, with TLS certificate verification. Deletion protection stays on. Point-in-time recovery is 35 days, and snapshots are copied to a separate backup account.

EC2 accepts HTTPS from the CloudFront origin prefix list. Port 22 stays closed. S3 traffic uses a gateway endpoint.

Database roles:

| Role | Use |
| --- | --- |
| `app_migrator` | Schema changes during deploy |
| `app_runtime` | Web and worker traffic |
| `app_readonly` | Checks and exports |

`app_runtime` can read and write rows. Schema changes stay with `app_migrator`.

Local development uses a disposable PostgreSQL container and synthetic fixtures.

## Tenancy

Every studio-owned row has `tenant_id`. Foreign keys include it. One studio is seeded: Sahil Basumatary. There is no public studio signup.

## Identity

The owner account uses multi-factor authentication. Passwords and gallery PINs use Argon2id. Sessions use `Secure`, `HttpOnly`, `SameSite=Lax` cookies. Owner sessions end after 12 idle hours and after 7 days.

A gallery link is a 256-bit single-use token, valid for 30 minutes. PostgreSQL stores the SHA-256 hash. The grant behind the link is revocable. A client account is optional and only sees rows named in its grants.

Login, link requests, PIN attempts, and downloads are rate limited per account and per IP. The login response is the same for every submitted email address.

## Media

JPEG and PNG. Files land in a quarantine prefix, then one memory-capped worker checks type, size, and pixel dimensions. Derivatives are sRGB JPEG and WebP at fixed widths. GPS and camera serials are removed from derivatives. Object keys are random ids.

A gallery ZIP uses the same per-photograph check as a single download.

## Payments

Stripe Checkout takes card payments. The ledger moves on a signature-checked webhook, deduplicated by Stripe event id. The browser return URL refreshes the invoice page. A manual bank transfer is a ledger row with amount, date, reference, and actor.

Invoice totals are calculated on the server and frozen when the invoice is issued. A signed contract stores the rendered document that the signer saw.

## First production cut

- Portfolio, enquiry form, and legal pages
- Galleries, favourites, selection limits, and downloads
- Leads, contacts, and projects
- Availability, booking requests, and self-booking
- Questionnaires, quotes, contracts, and invoices
- Client portal for the job, documents, balance, and gallery
- Active Pixieset galleries, checked by SHA-256 before the domain moves

Pixieset currently holds about 3.45 GB. Enable MFA there and clear the billing warning before the export.

## Later

Folders, presets, bulk sharing, client-only sets, mini sessions, calendar and video calls, inbox, instalments, coupons, print sales, campaigns, video, and RAW. A native app waits on real demand.
