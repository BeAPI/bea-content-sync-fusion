<a href="https://beapi.fr">![Be API Github Banner](.github/banner-github.png)</a>

# BEA - Content Sync Fusion

WordPress **multisite** plugin to **synchronize posts, attachments, taxonomies, and cross-site relations** between blogs using **emitter / receiver** rules, an **async queue**, and a **global relations table**.

**Current version:** 3.13.0

## Requirements

| Requirement | Notes |
|-------------|--------|
| WordPress | [Multisite](https://developer.wordpress.org/advanced-administration/multisite/create-network/) install, **4.9+** ([`WP_Site_Query`](https://developer.wordpress.org/reference/classes/wp_site_query/)) |
| PHP | **8.3+** (see plugin header `Requires PHP`) |
| Scheduling | Server **cron** should drive the queue via **WP-CLI** (see [Cron & queue](#cron--queue)) |

## Installation

1. **GitHub releases** — ZIP from [releases](https://github.com/BeAPI/bea-content-sync-fusion/releases).  
2. **Composer** — add to your project:

   ```json
   "require": {
     "bea/bea-content-sync-fusion": "*"
   }
   ```

3. **Git clone** — run `composer install` at the plugin root (PHP dependencies include `beapi/gutenberg-serializer`).

**Network-activate** the plugin (Network Admin → Plugins). Activation creates or updates global tables: `bea_csf_relations`, `bea_csf_queue`, `bea_csf_queue_maintenance`.

### Major upgrades (queue schema)

Some releases alter the queue table schema. Follow the notes in [CHANGELOG.md](CHANGELOG.md) (e.g. network deactivate / reactivate when required).

## Configuration

Use **Network Admin** to define synchronizations (emitters, receivers, post types, taxonomies, media behaviour). **Metaboxes** on individual sites control manual mode, exclusions, and attachment inclusion depending on settings.

### Optional constant

- `BEA_CSF_MEDIA_FEATURE` — defaults to `true`; set to `false` **before** the plugin loads to disable shared media handling (see `bea-content-sync-fusion.php`).

## Cron & queue

Processing is queue-based and intended to run through **WP-CLI**. A reference Bash driver is included:

- [`cron/cronjob.sh`](cron/cronjob.sh) — arguments: network URL, WP-CLI binary, WordPress path, alternate queue flag, extra WP-CLI args.

Typical setup: run **every minute** on the server; adjust the CLI binary (`wp`, `lando wp`, etc.).

More informations on the [wiki](https://github.com/BeAPI/bea-content-sync-fusion/wiki/CRON-Jobs).

## WP-CLI

Command namespace: **`content-sync-fusion`**.

```bash
wp help content-sync-fusion
```

Registered subcommands:

| Command | Purpose |
|---------|---------|
| `content-sync-fusion queue` | Queue stats, flush / pull, list site URLs with queued work |
| `content-sync-fusion flush` | Flush / targeted sync (see `wp help` for flags) |
| `content-sync-fusion resync` | Resync content (e.g. `--smart=true` to iterate emitter blogs only) |
| `content-sync-fusion relation` | Mirror two sites / rebuild relations (e.g. after cloning) |
| `content-sync-fusion migration` | Migrate legacy meta-based relations to the relations table |

For flags such as `--quantity`, `--alternativeq`, or post type / taxonomy / attachment filters, run `wp help content-sync-fusion <subcommand>`.

## WordPress compatibility

Targets **WordPress 4.9+** on multisite (including current 6.x releases while APIs remain compatible).

## Third-party integrations

Dedicated addons load when these plugins are active:

- [WooCommerce](https://wordpress.org/plugins/woocommerce/) (products & variations)
- [Polylang](https://wordpress.org/plugins/polylang/)
- [Advanced Custom Fields](https://www.advancedcustomfields.com/) (including Gutenberg ACF blocks)
- [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) (e.g. meta images)
- [Post Types Order](https://wordpress.org/plugins/post-types-order/)
- [The Events Calendar](https://theeventscalendar.com/) family
- [Revisionize](https://wordpress.org/plugins/revisionize/)
- [Multisite Clone Duplicator](https://wordpress.org/plugins/multisite-clone-duplicator/)
- **Gutenberg** block serialization / sync
- [Members](https://wordpress.org/plugins/members/) (metabox compatibility)

## Developer API (overview)

- `bea_csf_upload_dir()` — cached helper around `wp_upload_dir()` for multisite.
- `register_synchronization( $args )` — programmatic registration (see [documentation](https://github.com/BeAPI/bea-content-sync-fusion/wiki/Synchronizations-API)).

Many `bea_csf_*` filters and actions exist in the codebase for capabilities, merge behaviour, fields, etc.

## Development

```bash
composer install
composer cs      # PHPCS (phpcs.xml)
composer test-unit
composer test-wpunit
```

## Changelog

Full history: **[CHANGELOG.md](CHANGELOG.md)**.

## Who?

Built by [Be API](https://beapi.fr). This plugin is **maintained** without a promise of free support—please [open an issue](https://github.com/BeAPI/bea-content-sync-fusion/issues) if needed.  
Donations: [PayPal](https://www.paypal.me/BeAPI).

## License

GPL-2.0-or-later — see [LICENSE](LICENSE).
