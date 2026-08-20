<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2025 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Application\Application;
use CBLib\Language\CBTxt;
use CBLib\Registry\GetterInterface;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

if ( $items ) {
	$itemCount	=	0;

?>
	<div class="cb_template cb_template_<?php echo selectTemplate( 'dir' ); ?>">
		<div class="cbFeed">
			<table class="table table-hover m-0">
				<caption class="visually-hidden"><?php echo CBTxt::T( 'Community Builder Available Plugin Updates' ); ?></caption>
				<thead>
					<tr>
						<th scope="col" class="w-50 text-left cbFeedItemTitle"><?php echo CBTxt::T( 'Plugin' ); ?></th>
						<th scope="col" class="w-25 text-center cbFeedItemCurrent"><?php echo CBTxt::T( 'Current' ); ?></th>
						<th scope="col" class="w-25 text-center cbFeedItemLatest"><?php echo CBTxt::T( 'Latest' ); ?></th>
					</tr>
				</thead>
				<tbody>
<?php
		foreach ( $items as $index => $item ) {
			$itemCount++;
?>
					<tr class="cbFeedItem<?php echo ( $feedEntries && ( $index >= $feedEntries ) ? ' hidden' : '' ); ?>">
						<th scope="row" class="w-50 text-left cbFeedItemTitle">
<?php
						if ( ! $item[2] ) {
							echo cbTooltip( 0, CBTxt::T( 'This plugin is not compatible with your current CB version. This plugin may still be used, but it may not function properly.' ), null, null, null, '<span class="fa fa-warning text-warning"></span> ' );
						}

						if ( $item[1] && $item[1][3] && ( ! $item[3] ) ) {
							if ( Application::Config()->get( 'installFromWeb', 1, GetterInterface::INT ) ) {
?>
							<a href="<?php echo $_CB_framework->backendViewUrl( 'installcbplugin', true, [ 'tab' => 'installfrom2', 'websearch' => $item[0]->name ] ); ?>"><?php echo $item[0]->name; ?></a>
<?php
							} else {
?>
							<a href="<?php echo htmlspecialchars( $item[1][3] ); ?>" target="_blank"><?php echo $item[0]->name; ?></a>
<?php
							}
						} else {
							echo $item[0]->name;
						}
?>
						</th>
						<td class="w-25 text-overflow text-nowrap text-center <?php echo ( ! $item[3] ? 'text-danger' : 'text-success' ); ?> cbFeedItemCurrent"><?php echo ( $item[1] && $item[1][0] ? $item[1][0] : '-' ); ?></td>
						<td class="w-25 text-overflow text-nowrap text-center cbFeedItemLatest"><?php echo ( $item[1] && $item[1][1] ? $item[1][1] : '-' ); ?></td>
					</tr>
<?php

			if ( $feedEntries && ( $itemCount >= $feedEntries ) && ( ( $index + 1 ) != count( $items ) ) ) {
?>
				<tr class="shadow-none bg-transparent cbFeedShowMoreLink hidden">
					<td class="shadow-none bg-transparent" colspan="3">
						<button type="button" class="m-0 w-100 btn btn-primary cbFeedShowMoreButton"><?php echo CBTxt::T( 'More' ); ?></button>
					</td>
				</tr>
<?php
				$itemCount	=	0;
			}
		}
?>
				</tbody>
			</table>
		</div>
	</div>
<?php
} else {
?>
	<div class="p-3">
		<?php echo CBTxt::T( 'Your install is up to date.' ); ?>
	</div>
<?php
}