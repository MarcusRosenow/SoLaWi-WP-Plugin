<?php

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Alle DB-Zugriffe!
 */
final class SOLAWI_Repository {
	
	private static $instance;
	
	private string $tableUsers;
	private string $tableMitbauer;
	private string $tableMitbauer2ernteanteil;

	private string $tableVerteilstation;
	private string $tableVerteiltag;
	private string $tableVerteiltag2mitbauer;
	
	private string $tableBieterverfahren;
	private string $tableBieterverfahrenGebot;

	private string $tableUser2Rolle;

	/**
	 * die einzige Instanz
	 */
	public static function instance() : SOLAWI_Repository {
		if ( !isset( self::$instance ) )
			self::$instance = new SOLAWI_Repository();
		return self::$instance;
	}
	
	/**
	 * Konstruktor
	 */
	private function __construct() {
		global $wpdb;
		$this->tableUsers = $wpdb->prefix . 'users';
		$this->tableMitbauer = $wpdb->prefix . 'solawi_mitbauer';
		$this->tableMitbauer2ernteanteil = $wpdb->prefix . 'solawi_mitbauer2ernteanteil';
		$this->tableVerteilstation = $wpdb->prefix . 'solawi_verteilstation';
		$this->tableVerteiltag = $wpdb->prefix . 'solawi_verteiltag';
		$this->tableVerteiltag2mitbauer = $wpdb->prefix . 'solawi_verteiltag2mitbauer';
		$this->tableBieterverfahren = $wpdb->prefix . 'solawi_bieterverfahren';
		$this->tableBieterverfahrenGebot = $wpdb->prefix . 'solawi_bieterverfahren_gebot';
		$this->tableUser2Rolle = $wpdb->prefix . 'solawi_user2rolle';
	}
	
	// -----------------------------------------------------------------------------------------
	// --- Initialisierung ---------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------
	
	/**
	 * Wird beim Aktivieren des Plugins aufgerufen
	 */
	public function createTables() : void {
		// Tabelle wp_solawi_mitbauer
		$columns = [];
		$columns[ "verteilstation" ] = "int(11) DEFAULT 0 NOT NULL";
		$this->createTable( $this->tableMitbauer, $columns);
		
		// Tabelle wp_solawi_mitbauer2ernteanteil
		$columns = [];
		$columns[ "id" ] = "int(11) NOT NULL AUTO_INCREMENT";
		$columns[ "mitbauer_id" ] = "int(11) NOT NULL";
		$columns[ "gueltig_ab" ] = "datetime NOT NULL";
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$columns[ "anteil_" . $bereich->getDbName() ] = "decimal(4, 1) NOT NULL";
			$columns[ "preis_" . $bereich->getDbName() ] = "decimal(6, 2) NOT NULL";
		}
		$this->createTable( $this->tableMitbauer2ernteanteil, $columns);

		// Tabelle wp_solawi_verteilstation
		$this->createTable( $this->tableVerteilstation, [ "name" => "varchar(255) NOT NULL" ] );
		$this->save( new SOLAWI_Verteilstation( 0, "Klein Trebbow" ) );
		
		// Tabelle wp_solawi_verteiltag
		$columns = [];
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$columns[ "bereich_aktiv_" . $bereich->getDbName() ] = "bool DEFAULT FALSE NOT NULL";
			$columns[ "bereich_text_" . $bereich->getDbName() ] = "varchar(255)";
		}
		$columns["start_ernte"] = "varchar(10) DEFAULT '08:00' NOT NULL";
		$columns["ende_ernte"] = "varchar(10) DEFAULT '11:00' NOT NULL";
		$columns["start_packen"] = "varchar(10) DEFAULT '11:00' NOT NULL";
		$columns["ende_packen"] = "varchar(10) DEFAULT '13:00' NOT NULL";
		$columns["start_verteilung"] = "varchar(10) DEFAULT '13:00' NOT NULL";
		$columns["ende_verteilung"] = "varchar(10) DEFAULT '18:00' NOT NULL";
		$this->createTable( $this->tableVerteiltag, $columns );
		
		// Tabelle wp_solawi_verteiltag2mitbauer
		$columns = [];
		$columns[ "verteiltag_id" ] = "int(11) NOT NULL";
		$columns[ "mitbauer_id" ] = "int(11) NOT NULL";
		$columns[ "station_id" ] = "int(11)";
		$columns[ "gemuese_ernten" ] = "bool DEFAULT FALSE NOT NULL";
		$columns[ "kisten_packen" ] = "bool DEFAULT FALSE NOT NULL";
		$this->createTable( $this->tableVerteiltag2mitbauer, $columns );
		
		// Tabelle wp_solawi_bieterverfahren
		$columns = [];
		$columns[ "id" ] = "int(11) NOT NULL AUTO_INCREMENT";
		$columns[ "gesamtbudget" ] = "decimal(10, 2) NOT NULL";
		$columns[ "anzahlrunden" ] = "int(11) NOT NULL";
		$columns[ "startdersaison" ] = "datetime NOT NULL";
		$columns[ "aktuellerunde" ] = "int(11) NOT NULL";
		$columns[ "letzterunde" ] = "int(11) NOT NULL";
		$columns[ "gueltig_ab" ] = "datetime NOT NULL";
		$columns[ "gueltig_bis" ] = "datetime";
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$columns[ "richtwert_" . $bereich->getDbName() ] = "decimal(6, 2) NOT NULL";
			$columns[ "planVergebeneAnteile_" . $bereich->getDbName() ] = "decimal(5, 1) NOT NULL";
		}
		$this->createTable( $this->tableBieterverfahren, $columns );

		// Tabelle wp_solawi_bieterverfahren
		$columns = [];
		$columns[ "id" ] = "int(11) NOT NULL AUTO_INCREMENT";
		$columns[ "bieterverfahren_id" ] = "int(11) NOT NULL";
		$columns[ "user_id" ] = "int(11) NOT NULL";
		$columns[ "runde" ] = "int(11) NOT NULL";
		foreach ( SOLAWI_Bereich::values() as $bereich ) {
			$columns[ "anteil_" . $bereich->getDbName() ] = "decimal(4, 1) NOT NULL";
			$columns[ "preis_" . $bereich->getDbName() ] = "decimal(6, 2) NOT NULL";
		}
		$this->createTable( $this->tableBieterverfahrenGebot, $columns );

		// Tabelle wp_solawi_user2rolle
		$columns = [];
		$columns[ "rollen" ] = "int(11) NOT NULL";
		$this->createTable( $this->tableUser2Rolle, $columns);
	}
	
	/**
	 * Wird beim Löschen des Plugins aufgerufen
	 */
	public function dropTables() : void {
		global $wpdb;
		$wpdb->query( "DROP TABLE " . $this->tableMitbauer );
		$wpdb->query( "DROP TABLE " . $this->tableMitbauer2ernteanteil );
		$wpdb->query( "DROP TABLE " . $this->tableVerteilstation );
		$wpdb->query( "DROP TABLE " . $this->tableVerteiltag );
		$wpdb->query( "DROP TABLE " . $this->tableVerteiltag2mitbauer );
		$wpdb->query( "DROP TABLE " . $this->tableBieterverfahren );
		$wpdb->query( "DROP TABLE " . $this->tableBieterverfahrenGebot );
		$wpdb->query( "DROP TABLE " . $this->tableUser2Rolle );
	}
	
	/**
	 * Legt eine Tabelle an.
	 * @param {array} $columns darf id enthalten, muss aber nicht.
	 */
	private function createTable( string $table, array $columns ) : void {
		global $wpdb;
		$columnDefinitions = "";
		if ( !isset( $columns[ "id" ] ) )
			$columns[ "id" ] = "int(11) NOT NULL";
		foreach ( $columns as $name => $type )
			$columnDefinitions .= " $name $type,\n";
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table (
			$columnDefinitions PRIMARY KEY  (id)
			) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Löscht die übergebene Entity
	 */
	public function remove( SOLAWI_Verteilstation|SOLAWI_Verteiltag|SOLAWI_Verteiltag2Mitbauer $entity ) : void {
		$table = $this->getTableName( $entity );
		global $wpdb;
		$wpdb->delete( $table, array( 'id' => $entity->getId() ) );
		$entity::clearCache();
	}

	/**
	 * Speichert eine Entity
	 */
	public function save( SOLAWI_Mitbauer|
	    					SOLAWI_Verteilstation|
							SOLAWI_Verteiltag|
							SOLAWI_Verteiltag2Mitbauer|
							SOLAWI_Bieterverfahren|
							SOLAWI_BieterverfahrenGebot|
							SOLAWI_User2Rolle $entity ) : int|false {
		$table = $this->getTableName( $entity );
		$attributes = array();
		$attributes[ "id" ] = $entity->getId();

		if ( $entity instanceof SOLAWI_Mitbauer ) {
			$attributes[ "verteilstation" ] = $entity->getVerteilstation()->getId();
			$this->saveMitbauerErnteanteile( $entity );

		} elseif ( $entity instanceof SOLAWI_Verteilstation ) {
			$attributes[ "name" ] = $entity->getName();

		} elseif ( $entity instanceof SOLAWI_Verteiltag ) {
			foreach ( SOLAWI_Bereich::values() as $bereich ) {
				$attributes[ "bereich_aktiv_" . $bereich->getDbName() ] = $entity->isVerteilungVon( $bereich ) ? 1 : 0;
				$attributes[ "bereich_text_" . $bereich->getDbName() ] = $entity->getText( $bereich );
			}
			$attributes["start_ernte"] = $entity->getStartGemueseErnten();
			$attributes["ende_ernte"] = $entity->getEndeGemueseErnten();
			$attributes["start_packen"] = $entity->getStartKistenPacken();
			$attributes["ende_packen"] = $entity->getEndeKistenPacken();
			$attributes["start_verteilung"] = $entity->getStartVerteilung();
			$attributes["ende_verteilung"] = $entity->getEndeVerteilung();

		} elseif ( $entity instanceof SOLAWI_Verteiltag2Mitbauer ) {
			$attributes[ "verteiltag_id" ] = $entity->getVerteiltag()->getId();
			$attributes[ "mitbauer_id" ] = $entity->getMitbauer()->getId();
			$attributes[ "station_id" ] = $entity->getVerteilstation() !== null ? $entity->getVerteilstation()->getId() : null;
			$attributes[ "gemuese_ernten" ] = $entity->isGemueseErnten();
			$attributes[ "kisten_packen" ] = $entity->isKistenPacken();

		} elseif ( $entity instanceof SOLAWI_Bieterverfahren ) {
			$attributes[ "gesamtbudget" ] = $entity->getGesamtbudget();
			$attributes[ "anzahlrunden" ] = $entity->getAnzahlRunden();
			$attributes[ "startdersaison" ] = SOLAWI_formatDatum( $entity->getStartDerSaison(), true );
			$attributes[ "aktuellerunde" ] = $entity->getAktuelleRunde();
			$attributes[ "letzterunde" ] = $entity->getLetzteRunde();
			$attributes[ "gueltig_ab" ] = SOLAWI_formatDatum( $entity->getGueltigAb(), true );
			$attributes[ "gueltig_bis" ] = $entity-> getGueltigBis() != null ? SOLAWI_formatDatum( $entity->getGueltigBis(), true ) : null;
			foreach ( SOLAWI_Bereich::values() as $bereich ) {
				$attributes[ "richtwert_" . $bereich->getDbName() ] = $entity->getRichtwert( $bereich );
				$attributes[ "planVergebeneAnteile_" . $bereich->getDbName() ] = $entity->getPlanVergebeneAnteile( $bereich );	
			}
			
		} elseif ( $entity instanceof SOLAWI_BieterverfahrenGebot ) {
			$attributes[ "bieterverfahren_id" ] = $entity->getBieterverfahren()->getId();
			$attributes[ "user_id" ] = $entity->getMitbauer()->getId();
			$attributes[ "runde" ] = $entity->getRunde();
			foreach ( SOLAWI_Bereich::values() as $bereich ) {
				$attributes[ "anteil_" . $bereich->getDbName() ] = $entity->getAnzahl( $bereich );
				$attributes[ "preis_" . $bereich->getDbName() ] = $entity->getPreis( $bereich );	
			}
			
		} else if ( $entity instanceof SOLAWI_User2Rolle ) {
			$attributes[ "rollen" ] = $entity->getRollen();
		}


		global $wpdb;
		$entity::clearCache();
		return $wpdb->replace( $table, $attributes);
	}

	/**
	 * Gibt den Tabellennamen für die übergebene Entity zurück
	 */
	private function getTableName( SOLAWI_Mitbauer|
									SOLAWI_MitbauerErnteanteil|
									SOLAWI_Verteilstation|
									SOLAWI_Verteiltag|
									SOLAWI_Verteiltag2Mitbauer|
									SOLAWI_Bieterverfahren|
									SOLAWI_BieterverfahrenGebot|
									SOLAWI_User2Rolle $entity ) : string {
		if ( $entity instanceof SOLAWI_Mitbauer ) {
    		return $this->tableMitbauer;
		} elseif ( $entity instanceof SOLAWI_MitbauerErnteanteil ) {
    		return $this->tableMitbauer2ernteanteil;
		} elseif ( $entity instanceof SOLAWI_Verteilstation ) {
    		return $this->tableVerteilstation;
		} elseif ( $entity instanceof SOLAWI_Verteiltag ) {
    		return $this->tableVerteiltag;
		} elseif ( $entity instanceof SOLAWI_Verteiltag2Mitbauer ) {
    		return $this->tableVerteiltag2mitbauer;
		} elseif ( $entity instanceof SOLAWI_Bieterverfahren ) {
    		return $this->tableBieterverfahren;
		} elseif ( $entity instanceof SOLAWI_BieterverfahrenGebot ) {
    		return $this->tableBieterverfahrenGebot;
		} elseif ( $entity instanceof SOLAWI_User2Rolle ) {
    		return $this->tableUser2Rolle;
		}
	}
	
	// -----------------------------------------------------------------------------------------
	// --- Mitbauern ---------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------
	
	/**
	 * Gibt ein Array von Mitbauern zurück
	 */
	public function getMitbauern() : array {
		global $wpdb;
		$sql = "SELECT u.id user_id, u.display_name, u.user_email email, r.rollen, m.*
			FROM {$this->tableUsers} u
			     LEFT OUTER JOIN {$this->tableMitbauer} m ON m.id = u.ID
				 LEFT OUTER JOIN {$this->tableUser2Rolle} r ON r.id = u.ID
			ORDER BY u.display_name";
		$dbResultsMitbauern = $wpdb->get_results( $sql, ARRAY_A );
		$sql = "SELECT *
			FROM {$this->tableMitbauer2ernteanteil}
			ORDER BY gueltig_ab";
		$dbResultsErnteanteile = $wpdb->get_results( $sql, ARRAY_A );
		
		$result = array();
		foreach ( $dbResultsMitbauern as $row ) {
			$mitbauer = new SOLAWI_Mitbauer( intval( $row["user_id"] ), $row["display_name"], $row["email"] );
			$ernteanteileMitbauer = $this->filterErnteAnteile( $mitbauer->getId(), $dbResultsErnteanteile );
			foreach ( $ernteanteileMitbauer as $rowErnteanteil ) {
				$ernteanteil = new SOLAWI_MitbauerErnteanteil( new DateTime( $rowErnteanteil['gueltig_ab'] ) );
				foreach ( SOLAWI_Bereich::values() as $bereich ) {
					$anteil = $rowErnteanteil["anteil_{$bereich->getDbName()}"];
					if ( $anteil != null ) {
						$ernteanteil->setAnzahl( $bereich, $rowErnteanteil["anteil_{$bereich->getDbName()}"] );
						$ernteanteil->setPreis( $bereich, $rowErnteanteil["preis_{$bereich->getDbName()}"] );
					}
				}
				$mitbauer->addErnteAnteil( $ernteanteil );
			}
			$station = SOLAWI_Verteilstation::valueOf( intval( $row["verteilstation"] ) );
			$mitbauer->setVerteilstation( $station );
			// user2rolle muss angepasst werden, wenn noch kein Rolleneintrag existiert (neuer Nutzer)
			if ( $row[ "rollen" ] === null ) {
				$this->save( new SOLAWI_User2Rolle( $mitbauer->getId(), SOLAWI_Rolle::MITBAUER->getId() ) );
				SOLAWI_User2Rolle::clearCache();
			} elseif ( ( intval( $row[ "rollen" ] ) & SOLAWI_Rolle::MITBAUER->getId() ) == 0) {
				continue; // Dieser Mitbauer ist gar kein Mitbauer (mehr)
			}
			$result[] = $mitbauer;

		}
		return $result;
	}
	
	private function filterErnteAnteile( int $mitbauer_id, array $dbResultsErnteanteile ) : array {
		$result = array();
		foreach ( $dbResultsErnteanteile as $row )
			if ( $row[ "mitbauer_id" ] == $mitbauer_id )
				$result[] = $row;
		return $result;
	}

	/**
	 * Speichert die an den Mitbauer angehängte Ernteanteile
	 */
	private function saveMitbauerErnteanteile( SOLAWI_Mitbauer $mitbauer ) : void {
		global $wpdb;
		$table = $this->tableMitbauer2ernteanteil;
		// alle bisherigen Ernteanteile löschen
		$wpdb->delete( $table, array( 'mitbauer_id' => $mitbauer->getId() ) );
		// alle jetzigen Ernteanteile speichern
		$ernteanteile = $mitbauer->getErnteanteile();
		foreach ( $ernteanteile as $ernteanteil ) {
			$attributes = array();
			$attributes[ "mitbauer_id" ] = $mitbauer->getId();
			$attributes[ "gueltig_ab" ] = SOLAWI_formatDatum( $ernteanteil->getGueltigAb(), true );
			foreach ( SOLAWI_Bereich::values() as $bereich ) {
				$attributes[ "anteil_" . $bereich->getDbName() ] = $ernteanteil->getAnzahl( $bereich );
				$attributes[ "preis_" . $bereich->getDbName() ] = $ernteanteil->getPreis( $bereich );
			}
			$wpdb->replace( $table, $attributes);
		}
	}

	// -----------------------------------------------------------------------------------------
	// --- Verteilstationen --------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------
	
	/**
	 * Gibt ein array von voll initialisierten SOLAWI_Mitbauer-Objekten zurück
	 */
	public function getVerteilstationen() : array {
		global $wpdb;
		$dbResults = $wpdb->get_results( "SELECT id, name FROM {$this->tableVerteilstation} ORDER BY name", ARRAY_A );
		$result = array();
		foreach ( $dbResults as $row ) {
			$station = new SOLAWI_Verteilstation( intval( $row["id"] ) , $row["name"] );
			if ( $row["id"] == 0 ) // Die ID 0 soll an die erste Stelle
				array_unshift( $result, $station );
			else
				$result[] = $station;
		}
		return $result;
	}
	
	// -----------------------------------------------------------------------------------------
	// --- Verteiltage -------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------
	
	/**
	 * Gibt ein array aller Verteiltage zurück
	 */
	public function getVerteiltage() : array {
		global $wpdb;
		$zeit = (new DateTime())->sub( DateInterval::createFromDateString('4 weeks') );
		$sql = "SELECT * FROM {$this->tableVerteiltag}";
		$sql .= " WHERE id >= " . SOLAWI_formatDatum( $zeit, false );
		$sql .= " ORDER BY id";
		$dbResults = $wpdb->get_results( $sql, ARRAY_A );
		$result = array();
		foreach ( $dbResults as $row ) {
			$tag = SOLAWI_Verteiltag::createFromId( intval( $row["id"] ) );
			foreach ( SOLAWI_Bereich::values() as $bereich ) {
				if ( $row[ "bereich_aktiv_" . $bereich->getDbName() ] === "1" ) {
					$tag->setVerteilungVon( $bereich );
					$tag->setText( $bereich, $row[ "bereich_text_" . $bereich->getDbName() ] );
				}
			}
			$tag->setZeitGemueseErnten( $row["start_ernte"], $row["ende_ernte"] );
			$tag->setZeitKistenPacken( $row["start_packen"], $row["ende_packen"] );
			$tag->setZeitVerteilung( $row["start_verteilung"], $row["ende_verteilung"] );
			$result[] = $tag;
		}
		return $result;
	}
	
	// -----------------------------------------------------------------------------------------
	// --- Verteiltag2Mitbauer -----------------------------------------------------------------
	// -----------------------------------------------------------------------------------------
	
	/**
	 * Gibt ein array aller Verteiltag2Mitbauer zurück
	 */
	public function getVerteiltag2Mitbauer() : array {
		global $wpdb;
		$zeit = new DateTime();
		$sql = "SELECT id, verteiltag_id, mitbauer_id, station_id, gemuese_ernten, kisten_packen FROM {$this->tableVerteiltag2mitbauer}";
		$sql .= " WHERE verteiltag_id >= " . SOLAWI_formatDatum( $zeit, false );
		$sql .= " ORDER BY id";
		$dbResults = $wpdb->get_results( $sql, ARRAY_A );
		$result = array();
		foreach ( $dbResults as $row ) {
			$verteiltag = SOLAWI_Verteiltag::valueOf( intval( $row["verteiltag_id"] ) );
			$mitbauer = SOLAWI_Mitbauer::valueOf( intval( $row["mitbauer_id"] ) );
			$vt2mb = new SOLAWI_Verteiltag2Mitbauer( intval( $row["id"] ), $verteiltag, $mitbauer );
			if ( isset( $row["station_id"] ) ) {
				$station = SOLAWI_Verteilstation::valueOf( intval( $row["station_id"] ) );
				$vt2mb->setVerteilstation( $station );
			} else {
				$vt2mb->setVerteilstation( null );
			}
			$vt2mb->setGemueseErnten( boolval( $row["gemuese_ernten"] ) );
			$vt2mb->setKistenPacken( boolval( $row["kisten_packen"] ) );
			$result[] = $vt2mb;
		}
		return $result;
	}

	// -----------------------------------------------------------------------------------------
	// --- Bieterverfahren ---------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------

	/**
	 * Gibt ein array aller SOLAWI_Bieterverfahren zurück
	 */
	public function getBieterverfahren() : array {
		global $wpdb;
		$sql = "SELECT * FROM {$this->tableBieterverfahren} ORDER BY gueltig_ab DESC, gueltig_bis ASC";
		$dbResults = $wpdb->get_results( $sql, ARRAY_A );
		$result = array();
		foreach ( $dbResults as $row ) {
			$entity = new SOLAWI_Bieterverfahren( intval( $row["id"] ), new DateTime( $row['gueltig_ab'] ) );
			$entity->setGesamtbudget( floatval( $row["gesamtbudget"] ) );
			$entity->setAnzahlRunden( floatval( $row["anzahlrunden"] ) );
			$entity->setStartDerSaison( new DateTime( $row['startdersaison'] ) );
			$entity->setAktuelleRunde( intval( $row["aktuellerunde"] ) );
			$entity->setLetzteRunde( intval( $row["letzterunde"] ) );
			$entity->setGueltigBis( isset( $row['gueltig_bis'] ) ? new DateTime( $row['gueltig_bis'] ) : null );
			foreach ( SOLAWI_Bereich::values() as $bereich ) {
				$entity->setRichtwert( $bereich, floatval( $row[ "richtwert_" . $bereich->getDbName() ] ) );
				$entity->setPlanVergebeneAnteile( $bereich, floatval( $row[ "planVergebeneAnteile_" . $bereich->getDbName() ] ) );
			}
			$result[] = $entity;
		}
		return $result;
	}

	/**
	 * Gibt ein array aller SOLAWI_BieterverfahrenGebot zurück
	 */
	public function getBieterverfahrenGebote() : array {
		global $wpdb;
		$sql = "SELECT * FROM {$this->tableBieterverfahrenGebot}";
		$dbResults = $wpdb->get_results( $sql, ARRAY_A );
		$result = array();
		foreach ( $dbResults as $row ) {
			$mitbauer = SOLAWI_Mitbauer::valueOf( intval( $row["user_id"] ) );
			if ( $mitbauer == null )
				continue;
			$entity = new SOLAWI_BieterverfahrenGebot( intval( $row["id"] ) );
			$entity->setBieterverfahren( SOLAWI_Bieterverfahren::valueOf( intval( $row["bieterverfahren_id"] ) ) );
			$entity->setMitbauer( $mitbauer );
			$entity->setRunde( intval( $row["runde"] ) );
			foreach ( SOLAWI_Bereich::values() as $bereich ) {
				$entity->setAnzahl( $bereich, floatval( $row[ "anteil_" . $bereich->getDbName() ] ) );
				$entity->setPreis( $bereich, floatval( $row[ "preis_" . $bereich->getDbName() ] ) );
			}
			$result[] = $entity;
		}
		return $result;
	}

	// -----------------------------------------------------------------------------------------
	// --- User2Rolle --------------------------------------------------------------------------
	// -----------------------------------------------------------------------------------------

	/**
	 * Gibt ein array aller SOLAWI_User2Rolle zurück
	 */
	public function getUser2Rollen() : array {
		global $wpdb;
		$sql = "SELECT id, rollen FROM {$this->tableUser2Rolle}";
		$dbResults = $wpdb->get_results( $sql, ARRAY_A );
		$result = array();
		foreach ( $dbResults as $row ) {
			$entity = new SOLAWI_User2Rolle( intval( $row["id"] ), intval( $row['rollen'] ) );
			$result[] = $entity;
		}
		return $result;
	}
}