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
 *
 * $Revision: 297 $
 */

var CKFinder = function( basePath, width, height, selectFunction )
{
	// The URL path for the installation folder of CKFinder (default = "/ckfinder/").
	this.BasePath = basePath || CKFinder.DEFAULT_BASEPATH ;

	// The CKFinder width (ex: 600, '80%') (default = "100%").
	this.Width	= width || '100%' ;

	// The CKFinder height (ex: 500, '100%') (default = 400).
	this.Height	= height || 400 ;

	// An optional function to be called when the user selects a file in CKFinder.
	this.SelectFunction = selectFunction || null ;

	// The name of the CSS class rule assigned to the CKFinder frame (default = "CKFinderStyle").
	this.ClassName = null || 'CKFinderFrame' ;
	
	// The server language of the connector
	this.ConnectorLanguage = 'php' ;
}

CKFinder.DEFAULT_BASEPATH = '/ckfinder/' ;

CKFinder.prototype = {

	// Renders CKFinder in the current document.
	Create : function()
	{
		document.write( this.CreateHtml() ) ;
	},

	// Gets the HTML needed to create a CKFinder instance.
	CreateHtml : function()
	{
		var className = this.ClassName ;
		if ( className && className.length > 0 )
			className = ' class="' + className + '"' ;

		return '<iframe src="' + this._BuildUrl() + '" width="' + this.Width + '" ' +
			'height="' + this.Height + '"' + className + ' frameborder="0" scrolling="no"></iframe>' ;
	},

	// Opens CKFinder in a popup. The "width" and "height" parameters accept
	// numbers (pixels) or percent (of screen size) values.
	Popup : function( width, height )
	{
		width = width || '80%' ;
		height = height || '70%' ;

		if ( typeof width == 'string' && width.length > 1 && width.substr( width.length - 1, 1 ) == '%' )
			width = parseInt( window.screen.width * parseInt( width ) / 100 ) ;

		if ( typeof height == 'string' && height.length > 1 && height.substr( height.length - 1, 1 ) == '%' )
			height = parseInt( window.screen.height * parseInt( height ) / 100 ) ;

		if ( width < 200 )
			width = 200 ;

		if ( height < 200 )
			height = 200 ;

		var top = parseInt( ( window.screen.height - height ) / 2 ) ;
		var left = parseInt( ( window.screen.width  - width ) / 2 ) ;

		var options = 'location=no,menubar=no,toolbar=no,dependent=yes,minimizable=no,modal=yes,alwaysRaised=yes,resizable=yes' +
			',width='  + width +
			',height=' + height +
			',top='  + top +
			',left=' + left ;

		var popupWindow = window.open( '', 'CKFinderPopup', options, true ) ;

		// Blocked by a popup blocker.
		if ( !popupWindow )
			return false ;

		try
		{
			popupWindow.moveTo( left, top ) ;
			popupWindow.resizeTo( width, height ) ;
			popupWindow.focus() ;
			popupWindow.location.href = this._BuildUrl() ;
		}
		catch (e)
		{
			popupWindow = window.open( this._BuildUrl(), 'CKFinderPopup', options, true ) ;
		}

		return true ;
	},

	_BuildUrl : function()
	{
		var url = this.BasePath ;

		if ( !url || url.length == 0 )
			url = CKFinder.DEFAULT_BASEPATH ;

		if ( url.substr( url.length - 1, 1 ) != '/' )
			url = url + '/' ;

		url += 'ckfinder.html' ;

		if ( this.SelectFunction )
		{
			var functionName = this.SelectFunction ;
			if ( typeof functionName == 'function' )
				functionName = functionName.toString().match( /function ([^(]+)/ )[1] ;

			url += '?action=js&amp;func=' + functionName ;
		}

		return url ;
	}

} ;

// Static "Create".
CKFinder.Create = function( basePath, width, height, selectFunction )
{
	var ckfinder = new CKFinder( basePath, width, height, selectFunction ) ;
	ckfinder.Create() ;
}

// Static "Popup".
CKFinder.Popup = function( basePath, width, height, selectFunction )
{
	var ckfinder = new CKFinder( basePath, null, null, selectFunction ) ;
	ckfinder.Popup( width, height ) ;
}

// Static "SetupFCKeditor".
CKFinder.SetupFCKeditor = function( editorObj, basePath, imageType, flashType )
{
	if ( !basePath || basePath.length == 0 )
		basePath = CKFinder.DEFAULT_BASEPATH ;

	// If it is a path relative to the current page.
	if ( basePath.substr( 0, 1 ) != '/' )
	{
		basePath = document.location.pathname.substring( 0, document.location.pathname.lastIndexOf('/') + 1 ) +
			basePath ;
	}

	var ckfinder = new CKFinder( basePath ) ;
	var url = ckfinder._BuildUrl() ;

	editorObj.Config['LinkBrowserURL'] = url ;
	editorObj.Config['ImageBrowserURL'] = url + '?type=' + ( imageType || 'Images' ) ;
	editorObj.Config['FlashBrowserURL'] = url + '?type=' + ( flashType || 'Flash' ) ;
	
	var dir = url.substring(0, 1 + url.lastIndexOf("/"));	
	editorObj.Config['LinkUploadURL'] = dir + "core/connector/" + ckfinder.ConnectorLanguage + "/connector." 
		+ ckfinder.ConnectorLanguage + "?command=QuickUpload&type=Files" ;
	editorObj.Config['ImageUploadURL'] = dir + "core/connector/" + ckfinder.ConnectorLanguage + "/connector." 
		+ ckfinder.ConnectorLanguage + "?command=QuickUpload&type=" + ( imageType || 'Images' ) ;
	editorObj.Config['FlashUploadURL'] = dir + "core/connector/" + ckfinder.ConnectorLanguage + "/connector." 
		+ ckfinder.ConnectorLanguage + "?command=QuickUpload&type=" + ( flashType || 'Flash' ) ;
		
}
