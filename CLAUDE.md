# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

This is the active October CMS theme for `prisadydobetonu.stachema.cz`. The project-level `CLAUDE.md` (one directory up, two parents up) and the global `~/.claude/CLAUDE.md` cover stack, Twig/SASS/HTML conventions, and BEM rules — read them first. This file only documents what is specific to working *inside* the theme.

## Frontend build (run from this directory)

- `npx mix watch` — dev build + BrowserSync (proxy `localhost83/hucr/prisadydobetonu.stachema.cz`)
- `npx mix` — one-off dev build
- `npx mix --production` — production build
- `npm install` after pulling theme dep changes

Pipeline is Laravel Mix (`webpack.mix.js`), not Vite. Entries are `assets/src/sass/theme.sass` and `assets/src/js/theme.js`. Vendor libs (Bootstrap 5.3, jQuery, Popper, Swiper, lightgallery JS+CSS, lightgallery fonts) are copied separately by `webpack.mix.js` — when adding a new vendor asset, register it there, do not `@import` it through SASS/JS.

BrowserSync watches `layouts/`, `pages/`, `partials/`, `assets/src/sass/*`, `assets/src/js/*`. Files outside these globs do not trigger reload.

## Theme structure

- `layouts/default.htm` — the only layout
- `pages/*.htm` — top-level routes (Czech slugs, e.g. `produkty.htm`, `kontakty-region-jih.htm`). Detail pages use October's `:slug` params (`produkt.htm`, `aktualita.htm`, `kategorie.htm`, `video.htm`, `zajimavost.htm`, `akce-polozka.htm`).
- `partials/` — shared blocks; new BEM blocks live here.
- `assets/src/sass/` — `theme.sass` (entry) and `_custom.scss` (Bootstrap overrides / variable definitions imported first).
- `assets/src/js/theme.js` — single JS entry.
- `app/` and `plugins/` inside the theme are scaffolding copies meant for the project root per `README.md`; do not edit them as if they were the live code — the running plugins are at the project root.
- `theme.yaml` declares required plugins and backend-editable theme config (`phone`, `email`, `youtube`, `facebook`, `instagram`), accessed in Twig via `this.theme.<key>`.

## Conventions specific to this theme

- **SASS architecture debt:** the project/global rule is "`theme.sass` is `@import` only, one `_block.sass` per BEM block." The current `theme.sass` violates this — most styles are written inline against legacy non-BEM selectors (`#categories .categories .category`, `header .top .nav li a`, etc.) and `_custom.scss` is the only partial. When you add a new block, create its `_block-name.sass` partial and `@import` it from `theme.sass`; do not extend the inline section. Do not opportunistically refactor existing inline blocks unless asked.
- **Legacy non-BEM IDs/classes** (`#categories`, `#products`, `#contacts`, `.products .product`, `.posts .post`, `.videos .video`, `.products-swiper`, `.slider-swiper`, `.post-content`, etc.) are load-bearing — they're referenced from both SASS and Twig. Renaming them is a multi-file change; prefer adding new BEM blocks alongside rather than renaming existing ones.
- **Mask-icon system:** `.mi-<name>` classes in `theme.sass` use the `@mixin mask-image` helper to render SVGs from `assets/images/icons/*.svg` as CSS masks (colorable via `background-color`). Add new icons by dropping the SVG in `assets/images/icons/` and adding a `&-<name>` entry under `.mi`.
- **Czech UI:** all visible strings, slugs, and page filenames are Czech. Keep new copy in Czech unless the user says otherwise.
- **`control-*` classes** are October framework JS hooks — never rename them, even when otherwise BEM-ifying markup.
