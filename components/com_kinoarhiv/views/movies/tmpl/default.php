<?php defined('_JEXEC') or die;

$custom_review_comp = false;
if ($this->params->get('allow_reviews') == 1 && $this->params->get('custom_review_component') !== 'default') {
	// JComments
	if ($this->params->get('custom_review_component') == 'jc' && file_exists(JPATH_ROOT.'/components/com_jcomments/jcomments.php')) {
		include_once(JPATH_ROOT.'/components/com_jcomments/jcomments.php');
		$jc = new JComments;
		$custom_review_comp = true;
	}
}

if (JString::substr($this->params->get('media_rating_image_root_www'), 0, 1) == '/') {
	$rating_image_www = JURI::base().JString::substr($this->params->get('media_rating_image_root_www'), 1);
} else {
	$rating_image_www = $this->params->get('media_rating_image_root_www');
}
?>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/jquery.colorbox-min.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/i18n/colorbox/jquery.colorbox-<?php echo substr(JFactory::getLanguage()->getTag(), 0, 2); ?>.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/ui.aurora.min.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/jquery.rateit.min.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/jquery.lazyload.min.js" type="text/javascript"></script>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function($){
		function showMsg(selector, text) {
			$(selector).aurora({
				text: text,
				placement: 'after',
				button: 'close',
				button_title: '[<?php echo JText::_('COM_KA_CLOSE'); ?>]'
			});
		}

		<?php if ($this->params->get('vegas_enable') == 1):
		$src = explode(',', $this->params->get('vegas_bg'));
			if (count($src) > 0): ?>
			$.vegas('slideshow', {
				delay: <?php echo (int)$this->params->get('vegas_slideshow_delay'); ?>,
				backgrounds: [
					<?php foreach ($src as $image): ?>
					{src: '<?php echo trim($image); ?>', fade: 500},
					<?php endforeach; ?>
				]
			<?php else: ?>
			$.vegas({
				src: '<?php echo trim($image); ?>'
			<?php endif; ?>
			})<?php if ($this->params->get('vegas_overlay') != '-1'): ?>('overlay', {
				src: '<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/themes/component/default/images/overlays/<?php echo $this->params->get('vegas_overlay'); ?>',
				opacity: <?php echo $this->params->get('vegas_overlay_opacity'); ?>
			})<?php endif; ?>;
			<?php if ($this->params->get('vegas_bodybg_transparent') == 1): ?>$('<?php echo $this->params->get('vegas_bodybg_selector'); ?>').css('background-color', 'transparent');<?php endif; ?>
		<?php endif; ?>

		$('.hasTip, .hasTooltip').attr('data-uk-tooltip', '');
		$('img.lazy').lazyload({ threshold: 200 });

		$('a.zoom-icon').colorbox({
			title: function(){
				return $(this).closest('.poster').find('img').attr('alt');
			},
			maxHeight: '90%',
			maxWidth: '90%',
			returnFocus: false
		});

		<?php if (!$this->user->guest && $this->params->get('link_favorite') == 1): ?>
		$('.fav a').click(function(e){
			e.preventDefault();
			var _this = $(this);

			$.ajax({
				url: _this.attr('href') + '&format=raw'
			}).done(function(response){
				if (response.success) {
					_this.text(response.text);
					_this.attr('href', response.url);
					if (_this.hasClass('delete')) {
						_this.removeClass('delete').addClass('add');
					} else {
						_this.removeClass('add').addClass('delete');
					}
					showMsg(_this.closest('header'), response.message);
				} else {
					showMsg(_this.closest('header'), '<?php echo JText::_('JERROR_AN_ERROR_HAS_OCCURRED'); ?>');
				}
			}).fail(function(xhr, status, error){
				showMsg(_this.closest('header'), error);
			});
		});
		<?php endif; ?>

		<?php if ($this->params->get('search_movies_enable') == 1 && $this->activeFilters->exists('filters.movies')): ?>
		$('.adv-search #search_form').load('<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=search&task=movies&format=raw&'.JSession::getFormToken().'=1', false); ?>', <?php echo json_encode($this->activeFilters); ?>, function(response, status, xhr){
			if (status == 'error') {
				showMsg('Sorry but there was an error: ' + xhr.status + ' ' + xhr.statusText);
				return false;
			}

			$('.adv-search').accordion({ active: false, collapsible: true, heightStyle: 'content', animate: false });
		});
		<?php endif; ?>
	});
//]]>
</script>
<div class="uk-article ka-content">
	<?php if ($this->params->get('use_alphabet') == 1):
		echo $this->loadTemplate('alphabet');
	endif; ?>

	<?php if ($this->params->get('search_movies_enable') == 1 && $this->activeFilters->exists('filters.movies')): ?>
	<div class="adv-search">
		<h3><?php echo JText::_('COM_KA_SEARCH_ADV'); ?></h3>
		<div id="search_form"></div>
	</div>
	<?php endif; ?>

	<?php if (count($this->items['movies']) > 0):
		if ($this->params->get('search_movies_enable') == 1 && $this->activeFilters->exists('filters.movies')):
			$plural = $this->lang->getPluralSuffixes($this->pagination->total);
			echo '<br />'.JText::sprintf('COM_KA_SEARCH_KEYWORD_N_RESULTS_'.$plural[0], $this->pagination->total);
		endif; ?>

		<?php if ($this->params->get('pagevan_top') == 1): ?>
			<div class="pagination top">
				<?php echo $this->pagination->getPagesLinks(); ?>
			</div>
		<?php endif;

		foreach ($this->items['movies'] as $item): ?>
		<article class="item" data-permalink="<?php echo $item->params->get('url'); ?>">
			<header>
				<h1 class="uk-article-title title title-small">
					<?php if ($item->attribs->link_titles === ''): ?>
						<?php if ($this->params->get('link_titles') == 1): ?>
							<a href="<?php echo $item->params->get('url'); ?>" class="brand" title="<?php echo $this->escape($item->title.$item->year_str); ?>"><?php echo $this->escape($item->title.$item->year_str); ?></a>
						<?php else: ?>
							<span class="brand"><?php echo $this->escape($item->title.$item->year_str); ?></span>
						<?php endif; ?>
					<?php elseif ($item->attribs->link_titles == 1): ?>
						<a href="<?php echo $item->params->get('url'); ?>" class="brand" title="<?php echo $this->escape($item->title.$item->year_str); ?>"><?php echo $this->escape($item->title.$item->year_str); ?></a>
					<?php elseif ($item->attribs->link_titles == 0): ?>
						<span class="brand"><?php echo $this->escape($item->title.$item->year_str); ?></span>
					<?php endif; ?>
				</h1>
				<div class="middle-nav clearfix ui-helper-clearfix">
					<p class="meta">
						<?php if ($item->attribs->show_author === '' && !empty($item->username)): ?>
							<?php if ($this->params->get('show_author') == 1): ?>
								<span class="icon-user"></span> <?php echo JText::_('JAUTHOR'); ?>: <?php echo $item->username; ?><br />
							<?php endif; ?>
						<?php elseif ($item->attribs->show_author == 1 && !empty($item->username)): ?>
							<span class="icon-user"></span> <?php echo JText::_('JAUTHOR'); ?>: <?php echo $item->username; ?><br />
						<?php endif; ?>

						<?php if ($item->attribs->show_create_date === ''): ?>
							<?php if ($this->params->get('show_pubdate') == 1): ?>
								<span class="icon-calendar"></span> <?php echo JText::_('COM_KA_CREATED_DATE_ON'); ?><time pubdate="" datetime="<?php echo $item->created; ?>"><?php echo date('j F Y', strtotime($item->created)); ?></time>
							<?php endif; ?>
						<?php elseif ($item->attribs->show_create_date == 1): ?>
							<span class="icon-calendar"></span> <?php echo JText::_('COM_KA_CREATED_DATE_ON'); ?><time pubdate="" datetime="<?php echo $item->created; ?>"><?php echo date('j F Y', strtotime($item->created)); ?></time>
						<?php endif; ?>

						<?php
						if ((
								($item->attribs->show_create_date === '' && $this->params->get('show_pubdate') == 1) || $item->attribs->show_create_date == 1
							) && (
								($item->attribs->show_modify_date === '' && $this->params->get('show_moddate') == 1) || $item->attribs->show_modify_date == 1
							)):
							echo ' &bull; ';
						endif; ?>

						<?php if ($item->attribs->show_modify_date === ''): ?>
							<?php if ($this->params->get('show_moddate') == 1): ?>
								<?php echo JText::_('COM_KA_LAST_UPDATED'); ?><time pubdate="" datetime="<?php echo $item->modified; ?>"><?php echo date('j F Y', strtotime($item->modified)); ?></time>
							<?php endif; ?>
						<?php elseif ($item->attribs->show_modify_date == 1): ?>
							<?php echo JText::_('COM_KA_LAST_UPDATED'); ?><time pubdate="" datetime="<?php echo $item->modified; ?>"><?php echo date('j F Y', strtotime($item->modified)); ?></time>
						<?php endif; ?>
					</p>
					<?php if (!$this->user->guest && $this->params->get('link_favorite') == 1): ?>
					<p class="fav">
						<?php if ($item->favorite == 1): ?>
						<a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&task=favorite&action=delete&Itemid='.$this->itemid.'&id='.$item->id); ?>" class="delete"><?php echo JText::_('COM_KA_REMOVEFROM_FAVORITE'); ?></a>
						<?php else: ?>
						<a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&task=favorite&action=add&Itemid='.$this->itemid.'&id='.$item->id); ?>" class="add"><?php echo JText::_('COM_KA_ADDTO_FAVORITE'); ?></a>
						<?php endif; ?>
					</p>
					<?php endif; ?>
				</div>
			</header>
			<?php echo $item->event->afterDisplayTitle; ?>
			<?php echo $item->event->beforeDisplayContent; ?>
			<div class="clear"></div>
			<div class="content clearfix ui-helper-clearfix">
				<div>
					<div class="poster<?php echo $item->y_poster; ?>">
						<a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$item->id.'&Itemid='.$this->itemid); ?>" title="<?php echo $this->escape($item->title.$item->year_str); ?>">
							<div>
								<img data-original="<?php echo $item->poster; ?>" class="lazy" border="0" alt="<?php echo JText::_('COM_KA_POSTER_ALT').$this->escape($item->title); ?>" width="<?php echo $item->poster_width; ?>" height="<?php echo $item->poster_height; ?>" />
							</div>
						</a>
						<?php if ($item->y_poster != ''): ?><div class="overlay-poster">
							<a href="<?php echo $item->big_poster; ?>" title="<?php echo JText::_('COM_KA_POSTER_ZOOM'); ?>" class="zoom-icon hasTip"><div></div></a>
						</div><?php endif; ?>
					</div>
					<div class="introtext">
						<?php echo $item->text; ?>
						<div class="separator"></div>
						<?php echo $item->plot; ?>
						<?php if ($this->params->get('ratings_show_frontpage') == 1): ?>
						<div class="separator"></div>
						<div class="ratings-frontpage">
							<?php if (!empty($item->rate_custom)): ?>
							<div><?php echo $item->rate_custom; ?></div>
							<?php else: ?>
								<?php if ($this->params->get('ratings_show_img') == 1): ?>
									<div style="text-align: center; display: inline-block;">
										<?php if (!empty($item->imdb_id)) {
											if (file_exists($this->params->get('media_rating_image_root').'/imdb/'.$item->id.'_big.png')) { ?>
											<a href="http://www.imdb.com/title/tt<?php echo $item->imdb_id; ?>/" rel="nofollow" target="_blank"><img src="<?php echo $rating_image_www; ?>/imdb/<?php echo $item->id; ?>_big.png" border="0" /></a>
											<?php }
										} ?>
										<?php if (!empty($item->kp_id)): ?>
											<a href="http://www.kinopoisk.ru/film/<?php echo $item->kp_id; ?>/" rel="nofollow" target="_blank">
											<?php if ($this->params->get('ratings_img_kp_remote') == 0): ?>
												<img src="<?php echo $rating_image_www; ?>/kinopoisk/<?php echo $item->id; ?>_big.png" border="0" />
											<?php else: ?>
												<img src="http://www.kinopoisk.ru/rating/<?php echo $item->kp_id; ?>.gif" border="0" style="padding-left: 1px;" />
											<?php endif; ?>
											</a>
										<?php endif; ?>
										<?php if (!empty($item->rottentm_id)): ?>
											<?php if (file_exists($this->params->get('media_rating_image_root').'/rottentomatoes/'.$item->id.'_big.png')): ?>
											<a href="http://www.rottentomatoes.com/m/<?php echo $item->rottentm_id; ?>/" rel="nofollow" target="_blank"><img src="<?php echo $rating_image_www; ?>/rottentomatoes/<?php echo $item->id; ?>_big.png" border="0" /></a>
											<?php endif; ?>
										<?php endif; ?>
									</div>
								<?php else: ?>
									<?php if (!empty($item->imdb_votesum) && !empty($item->imdb_votes)): ?>
										<div id="rate-imdb"><span class="a"><?php echo JText::_('COM_KA_RATE_IMDB'); ?></span> <span class="b"><a href="http://www.imdb.com/title/tt<?php echo $item->imdb_id; ?>/?ref_=fn_al_tt_1" rel="nofollow" target="_blank"><?php echo $item->imdb_votesum; ?> (<?php echo $item->imdb_votes; ?>)</a></span></div>
									<?php else: ?>
										<div id="rate-imdb"><span class="a"><?php echo JText::_('COM_KA_RATE_IMDB'); ?></span> <?php echo JText::_('COM_KA_RATE_NO'); ?></div>
									<?php endif; ?>
									<?php if (!empty($item->kp_votesum) && !empty($item->kp_votes)): ?>
										<div id="rate-kp"><span class="a"><?php echo JText::_('COM_KA_RATE_KP'); ?></span> <span class="b"><a href="http://www.kinopoisk.ru/film/<?php echo $item->kp_id; ?>/" rel="nofollow" target="_blank"><?php echo $item->kp_votesum; ?> (<?php echo $item->kp_votes; ?>)</a></span></div>
									<?php else: ?>
										<div id="rate-kp"><span class="a"><?php echo JText::_('COM_KA_RATE_KP'); ?></span> <?php echo JText::_('COM_KA_RATE_NO'); ?></div>
									<?php endif; ?>
								<?php endif; ?>
							<?php endif; ?>
							<div class="local-rt<?php echo $item->rate_loc_label_class; ?>">
								<div class="rateit" data-rateit-value="<?php echo $item->rate_loc; ?>" data-rateit-min="0" data-rateit-max="<?php echo (int)$this->params->get('vote_summ_num'); ?>" data-rateit-ispreset="true" data-rateit-readonly="true"></div>&nbsp;<?php echo $item->rate_loc_label; ?>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="links">
					<a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$item->id.'&Itemid='.$this->itemid); ?>" class="btn btn-default uk-button readmore-link hasTip" title="<?php echo $item->title.$item->year_str; ?>"><?php echo JText::_('COM_KA_READMORE'); ?><span class="icon-chevron-right"></span></a>
					<?php if ($custom_review_comp && $this->params->get('custom_review_component') == 'jc'): ?>
						<span class="review-count"><a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$item->id.'&Itemid='.$this->itemid); ?>#reviews"><?php echo JText::sprintf('COM_KA_REVIEWS_COUNT', $jc::getCommentsCount($item->id, 'com_kinoarhiv')); ?></a></span>
					<?php endif; ?>
				</div>
			</div>
		</article>
		<?php echo $item->event->afterDisplayContent; ?>
		<?php endforeach; ?>
		<?php if ($this->params->get('pagevan_bottom') == 1): ?>
			<div class="pagination bottom">
				<form action="<?php echo htmlspecialchars(JURI::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" style="clear: both;" autocomplete="off">
				<?php echo $this->pagination->getPagesLinks(); ?><br />
				<?php echo $this->pagination->getResultsCounter(); ?>
				<?php echo $this->pagination->getLimitBox(); ?>
				</form>
			</div>
		<?php endif;
	else: ?>
		<br /><div><?php echo ($this->params->get('search_movies_enable') == 1 && $this->activeFilters->exists('filters.movies')) ? JText::sprintf('COM_KA_SEARCH_KEYWORD_N_RESULTS', 0) : GlobalHelper::showMsg(JText::_('COM_KA_NO_ITEMS')); ?></div>
	<?php endif; ?>
</div>
