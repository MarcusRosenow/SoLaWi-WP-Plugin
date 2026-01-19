<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Abstrate Oberklasse für Widgets
 */
abstract class SOLAWI_AbstractWidget {

    /**
     * Gibt das HTML für die Anzeige in der Oberfläche aus
     */
    public abstract function getHtml() : string;
}