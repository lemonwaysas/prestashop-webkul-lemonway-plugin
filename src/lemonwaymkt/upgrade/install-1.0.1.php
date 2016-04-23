<?php

if (!defined('_PS_VERSION_'))
	exit;

function upgrade_module_1_0_1($object)
{
	//Force auto dispath to true
	//Because admin configuration was removed.
	Configuration::updateValue('LEMONWAYMKT_AUTO_DISPATCH', 1);
	return true;
}
