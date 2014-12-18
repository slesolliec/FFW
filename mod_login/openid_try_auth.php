<?php

session_start();

if (empty($_GET['openid_identifier']))
	stop("Expected an OpenID URL.");

$openid = $_GET['openid_identifier'];
$consumer = getConsumer();

// Begin the OpenID authentication process.
$auth_request = $consumer->begin($openid);

// No auth request means we can't begin OpenID.
if (!$auth_request)
	stop("Erreur d'authentification : vous n'avez pas entré une openID valide.","Error: openID not valid");

$sreg_request = Auth_OpenID_SRegRequest::build(
		array(),			// Required
		array('nickname','fullname', 'email')	// Optional
	);

if ($sreg_request)
	$auth_request->addExtension($sreg_request);

$policy_uris = $_GET['policies'];

$pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
if ($pape_request)
	$auth_request->addExtension($pape_request);


// Redirect the user to the OpenID server for authentication.
// Store the token for this authentication so we can verify the
// response.

// For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
// form to send a POST request to the server.
if ($auth_request->shouldSendRedirect()) {
	$redirect_url = $auth_request->redirectURL(getTrustRoot(),getReturnTo());

	// If the redirect URL can't be built, display an error message.
	if (Auth_OpenID::isFailure($redirect_url)) {
		stop("Impossible de rediriger vers le serveur : " . $redirect_url->message,"openID login error");
	} else {
		// Send redirect.
		header("Location: ".$redirect_url);
	}
} else {
	// Generate form markup and render it.
	$form_id = 'openid_message';
	$form_html = $auth_request->formMarkup(getTrustRoot(), getReturnTo(), false, array('id' => $form_id));

	// Display an error if the form markup couldn't be generated;
	// otherwise, render the HTML.
	if (Auth_OpenID::isFailure($form_html)) {
		stop("Impossible de rediriger vers le serveur : " . $form_html->message,"openID login error");
	} else {
		$page_contents = array(
			"<html><head><title>",
			"OpenID transaction in progress",
			"</title></head>",
			"<body onload='document.getElementById(\"".$form_id."\").submit()'>",
			$form_html,
			"</body></html>");

		print implode("\n", $page_contents);
	}
}
