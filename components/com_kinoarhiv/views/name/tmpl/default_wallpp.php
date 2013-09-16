<?php defined('_JEXEC') or die; ?>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/jquery-ui.min.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/jquery.colorbox-min.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/i18n/colorbox/jquery.colorbox-<?php echo substr(JFactory::getLanguage()->getTag(), 0, 2); ?>.js" type="text/javascript"></script>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function($){
		$('.wp-list .item a').colorbox({ maxHeight: '90%' });
	});
//]]>
</script>
<div class="content name wallpp">
	<article>
		<header>
			<h1 class="title">
				<a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=name&id='.$this->item->id.'&Itemid='.$this->itemid); ?>" class="brand" title="<?php echo $this->item->title; ?>"><?php echo $this->item->title; ?></a>
			</h1>
		</header>
		<?php echo $this->loadTemplate('tabs'); ?>
		<div class="wp-list">
			<?php if (count($this->items) > 0): ?>
			<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" style="clear: both;">
				<div class="list-filter">
					<?php echo $this->filters['dimensions.list']; ?>
				</div>
				<div style="clear: both;"></div>
				<?php foreach ($this->items as $wp): ?>
				<div class="thumb">
					<div class="item">
						<a href="<?php echo $wp->image; ?>" title="<?php echo $this->item->title; ?>" rel="wp">
							<img src="<?php echo $wp->th_image; ?>" border="0" alt="<?php echo JText::_('COM_KA_WP_NAMES_ALT').$this->item->title; ?>" />
						</a>
					</div>
					<ul>
						<li class="size"><?php echo $wp->dimension; ?></li>
					</ul>
				</div>
				<?php endforeach; ?>
				<div style="clear: both;"></div>
				<div class="pagination bottom">
					<?php echo $this->pagination->getPagesLinks(); ?><br />
					<?php echo $this->pagination->getResultsCounter(); ?><br />
					<label for="limit" class="element-invisible"><?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?></label>
					<?php echo $this->pagination->getLimitBox(); ?>
					<input type="hidden" name="limitstart" value="" />
					<input type="hidden" name="task" value="" />
					<div class="clearfix"></div>
				</div>
				<div style="clear: both;">&nbsp;</div>
			</form>
			<?php else: ?>
			<div><?php echo GlobalHelper::showMsg(JText::_('COM_KA_NO_ITEMS')); ?></div>
			<?php endif; ?>
		</div>
	</article>
</div>