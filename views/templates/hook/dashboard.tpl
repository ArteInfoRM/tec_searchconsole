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
    {l s='Search Console SEO' mod='tec_searchconsole'}
    <span class="panel-heading-action">
      <a href="{$tec_gsc_dashboard_url|escape:'html':'UTF-8'}" class="btn btn-xs btn-default" style="white-space:nowrap;">
        {l s='View all' mod='tec_searchconsole'}
      </a>
    </span>
  </div>

  <div class="panel-body">
    {if $tec_gsc_is_connected}
      <div class="row tec-gsc-widget-kpis">
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{$tec_gsc_metrics.clicks|default:0|intval}</span>
            <span class="tec-gsc-widget-label">{l s='Clicks 28 days' mod='tec_searchconsole'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{$tec_gsc_metrics.impressions|default:0|intval}</span>
            <span class="tec-gsc-widget-label">{l s='Impressions' mod='tec_searchconsole'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{math equation='x * 100' x=$tec_gsc_metrics.ctr|default:0 format='%.2f'}%</span>
            <span class="tec-gsc-widget-label">{l s='Average CTR' mod='tec_searchconsole'}</span>
          </div>
        </div>
        <div class="col-xs-6 col-sm-3 text-center">
          <div class="tec-gsc-widget-kpi">
            <span class="tec-gsc-widget-number">{math equation='x' x=$tec_gsc_metrics.position|default:0 format='%.2f'}</span>
            <span class="tec-gsc-widget-label">{l s='Average position' mod='tec_searchconsole'}</span>
          </div>
        </div>
      </div>

      <h4 class="tec-gsc-widget-title">
        {l s='Top queries' mod='tec_searchconsole'}
      </h4>
      {if $tec_gsc_top_queries|count}
        <table class="table table-condensed tec-gsc-widget-queries">
          <thead>
            <tr>
              <th>{l s='Query' mod='tec_searchconsole'}</th>
              <th>{l s='Clicks' mod='tec_searchconsole'}</th>
              <th>{l s='Impressions' mod='tec_searchconsole'}</th>
              <th>{l s='CTR' mod='tec_searchconsole'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$tec_gsc_top_queries item=query}
              <tr>
                <td class="tec-gsc-query">{$query.query|escape:'html':'UTF-8'}</td>
                <td>{$query.clicks|intval}</td>
                <td>{$query.impressions|intval}</td>
                <td>{math equation='x * 100' x=$query.ctr|default:0 format='%.2f'}%</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {else}
        <p class="text-muted text-center">{l s='No query data available.' mod='tec_searchconsole'}</p>
      {/if}

      <h4 class="tec-gsc-widget-title">
        {l s='Submitted sitemaps' mod='tec_searchconsole'} ({$tec_gsc_sitemap_count|intval})
      </h4>
      {if $tec_gsc_sitemaps|count}
        <table class="table table-condensed tec-gsc-widget-sitemaps">
          <thead>
            <tr>
              <th>{l s='Sitemap' mod='tec_searchconsole'}</th>
              <th>{l s='URLs' mod='tec_searchconsole'}</th>
              <th>{l s='Status' mod='tec_searchconsole'}</th>
            </tr>
          </thead>
          <tbody>
            {foreach from=$tec_gsc_sitemaps item=sitemap}
              <tr>
                <td class="tec-gsc-url">{$sitemap.path|escape:'html':'UTF-8'}</td>
                <td>{$sitemap.submitted_urls|intval}</td>
                <td>
                  {if $sitemap.is_pending}
                    <span class="label label-warning">{l s='Pending' mod='tec_searchconsole'}</span>
                  {else}
                    <span class="label label-success">{l s='Processed' mod='tec_searchconsole'}</span>
                  {/if}
                </td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {else}
        <p class="text-muted text-center">{l s='No submitted sitemaps available.' mod='tec_searchconsole'}</p>
      {/if}
    {else}
      <p class="text-muted text-center">{l s='Search Console is not connected.' mod='tec_searchconsole'}</p>
    {/if}
  </div>
</div>
