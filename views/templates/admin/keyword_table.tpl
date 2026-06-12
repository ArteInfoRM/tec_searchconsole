{*
* 2009-2026 Tecnoacquisti.com
*
* For support feel free to contact us on our website at https://www.tecnoacquisti.com
*
* @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
* @copyright 2009-2026 Tecnoacquisti.com
* @license   https://opensource.org/licenses/MIT MIT License
*}

<div class="panel tec-gsc-table">
  <div class="panel-heading">{$table_title|escape:'html':'UTF-8'}</div>
  {if $rows|count}
    {assign var=has_search_volume value=false}
    {if $first_column == 'query'}
      {foreach from=$rows item=volume_row}
        {if isset($volume_row.search_volume)}
          {assign var=has_search_volume value=true}
        {/if}
      {/foreach}
    {/if}
    <table class="table">
      <thead>
        <tr>
          <th>{if $first_column == 'query'}{l s='Query' d='Modules.Tecsearchconsole.Admin'}{else}{l s='Page' d='Modules.Tecsearchconsole.Admin'}{/if}</th>
          <th>{l s='Clicks' d='Modules.Tecsearchconsole.Admin'}</th>
          <th>{l s='Impressions' d='Modules.Tecsearchconsole.Admin'}</th>
          {if $has_search_volume}
            <th>{l s='Search volume' d='Modules.Tecsearchconsole.Admin'}</th>
          {/if}
          <th>{l s='Position' d='Modules.Tecsearchconsole.Admin'}</th>
          {if isset($rows[0].ctr)}
            <th>{l s='CTR' d='Modules.Tecsearchconsole.Admin'}</th>
          {/if}
        </tr>
      </thead>
      <tbody>
        {foreach from=$rows item=row}
          <tr>
            <td class="tec-gsc-url">{$row[$first_column]|escape:'html':'UTF-8'}</td>
            <td>{$row.clicks|default:0|intval}</td>
            <td>{$row.impressions|default:0|intval}</td>
            {if $has_search_volume}
              <td>{if isset($row.search_volume)}{$row.search_volume|default:0|intval}{else}-{/if}</td>
            {/if}
            <td>{math equation='x' x=$row.position|default:0 format='%.2f'}</td>
            {if isset($row.ctr)}
              <td>{math equation='x * 100' x=$row.ctr format='%.2f'}%</td>
            {/if}
          </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <p class="text-muted">{l s='No data available.' d='Modules.Tecsearchconsole.Admin'}</p>
  {/if}
</div>
