<?php
namespace Craft;

class CacheClearService extends BaseApplicationComponent
{
	public static $nonFileCaches = array(
		'dataCache',
		'templateCaches',
		'assetTransformIndex',
		'assetIndexingData',
	);

	public function clearAll()
	{
		return craft()->templateCache->deleteAllCaches();
	}

	public function clear($cacheHandles = array())
	{
		if (!isset($cacheHandles))
		{
			return array('error' => 'No cache handles passed');
		}

		$allCacheFolders = $this->_getCacheFolders();
		$allCacheFolderHandles = array_keys($allCacheFolders);

		if ($cacheHandles == '*')
		{
			$foldersToClear = $allCacheFolderHandles;
		}
		else
		{
			$foldersToClear = array();

			foreach ($cacheHandles as $handle)
			{
				if (in_array($handle, $allCacheFolderHandles))
				{
					$foldersToClear[] = $handle;
				}
				elseif (!in_array($handle, self::$nonFileCaches))
				{
					CacheClearPlugin::Log('Unknown cache `' . $handle . '`', LogLevel::Warning, true);
				}
			}
		}

		foreach ($foldersToClear as $handle)
		{
			IOHelper::clearFolder($allCacheFolders[$handle], true);
			CacheClearPlugin::Log('Cleared cached for ' . $handle . ' (' . $allCacheFolders[$handle] . ')', LogLevel::Info);
		}

		if ($cacheHandles == '*' || in_array('dataCache', $cacheHandles))
		{
			craft()->cache->flush();
			CacheClearPlugin::Log('Cleared data cache', LogLevel::Info);
		}

		if ($cacheHandles == '*' || in_array('templateCaches', $cacheHandles))
		{
			craft()->templateCache->deleteAllCaches();
			CacheClearPlugin::Log('Cleared template cache', LogLevel::Info);
		}

		if ($cacheHandles == '*' || in_array('assetTransformIndex', $cacheHandles))
		{
			craft()->db->createCommand()->truncateTable('assettransformindex');
			CacheClearPlugin::Log('Cleared cached asset transforms', LogLevel::Info);
		}

		if ($cacheHandles == '*' || in_array('assetIndexingData', $cacheHandles))
		{
			craft()->db->createCommand()->truncateTable('assetindexdata');
			CacheClearPlugin::Log('Cleared cached asset index data', LogLevel::Info);
		}
	}


	public function clearExcept($cacheHandles = array())
	{

		$allCacheFolderHandles = array_keys($this->_getCacheFolders());
		$allCacheHandles = array_merge($allCacheFolderHandles, self::$nonFileCaches);

		foreach ($cacheHandles as $index => $handle)
		{
			unset($allCacheHandles[$index]);
		}

		return $this->clear($allCacheHandles);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the cache folders we allow to be cleared as well as any plugin cache paths that have used the
	 * 'registerCachePaths' hook.
	 *
	 *
	 * @return array
	 */
	private function _getCacheFolders()
	{
		$runtimePath = craft()->path->getRuntimePath();

		$cacheFolders = array(
			'rssCaches' 		=> $runtimePath.'cache',
			'assetCaches' 		=> $runtimePath.'assets',
			'compiledTemplates' => $runtimePath.'compiled_templates',
			'tempFiles' 		=> $runtimePath.'temp',
		);

		$pluginCachePaths = craft()->plugins->call('registerCachePaths');

		if (is_array($pluginCachePaths) && count($pluginCachePaths) > 0)
		{
			foreach ($pluginCachePaths as $paths)
			{
				foreach ($paths as $path => $label)
				{
					$cacheFolders[StringHelper::toCamelCase($label)] = $path;
				}
			}
		}

		return $cacheFolders;
	}
}
