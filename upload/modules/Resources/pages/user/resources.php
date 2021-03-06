<?php
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-pr9
 *
 *  License: MIT
 *
 *  UserCP resources page
 */

// Must be logged in
if(!$user->isLoggedIn()){
	Redirect::to(URL::build('/'));
	die();
}

// Always define page name for navbar
define('PAGE', 'resources_settings');
$page_title = $language->get('user', 'user_cp');
require_once(ROOT_PATH . '/core/templates/frontend_init.php');

$timeago = new Timeago(TIMEZONE);

if(Input::exists()){
	if(Token::check(Input::get('token'))){
		$paypal_address = null;

		if(isset($_POST['paypal_email'])){
			if((strlen(str_replace(' ', '', $_POST['paypal_email'])) > 0 && strlen(str_replace(' ', '', $_POST['paypal_email'])) < 4) || strlen($_POST['paypal_email']) > 64)
				$error = $resource_language->get('resources', 'invalid_email_address');
			else
				$paypal_address = Output::getClean($_POST['paypal_email']);
		}

		if(!isset($error)){
			$user_id = $queries->getWhere('resources_users_premium_details', array('user_id', '=', $user->data()->id));
			if(count($user_id)){
				$user_id = $user_id[0]->id;

				$queries->update('resources_users_premium_details', $user_id, array(
					'paypal_email' => $paypal_address
				));

			} else {
				$queries->create('resources_users_premium_details', array(
					'user_id' => $user->data()->id,
					'paypal_email' => $paypal_address
				));

			}

			$success = $resource_language->get('resources', 'settings_updated_successfully');
		}

	} else
		$error = $language->get('general', 'invalid_token');
}

$paypal_address_query = $queries->getWhere('resources_users_premium_details', array('user_id', '=', $user->data()->id));
if(count($paypal_address_query))
	$paypal_address = Output::getClean($paypal_address_query[0]->paypal_email);
else
	$paypal_address = '';

if(isset($success))
	$smarty->assign('SUCCESS', $success);

if(isset($error))
	$smarty->assign('ERROR', $error);

$purchased_resources = DB::getInstance()->query('SELECT nl2_resources.id as id, nl2_resources.name as name, nl2_resources.creator_id as author, nl2_resources.latest_version as version, nl2_resources.updated as updated FROM nl2_resources_payments LEFT JOIN nl2_resources ON nl2_resources.id = nl2_resources_payments.resource_id WHERE nl2_resources_payments.status = 1 AND nl2_resources_payments.user_id = ?', array($user->data()->id))->results();

$template_array = array();
if(count($purchased_resources)){
	foreach($purchased_resources as $resource){
        $author = new User($resource->author);
        
		$template_array[] = array(
			'name' => Output::getClean($resource->name),
			'author_username' => $author->getDisplayname(true),
			'author_nickname' => $author->getDisplayname(),
			'author_avatar' => $author->getAvatar('', 256),
			'author_style' => $author->getGroupClass(),
			'author_link' => $author->getProfileURL(),
			'latest_version' => Output::getClean($resource->version),
			'updated' => $timeago->inWords(date('d M Y, H:i', $resource->updated), $language->getTimeLanguage()),
			'updated_full' => date('d M Y, H:i', $resource->updated),
			'link' => URL::build('/resources/resource/' . Output::getClean($resource->id) . '-' . Util::stringToURL($resource->name))
		);
	}
}

// Language values
$smarty->assign(array(
	'USER_CP' => $language->get('user', 'user_cp'),
	'RESOURCES' => $resource_language->get('resources', 'resources'),
	'MY_RESOURCES_LINK' => URL::build('/resources/author/' . Output::getClean($user->data()->id  . '-' . $user->data()->nickname)),
	'MY_RESOURCES' => $resource_language->get('resources', 'my_resources'),
	'PURCHASED_RESOURCES' => $resource_language->get('resources', 'purchased_resources'),
	'PURCHASED_RESOURCES_VALUE' => $template_array,
	'NO_PURCHASED_RESOURCES' => $resource_language->get('resources', 'no_purchased_resources'),
	'PAYPAL_EMAIL_ADDRESS' => $resource_language->get('resources', 'paypal_email_address'),
	'PAYPAL_EMAIL_ADDRESS_INFO' => $resource_language->get('resources', 'paypal_email_address_info'),
	'PAYPAL_EMAIL_ADDRESS_VALUE' => $paypal_address,
	'INFO' => $language->get('general', 'info'),
	'TOKEN' => Token::get(),
	'SUBMIT' => $language->get('general', 'submit')
));

// Load modules + template
Module::loadPage($user, $pages, $cache, $smarty, array($navigation, $cc_nav, $mod_nav), $widgets);

require(ROOT_PATH . '/core/templates/cc_navbar.php');

$page_load = microtime(true) - $start;
define('PAGE_LOAD_TIME', str_replace('{x}', round($page_load, 3), $language->get('general', 'page_loaded_in')));

$template->onPageLoad();

require(ROOT_PATH . '/core/templates/navbar.php');
require(ROOT_PATH . '/core/templates/footer.php');

// Display template
$template->displayTemplate('resources/user/resources.tpl', $smarty);