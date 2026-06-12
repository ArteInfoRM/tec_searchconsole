{*
* 2009-2026 Tecnoacquisti.com
*
* For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
* @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
* @copyright 2009-2026 Tecnoacquisti.com
* @license   https://opensource.org/licenses/MIT MIT License
*}

<div class="panel">
  <div class="panel-heading">{l s='Search Console SEO' d='Modules.Tecsearchconsole.Admin'}</div>
  <div class="row">
    <div class="col-md-3">
      <strong>{l s='Clicks' d='Modules.Tecsearchconsole.Admin'}</strong><br>
      {$gsc_seo_data.total_clicks|default:0|intval}
    </div>
    <div class="col-md-3">
      <strong>{l s='Impressions' d='Modules.Tecsearchconsole.Admin'}</strong><br>
      {$gsc_seo_data.total_impressions|default:0|intval}
    </div>
    <div class="col-md-3">
      <strong>{l s='CTR' d='Modules.Tecsearchconsole.Admin'}</strong><br>
      {math equation='x * 100' x=$gsc_seo_data.avg_ctr|default:0 format='%.2f'}%
    </div>
    <div class="col-md-3">
      <strong>{l s='Position' d='Modules.Tecsearchconsole.Admin'}</strong><br>
      {math equation='x' x=$gsc_seo_data.avg_position|default:0 format='%.2f'}
    </div>
  </div>

  <hr>
  <h4 class="tec-gsc-product-section-title">{l s='Export product data' d='Modules.Tecsearchconsole.Admin'}</h4>
  <p>
    <a class="btn btn-default" href="{$gsc_export_action|escape:'html':'UTF-8'}&amp;export_gsc_data=1&amp;id_product={$gsc_export_product_id|intval}">
      <i class="icon-download"></i> {l s='Export product data' d='Modules.Tecsearchconsole.Admin'}
    </a>
    {if $gsc_seozoom_product_url}
      <a class="btn btn-default" href="{$gsc_seozoom_product_url|escape:'html':'UTF-8'}" target="_blank" rel="noopener noreferrer">
        <i class="icon-external-link"></i> {l s='Open product in SEOZoom' d='Modules.Tecsearchconsole.Admin'}
      </a>
    {/if}
  </p>

  <hr>
  <h4 class="tec-gsc-product-section-title">{l s='Keyword breakdown' d='Modules.Tecsearchconsole.Admin'}</h4>
  {if $gsc_top_keys|count}
    <table class="table tec-gsc-product-keywords">
      <thead>
        <tr>
          <th>{l s='Query' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Clicks' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Impressions' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='CTR' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Position' d='Modules.Tecsearchconsole.Admin'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$gsc_top_keys item=row}
          <tr>
            <td>{$row.query|escape:'html':'UTF-8'}</td>
            <td>{$row.clicks|default:0|intval}</td>
            <td>{$row.impressions|default:0|intval}</td>
            <td>{math equation='x * 100' x=$row.ctr|default:0 format='%.2f'}%</td>
            <td>{math equation='x' x=$row.position|default:0 format='%.2f'}</td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <p class="text-muted">{l s='No keyword data available for this product in the selected period.' d='Modules.Tecsearchconsole.Admin'}</p>
  {/if}
</div>
