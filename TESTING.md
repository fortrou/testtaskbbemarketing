# Plugin Test Checklist

## Install / Activate
- Activate plugin without PHP errors.
- Admin menu “Moda Database” appears.
- Tables created with correct prefix and indexes.

## Admin UI
- Stylists list renders with columns and pagination.
- Search by name works.
- Filter by celebrity works.
- Clicking a stylist opens detail page.
- Detail page shows stylist fields, reps list, celebrities list.
- Add rep form creates rep and shows in list.
- Remove rep deletes rep.
- Attach celebrity adds link.
- Detach celebrity removes link.
- Celebrity search (AJAX) returns only unlinked celebs.

## AJAX
- Save stylist details works with `stylist_*` payload keys.
- Invalid or missing nonce returns error.

## REST API
- `GET /stylists` supports `page`, `per_page`, `q`, `sort`, `order`, `celebrity`.
- `GET /stylists` returns `celebrity_count`.
- `GET /stylists/{id}` returns stylist + reps + celebrities; 404 if missing.
- POST/PATCH/DELETE endpoints require auth (401 unauth, 403 forbidden).
- Create stylist returns 201; invalid input returns 400.
- Update stylist returns 200; invalid input returns 400.
- Attach/detach celebrity works; missing link returns 404.
- Add/remove rep works; missing rep returns 404.

## WP-CLI
- `wp moda seed --stylists=2000 --celebs=5000 --links=30000 [--reps=1000]` seeds data.
- Re-running seed resets data (TRUNCATE).
