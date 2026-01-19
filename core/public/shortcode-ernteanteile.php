<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_ShortcodeErnteanteile extends SOLAWI_AbstractShortcode {

    public function getKey() : string {
        return "ernteanteile";
    }

    public function savePostdata( int $id, array $data ) : void {
    }

    public function getHtml( SOLAWI_Mitbauer $mitbauer ) : string {
        return $mitbauer->getHtmlTableErnteAnteile( true );
    }
}