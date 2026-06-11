# Tec Search Console

Google Search Console integration for PrestaShop 8 and 9, developed by Tecnoacquisti.com®.

The module connects a PrestaShop back office to the Google Search Console API, stores SEO metrics locally, and exposes Search Console data where merchants need it most: the module dashboard, the native back-office dashboard, and the product edit page.

## Features

- Google OAuth 2.0 connection for Search Console.
- Search Analytics metrics for the last 28 complete days.
- Dashboard cards for clicks, impressions, average CTR, and average position.
- Top pages and top queries ordered by clicks.
- Submitted sitemap list from Search Console.
- Back-office dashboard widget through `dashboardZoneTwo`.
- Product edit widget through `displayAdminProductsExtra`.
- Keyword breakdown for product URLs.
- Optional Google site verification meta tag.
- Daily cron endpoint for data synchronization.
- Bundled Google API dependencies, so production shops do not need Composer.

## Compatibility

| Requirement | Version |
| --- | --- |
| PrestaShop | 8.0 or newer |
| PHP | 8.1 or newer |
| Google API Client | Bundled in `lib/google_vendor/` |

Required PHP extensions:

- `curl`
- `json`
- `openssl`
- `pdo_mysql`

## Installation

1. Upload the module directory to `modules/tec_searchconsole/`.
2. Install the module from the PrestaShop Module Manager.
3. Open **Search Console SEO** in the back office.
4. Configure the Google OAuth credentials.
5. Connect the Google account and authorize Search Console access.

Release archives include `lib/google_vendor/`, so merchants do not need to run Composer after installation.
The bundled Google services package is trimmed to the Search Console/Webmasters service used by the module.

## Google OAuth Setup

Create OAuth credentials in Google Cloud Console before connecting the module.

### 1. Create or Select a Google Cloud Project

1. Open <https://console.cloud.google.com/>.
2. Select an existing project or create a new one.
3. Make sure the selected project is the one that will be used for this PrestaShop shop.

### 2. Enable the Search Console API

1. Open **APIs & Services > Library**.
2. Search for **Google Search Console API**.
3. Open the API page and click **Enable**.

### 3. Configure the OAuth Consent Screen

1. Open **APIs & Services > OAuth consent screen**.
2. Select the app type required for your Google account or organization.
3. Fill in the app name, support email, and developer contact email.
4. Add the required scope for Search Console access:

```text
https://www.googleapis.com/auth/webmasters.readonly
```

5. Save the consent screen configuration.

If the app is still in testing mode, add the Google account used to connect Search Console as a test user.

### 4. Create the OAuth Client ID

1. Open **APIs & Services > Credentials**.
2. Click **Create credentials > OAuth client ID**.
3. Select **Web application**.
4. Add the shop callback URL as an authorized redirect URI:

```text
https://example.com/modules/tec_searchconsole/callback.php
```

Replace `example.com` with the real shop domain.

5. Save the credential.
6. Copy the generated **Client ID** and **Client Secret**.

The module requires access to Search Console data for the configured property URL. The Google account used during connection must have access to that Search Console property.

## Configuration

The module configuration page includes separate panels for:

- Search Console OAuth credentials and property URL.
- Search Console connection status.
- Manual synchronization.
- Cron URL.
- Google site verification tag.

The client secret field uses a masked value. If the saved mask is submitted unchanged, the existing secret is preserved.

### Configure the Module

1. Open the PrestaShop back office.
2. Go to **Search Console SEO**.
3. Paste the Google **Client ID**.
4. Paste the Google **Client Secret**.
5. Enter the Search Console property URL exactly as it appears in Search Console, for example:

```text
https://www.example.com
```

6. Click **Save**.
7. Click **Connect Google**.
8. Select the Google account that has access to the Search Console property.
9. Approve the requested Search Console permission.
10. After the redirect back to PrestaShop, click **Sync now** or configure the cron URL.

If the property URL in the module does not match the property in Search Console, Google may return empty metrics even if the connection succeeds.

## Cron

Use the cron URL displayed in the module configuration page:

```text
https://example.com/modules/tec_searchconsole/cron.php?token=CRON_TOKEN
```

The CLI endpoint is also supported:

```bash
php /path/to/prestashop/modules/tec_searchconsole/cron.php --token=CRON_TOKEN
```

We recommend running the cron once per day after Search Console has updated the previous day's data.

## Back-Office Dashboard

The module registers `dashboardZoneTwo` and displays:

- Clicks 28 days.
- Impressions.
- Average CTR.
- Average position.
- Top queries.
- Submitted sitemaps.

The widget is read-only and links to the full Search Console SEO dashboard.

## Product SEO Widget

The module registers `displayAdminProductsExtra` and shows Search Console metrics inside the product edit page when product URLs can be matched with Search Console page data.

Displayed data includes:

- Clicks.
- Impressions.
- CTR.
- Average position.
- Top keyword breakdown.

## Site Verification Tag

Merchants can paste a full Google verification meta tag or just the verification token. The module stores only the token and renders the meta tag through `displayHeader`.

Example accepted input:

```html
<meta name="google-site-verification" content="verification-token">
```

## Development

Dependencies are intentionally installed in a private module directory:

```bash
composer install --no-dev --optimize-autoloader
```

Composer is configured with:

```json
{
  "config": {
    "vendor-dir": "lib/google_vendor"
  }
}
```

Do not move dependencies to a root `vendor/` directory, because PrestaShop may load module vendors globally and create package conflicts.

The release package intentionally keeps only the Search Console/Webmasters classes from `google/apiclient-services`. If dependencies are reinstalled, remove unused Google service classes again before packaging and regenerate the optimized autoloader.

## Support

For support, contact Tecnoacquisti.com:

- Website: <https://www.tecnoacquisti.com>
- Help desk: <https://help.tecnoacquisti.com>
- Email: <helpdesk@tecnoacquisti.com>

## License

MIT License.
