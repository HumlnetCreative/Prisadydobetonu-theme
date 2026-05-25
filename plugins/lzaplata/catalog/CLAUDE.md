# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Scope

This is the `lzaplata/catalog` October CMS plugin — the product catalog (categories + products) for the parent site at `prisadydobetonu.stachema.cz`. Plugin namespace: `LZaplata\Catalog`. See the project root `CLAUDE.md` for stack/commands (October 4.x, Laravel 12, PHP 8.2+, Laravel Mix); this file documents only what's specific to the plugin.

## Architecture

### Models (`models/`)
- `Category` — uses `NestedTree` (hierarchical) + `Multisite` + `SoftDelete`. `attachOne` image, `belongsToMany` products. Table: `lzaplata_catalog_categories`.
- `Product` — uses `Multisite` + `SoftDelete`. Has a large `$propagatable` array — every translatable field must be listed there or it won't sync across sites. Table: `lzaplata_catalog_products`.
- `CategoryImport` / `ProductImport` — import models (used with the `lzaplata/import` plugin).
- `Settings` — singleton model registered as a backend settings page (icon `shopping-cart`). Holds the `product_page` CMS page reference used by the XML feed and by components to build product URLs.

### Components (`components/`)
Four frontend components registered in `Plugin.php`: `categories`, `category`, `products`, `product`. Default partials live in matching lowercase subdirectories (`components/products/default.htm`, etc.). Note: there is no `components/category/` directory — the `Category` component renders without a default partial in that location.

### Backend controllers (`controllers/`)
Standard October backend CRUD for `Categories`, `Products`, plus `Settings`. Per-controller config lives in `controllers/<name>/` (config_form.yaml, config_list.yaml, columns.yaml, fields.yaml, _list_toolbar.htm, etc.).

### Feed controller (`controllers/Feed.php`)
Plain Laravel controller (not an October backend controller) routed in `routes.php` at `/feed/products`. Renders `views/feeds/products.blade.php` (or twig) as `text/xml`. Product URLs are built against the page configured in `Settings::get("product_page")`.

### Content fields (`contentfields/`)
`CategoryPicker` and `ProductPicker` — custom Tailor content fields registered via `registerContentFields()`, available as `categorypicker` / `productpicker` in blueprint YAML.

### Console commands (`console/`)
- `catalog.importfiles` — `ImportFiles`
- `catalog.deleteresizesimages` — `DeleteResizedImages` (sic — typo is in the registered command name)

### Markup tags
`Plugin::registerMarkupTags()` exposes two Twig functions to all themes:
- `file_exists(path, disk = "media")` — checks a file on a Laravel Storage disk.
- `preg_match(pattern, subject)` — returns the matches array.

### Media manager hook
`Plugin::boot()` clears `Storage::disk("resources")/resize` on every media upload, invalidating the cached resized-image directory.

## Migrations & versioning

Migrations are listed in `updates/version.yaml`. Each new schema change needs a new version entry and a migration file under `updates/`. Version-only entries (no migration file) are valid for changelog-only releases — see e.g. `v1.0.2`.

## Conventions

Follow the global PHP/Twig/SASS conventions in `~/.claude/CLAUDE.md` (double quotes, multi-line arrays with aligned `=>`, docblocks on every method, BEM for any frontend partials). Lang keys are under `lzaplata.catalog::lang.*`; Czech is the primary locale (`lang/cs/`).
