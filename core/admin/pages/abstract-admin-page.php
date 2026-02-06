<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Abstrakte Oberklasse für die Admin-Seiten.
 * Standardmäßig wird hier das Kachellayout angezeigt.
 */
abstract class SOLAWI_AbstractAdminPage {
	
	private SOLAWI_WidgetKachellayout $layout;

	/**
	 * Speichert die übersandten POST-Daten
	 */
	abstract public function savePostdata( int $id, array $postData ) : SOLAWI_SavePostdataResult;
	
	/**
	 * Gibt den Slug der Seite zurück.
	 * Das ist das interne Kürzel für die Identifikation der Seite.
	 */
	final public function getSlug() : string {
		$titel = preg_replace( "/[^a-z0-9]/", "", strtolower( $this->getTitel() ) );
		return "solawi_admin_" . $titel;
	}
	
	/**
	 * Gibt den Titel der Seite zurück (ohne SoLaWi).
	 */
	abstract public function getTitel() : string;
	
	/**
	 * Gibt die Anzahl der Spalten für das Layout zurück
	 */
	public function getAnzahlSpalten() : int {
		return 5;
	}

	/**
	 * Gibt den Inhalt der Seite aus
	 */
	final public function print() : void {
		echo "<h1>" . $this->getTitel() . "</h1>";
		echo $this->getFilterHtml();
		$this->layout = new SOLAWI_WidgetKachellayout( $this->getAnzahlSpalten() );
		$this->layout->setStyle( SOLAWI_WidgetKachellayout::STYLE_BORDER );
		$this->initInhalt();
		echo $this->layout->getHtml();
		echo $this->getEndeHtml();
	}

	final protected function addKachel( int|string|null $id, string $kachel, bool $normaleKachel = true ) : void {
		$this->layout->add( $id, $kachel, $normaleKachel );
	}
	
	/**
	 * Gibt den Inhalt der Seite aus
	 */
	abstract protected function initInhalt() : void;
	
	/**
	 * Baut das HTML für den Filter
	 */
	protected function getFilterHtml() : string {
		return "<p>Filter: <input type='text' id='jsfilter' oninput='filterDiv(\"jsfilter\")'/></p>";
	}

	/**
	 * Das HTML, welches nach dem Kachellayout am Ende der Seite stehen soll.
	 */
	protected function getEndeHtml() : string {
		return "";
	}
	/**
	 * Gibt den String für den Submit/Reset-Button zurück.
	 */
	final protected function getSubmitButtonHtml( int $id, bool $visible = false, string|null $submitButtonText = null, bool $mitResetButton = true ) : string {
		$result = "<input type='hidden' name='clazz' value='" . get_class( $this ) . "'/>";
		$result .= "<input type='hidden' name='id' value='$id'/>";
		$hidden = $visible ? "" : " hidden='true'";
		$result .= "<input type='submit'$hidden id='submit_$id'" . ( $submitButtonText != null ? " value='$submitButtonText'" : "" ) . "/>";
		if ( $mitResetButton )
			$result .= "<input type='reset'$hidden id='reset_$id'/>";
		$result .= "<div id='result_$id' class='errorText'></div>";
		return $result;
	}

    final public function getOnInputEventHtml( string $id, string $zusatzJsFunktion = "" ) : String {
        return "oninput='solawi_changed(\"$id\");$zusatzJsFunktion;'";
    }
}
