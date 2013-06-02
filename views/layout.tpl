<div>
	{$header}
</div>

<div>
	{$body}
</div>

<div>
	{$footer}
</div>

{if isset($devbar)}
	{$devbar}
{/if}

<script type="text/javascript">
	$('div.path_bar').remove();
</script>