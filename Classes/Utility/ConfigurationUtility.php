<?php
/**
 * Configuration Utility
 *
 * @package Calendarize\Utility
 * @author  Tim Lochmüller
 */

namespace HDNET\Calendarize\Utility;

/**
 * Configuration Utility
 *
 * @author Tim Lochmüller
 */
class ConfigurationUtility {

	/**
	 * Configuration cache
	 *
	 * @var array
	 */
	static protected $configuration;

	/**
	 * Get the given configuration value
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	static public function get($name) {
		self::loadConfiguration();
		return isset(self::$configuration[$name]) ? self::$configuration[$name] : NULL;
	}

	/**
	 * Load the current configuration
	 */
	static protected function loadConfiguration() {
		if (self::$configuration === NULL) {
			self::$configuration = (array)unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['calendarize']);
		}
	}
}
