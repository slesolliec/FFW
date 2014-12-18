<?php
/*
 * CKFinder
 * ========
 * http://www.ckfinder.com
 * Copyright (C) 2007-2008 Frederico Caldeira Knabben (FredCK.com)
 *
 * The software, this file and its contents are subject to the CKFinder
 * License. Please read the license.txt file before using, installing, copying,
 * modifying or distribute this file or part of its contents. The contents of
 * this file is part of the Source Code of CKFinder.
 */

define( 'CKFINDER_DEFAULT_BASEPATH', '/ckfinder/' ) ;

class CKFinder
{
	var $BasePath ;
	var $Width ;
	var $Height ;
	var $SelectFunction ;
	var $ClassName ;

	// PHP 4 Contructor
	function CKFinder( $basePath = CKFINDER_DEFAULT_BASEPATH, $width = '100%', $height = 400, $selectFunction = null )
	{
		$this->BasePath			= empty( $basePath ) ? CKFINDER_DEFAULT_BASEPATH : $basePath ;
		$this->Width			= empty( $width ) ? '100%' : $width ;
		$this->Height			= empty( $height ) ? 400 : $height ;
		$this->SelectFunction	= $selectFunction ;
		$this->ClassName		= '' ;
	}

	// Renders CKFinder in the current page.
	function Create()
	{
			echo $this->CreateHtml() ;
	}

	// Gets the HTML needed to create a CKFinder instance.
	function CreateHtml()
	{
		$className = $this->ClassName ;
		if ( !empty( $className ) )
			$className = ' class="' . $className . '"' ;

		return '<iframe src="' . $this->_BuildUrl() . '" width="' . $this->Width . '" ' .
			'height="' . $this->Height . '"' . $className . ' frameborder="0" scrolling="no"></iframe>' ;
	}

	function _BuildUrl()
	{
		$url = $this->BasePath ;

		if ( empty( $url ) )
			$url = CKFINDER_DEFAULT_BASEPATH ;

		if ( $url[ strlen( $url ) - 1 ] != '/' )
			$url = $url . '/' ;

		$url .= 'ckfinder.html' ;

		if ( !empty( $this->SelectFunction ) )
			$url .= '?action=js&amp;func=' . $this->SelectFunction ;

		return $url ;
	}

	// Static "Create".
	function CreateStatic( $basePath = CKFINDER_DEFAULT_BASEPATH, $width = '100%', $height = 400, $selectFunction = null )
	{
		$finder = new CKFinder( $basePath, $width, $height, $selectFunction ) ;
		$finder->Create() ;
	}

	// Static "SetupFCKeditor".
	function SetupFCKeditor( &$editorObj, $basePath = CKFINDER_DEFAULT_BASEPATH, $imageType = null, $flashType = null )
	{
		if ( empty( $basePath ) )
			$basePath = CKFINDER_DEFAULT_BASEPATH ;

		// If it is a path relative to the current page.
		if ( $basePath[0] != '/' )
		{
			$basePath = substr( $_SERVER[ 'REQUEST_URI' ], 0, strrpos( $_SERVER[ 'REQUEST_URI' ], '/' ) + 1 ) .
				$basePath ;
		}

		$ckfinder = new CKFinder( $basePath ) ;
		$url = $ckfinder->_BuildUrl() ;

		$editorObj->Config['LinkBrowserURL'] = $url ;
		$editorObj->Config['ImageBrowserURL'] = $url . '?type=' . ( empty( $imageType ) ? 'Images' : $imageType ) ;
		$editorObj->Config['FlashBrowserURL'] = $url . '?type=' . ( empty( $flashType ) ? 'Flash' : $flashType ) ;
		
		$dir = substr( $url, 0, strrpos( $url, "/" ) + 1 ) ;
		$editorObj->Config['LinkUploadURL'] = $dir . urlencode( 'core/connector/php/connector.php?command=QuickUpload&type=Files' ) ;
		$editorObj->Config['ImageUploadURL'] = $dir . urlencode( 'core/connector/php/connector.php?command=QuickUpload&type=') . ( empty( $imageType ) ? 'Images' : $imageType ) ;
		$editorObj->Config['FlashUploadURL'] = $dir . urlencode( 'core/connector/php/connector.php?command=QuickUpload&type=') . ( empty( $flashType ) ? 'Flash' : $flashType ) ;
	}
}

?>