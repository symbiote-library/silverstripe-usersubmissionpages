<?php

class UserSubmissionPage extends Page {
	private static $extensions = array(
		'UserSubmissionExtension'
	);

	private static $can_be_root = false;

	private static $allowed_children = 'none';

	private static $default_parent = 'UserSubmissionHolder';
}

class UserSubmissionPage_Controller extends Page_Controller {
}