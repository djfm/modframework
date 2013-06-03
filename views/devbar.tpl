<style type="text/css">
	
	div#devzone
	{
		margin-top: 50px;
	}

	div#devbar
	{
		border: 1px solid#ccc;
	}
	
	div#devbar a
	{
		width:100px;
		display: inline-block;
		border-right: 1px solid#aaa;
		padding:5px;
		background-color:#eee;
		text-align: center;
	}
	
	div#devbar a:hover
	{
		font-weight: bold;
	}
	
	div#devbar a.danger
	{
		float:right;
		background-color: #FF4747;
	}

	span.devbar
	{
		font-style: italic;
		color:#555;
	}

</style>

<div id="devzone">
	<hr/>
	<span class="devbar">developer toolbar</span>
	<div id="devbar">
		<a id="resetlink" class="danger" href="">{l s='Reinstall' mod='modframework'}</a>
		{foreach from=$devbar_models item=link key=name}
			<a href="{$link}" class="model">{$name}</a>
		{/foreach}
	</div>
</div>

<script type="text/javascript">
	
	var reinstall = "{l s='Reinstall' mod='modframework' js=true}";
	var thinking  = "{l s='...thinking...' mod='modframework' js=true}";

	$('#resetlink').click(function(e){

		$('#resetlink').html(thinking);
		
		e.preventDefault();
		$.get('{$reseturl}', function(data){
			if($(data).find('div.conf').length > 0)
			{
				$('#resetlink').html(reinstall+" (OK!)");
			}
			else
			{
				$('#resetlink').html(reinstall+" (Failed!)");
			}
		});
	});
</script>