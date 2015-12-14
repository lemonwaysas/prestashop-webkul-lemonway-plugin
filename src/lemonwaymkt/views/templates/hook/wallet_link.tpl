{if $mpmenu==0}
	<li>
		<a title="{l s='Lemonway payment' mod='lemonwaymkt'}" href="{$link->getModuleLink('lemonwaymkt', 'wallet')|escape:'html':'UTF-8'}" title="{l s='Lemonway payment' mod='lemonwaymkt'}">
			<i class="icon-money"></i>
			<span>{l s='Lemonway payment' mod='lemonwaymkt'}</span>
		</a>
	</li>
{else}
	<li {if $logic=='lemonwaymkt_wallet'}class="menu_active"{/if}>
		<span>
			<a title="{l s='Lemonway payment' mod='lemonwaymkt'}" href="{$link->getModuleLink('lemonwaymkt', 'wallet')|escape:'html':'UTF-8'}">
				{l s='Lemonway payment' mod='lemonwaymkt'}
			</a>
		</span>
	</li>
{/if}