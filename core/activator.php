<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Activator für das Plugin.
 * Wird nur einmalig aufgerufen
 */
final class SOLAWI_Activator {

	function __construct(){
		SOLAWI_Repository::instance()->createTables();
		SOLAWI_UserInitializer::initUsers();
		
	}
}