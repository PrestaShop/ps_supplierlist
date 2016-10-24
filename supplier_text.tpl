<ul>
  {foreach from=$suppliers item=supplier name=supplier_list}
    {if $smarty.foreach.supplier_list.iteration <= $text_list_nb}
      <li>
        <a href="{$supplier['link']}">
          {$supplier['name']}
        </a>
      </li>
    {/if}
  {/foreach}
</ul>
