<p>{l s='Something bad happened, sorry!' mod='modframework'}</p>

{if isset($flash['errors'])}
	{foreach from=$flash['errors'] item=message}
		<div class='error'>{$message}</div>
	{/foreach}
{/if}