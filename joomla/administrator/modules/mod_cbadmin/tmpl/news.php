<?php
/**
* Community Builder (TM)
* @version $Id: $
* @package CommunityBuilder
* @copyright (C) 2004-2025 www.joomlapolis.com / Lightning MultiCom SA - and its licensors, all rights reserved
* @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU/GPL version 2
*/

use CBLib\Language\CBTxt;

if ( ! ( defined( '_VALID_CB' ) || defined( '_JEXEC' ) || defined( '_VALID_MOS' ) ) ) { die( 'Direct Access to this location is not allowed.' ); }

if ( $items ) {
	$itemCount	=	0;

?>
	<div class="p-3 cb_template cb_template_<?php echo selectTemplate( 'dir' ); ?>">
		<div class="list-group cbFeed">
<?php
		foreach ( $items as $index => $item ) {
			$itemCount++;
?>
				<div class="d-flex gap-3 list-group-item border-top-0 cbFeedItem<?php echo ( $modalDisplay ? ' list-group-item-action cbTooltip' : '' ) . ( $feedEntries && ( $index >= $feedEntries ) ? ' hidden' : '' ); ?>"<?php echo ( $modalDisplay ? ' data-cbtooltip-modal="true" data-cbtooltip-title="' . htmlspecialchars( $item->title ) . '" data-cbtooltip-tooltip-target="> .cbFeedItemModal" data-cbtooltip-width="1200px" data-cbtooltip-height="90%" data-cbtooltip-title-classes="text-large text-center" data-cbtooltip-classes="cbFeedHideModal"' : '' ); ?>>
					<div class="cbFeedItemLogo">
						<?php echo modCBAdminHelper::descriptionIcon( $item->description ); ?>
					</div>
					<div>
						<h4 class="cbFeedItemTitle">
							<a href="<?php echo htmlspecialchars( $item->link ); ?>" target="_blank"><strong><?php echo $item->title; ?></strong></a>
						</h4>
						<div class="cbFeedItemDesc"><?php echo modCBAdminHelper::shortDescription( $item->description, 200 ); ?></div>
						<div class="text-small cbFeedItemDate"><?php echo cbFormatDate( $item->pubDate, true, 'timeago' ); ?></div>
					</div>
					<?php if ( $modalDisplay ) { ?>
					<template class="cbFeedItemModal">
						<?php echo modCBAdminHelper::longDescription( $item->description ); ?>
					</template>
					<?php } ?>
				</div>
<?php
			if ( $feedEntries && ( $itemCount >= $feedEntries ) && ( ( $index + 1 ) !== count( $items ) ) ) {
?>
				<button type="button" class="m-0 mt-3 w-100 btn btn-primary cbFeedShowMore hidden"><?php echo CBTxt::T( 'More' ); ?></button>
<?php
				$itemCount	=	0;
			}
		}
?>
		</div>
	</div>
<?php
} else {
?>
	<div class="p-3">
		<?php echo CBTxt::T( 'There currently is no news.' ); ?>
	</div>
<?php
}