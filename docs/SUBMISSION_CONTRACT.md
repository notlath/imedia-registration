# Submission Contract v1

This document defines the HTTP contract between the WordPress plugin (producer) and the standalone app (consumer). Any future integration targeting the standalone app's `/api/submit` endpoint must conform to this contract.

---

## Canonical Fields (in priority order)

| Canonical Name | Typed Column | Target Tables |
|---|---|---|
| `course` | `registrations.course` | registrations |
| `start_date` | `registrations.start_date` | registrations |
| `end_date` | `registrations.end_date` | registrations |
| `email` | `registrations.email`, `contacts.email`, `applications.email` | all |
| `mobile` | `registrations.mobile`, `contacts.mobile`, `applications.mobile` | all |
| `address` | `registrations.address` | registrations |
| `subject` | `contacts.subject` | contacts |
| `message` | `contacts.message`, `applications.message` | contacts, applications |
| `position` | `applications.position` | applications |
| `name` | `registrations.name`, `contacts.name`, `applications.name` | all |

Fields that match a canonical name are stored in typed database columns. Fields that do not match any canonical name are stored in the `dynamic_data` JSON column.

---

## Required Payload Shape

```json
{
  "form_id": 42,
  "fields": {
    "name": "John Doe",
    "email": "john@example.com",
    "mobile": "09171234567",
    "course": "Web Development",
    "start_date": "2026-07-01",
    "end_date": "2026-09-30",
    "address": "123 Main St",
    "subject": "Inquiry",
    "message": "I want to enroll"
  },
  "_imf_timestamp": 1782345678
}
```

### Reserved Top-Level Keys

These keys are consumed by the application and must never appear inside `fields`:

| Key | Purpose |
|---|---|
| `form_id` | Maps the submission to a form route (target table) |
| `_imf_form_id` | Legacy alias for `form_id` (accepted but not recommended for new payloads) |
| `_imf_timestamp` | Unix seconds — replay protection (±300s window) |
| `signature` | Reserved for future use |

---

## Field Resolution Priority

When the WordPress plugin builds the payload, each form field is resolved to a canonical name using this priority order:

1. **Explicit `canonical_name`** — set per-field in the form builder via `_imf_field_canonical` metadata.
2. **Field name exact match** — the form-builder field's `name` attribute matches a canonical name exactly.
3. **Label inference** — the field's human-readable `label` is matched against canonical names (exact match first, then contains-match). This is a temporary fallback; label-inferred mappings are logged for migration.

**Collision handling:** If two source fields resolve to the same canonical key, the first value wins. A warning is logged.

---

## HMAC Signing

| Header | Format |
|---|---|
| `Content-Type` | `application/json; charset=utf-8` |
| `X-IMF-Signature` | `sha256=<hex>` — HMAC-SHA256 of the raw body bytes |
| `User-Agent` | `IMediaRegistration/3.0.0 (+https://...)` |

The signature covers the **exact JSON bytes** of the body (including `form_id`, `fields`, `_imf_timestamp`). Re-serializing with different key order or whitespace invalidates the signature.

---

## Date Format

Canonical fields `start_date` and `end_date` must be formatted as `YYYY-MM-DD`.

The WordPress plugin normalizes dates automatically when the target canonical field is `start_date` or `end_date`. Accepted input formats:

| Format | Example |
|---|---|
| `YYYY-MM-DD` | `2026-07-01` |
| `n/j/Y` | `7/1/2026` |
| `m/d/Y` | `07/01/2026` |
| `d/m/Y` | `01/07/2026` |

If none of these formats match, the value is passed through unchanged and may fail downstream validation.

---

## Response Codes

| Status | Body | Meaning |
|---|---|---|
| 201 | `{"success":true,"id":N}` | Submission accepted and stored |
| 400 | `{"success":false,"error":"invalid_form_id"}` | Missing or invalid `form_id` |
| 400 | `{"success":false,"error":"invalid_fields"}` | Missing or empty `fields` |
| 401 | `{"success":false,"error":"hmac_invalid"}` | Bad or missing signature |
| 401 | `{"success":false,"error":"hmac_timestamp_missing"}` | Missing `_imf_timestamp` |
| 401 | `{"success":false,"error":"hmac_timestamp_stale"}` | Timestamp outside ±300s window |
| 404 | `{"success":false,"error":"no_route"}` | No form route for this `form_id` |
| 422 | `{"success":false,"error":"invalid_target"}` | Required canonical fields missing |
| 500 | `{"success":false,"error":"insert_failed"}` | Database write failed |

---

## Backward Compatibility

The standalone app's `SubmitController` accepts two legacy payload shapes in addition to the current contract:

### Legacy flat (pre-contract)
```json
{
  "_imf_form_id": 42,
  "name_a1b2": "John",
  "email_d4e5f6": "john@example.com"
}
```
Fields are read from the top level (minus reserved keys). No canonical mapping is applied — all data lands in `dynamic_data`.

### Legacy wrapped (transitional)
```json
{
  "_imf_form_id": 42,
  "fields": { ... },
  "_imf_timestamp": 1234567890
}
```
Uses the `fields` wrapper but still reads `_imf_form_id`. Canonical mapping works if field names match.

Both legacy shapes are accepted but logged. Migrate to the current contract when possible.
