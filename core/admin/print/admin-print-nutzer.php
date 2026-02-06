<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

final class SOLAWI_AdminPrintNutzer extends SOLAWI_AbstractAdminPrint {

    public function getHtmlBody() : string {
        if ( !SOLAWI_hasRolle( SOLAWI_Rolle::ADMINISTRATOR, SOLAWI_Rolle::MANAGER ) ) {
            wp_die('Nicht authorisiert');
        }

        $result = "<h1>Adressen der Mitbauern</h1>\n";
        $result .= "<p>Gedruckt am " . SOLAWI_formatDatum( new DateTime() ) . "</p>";

        $result .= "<table border width='100%'><tr><th>Name</th><th>E-Mail</th><th>Telefon</th><th>Straße</th><th>Ort</th><th>Bemerkung</th></tr>";
        $mitbauern = SOLAWI_Mitbauer::values();
        foreach ( $mitbauern as $mitbauer ) {
            $result .= "<tr>";
            $result .= "<td>" . $mitbauer->getName() . "</td>";
            $result .= "<td>" . $mitbauer->getEmail() . "</td>";
            $result .= "<td>" . $mitbauer->getTelefonnummer() . "</td>";
            $result .= "<td>" . $mitbauer->getStrasse() . "</td>";
            $result .= "<td>" . $mitbauer->getOrt() . "</td>";
            if ( $mitbauer->getBemerkung() !== null )
                $result .= "<td>" . str_replace( "\n", "<br>", $mitbauer->getBemerkung() ) . "</td>";
            else
                $result .= "<td></td>";
            $result .= "</tr>";
        }
        $result .= "</table>";
        return $result;
    }
}
?>