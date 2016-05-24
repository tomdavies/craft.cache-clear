<?php namespace Craft;

class CacheClearController extends BaseController {

	/**
	 * Allow anonymous access to the controller.
	 *
	 * @var array
	 */
	protected $allowAnonymous = array('actionClear', 'actionClearByHandles', 'actionClearExceptHandles');

	/**
	 * Handle the action to clear the cache.
	 */
	public function actionClear()
	{

		$this->_beforeClear();

		craft()->cacheClear->clearCaches('*');

		$this->_afterClear();
	}

	public function actionClearByHandles()
	{
		$this->_beforeClear();

		$handles = $this->_getCacheHandles();

		$result = craft()->cacheClear->clear($handles);

		$this->_afterClear($result);
	}

	public function actionClearExceptHandles()
	{
		$this->_beforeClear();

		$handles = $this->_getCacheHandles();

		$result = craft()->cacheClear->clearExcept($handles);

		$this->_afterClear($result);
	}


	private function _beforeClear()
	{
		if (!$plugin = craft()->plugins->getPlugin('cacheClear'))
		{
			$this->returnErrorJson("Could not find the plugin");
		}

		$settings = $plugin->getSettings();

		$requestKey = craft()->request->getParam('key');

		$storedKey = craft()->config->get('key', 'cacheclear');

		if(!$storedKey)
		{
			$storedKey = $settings->key;
		}


		if (!$storedKey OR $requestKey != $storedKey)
		{
			$this->returnErrorJson("Unauthorized key");
		}
	}

	private function _afterClear($result)
	{
		if (craft()->request->getPost('redirect'))
		{
			$this->redirectToPostedUrl();
		}

		if($result['error'])
		{
			$this->returnJson($result['error']);
		}

		$this->returnJson("Cache cleared successfully");
	}

	private function _getCacheHandles()
	{
		if (!$handles = craft()->request->getParam('handles'))
		{
			$this->returnErrorJson("Required parameter `handles` missing");
		}

		return $handles;
	}
}
