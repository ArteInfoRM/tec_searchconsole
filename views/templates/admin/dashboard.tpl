{*
* 2009-2026 Tecnoacquisti.com
*
* For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
* @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
* @copyright 2009-2026 Tecnoacquisti.com
* @license   https://opensource.org/licenses/MIT MIT License
*}

<form method="post" action="{$gsc_form_action|escape:'html':'UTF-8'}" class="form-horizontal tec-gsc-config-form">
  <div class="panel tec-gsc-dashboard">
    <div class="panel-heading">
      <i class="icon-search"></i> {l s='Search Console SEO' d='Modules.Tecsearchconsole.Admin'}
    </div>

    <div class="panel-body">
      {if !$gsc_vendor_ready}
        <div class="alert alert-warning">
          {l s='Google API Client is not bundled in this module package. Install a complete module package before connecting Google Search Console.' d='Modules.Tecsearchconsole.Admin'}
        </div>
      {/if}

      <div class="row">
        <div class="col-md-6 tec-gsc-connection">
          <p class="tec-gsc-section-title">{l s='Connection' d='Modules.Tecsearchconsole.Admin'}</p>
          <p>
            <strong>{l s='Status' d='Modules.Tecsearchconsole.Admin'}:</strong>
            {if $gsc_config.is_connected}
              <span class="label label-success">{l s='Connected' d='Modules.Tecsearchconsole.Admin'}</span>
            {else}
              <span class="label label-warning">{l s='Not connected' d='Modules.Tecsearchconsole.Admin'}</span>
            {/if}
          </p>
          <p><strong>{l s='Last sync' d='Modules.Tecsearchconsole.Admin'}:</strong> {$gsc_config.last_sync|escape:'html':'UTF-8'}</p>
          <p><strong>{l s='OAuth callback URL' d='Modules.Tecsearchconsole.Admin'}:</strong><br>{$gsc_callback_url|escape:'html':'UTF-8'}</p>
          <p><strong>{l s='Cron URL' d='Modules.Tecsearchconsole.Admin'}:</strong><br>{$gsc_cron_url|escape:'html':'UTF-8'}</p>
          {if $gsc_search_console_url}
            <p>
              <a class="btn btn-default" href="{$gsc_search_console_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener noreferrer">
                <i class="icon-external-link"></i> {l s='Open in Search Console' d='Modules.Tecsearchconsole.Admin'}
              </a>
            </p>
          {/if}

          <div class="tec-gsc-actions">
            <a class="btn btn-primary{if !$gsc_vendor_ready} disabled{/if}" href="{$gsc_connect_url|escape:'html':'UTF-8'}">
              <i class="icon-link"></i> {l s='Connect Google' d='Modules.Tecsearchconsole.Admin'}
            </a>
            <a class="btn btn-default" href="{$gsc_sync_url|escape:'html':'UTF-8'}">
              <i class="icon-refresh"></i> {l s='Sync now' d='Modules.Tecsearchconsole.Admin'}
            </a>
            {if $gsc_config.is_connected}
              <a class="btn btn-danger" href="{$gsc_disconnect_url|escape:'html':'UTF-8'}">
                <i class="icon-unlink"></i> {l s='Disconnect' d='Modules.Tecsearchconsole.Admin'}
              </a>
            {/if}
          </div>
        </div>

        <div class="col-md-6 tec-gsc-settings">
          <div class="form-group">
            <label class="control-label col-lg-4" for="tec-gsc-client-id">{l s='Client ID' d='Modules.Tecsearchconsole.Admin'}</label>
            <div class="col-lg-8">
              <input id="tec-gsc-client-id" type="text" name="client_id" value="{$gsc_config.client_id|escape:'html':'UTF-8'}" maxlength="255" required>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4" for="tec-gsc-client-secret">{l s='Client Secret' d='Modules.Tecsearchconsole.Admin'}</label>
            <div class="col-lg-8">
              <input id="tec-gsc-client-secret" type="text" name="client_secret" value="{$gsc_config.client_secret|escape:'html':'UTF-8'}" maxlength="255">
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4" for="tec-gsc-site-url">{l s='Property URL' d='Modules.Tecsearchconsole.Admin'}</label>
            <div class="col-lg-8">
              <input id="tec-gsc-site-url" type="text" name="site_url" value="{$gsc_config.site_url|escape:'html':'UTF-8'}" maxlength="255" placeholder="https://example.com/">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel-footer">
      <button type="submit" name="submitTecGscConfig" class="btn btn-primary pull-right">
        <i class="process-icon-save"></i> {l s='Save' d='Modules.Tecsearchconsole.Admin'}
      </button>
      <div class="clearfix"></div>
    </div>
  </div>
</form>

<form method="post" action="{$gsc_form_action|escape:'html':'UTF-8'}" class="form-horizontal tec-gsc-verification-form">
  <div class="panel tec-gsc-verification">
    <div class="panel-heading">
      <i class="icon-check"></i> {l s='Search Console verification' d='Modules.Tecsearchconsole.Admin'}
    </div>
    <div class="panel-body">
      <div class="form-group">
        <label class="control-label col-lg-3" for="tec-gsc-verification-tag">{l s='HTML verification tag' d='Modules.Tecsearchconsole.Admin'}</label>
        <div class="col-lg-9">
          <textarea id="tec-gsc-verification-tag" name="verification_tag" class="form-control" rows="3" placeholder="&lt;meta name=&quot;google-site-verification&quot; content=&quot;...&quot;&gt;">{$gsc_verification_tag|escape:'html':'UTF-8'}</textarea>
          <p class="help-block">
            {l s='Paste the full Google Search Console HTML tag. The module stores only the verification token and prints the meta tag in the front-office header.' d='Modules.Tecsearchconsole.Admin'}
          </p>
        </div>
      </div>
    </div>
    <div class="panel-footer">
      <button type="submit" name="submitTecGscVerification" class="btn btn-primary pull-right">
        <i class="process-icon-save"></i> {l s='Save verification tag' d='Modules.Tecsearchconsole.Admin'}
      </button>
      <div class="clearfix"></div>
    </div>
  </div>
</form>

<form method="post" action="{$gsc_form_action|escape:'html':'UTF-8'}" class="form-horizontal tec-gsc-retention-form">
  <div class="panel tec-gsc-retention">
    <div class="panel-heading">
      <i class="icon-database"></i> {l s='Data retention' d='Modules.Tecsearchconsole.Admin'}
    </div>
    <div class="panel-body">
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label class="control-label col-lg-4" for="tec-gsc-data-retention">{l s='Search data retention' d='Modules.Tecsearchconsole.Admin'}</label>
            <div class="col-lg-8">
              <select id="tec-gsc-data-retention" name="data_retention_months" class="form-control">
                <option value="16"{if $gsc_retention.data_retention_months == 16} selected="selected"{/if}>{l s='16 months' d='Modules.Tecsearchconsole.Admin'}</option>
                <option value="12"{if $gsc_retention.data_retention_months == 12} selected="selected"{/if}>{l s='12 months' d='Modules.Tecsearchconsole.Admin'}</option>
                <option value="6"{if $gsc_retention.data_retention_months == 6} selected="selected"{/if}>{l s='6 months' d='Modules.Tecsearchconsole.Admin'}</option>
                <option value="3"{if $gsc_retention.data_retention_months == 3} selected="selected"{/if}>{l s='3 months' d='Modules.Tecsearchconsole.Admin'}</option>
                <option value="0"{if $gsc_retention.data_retention_months == 0} selected="selected"{/if}>{l s='Never delete' d='Modules.Tecsearchconsole.Admin'}</option>
              </select>
              <p class="help-block">
                {l s='Rows older than this period are removed during cron cleanup.' d='Modules.Tecsearchconsole.Admin'}
              </p>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4" for="tec-gsc-alert-retention">{l s='Alert retention' d='Modules.Tecsearchconsole.Admin'}</label>
            <div class="col-lg-8">
              <select id="tec-gsc-alert-retention" name="alert_retention_days" class="form-control">
                <option value="180"{if $gsc_retention.alert_retention_days == 180} selected="selected"{/if}>{l s='180 days' d='Modules.Tecsearchconsole.Admin'}</option>
                <option value="365"{if $gsc_retention.alert_retention_days == 365} selected="selected"{/if}>{l s='365 days' d='Modules.Tecsearchconsole.Admin'}</option>
                <option value="90"{if $gsc_retention.alert_retention_days == 90} selected="selected"{/if}>{l s='90 days' d='Modules.Tecsearchconsole.Admin'}</option>
                <option value="0"{if $gsc_retention.alert_retention_days == 0} selected="selected"{/if}>{l s='Never delete' d='Modules.Tecsearchconsole.Admin'}</option>
              </select>
              <p class="help-block">
                {l s='Alerts older than this period are removed during cron cleanup.' d='Modules.Tecsearchconsole.Admin'}
              </p>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <p class="tec-gsc-section-title">{l s='Stored data' d='Modules.Tecsearchconsole.Admin'}</p>
          <table class="table tec-gsc-retention-stats">
            <tbody>
              <tr>
                <th>{l s='Search rows' d='Modules.Tecsearchconsole.Admin'}</th>
                <td>{$gsc_retention_stats.data_total_rows|intval}</td>
              </tr>
              <tr>
                <th>{l s='Oldest search date' d='Modules.Tecsearchconsole.Admin'}</th>
                <td>{$gsc_retention_stats.data_oldest_date|escape:'html':'UTF-8'}</td>
              </tr>
              <tr>
                <th>{l s='Search rows ready for cleanup' d='Modules.Tecsearchconsole.Admin'}</th>
                <td>{$gsc_retention_stats.data_deletable_rows|intval}</td>
              </tr>
              <tr>
                <th>{l s='Alerts' d='Modules.Tecsearchconsole.Admin'}</th>
                <td>{$gsc_retention_stats.alert_total_rows|intval}</td>
              </tr>
              <tr>
                <th>{l s='Alerts ready for cleanup' d='Modules.Tecsearchconsole.Admin'}</th>
                <td>{$gsc_retention_stats.alert_deletable_rows|intval}</td>
              </tr>
            </tbody>
          </table>
          <button type="submit" name="submitTecGscCleanRetention" class="btn btn-default">
            <i class="icon-trash"></i> {l s='Clean old data now' d='Modules.Tecsearchconsole.Admin'}
          </button>
        </div>
      </div>
    </div>
    <div class="panel-footer">
      <button type="submit" name="submitTecGscRetention" class="btn btn-primary pull-right">
        <i class="process-icon-save"></i> {l s='Save retention settings' d='Modules.Tecsearchconsole.Admin'}
      </button>
      <div class="clearfix"></div>
    </div>
  </div>
</form>

<div class="panel tec-gsc-export">
  <div class="panel-heading">
    <i class="icon-download"></i> {l s='Data export' d='Modules.Tecsearchconsole.Admin'}
  </div>
  <div class="panel-body">
    <form method="post" action="{$gsc_form_action|escape:'html':'UTF-8'}" class="form-inline tec-gsc-export-form tec-gsc-global-export-form">
      <div class="form-group">
        <label for="tec-gsc-export-format">{l s='Format' d='Modules.Tecsearchconsole.Admin'}</label>
        <select id="tec-gsc-export-format" name="export_format" class="form-control">
          <option value="json"{if $gsc_export_settings.format == 'json'} selected="selected"{/if}>JSON</option>
          <option value="csv"{if $gsc_export_settings.format == 'csv'} selected="selected"{/if}>CSV</option>
          <option value="xml"{if $gsc_export_settings.format == 'xml'} selected="selected"{/if}>XML</option>
        </select>
      </div>

      <div class="form-group">
        <label for="tec-gsc-export-period">{l s='Period' d='Modules.Tecsearchconsole.Admin'}</label>
        <select id="tec-gsc-export-period" name="export_period" class="form-control">
          <option value="24h"{if $gsc_export_settings.period == '24h'} selected="selected"{/if}>{l s='Last 24 hours' d='Modules.Tecsearchconsole.Admin'}</option>
          <option value="7d"{if $gsc_export_settings.period == '7d'} selected="selected"{/if}>{l s='Last 7 days' d='Modules.Tecsearchconsole.Admin'}</option>
          <option value="28d"{if $gsc_export_settings.period == '28d'} selected="selected"{/if}>{l s='Last 28 days' d='Modules.Tecsearchconsole.Admin'}</option>
          <option value="3m"{if $gsc_export_settings.period == '3m'} selected="selected"{/if}>{l s='Last 3 months' d='Modules.Tecsearchconsole.Admin'}</option>
          <option value="6m"{if $gsc_export_settings.period == '6m'} selected="selected"{/if}>{l s='Last 6 months' d='Modules.Tecsearchconsole.Admin'}</option>
          <option value="12m"{if $gsc_export_settings.period == '12m'} selected="selected"{/if}>{l s='Last 12 months' d='Modules.Tecsearchconsole.Admin'}</option>
          <option value="16m"{if $gsc_export_settings.period == '16m'} selected="selected"{/if}>{l s='Last 16 months' d='Modules.Tecsearchconsole.Admin'}</option>
          <option value="all"{if $gsc_export_settings.period == 'all'} selected="selected"{/if}>{l s='All data' d='Modules.Tecsearchconsole.Admin'}</option>
        </select>
      </div>

      <button type="submit" name="submitTecGscExportSettings" class="btn btn-primary">
        <i class="icon-save"></i> {l s='Save export settings' d='Modules.Tecsearchconsole.Admin'}
      </button>

      <a class="btn btn-default" href="{$gsc_export_action|escape:'html':'UTF-8'}&amp;export_gsc_data=1">
        <i class="icon-download"></i> {l s='Export stored data' d='Modules.Tecsearchconsole.Admin'}
      </a>
    </form>
  </div>
</div>

<div class="row tec-gsc-metrics">
  <div class="col-lg-3 col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Clicks 28 days' d='Modules.Tecsearchconsole.Admin'}</div>
      <div class="tec-gsc-kpi">{$gsc_stats.last_28_days.clicks|default:0|intval}</div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Impressions' d='Modules.Tecsearchconsole.Admin'}</div>
      <div class="tec-gsc-kpi">{$gsc_stats.last_28_days.impressions|default:0|intval}</div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Average CTR' d='Modules.Tecsearchconsole.Admin'}</div>
      <div class="tec-gsc-kpi">{math equation='x * 100' x=$gsc_stats.last_28_days.ctr|default:0 format='%.2f'}%</div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Average position' d='Modules.Tecsearchconsole.Admin'}</div>
      <div class="tec-gsc-kpi">{math equation='x' x=$gsc_stats.last_28_days.position|default:0 format='%.2f'}</div>
    </div>
  </div>
</div>

<div class="panel tec-gsc-sitemaps">
  <div class="panel-heading">
    <i class="icon-sitemap"></i> {l s='Submitted sitemaps' d='Modules.Tecsearchconsole.Admin'}
  </div>
  {if $gsc_sitemaps|count}
    <table class="table">
      <thead>
        <tr>
          <th>{l s='Sitemap' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Type' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Submitted URLs' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Last submitted' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Last downloaded' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Status' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Warnings' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Errors' d='Modules.Tecsearchconsole.Admin'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$gsc_sitemaps item=sitemap}
          <tr>
            <td class="tec-gsc-url">{$sitemap.path|escape:'html':'UTF-8'}</td>
            <td>{$sitemap.type|escape:'html':'UTF-8'}</td>
            <td>{$sitemap.submitted_urls|intval}</td>
            <td>{$sitemap.last_submitted|escape:'html':'UTF-8'}</td>
            <td>{$sitemap.last_downloaded|escape:'html':'UTF-8'}</td>
            <td>
              {if $sitemap.is_pending}
                <span class="label label-warning">{l s='Pending' d='Modules.Tecsearchconsole.Admin'}</span>
              {else}
                <span class="label label-success">{l s='Processed' d='Modules.Tecsearchconsole.Admin'}</span>
              {/if}
              {if $sitemap.is_sitemaps_index}
                <span class="label label-info">{l s='Index' d='Modules.Tecsearchconsole.Admin'}</span>
              {/if}
            </td>
            <td>{$sitemap.warnings|intval}</td>
            <td>{$sitemap.errors|intval}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <div class="panel-body">
      <p class="text-muted">{l s='No submitted sitemaps available for the connected Search Console property.' d='Modules.Tecsearchconsole.Admin'}</p>
    </div>
  {/if}
</div>

<div class="row">
  <div class="col-md-6">
    {capture name=tec_gsc_top_pages_title}{l s='Top pages' d='Modules.Tecsearchconsole.Admin'}{/capture}
    {include file="./keyword_table.tpl" table_title=$smarty.capture.tec_gsc_top_pages_title rows=$gsc_stats.top_pages first_column='page'}
  </div>
  <div class="col-md-6">
    {capture name=tec_gsc_top_queries_title}{l s='Top queries' d='Modules.Tecsearchconsole.Admin'}{/capture}
    {include file="./keyword_table.tpl" table_title=$smarty.capture.tec_gsc_top_queries_title rows=$gsc_stats.top_queries first_column='query'}
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    {capture name=tec_gsc_low_ctr_title}{l s='Low CTR opportunities' d='Modules.Tecsearchconsole.Admin'}{/capture}
    {include file="./keyword_table.tpl" table_title=$smarty.capture.tec_gsc_low_ctr_title rows=$gsc_stats.low_ctr_opportunities first_column='page'}
  </div>
  <div class="col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Unread alerts' d='Modules.Tecsearchconsole.Admin'}</div>
      {if $gsc_alerts|count}
        <table class="table">
          <thead>
            <tr>
              <th>{l s='Type' d='Modules.Tecsearchconsole.Admin'}</th>
              <th>{l s='Page' d='Modules.Tecsearchconsole.Admin'}</th>
              <th>{l s='Delta' d='Modules.Tecsearchconsole.Admin'}</th>
              <th>{l s='Date' d='Modules.Tecsearchconsole.Admin'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$gsc_alerts item=alert}
              <tr>
                <td>{$alert.alert_type|escape:'html':'UTF-8'}</td>
                <td class="tec-gsc-url">{$alert.page|escape:'html':'UTF-8'}</td>
                <td>{math equation='x' x=$alert.delta_pct|default:0 format='%.2f'}%</td>
                <td>{$alert.date_add|escape:'html':'UTF-8'}</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {else}
        <p class="text-muted">{l s='No unread alerts.' d='Modules.Tecsearchconsole.Admin'}</p>
      {/if}
    </div>
  </div>
</div>
