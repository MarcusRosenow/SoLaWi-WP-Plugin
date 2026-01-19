<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class SOLAWI_AbstractAdminPrint {

    /**
     * Gibt die URL zurück, die zu dieser Seite führt.
     * @param zusaetzlicheAttribute ein assoziatives array mit zusätzlichen Keys und Values
     */
    public final function getUrl( array|null $zusaetzlicheAttribute ) : string {
		$url = 'admin-post.php?action=print_solawi';
		$url .= '&wpnonce=' . wp_create_nonce();
		$url .= '&clazz=' . get_class( $this );
        if ( $zusaetzlicheAttribute != null ) {
            foreach( $zusaetzlicheAttribute as $key => $value ) {
                $url .= '&' . $key . '=' . $value;
            }
        }
		return esc_url( admin_url( $url ) );
    }

    /**
     * Druckt nach den angegebenen Vorgaben
     */
    public final function print() : void {
        if ( !isset( $_GET['wpnonce'] ) || !wp_verify_nonce( $_GET['wpnonce'] ) ) {
            wp_die('Invalid nonce');
        }
        if ( !isset( $_GET['clazz'] ) || $_GET['clazz'] != get_class( $this ) ) {
            wp_die('Class falsch');
        }
        echo "<html>";
        echo "<head><title>SoLaWi Klein Trebbow</title></head>";
        echo "<body>";
        echo $this->getHtmlBody();
        echo "</body>";
        echo "</html>";
    }

    /**
     * Das HTML, welches als Body ausgegeben werden soll
     */
    public abstract function getHtmlBody() : string;
}
?>