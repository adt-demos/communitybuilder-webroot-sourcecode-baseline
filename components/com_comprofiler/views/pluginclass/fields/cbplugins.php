<?php
/**
 * Community Builder (TM) form files plugin
 * @version $Id: $
 * @copyright (C) 2004-2023 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
 */

use CBLib\Language\CBTxt;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Version;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

FormHelper::loadFieldClass( 'list' );

if ( Version::MAJOR_VERSION < 4 ) {
	class_alias( 'JFormFieldList', '\Joomla\CMS\Form\Field\ListField', false );
}

class JFormFieldCBplugins extends ListField {
	protected $type		=	'cbplugins';

	protected function getOptions() {
		if ( ( ! file_exists( JPATH_SITE . '/libraries/CBLib/CBLib/Core/CBLib.php' ) ) || ( ! file_exists( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' ) ) ) {
			return array();
		}

		/** @noinspection PhpIncludeInspection */
		include_once( JPATH_ADMINISTRATOR . '/components/com_comprofiler/plugin.foundation.php' );

		cbimport( 'language.front' );

		$db					=	Factory::getDBO();

		$options			=	array();

		$query				=	'SELECT ' . $db->quoteName( 'element' ) . ' AS value'
							.	', ' . $db->quoteName( 'name' ) . ' AS text'
							.	"\n FROM " . $db->quoteName( '#__comprofiler_plugin' )
							.	"\n WHERE " . $db->quoteName( 'type' ) . " NOT IN ( " . $db->quote( 'templates' ) . ", " . $db->quote( 'language' ) . " )"
							.	"\n ORDER BY " . $db->quoteName( 'ordering' );
		$db->setQuery( $query );
		$plugins			=	$db->loadObjectList();

		if ( $plugins ) foreach ( $plugins as $plugin ) {
			$options[]		=	HTMLHelper::_( 'select.option', $plugin->value, CBTxt::T( $plugin->text ) );
		}

		return $options;
	}
}
