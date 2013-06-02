<h2>{$model} {l s='List' mod='modframework'}</h2>

<a href="{module_action module=$module_name action="new{$model}" object_identifier=$object->id}"><img src="../img/admin/add.gif" border="0">{l s='Create' mod='modframework'} {$model}</a>
<br/><br/>

<table class="table" style="width:100%">
	<tr>
		{foreach from=$type item=spec key=name}
			<th>{if isset($spec['title'])}{$spec['title']}{else if isset($spec['id']) and $spec['id']}ID{else}{$name}{/if}</th>
		{/foreach}
		<th style="width:40px; text-align:center"></th>
	</tr>
	{foreach from=$objects item=object}
	<tr class='{cycle values="alt_row,reg_row"}'>
		{foreach from=$type item=spec key=name}
			<td>
				{if $spec['lang']}
					{$object->language_field($name, $id_lang)}
				{else if isset($spec['id']) and $spec['id']}
					<a href='{module_action module=$module_name action="show{$model}" object_identifier=$object->id}'>{$object->id}</a>
				{else}
					{$object->$name}
				{/if}
			</td>
		{/foreach}
		<td>
			<a href="{module_action module=$module_name action="edit{$model}" object_identifier=$object->id}"><img src="../img/admin/edit.gif"></a>
			<form style="display:inline" method="post" action="{module_action module=$module_name action="delete{$model}" object_identifier=$object->id}">
				<img src="../img/admin/delete.gif" class="pointer" onclick="javascript:$(this).parents('form').submit()">
			</form>
		</td>
	</tr>
	{/foreach}
</table>