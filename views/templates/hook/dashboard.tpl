{*
* 2009-2026 Tecnoacquisti.com
*
* For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
* @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
* @copyright 2009-2026 Tecnoacquisti.com
* @license   https://opensource.org/licenses/MIT MIT License
*}

<div class="panel tec-dashboard-widget tec-gsc-dashboard-widget">
  <div class="panel-heading">
    <i class="icon icon-search"></i>
    {l s='Search Console SEO' d='Modules.Tecsearchconsole.Admin'}
    <span class="panel-heading-action">
      <a href="{$tec_gsc_dashboard_url|escape:'html':'UTF-8'}" class="btn btn-xs btn-default" style="white-space:nowrap;">
        {l s='View all' d='Modules.Tecsearchconsole.Admin'}
      </a>
    </span>
  </div>

  <div class="panel-body">
    {if $tec_gsc_seozoom_domain_metrics.enabled && $tec_gsc_seozoom_domain_metrics.has_data}
      <h4 class="tec-gsc-widget-title tec-gsc-widget-seozoom-title">
        {l s='SEOZoom domain metrics' d='Modules.Tecsearchconsole.Admin'}
      </h4>
      <div class="row tec-gsc-widget-kpis tec-gsc-widget-seozoom-kpis">
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{math equation='x' x=$tec_gsc_seozoom_domain_metrics.metrics.zoom_authority|default:0 format='%.0f'}</span>
            <span class="tec-gsc-widget-label">{l s='Zoom Authority' d='Modules.Tecsearchconsole.Admin'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{math equation='x' x=$tec_gsc_seozoom_domain_metrics.metrics.zoom_trust|default:0 format='%.0f'}</span>
            <span class="tec-gsc-widget-label">{l s='Zoom Trust' d='Modules.Tecsearchconsole.Admin'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{$tec_gsc_seozoom_domain_metrics.metrics.organic_traffic|default:0|intval}</span>
            <span class="tec-gsc-widget-label">{l s='Estimated organic traffic' d='Modules.Tecsearchconsole.Admin'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{$tec_gsc_seozoom_domain_metrics.metrics.organic_keywords|default:0|intval}</span>
            <span class="tec-gsc-widget-label">{l s='Organic keywords' d='Modules.Tecsearchconsole.Admin'}</span>
          </div>
        </div>
      </div>
    {/if}

    {if $tec_gsc_is_connected}
      <div class="row tec-gsc-widget-kpis">
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{$tec_gsc_metrics.clicks|default:0|intval}</span>
            <span class="tec-gsc-widget-label">{l s='Clicks 28 days' d='Modules.Tecsearchconsole.Admin'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{$tec_gsc_metrics.impressions|default:0|intval}</span>
            <span class="tec-gsc-widget-label">{l s='Impressions' d='Modules.Tecsearchconsole.Admin'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{math equation='x * 100' x=$tec_gsc_metrics.ctr|default:0 format='%.2f'}%</span>
            <span class="tec-gsc-widget-label">{l s='Average CTR' d='Modules.Tecsearchconsole.Admin'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{math equation='x' x=$tec_gsc_metrics.position|default:0 format='%.2f'}</span>
            <span class="tec-gsc-widget-label">{l s='Average position' d='Modules.Tecsearchconsole.Admin'}</span>
          </div>
        </div>
      </div>

      <h4 class="tec-gsc-widget-title">
        {l s='Top queries' d='Modules.Tecsearchconsole.Admin'}
      </h4>
      {if $tec_gsc_top_queries|count}
        {assign var=tec_gsc_has_search_volume value=false}
        {foreach from=$tec_gsc_top_queries item=volume_query}
          {if isset($volume_query.search_volume)}
            {assign var=tec_gsc_has_search_volume value=true}
          {/if}
        {/foreach}
        <table class="table table-condensed tec-gsc-widget-queries">
          <thead>
            <tr>
              <th>{l s='Query' d='Modules.Tecsearchconsole.Admin'}</th>
              <th>{l s='Clicks' d='Modules.Tecsearchconsole.Admin'}</th>
              <th>{l s='Impressions' d='Modules.Tecsearchconsole.Admin'}</th>
              {if $tec_gsc_has_search_volume}
                <th>{l s='Search volume' d='Modules.Tecsearchconsole.Admin'}</th>
              {/if}
              <th>{l s='CTR' d='Modules.Tecsearchconsole.Admin'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$tec_gsc_top_queries item=query}
              <tr>
                <td class="tec-gsc-query">{$query.query|escape:'html':'UTF-8'}</td>
                <td>{$query.clicks|intval}</td>
                <td>{$query.impressions|intval}</td>
                {if $tec_gsc_has_search_volume}
                  <td>{if isset($query.search_volume)}{$query.search_volume|intval}{else}-{/if}</td>
                {/if}
                <td>{math equation='x * 100' x=$query.ctr|default:0 format='%.2f'}%</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {else}
        <p class="text-muted text-center">{l s='No query data available.' d='Modules.Tecsearchconsole.Admin'}</p>
      {/if}

      <h4 class="tec-gsc-widget-title">
        {l s='Submitted sitemaps' d='Modules.Tecsearchconsole.Admin'} ({$tec_gsc_sitemap_count|intval})
      </h4>
      {if $tec_gsc_sitemaps|count}
        <table class="table table-condensed tec-gsc-widget-sitemaps">
          <thead>
            <tr>
              <th>{l s='Sitemap' d='Modules.Tecsearchconsole.Admin'}</th>
              <th>{l s='URLs' d='Modules.Tecsearchconsole.Admin'}</th>
              <th>{l s='Status' d='Modules.Tecsearchconsole.Admin'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$tec_gsc_sitemaps item=sitemap}
              <tr>
                <td class="tec-gsc-url">{$sitemap.path|escape:'html':'UTF-8'}</td>
                <td>{$sitemap.submitted_urls|intval}</td>
                <td>
                  {if $sitemap.is_pending}
                    <span class="label label-warning">{l s='Pending' d='Modules.Tecsearchconsole.Admin'}</span>
                  {else}
                    <span class="label label-success">{l s='Processed' d='Modules.Tecsearchconsole.Admin'}</span>
                  {/if}
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {else}
        <p class="text-muted text-center">{l s='No submitted sitemaps available.' d='Modules.Tecsearchconsole.Admin'}</p>
      {/if}
    {else}
      <p class="text-muted text-center">{l s='Search Console is not connected.' d='Modules.Tecsearchconsole.Admin'}</p>
    {/if}
  </div>
</div>
