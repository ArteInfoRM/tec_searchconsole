# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows semantic versioning.

## [Unreleased]

## [1.0.4] - 2026-06-12

### Added

- Added SEOZoom API v2 key, database, and cache settings to the back-office module dashboard.
- Added cached SEOZoom API v2 domain metrics for Zoom Authority, Zoom Trust, estimated organic traffic, and organic keywords.
- Added cached SEOZoom API v2 search volume for dashboard top queries.
- Added direct SEOZoom links for the configured domain and product URLs.

## [1.0.3] - 2026-06-12

### Added

- Added configurable database retention for Search Console history and generated alerts.
- Added manual cleanup and storage statistics to the back-office dashboard.
- Added cron cleanup for old Search Console data after each successful synchronization.
- Added global and product-level exports for stored Search Console data in JSON, CSV, and XML formats.
- Added default export format and period settings for back-office exports.
- Added direct back-office link to open the configured property in Google Search Console.
- Added modern Italian translations for the `Modules.Tecsearchconsole.Admin` translation domain.

### Fixed

- Fixed back-office form submissions on PrestaShop 9 by relying on the native admin action token.
- Fixed direct access to OAuth callback and cron endpoints under the PrestaShop 9 modules directory rules.
- Fixed visual alignment of the stored data table and export controls in the back-office dashboard.

### Changed

- Migrated module UI translations from the legacy module translation system to modern PrestaShop domains.
- Simplified product edit export controls so product exports use the module-level export settings.

## [1.0.1] - 2026-06-11

### Changed

- Trimmed bundled Google API service classes to the Search Console/Webmasters service used by the module.
- Regenerated the optimized Composer autoloader after vendor trimming.
- Reduced the release archive size while keeping the module installable without Composer.
- Switched module license metadata and headers to MIT.
- Configured Composer autoload as non-prepending for PrestaShop compatibility.
- Added PrestaShop context guards to module-owned PHP files reported by the validator.
- Added directory index files under the bundled library tree.
- Replaced Smarty `string_format` usage in module templates with validator-friendly formatting.
- Removed the unused Google filesystem cache class to avoid serialized payload warnings in validation.
- Removed direct `Context::getContext()` usage from module-owned files reported by the validator.
- Removed direct access to the protected module context from the OAuth callback.

## [1.0.0] - 2026-06-11

### Added

- Initial Google Search Console OAuth 2.0 connection.
- Google API Client dependencies bundled under `lib/google_vendor/`.
- Search Console configuration page with client ID, masked client secret, property URL, connection status, callback URL, and cron URL.
- Separate Google site verification panel with dedicated save action.
- `displayHeader` integration for the Google site verification meta tag.
- Search Console dashboard with last 28 complete days metrics.
- Live KPI cards for clicks, impressions, average CTR, and average position.
- Top pages and top queries ordered by clicks.
- Submitted sitemap list from Search Console.
- Manual synchronization action.
- Cron endpoint with token validation.
- `dashboardZoneTwo` widget for the native PrestaShop back-office dashboard.
- Dashboard widget top queries and submitted sitemap summary.
- Product edit SEO widget through `displayAdminProductsExtra`.
- Product keyword breakdown by Search Console query.
- Local database tables for configuration, Search Console data, and alerts.
- Safe OAuth callback handling with back-office return URL persistence.

### Fixed

- Prevented the module menu tab from being installed as a child of `AdminStats`, keeping the native PrestaShop statistics page clickable.
- Avoided PrestaShop autoload conflicts by using the private `lib/google_vendor/` dependency directory.
- Fixed OAuth callback redirects that could return to an invalid module URL with `gsc_error=csrf`.
- Aligned dashboard metrics with Google Search Console's last 28 complete days range.

### Changed

- Search Console dashboard and dashboard widget now use live API data when the account is connected.
- Stored client secrets are masked in the back office and preserved when the submitted value is unchanged.
