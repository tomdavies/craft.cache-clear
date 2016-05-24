<?php
namespace Craft;

class CacheClearVariable
{
	public function getKey()
	{
		return craft()->config->get('key', 'cacheClear');
	}
}
