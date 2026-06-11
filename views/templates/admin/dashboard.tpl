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
      <i class="icon-search"></i> {l s='Search Console SEO' mod='tec_searchconsole'}
    </div>

    <div class="panel-body">
      {if !$gsc_vendor_ready}
        <div class="alert alert-warning">
          {l s='Google API Client is not bundled in this module package. Install a complete module package before connecting Google Search Console.' mod='tec_searchconsole'}
        </div>
      {/if}

      <div class="row">
        <div class="col-md-6 tec-gsc-connection">
          <p class="tec-gsc-section-title">{l s='Connection' mod='tec_searchconsole'}</p>
          <p>
            <strong>{l s='Status' mod='tec_searchconsole'}:</strong>
            {if $gsc_config.is_connected}
              <span class="label label-success">{l s='Connected' mod='tec_searchconsole'}</span>
            {else}
              <span class="label label-warning">{l s='Not connected' mod='tec_searchconsole'}</span>
            {/if}
          </p>
          <p><strong>{l s='Last sync' mod='tec_searchconsole'}:</strong> {$gsc_config.last_sync|escape:'html':'UTF-8'}</p>
          <p><strong>{l s='OAuth callback URL' mod='tec_searchconsole'}:</strong><br>{$gsc_callback_url|escape:'html':'UTF-8'}</p>
          <p><strong>{l s='Cron URL' mod='tec_searchconsole'}:</strong><br>{$gsc_cron_url|escape:'html':'UTF-8'}</p>

          <div class="tec-gsc-actions">
            <a class="btn btn-primary{if !$gsc_vendor_ready} disabled{/if}" href="{$gsc_connect_url|escape:'html':'UTF-8'}">
              <i class="icon-link"></i> {l s='Connect Google' mod='tec_searchconsole'}
            </a>
            <a class="btn btn-default" href="{$gsc_sync_url|escape:'html':'UTF-8'}">
              <i class="icon-refresh"></i> {l s='Sync now' mod='tec_searchconsole'}
            </a>
            {if $gsc_config.is_connected}
              <a class="btn btn-danger" href="{$gsc_disconnect_url|escape:'html':'UTF-8'}">
                <i class="icon-unlink"></i> {l s='Disconnect' mod='tec_searchconsole'}
              </a>
            {/if}
          </div>
        </div>

        <div class="col-md-6 tec-gsc-settings">
          <div class="form-group">
            <label class="control-label col-lg-4" for="tec-gsc-client-id">{l s='Client ID' mod='tec_searchconsole'}</label>
            <div class="col-lg-8">
              <input id="tec-gsc-client-id" type="text" name="client_id" value="{$gsc_config.client_id|escape:'html':'UTF-8'}" maxlength="255" required>
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4" for="tec-gsc-client-secret">{l s='Client Secret' mod='tec_searchconsole'}</label>
            <div class="col-lg-8">
              <input id="tec-gsc-client-secret" type="text" name="client_secret" value="{$gsc_config.client_secret|escape:'html':'UTF-8'}" maxlength="255">
            </div>
          </div>
          <div class="form-group">
            <label class="control-label col-lg-4" for="tec-gsc-site-url">{l s='Property URL' mod='tec_searchconsole'}</label>
            <div class="col-lg-8">
              <input id="tec-gsc-site-url" type="text" name="site_url" value="{$gsc_config.site_url|escape:'html':'UTF-8'}" maxlength="255" placeholder="https://example.com/">
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel-footer">
      <button type="submit" name="submitTecGscConfig" class="btn btn-primary pull-right">
        <i class="process-icon-save"></i> {l s='Save' mod='tec_searchconsole'}
      </button>
      <div class="clearfix"></div>
    </div>
  </div>
</form>

<form method="post" action="{$gsc_form_action|escape:'html':'UTF-8'}" class="form-horizontal tec-gsc-verification-form">
  <div class="panel tec-gsc-verification">
    <div class="panel-heading">
      <i class="icon-check"></i> {l s='Search Console verification' mod='tec_searchconsole'}
    </div>
    <div class="panel-body">
      <div class="form-group">
        <label class="control-label col-lg-3" for="tec-gsc-verification-tag">{l s='HTML verification tag' mod='tec_searchconsole'}</label>
        <div class="col-lg-9">
          <textarea id="tec-gsc-verification-tag" name="verification_tag" class="form-control" rows="3" placeholder="&lt;meta name=&quot;google-site-verification&quot; content=&quot;...&quot;&gt;">{$gsc_verification_tag|escape:'html':'UTF-8'}</textarea>
          <p class="help-block">
            {l s='Paste the full Google Search Console HTML tag. The module stores only the verification token and prints the meta tag in the front-office header.' mod='tec_searchconsole'}
          </p>
        </div>
      </div>
    </div>
    <div class="panel-footer">
      <button type="submit" name="submitTecGscVerification" class="btn btn-primary pull-right">
        <i class="process-icon-save"></i> {l s='Save verification tag' mod='tec_searchconsole'}
      </button>
      <div class="clearfix"></div>
    </div>
  </div>
</form>

<div class="row tec-gsc-metrics">
  <div class="col-lg-3 col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Clicks 28 days' mod='tec_searchconsole'}</div>
      <div class="tec-gsc-kpi">{$gsc_stats.last_28_days.clicks|default:0|intval}</div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Impressions' mod='tec_searchconsole'}</div>
      <div class="tec-gsc-kpi">{$gsc_stats.last_28_days.impressions|default:0|intval}</div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Average CTR' mod='tec_searchconsole'}</div>
      <div class="tec-gsc-kpi">{math equation='x * 100' x=$gsc_stats.last_28_days.ctr|default:0 format='%.2f'}%</div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Average position' mod='tec_searchconsole'}</div>
      <div class="tec-gsc-kpi">{math equation='x' x=$gsc_stats.last_28_days.position|default:0 format='%.2f'}</div>
    </div>
  </div>
</div>

<div class="panel tec-gsc-sitemaps">
  <div class="panel-heading">
    <i class="icon-sitemap"></i> {l s='Submitted sitemaps' mod='tec_searchconsole'}
  </div>
  {if $gsc_sitemaps|count}
    <table class="table">
      <thead>
        <tr>
          <th>{l s='Sitemap' mod='tec_searchconsole'}</th>
          <th>{l s='Type' mod='tec_searchconsole'}</th>
          <th>{l s='Submitted URLs' mod='tec_searchconsole'}</th>
          <th>{l s='Last submitted' mod='tec_searchconsole'}</th>
          <th>{l s='Last downloaded' mod='tec_searchconsole'}</th>
          <th>{l s='Status' mod='tec_searchconsole'}</th>
          <th>{l s='Warnings' mod='tec_searchconsole'}</th>
          <th>{l s='Errors' mod='tec_searchconsole'}</th>
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
                <span class="label label-warning">{l s='Pending' mod='tec_searchconsole'}</span>
              {else}
                <span class="label label-success">{l s='Processed' mod='tec_searchconsole'}</span>
              {/if}
              {if $sitemap.is_sitemaps_index}
                <span class="label label-info">{l s='Index' mod='tec_searchconsole'}</span>
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
      <p class="text-muted">{l s='No submitted sitemaps available for the connected Search Console property.' mod='tec_searchconsole'}</p>
    </div>
  {/if}
</div>

<div class="row">
  <div class="col-md-6">
    {capture name=tec_gsc_top_pages_title}{l s='Top pages' mod='tec_searchconsole'}{/capture}
    {include file="./keyword_table.tpl" table_title=$smarty.capture.tec_gsc_top_pages_title rows=$gsc_stats.top_pages first_column='page'}
  </div>
  <div class="col-md-6">
    {capture name=tec_gsc_top_queries_title}{l s='Top queries' mod='tec_searchconsole'}{/capture}
    {include file="./keyword_table.tpl" table_title=$smarty.capture.tec_gsc_top_queries_title rows=$gsc_stats.top_queries first_column='query'}
  </div>
</div>

<div class="row">
  <div class="col-md-6">
    {capture name=tec_gsc_low_ctr_title}{l s='Low CTR opportunities' mod='tec_searchconsole'}{/capture}
    {include file="./keyword_table.tpl" table_title=$smarty.capture.tec_gsc_low_ctr_title rows=$gsc_stats.low_ctr_opportunities first_column='page'}
  </div>
  <div class="col-md-6">
    <div class="panel">
      <div class="panel-heading">{l s='Unread alerts' mod='tec_searchconsole'}</div>
      {if $gsc_alerts|count}
        <table class="table">
          <thead>
            <tr>
              <th>{l s='Type' mod='tec_searchconsole'}</th>
              <th>{l s='Page' mod='tec_searchconsole'}</th>
              <th>{l s='Delta' mod='tec_searchconsole'}</th>
              <th>{l s='Date' mod='tec_searchconsole'}</th>
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
        <p class="text-muted">{l s='No unread alerts.' mod='tec_searchconsole'}</p>
      {/if}
    </div>
  </div>
</div>
