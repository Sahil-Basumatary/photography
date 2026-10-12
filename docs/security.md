# Security

## Data

| Data | Class | Where |
| --- | --- | --- |
| App secrets, MFA seed, webhook secret, database credentials | Restricted | SSM SecureString or Secrets Manager |
| Password, PIN, and token hashes, session ids | Restricted | PostgreSQL |
| Contracts, signature evidence, invoices, payment ledger | Restricted | PostgreSQL |
| Client contact details, questionnaires, notes, grants, favourites | Confidential | PostgreSQL |
| Originals and derivatives | Confidential | Private S3 |
| EXIF kept for the studio | Confidential | PostgreSQL JSONB |
| Audit events | Confidential | PostgreSQL |
| Redacted request logs | Internal | CloudWatch, 30 days |
| Published portfolio pages and selected photographs | Public | CloudFront |

A photograph of a child stays confidential. Publishing it requires a stored parent or guardian permission.

Stripe holds card details. A manual payment stores the date, amount, and bank reference.

Log records keep the request id, tenant id, actor id, route, status, and timing. They drop cookies, links, signed URLs, passwords, PINs, MFA codes, webhook signatures, email addresses, phone numbers, addresses, questionnaire answers, and contract bodies.

## Access

| Who | Scope |
| --- | --- |
| Owner | Studio console, after MFA |
| Client account | Grants for bookings, documents, invoices, and galleries |
| Gallery link | One gallery until the grant expires or is revoked |
| Public visitor | Published pages and the enquiry form |

Gallery permissions are separate: view, favourite, submit a selection, download a web size, download an original, manage the grant.

Private misses return 404. A visible action that the user cannot perform returns 403. State-changing requests carry a CSRF token.

Audit rows are written in the same transaction for sign-in, grant changes, downloads, contracts, payments, exports, deletions, and permission changes. A row stores actor, action, object id, request id, time, and source address.

## Retention

Financial periods still need a solicitor's check before the first paid contract.

| Record | Keep |
| --- | --- |
| Magic-link hash | 30 minutes, then delete the row within 24 hours |
| Sign-in and PIN attempts | 90 days |
| Abandoned uploads | 24 hours |
| Galleries | 24 months after delivery, or the period written in the job contract |
| Enquiries, bookings, questionnaires, selections | 24 months after the job closes |
| Invoices and payments | 6 years after the financial year |
| Signed contracts | 6 years after the contract ends |
| Audit events | 24 months |
| RDS point-in-time recovery | 35 days |
| Off-account snapshots | 90 days |

Erasure revokes sessions and grants, removes the live rows, deletes the S3 objects, and writes a tombstone with an irreversible id and a timestamp. A later restore replays tombstones before traffic returns.

## Service

Monthly availability target is 99.0%, measured at CloudFront. The 30-day error budget is 7 hours 12 minutes.

| Failure | Point | Time |
| --- | --- | --- |
| Bad release | No database loss | 30 minutes, previous image |
| Bad data change | 5 minutes | 4 hours |
| Single-AZ database loss | Latest restorable point | 4 hours |

Ordinary reads target p95 under 300 ms. Cached public images in the UK target p95 time to first byte under 200 ms. A normal JPEG is viewable within 10 minutes. A 2,000-file upload finishes within 60 minutes.

Alarms: authentication bursts, queue age over 15 minutes, any dead-letter message, RDS backup age, RDS health, HTTP errors, and the £45 budget line.

## Incident

| Severity | Example | Action |
| --- | --- | --- |
| 1 | Photographs, contracts, or the database exposed | Maintenance mode, start the ICO clock |
| 2 | Owner account, payment state, or a malicious upload | Revoke credentials, keep evidence |
| 3 | One failed job or a blocked abuse attempt | Fix in the next change |

Record the detection time and the first evidence. Revoke sessions, grants, and affected AWS or Stripe credentials. Leave RDS recovery, S3 versions, and CloudTrail in place. If personal data was accessed, assess ICO notification from the time of awareness. The 72-hour clock is real; the notification decision follows current ICO guidance.

Keep the request ids, redacted logs, CloudTrail events, release version, and the action timeline in AWS.

Rollback uses the previous image. Database loss uses the restore steps below. A leaked grant is revoked. A leaked owner password is rotated and MFA is enrolled again. Stripe keys are rolled in Stripe, then in Secrets Manager.

Afterward, write the cause, the data involved, the customer impact, and the code or infrastructure change.

## Restore

A drill restores a copy and leaves production on the original instance.

1. Note the time, cause, and last known good point.
2. Stop deploys. Use maintenance mode when live rows are wrong.
3. Restore a new private encrypted RDS instance to the chosen time.
4. Put a new master credential in Secrets Manager.
5. Check the migration version, latest ledger and audit timestamps, table counts, and tombstones over verified TLS.
6. Replay tombstones that predate the backup.
7. Smoke test with `app_runtime`: owner sign-in, one gallery grant, one refused cross-tenant read.
8. Confirm a connection outside the application security group fails.
9. Move the application secret, restart web and workers, and watch errors for 30 minutes.
10. Destroy a drill instance the same day.

The drill passes when the recovered point is within five minutes, the smoke tests pass, and the work finishes within four hours.
