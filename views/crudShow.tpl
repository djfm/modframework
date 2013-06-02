<style type="text/css">
	dl.show dt
	{
		margin-top: 5px;
		font-weight: bold;
		border-top: 1px solid #eee;
		background-color: rgb(247, 241, 231);
	}
	dl.show dd
	{
		margin-left: 200px;
	}
</style>

<h2>{$model}</h2>

<dl class="show">
	{foreach from=$type key=name item=spec}
		<dt>{if isset($spec['title'])}{$spec['title']}{else if isset($spec['id']) and $spec['id']}ID{else}{$name}{/if}</dt>
		<dd>
			{if $spec['type'] == 'text'}
				<pre>{$object->$name}</pre>
			{else}
				{$object->$name}
			{/if}
			
		</dd>
	{/foreach}
</dl>
<BR/>
<a href="{module_action module=$module_name action="{$model}List"}">&lt;&lt;{l s='Back to the list' mod='modframework'}</a>