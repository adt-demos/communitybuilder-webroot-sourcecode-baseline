<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2023 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Language\CBTxt;
use CBLib\Registry\GetterInterface;
use CBLib\Input\Get;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Router\SiteRouter;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\Version;
use Joomla\Registry\Registry;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

jimport( 'joomla.plugin.plugin' );

class plgSystemCommunityBuilder extends CMSPlugin
{
	/** @var array list of URLs that are allowed access when verifying privacy consent */
	private $termsUrls	=	array();

	/**
	 * @param  Form          $form  Joomla XML form
	 * @param  array|object  $data  Form data (j2.5 is array, j3.0 is object, converted to array for easy usage between both)
	 */
	public function onContentPrepareForm( $form, $data )
	{
		if ( ( $form instanceof Form ) && ( $form->getName() == 'com_menus.item' ) ) {
			$data				=	(array) $data;

			if ( isset( $data['request']['option'] ) && ( $data['request']['option'] == 'com_comprofiler' ) && isset( $data['request']['view'] ) && ( $data['request']['view'] == 'pluginclass' ) ) {
				$element		=	( isset( $data['request']['plugin'] ) ? $data['request']['plugin'] : 'cb.core' );

				if ( $element ) {
					$db			=	Factory::getDBO();

					$query		=	'SELECT ' . $db->quoteName( 'type' )
								.	', ' . $db->quoteName( 'folder' )
								.	"\n FROM " . $db->quoteName( '#__comprofiler_plugin' )
								.	"\n WHERE " . $db->quoteName( 'element' ) . " = " . $db->quote( $element );
					$db->setQuery( $query );
					$plugin		=	$db->loadAssoc();

					if ( $plugin ) {
						$path	=	JPATH_ROOT . '/components/com_comprofiler/plugin/' . $plugin['type'] . '/' . $plugin['folder'] . '/xml';

						if ( file_exists( $path ) ) {
							Form::addFormPath( $path );

							$form->loadFile( 'metadata', false );
						}
					}
				}
			}
		}
	}

	public function onAfterRoute()
	{
		$app								=	Factory::getApplication();

		// Check if we need to redirect from Joomla com_user endpoints to CB:
		if ( $this->isClientSite() && ( $this->params->get( 'redirect_urls', 1 ) || $this->params->get( 'verify_consent', 0 ) ) && $this->isRerouteSafe() ) {
			$exclude						=	[ 'methods', 'method', 'captive', 'callback' ];
			$skip							=	false;

			// Have to verify both task and view as Joomla is using them interchangeably on MFA views
			if ( in_array( ( explode( '.', $app->input->get( 'task', '' ), 2 )[0] ?? '' ), $exclude, true ) || in_array( ( explode( '.', $app->input->get( 'view', '' ), 2 )[0] ?? '' ), $exclude, true ) ) {
				$skip						=	true;
			}

			$option							=	$app->input->get( 'option' );
			$view							=	$app->input->get( 'task' );

			if ( ! $view ) {
				$view						=	$app->input->get( 'view' );
			}

			$redirect						=	null;

			if ( ( $option == 'com_users' ) && $this->params->get( 'redirect_urls', 1 ) && ( ! $skip ) ) {
				$option						=	'com_comprofiler';
				$userId						=	null;

				switch ( $view ) {
					case 'profile.edit':
					case 'profile.apply':
					case 'profile.save':
					case 'profile':
						if ( ( $view == 'profile.edit' ) || ( $app->input->get( 'layout' ) == 'edit' ) ) {
							$view			=	'userdetails';
							$userId			=	(int) $app->input->get( 'user_id' );
						} else {
							$view			=	'userprofile';
						}
						break;
					case 'registration.register':
					case 'registration':
						$view				=	'registers';
						break;
					case 'reset':
					case 'reset.complete':
					case 'reset.confirm':
					case 'reset.request':
					case 'remind':
					case 'remind.remind':
						$view				=	'lostpassword';
						break;
					case 'user.logout':
					case 'user.menulogout':
					case 'logout':
						$view				=	'logout';
						break;
					case 'user.login':
					case 'login':
						$view							=	'login';

						if ( $_POST && Session::checkToken() ) {
							$cbSpoofField				=	Session::getFormToken();

							// Change the request variables so it points to CBs login page before rendering in CB:
							$app->input->set( 'option', 'com_comprofiler' );
							$app->input->set( 'task', 'login' );
							$app->input->set( 'view', 'login' );
							$app->input->set( $cbSpoofField, 1 );

							$_REQUEST['option']			=	'com_comprofiler';
							$_REQUEST['task']			=	'login';
							$_REQUEST['view']			=	'login';
							$_REQUEST[$cbSpoofField]	=	1;

							$_GET['option']				=	'com_comprofiler';
							$_GET['task']				=	'login';
							$_GET['view']				=	'login';

							$_POST[$cbSpoofField]		=	1;

							if ( isset( $_POST['return'] ) ) {
								// Make the return redirect compatible with CB:
								$_POST['return']		=	'B:' . $_POST['return'];
							}

							try {
								ComponentHelper::renderComponent( 'com_comprofiler' );
							} catch( Exception $e ) {
								// Just silently fail and do a normal redirect if CB didn't render
							}
						}
						break;
					default:
						$view				=	'login';
						break;
				}

				if ( ! $skip ) {
					$Itemid					=	$this->getItemid( $view . ( $userId ? '&user=' . $userId : null ) );
					$redirect				=	'index.php?option=com_comprofiler' . ( $view ? '&view=' . $view : null ) . ( $userId ? '&user=' . $userId : null ) . ( $Itemid ? '&Itemid=' . $Itemid : null );

					if ( in_array( $view, array( 'login', 'logout' ) ) ) {
						$return				=	$app->input->get( 'return', '', 'BASE64' );

						if ( $return ) {
							$redirect		.=	'&return=' . $return;
						}
					}
				}
			}

			// Lets check if the user has any incomplete required terms and conditions fields:
			$format							=	$app->input->get( 'format' );

			if ( $this->params->get( 'verify_consent', 0 )
				 && ( $option != 'com_privacy' ) // Allow access to Joomla privacy component
				 && ( ! in_array( $format, array( 'raw', 'json' ) ) ) // Never block ajax endpoints
				 && ( ( $option != 'com_comprofiler' ) || ( ! in_array( $view, array( 'userdetails', 'saveuseredit', 'logout' ) ) ) )
				 && ( ! $this->getCBUserConsented() )
			) {
				$Itemid						=	$this->getItemid( 'userdetails' );
				$redirect					=	'index.php?option=com_comprofiler&view=userdetails' . ( $Itemid ? '&Itemid=' . $Itemid : null );

				// If a plugin is being loaded lets check if it's overriding the redirect url or allowing access:
				$plugin						=	$app->input->get( 'plugin' );

				if ( ( $option == 'com_comprofiler' ) && ( $view == 'pluginclass' ) && $plugin ) {
					global $_PLUGINS;

					// Lets only bother loading the plugin being accessed:
					if ( $_PLUGINS->loadPluginGroup( 'user', $plugin ) ) {
						$integrations		=	$_PLUGINS->trigger( 'onVerifyConsent', array( $plugin ) );

						// Check if any trigger results are allowing access (return true to allow access otherwise don't allow access)
						foreach ( $integrations as $integration ) {
							if ( ! is_bool( $integration ) ) {
								continue;
							}

							if ( $integration ) {
								$redirect	=	null;
							}
						}
					}
				}

				// See if there are any terms and condition URLs or custom URLs as we need to ensure the user can access them:
				$allowedUrls				=	$this->termsUrls;
				$consentUrls				=	$this->params->get( 'verify_consent_urls' );

				if ( $consentUrls ) {
					if ( is_string( $consentUrls ) ) {
						$consentUrls		=	json_decode( $consentUrls, true );
						$consentUrls		=	( $consentUrls['url'] ?? [] );
					} else {
						$consentUrls		=	array_map( static function( $url ) {
													return ( $url->url ?? '' );
												}, (array) $consentUrls );
					}

					foreach ( $consentUrls as $consentUrl ) {
						if ( ( ! $consentUrl ) || in_array( $consentUrl, $allowedUrls ) || ( ! Uri::isInternal( $consentUrl ) ) ) {
							continue;
						}

						$allowedUrls[]		=	$consentUrl;
					}
				}

				if ( $allowedUrls ) {
					$currentUrl				=	Uri::getInstance()->toString();

					foreach ( $allowedUrls as $allowedUrl ) {
						if ( ( stripos( $currentUrl, $allowedUrl ) !== false ) || ( stripos( $currentUrl, Route::_( $allowedUrl, false ) ) !== false ) ) {
							// The URL matches before or after routing parsing so lets just allow access:
							$redirect		=	null;
							break;
						}
					}
				}

				// Consent is required lets see if we need to output a message:
				if ( $redirect ) {
												// CBTxt::T( 'Your consent is required.' )
					$consentMsg				=	$this->params->get( 'verify_consent_msg', 'Your consent is required.' );
					$consentMsgType			=	$this->params->get( 'verify_consent_msg_type', 'error' );

					if ( $consentMsg ) {
						$app->enqueueMessage( CBTxt::T( $consentMsg ), $consentMsgType );
					}
				}
			}

			if ( $redirect ) {
				$app->redirect( Route::_( $redirect, false ) );
			}
		}

		// Joomla doesn't populate GET so we'll do it below based off the menu item and routing variables:
		if ( $this->isClientSite() && ( $app->input->get( 'option' ) == 'com_comprofiler' ) ) {
			// Map the current route variables to GET if missing:
			if ( Version::MAJOR_VERSION < 5 ) {
				$route						=	$app->getRouter()->getVars();
			} else {
				$route						=	Factory::getContainer()->get( SiteRouter::class )->getVars();
			}

			if ( $route && ( isset( $route['option'] ) ) && ( $route['option'] == 'com_comprofiler' ) ) {
				foreach( $route as $k => $v ) {
					if ( ! isset( $_GET[$k] ) ) {
						$_GET[$k]			=	$v;
					}

					if ( ! isset( $_REQUEST[$k] ) ) {
						$_REQUEST[$k]		=	$v;
					}
				}
			}

			// Map the current menu item variables to GET if missing:
			$menu							=	$app->getMenu()->getActive();

			if ( $menu && isset( $menu->query ) && ( isset( $menu->query['option'] ) ) && ( $menu->query['option'] == 'com_comprofiler' ) ) {
				foreach( $menu->query as $k => $v ) {
					if ( ! isset( $_GET[$k] ) ) {
						$_GET[$k]			=	$v;
					}

					if ( ! isset( $_REQUEST[$k] ) ) {
						$_REQUEST[$k]		=	$v;
					}
				}
			}

			// Adjust the reset password overrides so CB profile edit can be used to reset the password:
			if ( $this->params->get( 'rewrite_urls', 1 ) ) {
				$view						=	$app->input->get( 'view' );

				$app->set( 'site_reset_password_override', 1 );
				$app->set( 'site_reset_password_option', 'com_comprofiler' );
				$app->set( 'site_reset_password_view', 'userdetails' );

				if ( in_array( $view, array( 'saveuseredit', 'logout', 'fieldclass' ) ) || in_array( $app->input->get( 'format' ), array( 'raw', 'json' ), true ) ) {
					$app->set( 'site_reset_password_view', $view );
				}

				$app->set( 'site_reset_password_layout', '' );
				$app->set( 'site_reset_password_tasks', 'com_comprofiler/userdetails,com_comprofiler/saveuseredit,com_comprofiler/logout,com_comprofiler/fieldclass' );
			}
		}
	}

	public function onAfterInitialise()
	{
		$app				=	Factory::getApplication();

		if ( $this->isClientSite() ) {
			if ( $app->input->get( 'option' ) == 'com_comprofiler' ) {
				// CB is dynamic and can't be page cached; so remove the cache:
				if ( Factory::getConfig()->get( 'caching' ) ) {
					Factory::getCache( 'page' )->remove( Uri::getInstance()->toString() );
				}
			}

			if ( $this->params->get( 'rewrite_urls', 1 )  && $this->isRerouteSafe() ) {
				if ( Version::MAJOR_VERSION < 5 ) {
					$router	=	$app->getRouter();
				} else {
					$router	=	Factory::getContainer()->get( SiteRouter::class );
				}

				$router->attachBuildRule( array( $this, 'buildRule' ) );
			}
		}
	}

	/**
	 * Method is called before user data is deleted from the database
	 *
	 * @param   array    $user     Holds the user data
	 *
	 * @return  void
	 */
	public function onUserBeforeDelete( $user )
	{
		if ( ! $this->loadCB() ) {
			// CB failed to load so just skip CB user delete:
			return;
		}

		// Prefetch and cache the user since we need a completed user object for proper delete in CB:
		CBuser::getUserDataInstance( (int) $user['id'] );
	}

	/**
	 * Method is called after user data is deleted from the database
	 *
	 * @param   array    $user     Holds the user data
	 * @param   boolean  $success  True if user was successfully stored in the database
	 * @param   string   $msg      Message
	 *
	 * @return  void
	 */
	public function onUserAfterDelete( $user, $success, $msg )
	{
		if ( ! $success ) {
			// Skip if user failed to delete:
			return;
		}

		if ( ! $this->loadCB() ) {
			// CB failed to load so just skip CB user delete:
			return;
		}

		// Load the prefetched user object so we still have profile data to pass to delete events:
		$cbUser		=	CBuser::getUserDataInstance( (int) $user['id'] );

		if ( ! $cbUser->get( 'id', 0, GetterInterface::INT ) ) {
			// User doesn't exist so skip:
			return;
		}

		// Be sure to only delete CB:
		$cbUser->delete( null, true );
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function onComUsersCaptiveValidateSuccess()
	{
		// Clear CBs MFA flag since Joomla's MFA checks happen after routing event
		if ( ! method_exists( Factory::getApplication(), 'isMultiFactorAuthenticationPage' ) ) {
			return;
		}

		Factory::getApplication()->getSession()->remove( 'com_comprofiler.ismfa' );
	}

	/**
	 * Processes an export request for CB user data
	 *
	 * @param   PrivacyTableRequest $request The request record being processed
	 * @param   User                $user    The user account associated with this request if available
	 *
	 * @return  PrivacyExportDomain[]
	 *
	 * @since   3.9.0
	 */
	public function onPrivacyExportRequest( PrivacyTableRequest $request, User $user = null )
	{
		global $_PLUGINS;

		if ( ! $user ) {
			return array();
		}

		if ( ! $this->loadCB() ) {
			return array();
		}

		$cbUser						=	CBuser::getUserDataInstance( $user->id );

		if ( ! $cbUser->get( 'id', 0, GetterInterface::INT ) ) {
			return array();
		}

		$rowData					=	array();

		foreach ( get_object_vars( $cbUser ) as $k => $v ) {
			if ( ( ! $k ) || ( $k[0] == '_' ) ) {
				// Skip empty keys and private variables:
				continue;
			}

			$rowData[$k]			=	$v;
		}

		$integrations				=	$_PLUGINS->trigger( 'onExportUserTable', array( $cbUser, &$rowData, 'xml' ) );

		if ( ! $rowData ) {
			return array();
		}

		$domains					=	array();

		$domain						=	new PrivacyExportDomain();
		$domain->name				=	'communitybuilder';
		$domain->description		=	'communitybuilder_user_data';

		$item						=	new PrivacyExportItem();
		$item->id					=	$cbUser->get( 'id', 0, GetterInterface::INT );

		foreach ( $rowData as $dataName => $dataValue ) {
			$field					=	new PrivacyExportField();
			$field->name			=	$dataName;
			$field->value			=	$this->getExportSafeValue( $dataValue );

			$item->addField( $field );
		}

		$domain->addItem( $item );

		$domains[]					=	$domain;

		// Parse trigger results into custom domains, but do so from a generic format of array( 'domain' => array( 'item_id' => array( 'field_name' => 'field_value' ) ) )
		foreach ( $integrations as $customDomains ) {
			if ( ( ! $customDomains ) || ( ! is_array( $customDomains ) ) ) {
				continue;
			}

			foreach ( $customDomains as $customDomain => $customItems ) {
				$plgDomain							=	new PrivacyExportDomain();
				$plgDomain->name					=	$customDomain;

				if ( is_array( $customItems ) ) {
					foreach ( $customItems as $customItem => $customFields ) {
						$plgItem					=	new PrivacyExportItem();
						$plgItem->id				=	$customItem;

						if ( is_array( $customFields ) ) {
							foreach ( $customFields as $customName => $customValue ) {
								$plgField			=	new PrivacyExportField();
								$plgField->name		=	$customName;
								$plgField->value	=	$this->getExportSafeValue( $customValue );

								$plgItem->addField( $plgField );
							}
						}

						$plgDomain->addItem( $plgItem );
					}
				}

				$domains[]							=	$plgDomain;
			}
		}

		return $domains;
	}

	/**
	 * @param plgSystemCommunityBuilder $router
	 * @param Uri                       $uri
	 */
	public function buildRule( &$router, &$uri )
	{
		if ( $this->isClientSite() && ( $uri->getVar( 'option' ) == 'com_users' ) && $this->isRerouteSafe() ) {
			$exclude						=	[ 'methods', 'method', 'captive', 'callback' ];

			// Have to verify both task and view as Joomla is using them interchangeably on MFA views
			if ( in_array( ( explode( '.', $uri->getVar( 'task', '' ), 2 )[0] ?? '' ), $exclude, true ) || in_array( ( explode( '.', $uri->getVar( 'view', '' ), 2 )[0] ?? '' ), $exclude, true ) ) {
				return;
			}

			$view							=	$uri->getVar( 'task' );

			if ( ! $view ) {
				$view						=	$uri->getVar( 'view' );
			}

			switch ( $view ) {
				case 'profile.edit':
				case 'profile.apply':
				case 'profile.save':
				case 'profile':
					if ( ( $view == 'profile.edit' ) || ( $uri->getVar( 'layout' ) == 'edit' ) ) {
						$userId				=	(int) $uri->getVar( 'user_id' );
						$task				=	'userdetails';

						if ( $userId ) {
							$task			.=	'&user=' . $userId;
						}
					} else {
						$task				=	'userprofile';
					}
					break;
				case 'registration.register':
				case 'registration':
					$task					=	'registers';
					break;
				case 'reset':
				case 'reset.complete':
				case 'reset.confirm':
				case 'reset.request':
				case 'remind':
				case 'remind.remind':
					$task					=	'lostpassword';
					break;
				case 'user.logout':
				case 'user.menulogout':
				case 'logout':
					$task					=	'logout';
					break;
				case 'user.login':
				case 'login':
				default:
					$task					=	'login';
					break;
			}

			$uri->setVar( 'option', 'com_comprofiler' );
			$uri->delVar( 'task' );
			$uri->delVar( 'view' );
			$uri->delVar( 'layout' );

			if ( $task ) {
				$uri->setVar( 'view', $task );
			}

			$Itemid							=	$this->getItemid( $task );

			$uri->delVar( 'Itemid' );

			if ( $Itemid ) {
				$uri->setVar( 'Itemid', $Itemid );
			}
		}
	}

	/**
	 * Checks if on a MFA is required or not so we can avoid doing redirects and rewrites
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function isMFARequired(): bool
	{
		if ( ! method_exists( Factory::getApplication(), 'isMultiFactorAuthenticationPage' ) ) {
			return false;
		}

		if ( Factory::getApplication()->isMultiFactorAuthenticationPage() ) {
			// Landed on an MFA page so lets keep track of this MFA session for CB specifically since MFA checks happen after CB checks
			Factory::getApplication()->getSession()->set( 'com_comprofiler.ismfa', true );

			return true;
		}

		if ( Factory::getApplication()->getSession()->get( 'com_comprofiler.ismfa', false ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns the task specific Itemid from Joomla CB menu items
	 *
	 * @param string $task
	 * @return null|int
	 */
	private function getItemid( $task )
	{
		static $items			=	null;

		if ( ! isset( $items ) ) {
			$items				=	Factory::getApplication()->getMenu()->getItems( 'component', 'com_comprofiler' );
		}

		$Itemid					=	null;

		if ( $task ) {
			// See if a menu item for this specific task exists:
			if ( $items ) foreach ( $items as $item ) {
				if ( ( isset( $item->query['view'] ) && ( $item->query['view'] == $task ) ) || ( isset( $item->query['task'] ) && ( $item->query['task'] == $task ) ) ) {
					$Itemid		=	$item->id;
				}
			}
		}

		if ( ( ! $Itemid ) && ( ! in_array( $task, array( 'login', 'logout', 'registers', 'lostpassword' ) ) ) ) {
			// Check if generic profile menu item exists:
			if ( $items ) foreach ( $items as $item ) {
				if ( ( ( ! isset( $item->query['view'] ) ) && ( ! isset( $item->query['task'] ) ) )
					 || ( ( isset( $item->query['view'] ) && ( $item->query['view'] == 'userprofile' ) ) || ( isset( $item->query['task'] ) && ( $item->query['task'] == 'userprofile' ) ) )
				) {
					$Itemid		=	$item->id;
				}
			}

			if ( ! $Itemid ) {
				// As last resort fallback to userlist menu item:
				if ( $items ) foreach ( $items as $item ) {
					if ( ( isset( $item->query['view'] ) && ( $item->query['view'] == 'userslist' ) ) || ( isset( $item->query['task'] ) && ( $item->query['task'] == 'userslist' ) ) ) {
						$Itemid	=	$item->id;
					}
				}
			}
		}

		return $Itemid;
	}

	/**
	 * Checks if the viewing user has consented to the privacy policy
	 * This is needed as we don't want to redirect away from profile edit since they will need to consent there
	 *
	 * @return bool
	 */
	private function getJoomlaUserConsented()
	{
		if ( ! PluginHelper::isEnabled( 'system', 'privacyconsent' ) ) {
			// Privacy consent plugin is not in use so go ahead and return true to block profile access:
			return true;
		}

		$userId				=	Factory::getUser()->get( 'id', 0 );

		if ( ! $userId ) {
			// Public users can't consent to return true so we can block profile access:
			return true;
		}

		static $cache		=	array();

		if ( ! isset( $cache[$userId] ) ) {
			$db				=	Factory::getDbo();
			$query			=	$db->getQuery(true);
			$query->select('COUNT(*)')
				->from('#__privacy_consents')
				->where('user_id = ' . (int) $userId)
				->where('subject = ' . $db->quote('PLG_SYSTEM_PRIVACYCONSENT_SUBJECT'))
				->where('state = 1');
			$db->setQuery($query);

			$cache[$userId]	=	( (int) $db->loadResult() > 0 );
		}

		return $cache[$userId];
	}

	/**
	 * Checks if the viewing user has accepted all required terms and conditions fields
	 *
	 * @return bool
	 */
	private function getCBUserConsented()
	{
		if ( ! $this->params->get( 'verify_consent', 0 ) ) {
			// Checking for consent has been turned off so lets ignore this check:
			return true;
		}

		$userId								=	(int) Factory::getUser()->get( 'id', 0 );

		if ( ! $userId ) {
			// Public users can't consent so just ignore this check:
			return true;
		}

		static $fields						=	null;

		if ( $fields === null ) {
			$db								=	Factory::getDBO();

			$query							=	'SELECT *'
											.	"\n FROM " . $db->quoteName( '#__comprofiler_fields' )
											.	"\n WHERE " . $db->quoteName( 'type' ) . " = " . $db->quote( 'terms' )
											.	"\n AND " . $db->quoteName( 'required' ) . " = 1"
											.	"\n AND " . $db->quoteName( 'published' ) . " = 1"
											.	"\n AND " . $db->quoteName( 'edit' ) . " > 0";
			$db->setQuery( $query );
			$fields							=	$db->loadObjectList( 'fieldid' );

			foreach ( $fields as &$field ) {
				$field->params				=	new Registry( $field->params );
			}
		}

		if ( ! $fields ) {
			// There's no terms fields to check so lets just skip this check:
			return true;
		}

		if ( ! $this->loadCB() ) {
			// CB failed to load so skip this check:
			return true;
		}

		static $cache						=	array();

		if ( ! isset( $cache[$userId] ) ) {
			$cbUser							=	CBuser::getInstance( $userId, false );
			$consented						=	true;

			foreach ( $fields as $field ) {
				// We'll use getField API so all access checks can be respected to make sure the user can even access the field:
				$fieldValue					=	$cbUser->getField( $field->name, null, 'php', 'none', 'edit' );

				if ( is_array( $fieldValue ) ) {
					$fieldValue				=	array_shift( $fieldValue );

					if ( is_array( $fieldValue ) ) {
						$fieldValue			=	implode( '|*|', $fieldValue );
					}
				}

				if ( $fieldValue === null ) {
					// Can't reach the field so lets skip:
					continue;
				}

				/** @var Registry $fieldParams */
				$fieldParams				=	$field->params;
				$termsOutput				=	$fieldParams->get( 'terms_output', 'url' );
				$termsURL					=	$cbUser->replaceUserVars( $field->params->get( 'terms_url', null ) );

				if ( ( $termsOutput == 'url' ) && $termsURL && Uri::isInternal( $termsURL ) && ( ! in_array( $termsURL, $this->termsUrls ) ) ) {
					// If this is a URL based terms display then we need to be sure access to the URL is allowed (we only need to do this for internal URLs):
					$this->termsUrls[]		=	$termsURL;
				}

				if ( ! $fieldValue ) {
					// User didn't consent or consent has expired:
					$consented				=	false;
				}
			}

			$cache[$userId]					=	$consented;
		}

		return $cache[$userId];
	}

	/**
	 * Checks if the viewing user is a guest
	 *
	 * @return bool
	 */
	private function getUserIsGuest()
	{
		static $cache	=	null;

		if ( $cache === null ) {
			$cache		=	Factory::getUser()->get( 'guest' );
		}

		return $cache;
	}

	/**
	 * Checks online status of the site and if the user is a guest or if privacy consent is required
	 * Used to determine if URL rewriting and redirecting is safe to perform
	 *
	 * @return bool
	 */
	private function isRerouteSafe()
	{
		if ( $this->isMFARequired() ) {
			// MFA authentication is required so it's not safe to try and redirect away:
			return false;
		}

		if ( ! $this->getJoomlaUserConsented() ) {
			// Privacy consent is required so it's not safe to try and redirect away:
			return false;
		}

		return ( ( Factory::getConfig()->get( 'offline' ) == 1 ) && $this->getUserIsGuest() ? false : true );
	}

	/**
	 * Loads CB API
	 * Note this should not be used too early (e.g. during initialise)
	 *
	 * @return bool|null
	 */
	private function loadCB()
	{
		static $CB_loaded	=	null;

		if ( $CB_loaded === null ) {
			if ( ( ! file_exists( JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php' ) ) || ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) ) {
				$CB_loaded	=	false;

				return false;
			}

			include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );

			cbimport( 'cb.html' );
			cbimport( 'language.front' );

			$CB_loaded		=	true;
		}

		return $CB_loaded;
	}

	/**
	 * Reformats supplied value to be safe for usage in Joomla user export
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function getExportSafeValue( $value )
	{
		if ( is_array( $value ) || is_object( $value ) ) {
			$value			=	json_encode( $value );
		} elseif ( is_object( $value ) ) {
			if ( method_exists( $value, 'asArray' ) ) {
				$value		=	$value->asArray();
			} else {
				$value		=	get_object_vars( $value );
			}

			if ( $value ) {
				$value		=	json_encode( $value );
			} else {
				$value		=	null;
			}
		}

		if ( is_bool( $value ) ) {
			$value			=	( $value ? 'true' : 'false' );
		} elseif ( $value && ( ! is_numeric( $value ) ) ) {
			if ( ( Get::clean( $value, GetterInterface::STRING ) != $value ) // Check if contains any HTML
				 || ( strpos( $value, '<' ) !== false ) // Check if contains a lt
				 || ( strpos( $value, '&' ) !== false ) // Check if contains a amp
				 || ( strpos( $value, ']]>' ) !== false ) // Check if it contains CDATA closing token
			) {
				// Contains characters that could malform the XML so enclose in CDATA:
				$value		=	'<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $value ) . ']]>';
			}
		}

		return $value;
	}

	private function isClientSite( )
	{
		$app = Factory::getApplication();

		// Joomla >= 3.7.0:
		if ( is_callable( array( $app, 'isClient' ) ) ) {
			return $app->isClient( 'site' );
		}

		// Joomla >= 3.2 and < 4.0:
		return ( $app->getClientId() == 0 );
	}
}
