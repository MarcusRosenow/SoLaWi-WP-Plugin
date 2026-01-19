<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Uninstaller für das Plugin.
 * Wird nur einmalig aufgerufen
 */
final class SOLAWI_Uninstaller {

	function __construct(){
		SOLAWI_Repository::instance()->dropTables();
	}
}