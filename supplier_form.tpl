<form>
  <select>
    <option value="0">{l s='All suppliers' d='Modules.Supplierlist.Shop'}</option>
    {foreach from=$suppliers item=supplier}
      <option value="{$supplier['link']}">{$supplier['name']}</option>
    {/foreach}
  </select>
</form>
