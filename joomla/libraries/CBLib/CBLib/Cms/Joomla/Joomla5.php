<?php
/**
* CBLib, Community Builder Library(TM)
* @version $Id: 08.06.13 22:32 $
* @package ${NAMESPACE}
* @copyright (C) 2004-2025 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

namespace CBLib\Cms\Joomla;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Session\Session;

defined('CBLIB') or die();

/**
 * CBLib\Cms\Joomla\Joomla5 Class implementation
 *
 */
class Joomla5 extends Joomla6
{
	/**
	 * Returns CMS config
	 *
	 * @return \Joomla\Registry\Registry
	 */
	public function getConfig()
	{
		return Factory::getConfig();
	}

	/**
	 * Returns CMS session
	 *
	 * @return Session
	 */
	public function getSession()
	{
		return Factory::getSession();
	}

	/**
	 * Returns CMS language
	 *
	 * @return Language
	 */
	public function getLanguage()
	{
		return Factory::getLanguage();
	}

	/**
	 * Returns Joomla CMS phpmailer instance
	 *
	 * @return Mail|\JMail
	 */
	public function getMailer()
	{
		return Factory::getMailer();
	}
}
