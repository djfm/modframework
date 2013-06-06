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
		<dt>{$spec['title']}</dt>
		<dd>
			{if $spec['type'] == 'text'}
				<pre>{$spec['value']}</pre>
			{else if $spec['type'] == 'select' and (is_array($spec['options']) or is_array($spec['options_with_values']))}
				{if is_array($spec['options'])}
					{$spec['options'][$spec['value']]}
				{else}
					{$spec['options_with_values'][$spec['value']]}
				{/if}						
			{else}
				{$spec['value']}
			{/if}
			
		</dd>
	{/foreach}
</dl>
<BR/>
<a href="{module_action module=$module_name action="{$model}List"}">&lt;&lt;{l s='Back to the list' mod='modframework'}</a>