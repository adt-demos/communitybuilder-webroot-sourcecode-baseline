<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2025 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

namespace CB\Plugin\Core\Field;

use CB\Database\Table\FieldTable;
use CB\Database\Table\UserTable;
use cbFieldHandler;
use CBLib\Application\Application;
use CBLib\Language\CBTxt;
use cbSqlQueryPart;

\defined( 'CBLIB' ) or die();

class PmField extends cbFieldHandler
{
	/**
	 * Returns a field in specified format
	 *
	 * @param  FieldTable  $field
	 * @param  UserTable   $user
	 * @param  string      $output  'html', 'xml', 'json', 'php', 'csvheader', 'csv', 'rss', 'fieldslist', 'htmledit'
	 * @param  string      $reason  'profile' for user profile view, 'edit' for profile edit, 'register' for registration, 'list' for user-lists
	 * @param  int         $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @return mixed
	 */
	public function getField( &$field, &$user, $output, $reason, $list_compare_types )
	{
		global $_CB_PMS;

		if ( ! $_CB_PMS ) {
			return null;
		}

		$pmLinks	=	$_CB_PMS->getPMSlinks( $user->getInt( 'id', 0 ), Application::MyUser()->getUserId(), null, null, 1 ) ;

		switch ( $output ) {
			case 'html':
			case 'rss':
				$imgMode					=	$field->getInt( '_imgMode' ); // For B/C

				if ( $imgMode === null ) {
					$imgMode				=	$field->params->getInt( ( $reason === 'list' ? 'displayModeList' : 'displayMode' ), 0 );
				}

				if ( $imgMode === 3 ) {
					if ( $user->getInt( 'id', 0 ) === Application::MyUser()->getUserId() ) {
						return ( $_CB_PMS->getPMSicon( $user->getInt( 'id', 0 ) )[0] ?? null );
					}

					$imgMode				=	1;
				}

				$pmIMG						=	'<span class="fa fa-comment" title="' . htmlspecialchars( CBTxt::T( '_UE_PM_USER', 'Send Private Message' ) ) . '"></span>';
				$useLayout					=	true;
				$return						=	'';

				foreach ( $pmLinks as $pmLink ) {
					if ( ! \is_array( $pmLink ) ) {
						continue;
					}
					switch ( $imgMode ) {
						case 1:
							$useLayout		=	false; // We don't want to use layout for icon only display as we use it externally
							$linkItem		=	$pmIMG;
							break;
						case 2:
							$linkItem		=	$pmIMG . ' ' . $pmLink['caption'];
							break;
						case 0:
						default:
							$linkItem		=	$pmLink['caption'];		// Already translated in PMS plugin
							break;
					}

					$return					.=	'<a href="' . cbSef( $pmLink['url'] ) . '" title="' . htmlspecialchars( $pmLink['tooltip'] ) . '">' . $linkItem . '</a>';
				}

				if ( $useLayout ) {
					$return					=	$this->formatFieldValueLayout( $return, $reason, $field, $user );
				}

				return $return;
			case 'htmledit':
				return null;
			case 'json':
			case 'php':
			case 'xml':
			case 'csvheader':
			case 'fieldslist':
			case 'csv':
			default:
				$retArray			=	[];

				foreach ( $pmLinks as $pmLink ) {
					if ( ! \is_array( $pmLink ) ) {
						continue;
					}

					$title			=	cbReplaceVars( $pmLink['caption'], $user );
					$url			=	cbSef( $pmLink['url'] );
					$description	=	cbReplaceVars( $pmLink['tooltip'], $user );

					$retArray[]		=	[ 'title' => $title, 'url' => $url, 'tooltip' => $description ];
				}

				return $this->_linksArrayToFormat( $field, $retArray, $output );
		}
	}

	/**
	 * Prepares field data for saving to database (safe transfer from $postdata to $user)
	 * Override
	 *
	 * @param  FieldTable  $field
	 * @param  UserTable   $user      RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array       $postdata  Typically $_POST (but not necessarily), filtering required.
	 * @param  string      $reason    'edit' for save profile edit, 'register' for registration, 'search' for searches
	 */
	public function prepareFieldDataSave( &$field, &$user, &$postdata, $reason )
	{
		$this->_prepareFieldMetaSave( $field, $user, $postdata, $reason );
		// on purpose don't log field update
		// nothing to do, PM fields don't save :-)
	}

	/**
	 * Finder:
	 * Prepares field data for saving to database (safe transfer from $postdata to $user)
	 *
	 * @param  FieldTable  $field
	 * @param  UserTable   $user      RETURNED populated: touch only variables related to saving this field (also when not validating for showing re-edit)
	 * @param  array       $postdata  Typically $_POST (but not necessarily), filtering required.
	 * @param  int         $list_compare_types   IF reason == 'search' : 0 : simple 'is' search, 1 : advanced search with modes, 2 : simple 'any' search
	 * @param  string      $reason    'edit' for save profile edit, 'register' for registration, 'search' for searches
	 * @return cbSqlQueryPart[]
	 */
	function bindSearchCriteria( &$field, &$user, &$postdata, $list_compare_types, $reason )
	{
		return array();
	}
}