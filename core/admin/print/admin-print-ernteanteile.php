<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_AdminPrintErnteanteile extends SOLAWI_AbstractAdminPrint {

    public function getHtmlBody() : string {
        if ( !SOLAWI_hasRolle( SOLAWI_Rolle::MANAGER) ) {
            wp_die('Nicht authorisiert');
        }
        if ( !isset( $_GET['datum'] ) ) {
            wp_die('Datum fehlt');
        }

        $tag = SOLAWI_Verteiltag::createFromId( intval( $_GET['datum'] ) );
        $result = "<h1>Ernteanteile zum " . SOLAWI_formatDatum( $tag->getDatum() ) . "</h1>\n";
        $result .= "<p>Gedruckt am " . SOLAWI_formatDatum( new DateTime() ) . "<br></p>";

        $result .= "<table border width='100%'><tr><th>Name</th>";
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $result .= "<th>Anteile<br>" . $bereich->getName() . "</th>";
            $result .= "<th>Preis<br>" . $bereich->getName() . "</th>";
        }
        $result .= "</tr>";
        $mitbauern = SOLAWI_Mitbauer::values();
        foreach ( $mitbauern as $mitbauer )
            if ( $mitbauer->hasErnteanteile( null, $tag->getDatum() ) )
                $result .= $this->getHtmlZeile( $mitbauer, $tag  );
        $result .= "</table>";
        return $result;
    }

    private function getHtmlZeile( SOLAWI_Mitbauer $mitbauer, SOLAWI_Verteiltag $tag ) : string {
        $result = "<tr><td>" . $mitbauer->getName() . "</td>";
        $ea = $mitbauer->getErnteanteilIntern( $tag->getDatum() );
        foreach( SOLAWI_Bereich::values() as $bereich ) {
            $result .= "<td align=right>" . SOLAWI_formatAnzahl( $ea->getAnzahl( $bereich ) ) . "</td>";
            $result .= "<td align=right>" . SOLAWI_formatWaehrung( $ea->getPreis( $bereich ) ) . "</td>";
        }
        $result .= "</tr>";
        return $result;
    }
}
?>