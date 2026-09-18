# Saiga Conservation Alliance — website

The approved design (the `SCA-design` Next.js mockup), rebuilt in plain PHP 8
with MySQL, so it runs on ordinary shared hosting. Every piece of text and
every image can be edited at `/admin`.

## How it fits together

```
public/            web root — index.php front controller, CSS/JS, images, uploads
app/
  bootstrap.php    config, helpers, first-run install
  lib/             db, content access, components (ported 1:1 from the design), icons, forms
  views/pages/     one template per page type
  views/partials/  header and footer
  admin/           /admin — router, form builder, media library
  views/admin/     admin screens
database/seed/     the design's original content, loaded into an empty database
resources/css/     Tailwind source (design tokens from the mockup's globals.css)
```

- **Design parity.** Templates use the exact Tailwind classes of the React
  components, compiled by Tailwind v4 from the PHP files. Motion (reveal on
  scroll, header shrink, dropdowns, mobile menu, count-up numbers, archive
  filters, accordions) lives in `public/assets/js/app.js`.
- **Content.** Each page is one JSON document in the `pages` table; news,
  projects, Our Work themes and grant programmes are rows in `entries`. The
  admin builds its forms from that JSON, so adding a field to a template and to
  `database/seed/pages.json` is all it takes to make it editable.
- **Install.** None. The first request creates the tables, loads the seed
  content and creates the first admin user from `app/config.local.php`.

## Local development

```bash
cp app/config.example.php app/config.local.php   # fill in a local MySQL database
npm install
npm run watch:css                                # rebuild CSS while editing
php -S 127.0.0.1:8090 -t public public/index.php
```

## Deploying

Pushing to `main` runs `.github/workflows/deploy.yml`: it builds the CSS and
uploads changed files over FTPS. Repository secrets it needs:

| Secret | Value |
| --- | --- |
| `FTP_SERVER` | FTP host |
| `FTP_USERNAME` | FTP account whose home is the site folder |
| `FTP_PASSWORD` | its password |
| `SITE_URL` | e.g. `https://staging.saiga-conservation.org` |

`app/config.local.php` and `public/uploads/` exist only on the server and are
never overwritten by a deploy.
