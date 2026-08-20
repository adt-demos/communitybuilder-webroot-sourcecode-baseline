<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2025 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Core\CBLib;
use CBLib\Application\Application;
use CBLib\Registry\GetterInterface;
use CBLib\Language\CBTxt;
use Joomla\CMS\Helper\ModuleHelper;
use CB\Database\Table\PluginTable;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

global $_CB_database, $_CB_framework, $_PLUGINS, $ueConfig;

if ( ( ! file_exists( JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php' ) ) || ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) ) {
	echo 'CB not installed!';
	return;
}

try {
/** @noinspection PhpIncludeInspection */
include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );

if ( ! defined( 'CBLIB' ) ) {
	echo 'CB version is not 2.0.0+';
	return;
}

cbimport( 'cb.html' );

outputCbTemplate();

initToolTip();

require_once( dirname( __FILE__ ) . '/helper.php' );

$mode								=	(int) $params->get( 'mode', 1 );

if ( ( $module->position === 'menu' ) && ( $mode !== 1 ) ) {
	// Do not allow the other modes to render in the menu position or it will overflow the header making it impossible to navigate!
	echo 'CB Admin Module has the wrong mode selected for menu position';
	return;
}

$cbUser								=	CBuser::getMyInstance();
$user								=	$cbUser->getUserData();
$disabled							=	( Application::Input()->get( 'hidemainmenu', 0, GetterInterface::INT ) ? true : false );
$feedEntries						=	(int) $params->get( 'feed_entries', 5 );
$feedDuration						=	(int) $params->get( 'feed_duration', 12 );
$modalDisplay						=	(int) $params->get( 'modal_display', 1 );

$_CB_framework->document->addHeadStyleSheet( $_CB_framework->getCfg( 'live_site' ) . '/administrator/modules/mod_cbadmin/mod_cbadmin.css' );

if ( in_array( $mode, array( 3, 4 ) ) ) {
	static $JS1_loaded				=	0;

	if ( ! $JS1_loaded++ ) {
		$js							=	"$( '.cbFeedShowMore,.cbFeedShowMoreLink' ).click( function() {"
									.		"$( this ).nextUntil( '.cbFeedShowMore,.cbFeedShowMoreLink' ).removeClass( 'hidden' );"
									.		"$( this ).find( '~ .cbFeedShowMore:first,~ .cbFeedShowMoreLink:first' ).removeClass( 'hidden' );"
									.		"$( this ).remove();"
									.	"});"
									.	"$( '.cbFeed' ).each( function() {"
									.		"$( this ).find( '.cbFeedShowMore:first,.cbFeedShowMoreLink:first' ).removeClass( 'hidden' );"
									.	"});";

		$_CB_framework->outputCbJQuery( $js );
	}
}

switch ( $mode ) {
	case 6:
		if ( ! Application::MyUser()->isAuthorizedToPerformActionOnAsset( 'core.admin', 'com_comprofiler' ) ) {
			return;
		}

		if ( ! Application::Config()->getString( 'versionCheckInterval', '+12 HOURS' ) ) {
			return;
		}

		if ( ! Application::Config()->getBool( 'pluginVersionCheck', true ) ) {
			return;
		}

		$query						=	"SELECT *"
									.	" FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
									.	" WHERE " . $_CB_database->NameQuote( 'iscore' ) . " = 0"
									.	" AND " . $_CB_database->NameQuote( 'published' ) . " = 1";
		$_CB_database->setQuery( $query );
		foreach ( $_CB_database->loadObjectList( 'id', PluginTable::class, [ &$_CB_database ] ) as $plugin ) {
			$version				=	$_PLUGINS->getPluginVersion( $plugin, 2 );

			if ( $version[2] !== false ) {
				continue;
			}

			if ( Application::Config()->get( 'installFromWeb', 1, GetterInterface::INT ) ) {
				$updateUrl			=	$_CB_framework->backendViewUrl( 'installcbplugin', false, [ 'tab' => 'installfrom2' ] );
			} else {
				$updateUrl			=	$version[3];
			}

			if ( ! $updateUrl ) {
				// Nothing to link to (feed entry without an url and install-from-web disabled):
				continue;
			}

			$latestVersion			=	'<span class="cbUpdateVersion badge text-bg-danger">' . $version[1] . '</span>';
			$learnButton			=	'<a href="' . htmlspecialchars( 'https://www.joomlapolis.com/?pk_campaign=in-cb&pk_kwd=admin-module-update-button' ) . '" target="_blank" class="btn btn-outline-primary cbLearnButton">' . CBTxt::T( 'Learn More' ) . '</a>';
			$updateButton			=	'<a href="' . htmlspecialchars( $updateUrl ) . '" class="btn btn-primary cbUpdateButton">' . CBTxt::T( 'Update Now' ) . '</a>';

			$_CB_framework->enqueueMessage( CBTxt::T( 'PLUGIN_VERSION_IS_AVAILABLE_BUTTON', '[plugin] version [version] is available: [learn_button] [update_button]', [ '[plugin]' => htmlspecialchars( CBTxt::T( $plugin->getString( 'name', '' ) ) ), '[version]' => $latestVersion, '[learn_button]' => $learnButton, '[update_button]' => $updateButton ] ), 'alert' );
		}
		break;
	case 5:
		if ( ! Application::MyUser()->isAuthorizedToPerformActionOnAsset( 'core.admin', 'com_comprofiler' ) ) {
			return;
		}

		if ( Application::Config()->get( 'versionCheckInterval', '+12 HOURS', GetterInterface::STRING ) ) {
			$cbVersion				=	$_PLUGINS->getPluginVersion( 1, 2 );

			if ( $cbVersion[2] === false ) {
				modCBAdminHelper::enableUpdateSite();

				$isBuild			=	( strpos( $cbVersion[1], '+build' ) !== false );

				if ( $isBuild ) {
					$infoUrl		=	'https://www.joomlapolis.com/forge?pk_campaign=in-cb&pk_kwd=admin-module-update-button';

					if ( Application::Config()->get( 'installFromWeb', 1, GetterInterface::INT ) ) {
						$updateUrl	=	$_CB_framework->backendViewUrl( 'installcbplugin', false, array( 'tab' => 'installfrom2' ) );
					} else {
						$updateUrl	=	$cbVersion[3];
					}
				} else {
					$infoUrl		=	'https://www.joomlapolis.com/?pk_campaign=in-cb&pk_kwd=admin-module-update-button';
					$updateUrl		=	$_CB_framework->backendUrl( 'index.php?option=com_installer&view=update', false );
				}

				$latestVersion		=	'<span class="cbUpdateVersion badge badge-danger">' . $cbVersion[1] . '</span>';

				$learnButton		=	'<a href="' . htmlspecialchars( $infoUrl ) . '" target="_blank"><button class="btn btn-outline-primary cbLearnButton">' . CBTxt::T( 'Learn More' ) . '</button></a>';

				$updateButton		=	'<a href="' . htmlspecialchars( $updateUrl ) . '"><button class="btn btn-primary cbUpdateButton">' . CBTxt::T( 'Update Now' ) . '</button></a>';

				$configUrl			=	$_CB_framework->backendViewUrl( 'showconfig', true, array( 'tab' => 'config7' ) );

				$message			=	CBTxt::T( 'COMMUNITY_BUILDER_VERSION_VERSION_IS_AVAILABLE_BUTTON', 'Community Builder version [version] is available: [learn_button] [update_button]', array( '[version]' => $latestVersion, '[learn_button]' => $learnButton, '[update_button]' => $updateButton ) )
									.	( $isBuild ? '<div class="mt-1 cbUpdateNotificationBuild">' . CBTxt::T( 'COMMUNITY_BUILDER_VERSION_IS_BUILD', 'The new version available is a build release. If you do not want to be notified about build releases you may disable Plugin Version and Build Release Checking in your <a href="[config_url]">Community Builder configuration</a>.', array( '[config_url]' => $configUrl ) ) . '</div>' : null );

				$_CB_framework->enqueueMessage( $message, 'alert' );
			}
		}

		$versionChecks				=	explode( '|*|', Application::Config()->get( 'unsupportedVersionsCheck', 'php|*|database|*|joomla', GetterInterface::STRING ) );
		$versionMinimums			=	CBLib::supportedVersions();

		foreach ( $versionChecks as $versionCheck ) {
			$notes						=	[];

			switch ( $versionCheck ) {
				case 'php':
					$type				=	CBTxt::T( 'PHP' );
					$current			=	PHP_VERSION;
					$minimum			=	( $versionMinimums['php']['min'] ?? '' );
					$maximum			=	( $versionMinimums['php']['max'] ?? '' );
					$recommended		=	( $versionMinimums['php']['rec'] ?? '' );

					$notes[]			=	CBTxt::T( 'This can often be changed from within your hosting panel or contact your host if you are unsure how.' );

					if ( version_compare( $current, $minimum, '>=' ) ) {
						continue 2;
					}
					break;
				case 'database':
					$current			=	$_CB_database->getVersion();

					if ( stripos( $current, 'mariadb') !== false ) {
						$type			=	CBTxt::T( 'MariaDB' );
						$minimum		=	( $versionMinimums['mariadb']['min'] ?? '' );
						$maximum		=	( $versionMinimums['mariadb']['max'] ?? '' );
						$recommended	=	( $versionMinimums['mariadb']['rec'] ?? '' );

						if ( version_compare( preg_replace( '/^5\.5\.5-/', '', $current ), $minimum, '>=' ) )  {
							continue 2;
						}
					} else {
						$type			=	CBTxt::T( 'MySQL' );
						$minimum		=	( $versionMinimums['mysql']['min'] ?? '' );
						$maximum		=	( $versionMinimums['mysql']['max'] ?? '' );
						$recommended	=	( $versionMinimums['mysql']['rec'] ?? '' );

						if ( $_CB_database->versionCompare( $minimum ) ) {
							continue 2;
						}

						$minimum		.=	' ' . CBTxt::T( 'CB_UNSUPPORTED_VERSION_MYSQL_MIN_OR', 'or MariaDB [minimum]', [ '[minimum]' => ( $versionMinimums['mariadb']['min'] ?? '' ) ] );
						$recommended	.=	' ' . CBTxt::T( 'CB_UNSUPPORTED_VERSION_MYSQL_REC_OR', 'or MariaDB [recommended]', [ '[recommended]' => ( $versionMinimums['mariadb']['rec'] ?? '' ) ] );
					}

					$notes[]			=	CBTxt::T( 'Contact your host if you are unsure how.' );
					break;
				case 'joomla':
					$type				=	CBTxt::T( 'Joomla' );
					$current			=	Application::Cms()->getCmsVersion();
					$minimum			=	( $versionMinimums['joomla']['min'] ?? '' );
					$maximum			=	( $versionMinimums['joomla']['max'] ?? '' );
					$recommended		=	( $versionMinimums['joomla']['rec'] ?? '' );

					$notes[]			=	CBTxt::T( 'CB_UNSUPPORTED_VERSION_JOOMLA', 'This can be done from directly within the <a href="[url]">Joomla Update component under the Components menu</a>.', [ '[url]' => $_CB_framework->backendUrl( 'index.php?option=com_joomlaupdate' ) ] );

					if ( version_compare( $current, $minimum, '>=' ) ) {
						continue 2;
					}
					break;
				default:
					continue 2;
			}

			$notes[]					=	CBTxt::T( 'CB_UNSUPPORTED_VERSION_SUPPORT', 'If you have any questions regarding this notice <a href="[url]" target="_blank">please do not hesitate to ask on our support forums</a>.', [ '[url]' => 'https://www.joomlapolis.com/forum' ] );
			$notes[]					=	CBTxt::T( 'CB_UNSUPPORTED_VERSION_DISABLE', 'You may disable this notice <a href="[url]">within CB &gt; Configuration &gt; Integrations</a>.', [ '[url]' => $_CB_framework->backendViewUrl( 'showconfig', true, [ 'tab' => 'config7' ] ) ] );

			if ( ( ! $recommended ) || ( $minimum === $recommended ) ) {
				$_CB_framework->enqueueMessage( CBTxt::T( 'CB_UNSUPPORTED_VERSION_NOTICE', '<strong>This server is running [type] [current], which is an outdated version.</strong> You are not required to update now, but a future stable release of Community Builder will be requiring a minimum of [type] [minimum]. Please consider updating to [type] [minimum]. [notes]', [ '[type]' => $type, '[current]' => $current, '[minimum]' => $minimum, '[notes]' => implode( ' ', $notes ) ] ), 'info' );
			} else {
				if ( $maximum ) {
					$recommended		.=	' - ' . $maximum;
				}

				$_CB_framework->enqueueMessage( CBTxt::T( 'CB_UNSUPPORTED_VERSION_NOTICE_RECOMMEND', '<strong>This server is running [type] [current], which is an outdated version.</strong> You are not required to update now, but a future stable release of Community Builder will be requiring a minimum of [type] [minimum] and recommends [type] [recommended]. Please consider updating to at least [type] [minimum]. [notes]', [ '[type]' => $type, '[current]' => $current, '[minimum]' => $minimum, '[recommended]' => $recommended, '[notes]' => implode( ' ', $notes ) ] ), 'info' );
			}
		}

		$query						=	'SELECT ' . $_CB_database->NameQuote( 'extension_id' )
									.	"\n FROM " . $_CB_database->NameQuote( '#__extensions' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'type' ) . ' = ' . $_CB_database->Quote( 'plugin' )
									.	"\n AND " . $_CB_database->NameQuote( 'element' ) . ' = ' . $_CB_database->Quote( 'communitybuilder' )
									.	"\n AND " . $_CB_database->NameQuote( 'folder' ) . ' = ' . $_CB_database->Quote( 'system' )
									.	"\n AND " . $_CB_database->NameQuote( 'enabled' ) . ' = 0';
		$_CB_database->setQuery( $query );
		$systemPluginId				=	$_CB_database->loadResult();

		if ( $systemPluginId ) {
			$enableButton			=	'<a href="index.php?option=com_plugins&view=plugin&layout=edit&extension_id=' . (int) $systemPluginId . '" target="_blank"><button class="btn btn-primary btn-sm cbDisabledSystemPlgButton">' . CBTxt::T( 'Please click here to enable.' ) . '</button></a>';

			$_CB_framework->enqueueMessage( CBTxt::T( 'CB_SYSTEM_PLUGIN_DISABLED', 'The Community Builder System plugin has been disabled! Please enable it for Community Builder to function properly. [button]', array( '[button]' => $enableButton ) ), 'danger' );
		}
		break;
	case 4:
		static $items				=	null;

		if ( ! Application::MyUser()->isAuthorizedToPerformActionOnAsset( 'core.admin', 'com_comprofiler' ) ) {
			return;
		}

		if ( ! isset( $items ) ) {
			$items					=	array();
			$plugins				=	array();

			$query					=	'SELECT *'
									.	"\n FROM " . $_CB_database->NameQuote( '#__comprofiler_plugin' )
									.	"\n WHERE " . $_CB_database->NameQuote( 'iscore' ) . " = 0";
			$_CB_database->setQuery( $query );
			$rows					=	$_CB_database->loadObjectList( 'id', '\CB\Database\Table\PluginTable', array( &$_CB_database ) );

			if ( $rows ) foreach ( $rows as $row ) {
				$rowVer				=	$_PLUGINS->getPluginVersion( $row, 2 );

				if ( $rowVer[2] === false ) {
					$items[]		=	array( $row, $rowVer, $_PLUGINS->checkPluginCompatibility( $row ), false );
					$plugins[]		=	(int) $row->id;
				}

				if ( ! in_array( $row->id, $plugins ) ) {
					if ( ! $_PLUGINS->checkPluginCompatibility( $row ) ) {
						$items[]	=	array( $row, $rowVer, false, true );
					}
				}
			}
		}

		/** @noinspection PhpIncludeInspection */
		require( ModuleHelper::getLayoutPath( 'mod_cbadmin', 'updates' ) );
		break;
	case 3:
		$xml						=	modCBAdminHelper::getFeedXML( 'https://www.joomlapolis.com/news?format=feed&type=rss', 'cbnewsfeed.xml', $feedDuration );

		if ( $xml ) {
			$items					=	$xml->xpath( '//channel/item' );

			/** @noinspection PhpIncludeInspection */
			require( ModuleHelper::getLayoutPath( 'mod_cbadmin', 'news' ) );
		}
		break;
	case 2:
	case 1:
	default:
		$menu						=	array();

		/** @noinspection PhpIncludeInspection */
		require( ModuleHelper::getLayoutPath( 'mod_cbadmin', 'menu' ) );

		if ( $mode == 2 ) {
			$return					=	'<div class="cb_template cb_template_' . selectTemplate( 'dir' ) . '">'
									.		modCBAdminHelper::getTable( $menu, $disabled )
									.	'</div>';

			echo $return;
		} else {
			echo modCBAdminHelper::getMenu( $menu, $disabled );
		}
		break;
}
} catch ( Exception $e ) {
	echo '<div>'
		 . '<a href="javascript: void(0);" style="color: red;" onclick="this.nextElementSibling.style.display = ( this.nextElementSibling.style.display == \'block\' ? \'none\' : \'block\' ); return;">CB Admin Module Failed</a>'
		 . '<div style="display: none;">' . $e->getTraceAsString() . '</div>'
		 . '</div>';
	return;
} catch ( Throwable $e ) {
	echo '<div>'
		 . '<a href="javascript: void(0);" style="color: red;" onclick="this.nextElementSibling.style.display = ( this.nextElementSibling.style.display == \'block\' ? \'none\' : \'block\' ); return;">CB Admin Module Failed</a>'
		 . '<div style="display: none;">' . $e->getTraceAsString() . '</div>'
		 . '</div>';
	return;
}