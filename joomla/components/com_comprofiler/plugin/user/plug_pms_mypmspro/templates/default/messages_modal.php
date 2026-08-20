<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2025 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CB\Plugin\PMS\PMSHelper;
use CBLib\Application\Application;
use CBLib\Language\CBTxt;
use CB\Database\Table\UserTable;
use CB\Plugin\PMS\Table\MessageTable;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

/**
 * @var CBplug_pmsmypmspro $this
 * @var int                $total
 * @var MessageTable[]     $rows
 * @var array              $input
 * @var UserTable          $user
 * @var cbPageNav          $pageNav
 * @var bool               $searching
 *
 * @var string             $returnUrl
 * @var string             $type
 * @var bool               $allowTypeFilter
 * @var int                $unread
 */

global $_CB_framework, $_PLUGINS;
?>
<div class="d-flex flex-column h-100 mh-100 pmMessages pmMessagesDefault">
	<?php echo implode( '', $_PLUGINS->trigger( 'pm_onBeforeDisplayMessages', [ &$rows, &$input, $type, $user ] ) ); ?>
	<div class="d-flex user-select-none pmMessagesHeader">
		<div class="d-flex gap-2 align-items-center flex-grow-1 pl-2 pr-2 pmMessagesToolbar">
			<?php if ( PMSHelper::canMessage( $user->getInt( 'id', 0 ), false ) ) { ?>
			<button type="button" onclick="window.location.href='<?php echo $_CB_framework->pluginClassUrl( $this->element, true, [ 'action' => 'messages', 'func' => 'new', 'return' => $returnUrl ] ); ?>';" class="btn btn-success pmButton pmButtonNew"><span class="fa fa-plus-circle"></span> <?php echo CBTxt::T( 'Create New Message' ); ?></button>
			<?php } ?>
			<a href="<?php echo $_CB_framework->pluginClassUrl( $this->element, true, [ 'action' => 'messages' ] ); ?>" class="btn btn-light border pmButton pmButtonSeeAll"><?php echo CBTxt::T( 'See All' ); ?></a>
			<?php if ( $unread ) { ?>
			<a href="<?php echo $_CB_framework->pluginClassUrl( $this->element, true, [ 'action' => 'message', 'func' => 'read', 'return' => $returnUrl ] ); ?>" class="ml-auto pmButton pmButtonRead"><?php echo CBTxt::T( 'Mark All Read' ); ?></a>
			<?php } ?>
		</div>
		<button type="button" class="ml-2 mr-2 align-self-center btn btn-lg btn-light border rounded-circle cbTooltipClose" tabindex="0" aria-label="<?php echo htmlspecialchars( CBTxt::T( 'Close' ) ); ?>">
			<span class="fa fa-times"></span>
		</button>
	</div>
	<div class="p-2 flex-grow-1 pmMessagesRows" role="grid">
		<?php
		$i							=	0;

		if ( $rows ) foreach ( $rows as $row ) {
			$i++;

			$menu					=	[];

			if ( $row->getInt( 'from_user', 0 ) === $user->getInt( 'user_id', 0 ) ) {
				$read				=	$row->getRead();

				if ( ! $read ) {
					$menu[]			=	'<li class="pmMessagesMenuItem" role="presentation"><a href="' . $_CB_framework->pluginClassUrl( $this->element, true, [ 'action' => 'message', 'func' => 'edit', 'id' => $row->getInt( 'id', 0 ), 'return' => $returnUrl ] ) . '" class="dropdown-item" role="menuitem"><span class="fa fa-edit"></span> ' . CBTxt::T( 'Edit' ) . '</a></li>';
				}

				$readTooltip		=	cbTooltip( null, ( $row->getRead() ? CBTxt::T( 'READ_ON_DATE', 'Read on [date]', [ '[date]' => cbFormatDate( $read ) ] ) : CBTxt::T( 'Unread' ) ), null, 'auto', null, null, null, 'data-hascbtooltip="true" data-cbtooltip-position-my="bottom center" data-cbtooltip-position-at="top center" data-cbtooltip-classes="qtip-simple" aria-label="' . htmlspecialchars( ( $row->getRead() ? CBTxt::T( 'Read' ) : CBTxt::T( 'Unread ' ) ) ) . '"' );

				$avatar				=	$row->getTo( 'avatar' );
				$name				=	$row->getTo( 'name' );
				$status				=	$row->getTo( 'status' );
			} else {
				$read				=	$row->getRead( $user->getInt( 'user_id', 0 ) );
				$readTooltip		=	cbTooltip( null, ( $read ? CBTxt::T( 'Mark Unread' ) : CBTxt::T( 'Mark Read' ) ), null, 'auto', null, null, null, 'data-hascbtooltip="true" data-cbtooltip-position-my="bottom center" data-cbtooltip-position-at="top center" data-cbtooltip-classes="qtip-simple" aria-label="' . htmlspecialchars( ( $read ? CBTxt::T( 'Mark Unread' ) : CBTxt::T( 'Mark Read' ) ) ) . '"' );

				$avatar				=	$row->getFrom( 'avatar' );
				$name				=	$row->getFrom( 'name' );
				$status				=	$row->getFrom( 'status' );
			}

			$_PLUGINS->trigger( 'pm_onDisplayMessage', [ &$row, &$avatar, &$name, &$menu, $user ] );

			if ( ( $row->getInt( 'from_user', 0 ) === $user->getInt( 'user_id', 0 ) ) || ( $row->getInt( 'to_user', 0 ) === $user->getInt( 'user_id', 0 ) ) || Application::MyUser()->isGlobalModerator() ) {
				$menu[]				=	'<li class="pmMessagesMenuItem" role="presentation"><a href="javascript: void(0);" onclick="cbjQuery.cbconfirm( \'' . addslashes( CBTxt::T( 'Are you sure you want to delete this message?' ) ) . '\' ).done( function() { window.location.href = \'' . addslashes( $_CB_framework->pluginClassUrl( $this->element, true, array( 'action' => 'message', 'func' => 'delete', 'id' => $row->getInt( 'id', 0 ), Application::Session()->getFormTokenName() => Application::Session()->getFormTokenValue(), 'return' => $returnUrl ) ) ) . '\'; })" class="dropdown-item" role="menuitem"><span class="fa fa-trash-o"></span> ' . CBTxt::T( 'Delete' ) . '</a></li>';
			}

			if ( ( $row->getInt( 'to_user', 0 ) === $user->getInt( 'user_id', 0 ) ) && ( ! $row->getBool( 'from_system', false ) ) ) {
				$menu[]				=	'<li class="pmMessagesMenuItem" role="presentation"><a href="javascript: void(0);" onclick="cbjQuery.cbconfirm( \'' . addslashes( CBTxt::T( 'Are you sure you want to report this message?' ) ) . '\' ).done( function() { window.location.href = \'' . addslashes( $_CB_framework->pluginClassUrl( $this->element, true, array( 'action' => 'message', 'func' => 'report', 'id' => $row->getInt( 'id', 0 ), Application::Session()->getFormTokenName() => Application::Session()->getFormTokenValue(), 'return' => $returnUrl ) ) ) . '\'; })" class="dropdown-item" role="menuitem"><span class="fa fa-warning"></span> ' . CBTxt::T( 'Report' ) . '</a></li>';
			}

			if ( $menu ) {
				$menuItems			=	'<ul class="list-unstyled dropdown-menu d-block position-relative m-0 pmMessagesMenuItems" role="menu">'
									.		implode( '', $menu )
									.	'</ul>';

				$menuAttr			=	cbTooltip( null, $menuItems, null, 'auto', null, null, null, 'class="text-body cbDropdownMenu pmMessagesMenu" data-cbtooltip-menu="true" data-cbtooltip-classes="qtip-nostyle" data-cbtooltip-open-classes="active" aria-label="' . htmlspecialchars( CBTxt::T( 'Message Options' ) ) . '"' );
			}
		?>
		<?php if ( ( $i > 1 ) || ( ( $i > 1 ) && ( $i === count( $rows ) ) ) ) { ?>
		<hr class="mt-2 mb-2" role="presentation" />
		<?php } ?>
		<div class="media pmMessagesRow <?php echo ( $read ? 'pmMessagesRowRead' : 'pmMessagesRowUnread' ); ?>" data-pm-url="<?php echo $_CB_framework->pluginClassUrl( $this->element, true, [ 'action' => 'message', 'func' => 'show', 'id' => $row->getInt( 'id', 0 ), 'return' => $returnUrl ] ); ?>" role="row">
			<div class="media-left pmMessagesRowImg" role="gridcell">
				<?php echo $avatar; ?>
			</div>
			<div class="pl-3 media-body pmMessagesRowMsg" role="gridcell">
				<div class="row no-gutters">
					<div class="text-wrap col pmMessagesRowMsgUser">
						<?php if ( $row->getInt( 'from_user', 0 ) === $user->getInt( 'user_id', 0 ) ) { ?>
						<span class="ml-n1 pl-1 pt-1 pb-1 pr-1 text-large fa fa-envelope<?php echo ( $read ? '-open text-muted' : ' text-primary' ); ?>"<?php echo $readTooltip; ?>></span>
						<?php } else { ?>
						<a href="<?php echo $_CB_framework->pluginClassUrl( $this->element, true, [ 'action' => 'message', 'func' => ( $read ? 'unread' : 'read' ), 'id' => $row->getInt( 'id', 0 ), 'return' => $returnUrl ] ); ?>"<?php echo $readTooltip; ?>><span class="ml-n1 pl-1 pt-1 pb-1 pr-1 text-large fa fa-envelope<?php echo ( $read ? '-open text-muted' : ' text-primary' ); ?>"></span></a>
						<?php } ?>
						<a href="<?php echo $_CB_framework->pluginClassUrl( $this->element, true, [ 'action' => 'message', 'func' => 'show', 'id' => $row->getInt( 'id', 0 ), 'return' => $returnUrl ] ); ?>" class="text-large"><?php echo $name; ?></a>
						<?php echo $status; ?>
					</div>
					<?php if ( $menu ) { ?>
					<div class="col-auto pmMessagesRowMsgMenu">
						<span class="d-none d-sm-inline align-text-bottom pmMessagesRowDate"><?php echo cbFormatDate( $row->getString( 'date', '' ), true, false ); ?></span>
						<a href="javascript: void(0);" <?php echo trim( $menuAttr ); ?>><span class="pt-1 pb-1 pl-3 pr-3 text-large fa fa-ellipsis-v"></span></a>
					</div>
					<?php } ?>
				</div>
				<div class="mt-1 row no-gutters">
					<div class="col-sm text-wrap pmMessagesRowMsgIntro" tabindex="0">
						<a href="<?php echo $_CB_framework->pluginClassUrl( $this->element, true, [ 'action' => 'message', 'func' => 'show', 'id' => $row->getInt( 'id', 0 ), 'return' => $returnUrl ] ); ?>" class="text-inherit text-plain"><?php echo $row->getMessage( 200 ); ?></a>
					</div>
					<div class="col-sm-auto d-block d-sm-none pmMessagesRowDate">
						<?php echo cbFormatDate( $row->getString( 'date', '' ), true, false ); ?>
					</div>
				</div>
			</div>
		</div>
		<?php } else { ?>
		<div class="pmMessagesRow pmMessagesRowEmpty" role="row">
		<?php if ( $searching ) { ?>
			<?php echo CBTxt::T( 'No message search results found.' ); ?>
		<?php } else { ?>
			<?php echo CBTxt::T( 'You currently have no messages.' ); ?>
		<?php } ?>
		</div>
		<?php } ?>
	</div>
	<?php if ( $this->params->getBool( 'messages_paging', true ) && ( $pageNav->total > $pageNav->limit ) ) { ?>
	<div class="m-2 pmMessagesPaging">
		<?php echo $pageNav->getListLinks(); ?>
	</div>
	<?php } ?>
	<?php echo implode( '', $_PLUGINS->trigger( 'pm_onAfterDisplayMessages', [ $rows, $input, $type, $user ] ) ); ?>
</div>
