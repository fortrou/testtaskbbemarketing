# Moda Plugin (Simple Overview)

## Structure 
All classes are loading throug PSR-4 autoload

## Classes
- `Moda\ModaApi` — REST API routes for stylists, reps, and celebrity links.
- `Moda\ModaAjax` — admin AJAX handlers (save stylist details, search celebrities).
- `Moda\ModaAdminLayouts` — WP Admin UI (list + detail screens) using `WP_List_Table`.
- `Moda\ModaCli` — WP-CLI seeding command.
- `Moda\DB\Abstracts\ModaDB` — base DB class (abstract).
- `Moda\DB\ModaStylists` — CRUD for stylists + table creation.
- `Moda\DB\ModaCelebrities` — CRUD for celebrities + attach/detach to stylist + table creation.
- `Moda\DB\ModaStylistReps` — CRUD for reps + table creation.
- `Moda\ModaStylistsListTable` — list table for stylists in admin.

## REST API Endpoints (`/wp-json/moda/v1`)
All write endpoints require `manage_options` permission (401 if not logged in, 403 if forbidden).

- `GET /stylists`  
  Query params: `page`, `per_page`, `q` (search by stylist name), `sort` (`name` or `updated_at`), `order` (`ASC`/`DESC`), `celebrity` (ID or celebrity name).  
  Response: basic stylist fields + `celebrity_count`.  
  http://wplocal.test:8080/wp-json/moda/v1/stylists?page=1&per_page=20&celebrity=Alex

- `GET /stylists/{id}`  
  Returns stylist, reps, and celebrities.
  http://wplocal.test:8080/wp-json/moda/v1/stylists/2000

- `POST /stylists`  
  Create a stylist.
  http://wplocal.test:8080/wp-json/moda/v1/stylists

- `PATCH /stylists/{id}`  
  Update a stylist.
  http://wplocal.test:8080/wp-json/moda/v1/stylists/128

- `POST /stylists/{id}/celebrities/{celebrity_id}`  
  Attach a celebrity to a stylist.
  http://wplocal.test:8080/wp-json/moda/v1/stylists/200/celebrities/556

- `DELETE /stylists/{id}/celebrities/{celebrity_id}`  
  Detach a celebrity from a stylist.
  http://wplocal.test:8080/wp-json/moda/v1/stylists/200/celebrities/556

- `POST /stylists/{id}/reps`  
  Add a representative to a stylist.
  http://wplocal.test:8080/wp-json/moda/v1/stylists/200/reps

- `DELETE /reps/{id}`  
  Remove a representative.
  http://wplocal.test:8080/wp-json/moda/v1/reps/669

## Admin AJAX Actions
- `moda_save_stylist_details` — save stylist fields from admin UI.
- `moda_search_celebrities` — search celebrities not yet linked to the stylist.

## WP-CLI
- `wp moda seed --stylists=2000 --celebs=5000 --links=30000 [--reps=1000]`  
  Seeds stylists, celebrities, links, and reps.


## Indexes for database
- `moda_stylists`: `idx_full_name` (full_name), `idx_updated_at` (updated_at)
- `moda_celebrities`: `idx_celeb_full_name` (full_name) (for search and filter by name),  `idx_industry` (industry) (for scale, search and filter by industry)
- `moda_stylist_celebrity`: `uniq_stylist_celebrity` (stylist_id, celebrity_id), `idx_stylist_id` (stylist_id), `idx_celebrity_id` (celebrity_id)
- `moda_stylist_reps`: `idx_stylist_id` (stylist_id)
