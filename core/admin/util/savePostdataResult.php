<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Das Ergebnis des Speicherns der Daten von einem AJAX-Request
 */
class SOLAWI_SavePostdataResult {
	
	private array $messages;

    private bool $hasError;
    
	private bool $reloadPage;

    public function __construct( string|null $successText = null, string|null $errorText = null, bool $reloadPage = false ) {
        $this->messages = array();
        $this->hasError = $errorText !== null;
        if ( $successText != null )
            $this->messages[] = $successText;
        if ( $errorText != null )
            $this->messages[] = $errorText;
        $this->reloadPage = $reloadPage;
    }

    public function isError() {
        return $this->hasError;
    }

    public function getMessages() : array {
        return $this->messages;
    }

    public function addErrorText( string $error ) : void {
        $this->messages[] = $error;
        $this->hasError = true;
    }

    public function isReloadPage() : bool {
        return $this->reloadPage;
    }

    public function getJsonResponse() : array {
        return array(
            'messages' => $this->getMessages(),
            'reloadPage' => $this->isReloadPage()
        );
    }
}