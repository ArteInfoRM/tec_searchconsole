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
  <div class="panel-heading">{l s='Search Console SEO' mod='tec_searchconsole'}</div>
  <div class="row">
    <div class="col-md-3">
      <strong>{l s='Clicks' mod='tec_searchconsole'}</strong><br>
      {$gsc_seo_data.total_clicks|default:0|intval}
    </div>
    <div class="col-md-3">
      <strong>{l s='Impressions' mod='tec_searchconsole'}</strong><br>
      {$gsc_seo_data.total_impressions|default:0|intval}
    </div>
    <div class="col-md-3">
      <strong>{l s='CTR' mod='tec_searchconsole'}</strong><br>
      {math equation='x * 100' x=$gsc_seo_data.avg_ctr|default:0 format='%.2f'}%
    </div>
    <div class="col-md-3">
      <strong>{l s='Position' mod='tec_searchconsole'}</strong><br>
      {math equation='x' x=$gsc_seo_data.avg_position|default:0 format='%.2f'}
    </div>
  </div>

  <hr>
  <h4 class="tec-gsc-product-section-title">{l s='Keyword breakdown' mod='tec_searchconsole'}</h4>
  {if $gsc_top_keys|count}
    <table class="table tec-gsc-product-keywords">
      <thead>
        <tr>
          <th>{l s='Query' mod='tec_searchconsole'}</th>
          <th>{l s='Clicks' mod='tec_searchconsole'}</th>
          <th>{l s='Impressions' mod='tec_searchconsole'}</th>
          <th>{l s='CTR' mod='tec_searchconsole'}</th>
          <th>{l s='Position' mod='tec_searchconsole'}</th>
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
    <p class="text-muted">{l s='No keyword data available for this product in the selected period.' mod='tec_searchconsole'}</p>
  {/if}
</div>
