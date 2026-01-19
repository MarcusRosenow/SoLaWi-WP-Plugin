<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Widget für Registerkarten
 */
final class SOLAWI_WidgetRegisterkarten extends SOLAWI_AbstractWidget {

    /**
     * Zeigt um die Registerkarten eine Border an
     */
    const STYLE_BORDER = 0;

    /**
     * Zeigt für die Registerkarten nur einen dunkleren Hintergrund an
     */
    const STYLE_BACKGROUND = 1;

    private array $titles;

    private array $bodies;

    private string|null $selectedId;
    
    private string $gruppenId;

    private int $style;

    public function __construct( string|int $gruppenId = "" ) {
        $this->titles = array();
        $this->bodies = array();
        $this->selectedId = null;
        $this->gruppenId = strval( $gruppenId );
        $this->style = self::STYLE_BORDER;
    }

    /**
     * Setzt den Style ... siehe Konstanten
     */
    public function setStyle( int $style ) : void {
        $this->style = $style;
    }

    public function add( int|string $id, string $title, string $body, bool $selected = false ) : void {
        $this->titles[ strval( $id ) ] = $title;
        $this->bodies[ strval( $id ) ] = $body;
        if ( $this->selectedId === null || $selected )
            $this->selectedId = strval( $id );
    }

    public function getHtml() : string {
        $gruppenId = $this->gruppenId;
        $zusatzClass = $this->style === self::STYLE_BACKGROUND ? "registerkarten_backgroundstyle" : "registerkarten_borderstyle";
        $result = "<div class='registerkarten $zusatzClass registerkartenspalten" . count( $this->titles ) . "'>";
        foreach ( $this->titles as $id => $title ) {
            $cssClass = $id == $this->selectedId ? 'header_selected' : 'header_unselected';
            $result .= "<div class='$cssClass' name='registerkarteHeader_$gruppenId' id='registerkarteHeader_$gruppenId" . "_$id'>"
                       . "<a href='javascript:registerkarteSelected(\"$id\", \"$gruppenId\")'>$title</a></div>\n";
        }
        $result .= "</div>\n";
        $result .= "<div class='$zusatzClass'>";
        foreach ( $this->bodies as $id => $body ) {
            $cssClass = $id == $this->selectedId ? "registerkarte_visible" : "registerkarte_hidden";
            $result .= "<div class='$cssClass' name='registerkarteBody_$gruppenId' id='registerkarteBody_$gruppenId" . "_$id'>$body</div>\n";
        }
        $result.= "</div>";
        return $result;
    }
}