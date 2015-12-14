{capture name=path}
	<a {if $logged}href="{$link->getModuleLink('marketplace', 'dashboard')|escape:'html':'UTF-8'}"{/if}>
		{l s='Marketplace' mod='lemonwaymkt'}
	</a>
	<span class="navigation-pipe">{$navigationPipe}</span>
	<span class="navigation_page">{l s='Money out' mod='lemonwaymkt'}</span>
{/capture}
{if $logged}
	<div class="main_block">
	{include file="$tpl_dir./errors.tpl"}
		{hook h="DisplayMpmenuhook"}
		<div class="dashboard_content">
			<div class="dashboard">
				<div class="page-title">
					<span>{l s='Money out' mod='lemonwaymkt'}</span>
				</div>
				<div class="wk_right_col">
				{if isset($success) && $success}
        			<p class="alert alert-success">
            			{l s='Your Money out has been successfully transfered.' mod='lemonwaymkt'}
        			</p>
				{else}
					<h3>{l s='Wallet balance' mod='lemonwaymkt'} <span class="label label-success">{displayWtPriceWithCurrency price=$bal currency=$currency}</span></h3>
					<form class="std" action="{$link->getModuleLink('lemonwaymkt', 'moneyout',['add'=>1])|escape:'html':'UTF-8'}" method="post">
						<div class="form-group">
							<label for="id_iban" class="required control-label">{l s='Ibans' mod='lemonwaymkt'}</label>
							<select class="form-control is_required validate" style="width: 205px" id="id_iban" name="moneyout_data[id_iban]">
								<option value="">{l s='Select a creditor account' mod='lemonwaymkt'}</option>
								{foreach from=$ibans item=iban}
									<option value="{$iban.id_iban}">{$iban.iban}</option>
								{/foreach}
							</select>
						</div>
						<div class="form-group">
							<label for="amount_to_pay" class="required"  >{l s='Amount to transfert' mod='lemonwaymkt'}</label>
							<div class="input-group" style="width: 210px">
								<input type="text"  data-validate="isPrice" aria-describedby="amount_currency" class="form-control is_required validate" id="amount_to_pay" name="moneyout_data[amount_to_pay]" value="{$moneyout->amount_to_pay}">
								<span class="input-group-addon" id="amount_currency">{$currency->sign}</span>
							</div>
						</div>

						<button type="submit" name="addMoneyoutSubmit" class="button btn btn-default button-medium">
							<span>{l s='Transfert' mod='lemonwaymkt'}</span>
						</button>

					</form>
				{/if}
				</div>
			</div>
		</div>
	</div>
{/if}
