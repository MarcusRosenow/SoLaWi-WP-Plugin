<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Widget für Kacheln
 */
final class SOLAWI_WidgetKachellayout extends SOLAWI_AbstractWidget {

    /**
     * Zeigt nichts an, null Abstände
     */
    const STYLE_NONE = "simple";

    /**
     * Zeigt um jede Kachel einen Rahmen und Abstand
     */
    const STYLE_BORDER = "border";

    private array $kacheln;

    private string $style;

    private int $anzahlSpalten;

    public function __construct( int $anzahlSpalten ) {
        $this->kacheln = array();
        $this->style = self::STYLE_NONE;
        $this->anzahlSpalten = $anzahlSpalten;
    }

    /**
     * Setzt den Style ... siehe Konstanten
     */
    public function setStyle( string $style ) : void {
        $this->style = $style;
    }

    /**
     * Fügt dem Ganzen eine Kachel hinzu
     */
    public function add( int|string|null $id, string $kachel, bool $normaleKachel = true ) : void {
        if ( $id == null )
            $this->kacheln[] = $kachel;
        else
            $this->kacheln[ (!$normaleKachel ? "neu___" : "___") . strval( $id ) ] = $kachel;
    }

    public function getHtml() : string {
        $style = "grid-template-columns:";
        for ( $i=0; $i < $this->anzahlSpalten; $i++ )
            $style .= "auto ";
        $className = $this->style == self::STYLE_BORDER ? "solawi_grid_border" : "solawi_grid_simple";
        $result = "<div style='$style' class='$className'>";
        foreach ( $this->kacheln as $id => $kachel ) {
            $idTag = is_string( $id ) ? " id='$id'" : "";
            $result .= "<div$idTag>$kachel</div>\n";
        }
        $result.= "</div>";
        return $result;
    }
}