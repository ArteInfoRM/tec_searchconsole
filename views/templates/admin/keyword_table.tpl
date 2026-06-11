{*
* 2009-2026 Arte e Informatica
*
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license.
*
* @author    Arte e Informatica <helpdesk@tecnoacquisti.com>
* @copyright 2009-2026 Arte e Informatica
* @license   Commercial license
*}

<div class="panel tec-gsc-table">
  <div class="panel-heading">{$table_title|escape:'html':'UTF-8'}</div>
  {if $rows|count}
    <table class="table">
      <thead>
        <tr>
          <th>{if $first_column == 'query'}{l s='Query' mod='tec_searchconsole'}{else}{l s='Page' mod='tec_searchconsole'}{/if}</th>
          <th>{l s='Clicks' mod='tec_searchconsole'}</th>
          <th>{l s='Impressions' mod='tec_searchconsole'}</th>
          <th>{l s='Position' mod='tec_searchconsole'}</th>
          {if isset($rows[0].ctr)}
            <th>{l s='CTR' mod='tec_searchconsole'}</th>
          {/if}
        </tr>
      </thead>
      <tbody>
        {foreach from=$rows item=row}
          <tr>
            <td class="tec-gsc-url">{$row[$first_column]|escape:'html':'UTF-8'}</td>
            <td>{$row.clicks|default:0|intval}</td>
            <td>{$row.impressions|default:0|intval}</td>
            <td>{$row.position|default:0|string_format:'%.2f'}</td>
            {if isset($row.ctr)}
              <td>{math equation='x * 100' x=$row.ctr format='%.2f'}%</td>
            {/if}
          </tr>
        {/foreach}
      </tbody>
    </table>
  {else}
    <p class="text-muted">{l s='No data available.' mod='tec_searchconsole'}</p>
  {/if}
</div>
