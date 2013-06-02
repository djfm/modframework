<script type="text/javascript">
function changeLanguageFields(node, id_lang)
{
	var form = $(node).parents('form');
	form.find('div[data-id-lang]').hide();
	form.find('div[data-id-lang='+id_lang+']').show();
	form.find('img.current_language').attr('src', '../img/l/'+id_lang+'.jpg')
	toggleLanguagSelection(node);
}
function toggleLanguagSelection(node)
{
	$(node).parents('div.language_menu').find('div.language_flags').toggle();
}
</script>

<style type="text/css">
	div.spacer
	{
		clear:both;
		margin-top: 5px;
	}
</style>

{function make_field_html}
	
	{if isset($language_id)}
		{assign var=value value=$spec['value'][$language_id]}
	{else}
		{assign var=value value=$spec['value']}
	{/if}

	{if $spec['type'] == 'text'}
		<textarea name="{$input_name}" style="width:320px">{$value}</textarea>
	{else}
		<input name="{$input_name}" type="text" value="{$value}"/>
	{/if}

{/function}

{if isset($validation_error)}
	<div class="error">{l s='Validation error, PrestaShop kindly says: ' mod='modframework'}<p>{$validation_error}</p><p>How cool is that?</p></div>
{/if}

{if isset($saved)}
	{if $saved}
		<div class="conf">
			{l s='Successfully saved object!' mod='modframework'}
		</div>
	{else}
		<div class="error">
			{l s='Something went wrong while saving the object, but I can\'t tell what :(' mod='modframework'}
		</div>
	{/if}
{/if}

<fieldset>
	<legend>{if $operation == 'new'}{l s='Create' mod='modframework'}{/if} {$model}</legend>
	<form method="post" action="{module_action module={$module_name} action="create{$model}"}">

		{if $operation == 'edit'}
			<input type="hidden" name="{$object->identifier}" value="{$object->id}"/>
		{/if}

		{foreach from=$fields item=spec key=name}
			<label>{$name}</label>
			<div class="margin-form">
				{if $spec['lang']}					
						{foreach from=$languages item=language}
							<div data-id-lang="{$language['id_lang']}" style="float:left; {if $language['id_lang'] != $id_lang}display:none;{/if}">
								{make_field_html input_name="{$model}_{$name}[{$language['id_lang']}]" spec=$spec language_id=$language['id_lang']}
							</div>
						{/foreach}
						
				{else}
					{make_field_html input_name="{$model}_{$name}" spec=$spec}
				{/if}

				{if $spec['lang']}
					<div class="language_menu">
						<div class="displayed_flag">
							<img class="current_language pointer" src="../img/l/{$id_lang}.jpg" onclick="javascript:toggleLanguagSelection(this);">
						</div>
						<div class="language_flags" class="language_flags" style="display: none;">
							{foreach from=$languages item=language}
								<img src="../img/l/{$language['id_lang']}.jpg" class="pointer" alt="{$language['name']}" title="{$language['name']}" onclick='javascript:changeLanguageFields(this, {$language["id_lang"]})'/>
							{/foreach}
						</div>
					</div>
				{/if}
				<p style="clear:both"></p>
			</div>
		{/foreach}
		<div class="margin-form">
			<button class="button" type="submit">{if $operation == 'new'}{l s='Save' mod='modframework'}{else}{l s='Update' mod='modframework'}{/if}</button>
		</div>
	</form>
</fieldset>

<BR/>
<a href="{module_action module=$module_name action="{$model}List"}">&lt;&lt;{l s='Back to the list' mod='modframework'}</a>