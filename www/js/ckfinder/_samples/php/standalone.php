<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>CKFinder - Sample - Standalone</title>
	<style type="text/css">

		/* By defining CKFinderFrame, you are able to customize the CKFinder frame style */
		.CKFinderFrame
		{
			border: solid 2px #e3e3c7;
			background-color: #f1f1e3;
		}

	</style>
	<script type="text/javascript">

// This is a sample function which is called when a file is selected in CKFinder.
function ShowFileInfo( fileUrl )
{
	alert( 'The selected file URL is "' + fileUrl + '"' ) ;
}

	</script>
</head>
<body>
	<h1>
		CKFinder - Sample - Standalone
	</h1>
	<hr />
	<p>
		CKFinder may be used in standalone mode inside any page, to create a repository
		manager with easy. You may define a custom JavaScript function to be called when
		an image is selected (double-clicked).</p>
	<p style="padding-left: 30px; padding-right: 30px;">
<?php

require_once '../../ckfinder.php' ;

// You can use the "CKFinder" class to render CKFinder in a page:
$finder = new CKFinder() ;
$finder->BasePath = '../../' ;	// The path for the installation of CKFinder (default = "/ckfinder/").
$finder->SelectFunction = 'ShowFileInfo' ;
$finder->Create() ;

// It can also be done in a single line, calling the "static"
// Create( basePath, width, height, selectFunction ) function:
// CKFinder::CreateStatic( '../../', null, null, 'ShowFileInfo' ) ;

?>
	</p>
</body>
</html>
