<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2025 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

namespace CB\Plugin\PMS\Trigger;

use CB\Database\Table\UserTable;
use CBLib\Application\Application;

defined('CBLIB') or die();

class MenuTrigger extends \cbPluginHandler
{

	/**
	 * Displays frontend messages icon on cb menu bar
	 *
	 * @param UserTable $user
	 * @return null|string
	 */
	public function getMessages( $user )
	{
		global $_CB_PMS;

		if ( ( ! $this->params->getBool( 'messages_icon', true ) )
			 || ( Application::MyUser()->getUserId() !== $user->getInt( 'id', 0 ) ) ) {
			return null;
		}

		return ( $_CB_PMS->getPMSMenuIcon( $user->getInt( 'id', 0 ) )[0] ?? null );
	}
}