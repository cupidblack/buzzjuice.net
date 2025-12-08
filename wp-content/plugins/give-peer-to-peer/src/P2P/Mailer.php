<?php

namespace GiveP2P\P2P;

class Mailer {

	/**
	 * @var string
	 */
	protected $templateDirectoryPath;

	/**
	 * @param string $templateDirectoryPath
	 */
	public function __construct( $templateDirectoryPath ) {
		$this->templateDirectoryPath = $templateDirectoryPath;
	}

	/**
	 * @param string $templatePath
	 * @param array $data
	 * @return false|string
	 */
	public function getContents( $templatePath, $data = [] ) {
		extract( $data );
		ob_start();
		include $this->templateDirectoryPath . $templatePath . '.html.php';
		return ob_get_clean();
	}
}
