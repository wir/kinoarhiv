<?php defined('_JEXEC') or die;
if (isset($this->item->soundtracks)):
	//GlobalHelper::loadPlayerAssets();
endif; ?>
<div class="content movie snd">
	<?php if ($this->params->get('use_alphabet') == 1):
		echo $this->loadTemplate('alphabet');
	endif; ?>

	<article class="uk-article">
		<header>
			<h1 class="uk-article-title title">
				<?php if ($this->item->attribs->link_titles === ''): ?>
					<?php if ($this->params->get('link_titles') == 1): ?>
						<a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid); ?>" class="brand" title="<?php echo $this->escape($this->item->title.$this->item->year_str); ?>"><?php echo $this->escape($this->item->title.$this->item->year_str); ?></a>
					<?php else: ?>
						<span class="brand"><?php echo $this->escape($this->item->title.$this->item->year_str); ?></span>
					<?php endif; ?>
				<?php elseif ($this->item->attribs->link_titles == 1): ?>
					<a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid); ?>" class="brand" title="<?php echo $this->escape($this->item->title.$this->item->year_str); ?>"><?php echo $this->escape($this->item->title.$this->item->year_str); ?></a>
				<?php elseif ($this->item->attribs->link_titles == 0): ?>
					<span class="brand"><?php echo $this->escape($this->item->title.$this->item->year_str); ?></span>
				<?php endif; ?>
			</h1>
		</header>
		<div class="middle-nav clearfix">
			<p class="meta">
				<?php if ($this->item->attribs->show_author === '' && !empty($this->item->username)): ?>
					<?php if ($this->params->get('show_author') == 1): ?>
						<span class="icon-user"></span> <?php echo JText::_('JAUTHOR'); ?>: <?php echo $this->item->username; ?><br />
					<?php endif; ?>
				<?php elseif ($this->item->attribs->show_author == 1 && !empty($this->item->username)): ?>
					<span class="icon-user"></span> <?php echo JText::_('JAUTHOR'); ?>: <?php echo $this->item->username; ?><br />
				<?php endif; ?>

				<?php if ($this->item->attribs->show_create_date === ''): ?>
					<?php if ($this->params->get('show_pubdate') == 1): ?>
						<span class="icon-calendar"></span> <?php echo JText::_('COM_KA_CREATED_DATE_ON'); ?><time pubdate="" datetime="<?php echo $this->item->created; ?>"><?php echo date('j F Y', strtotime($this->item->created)); ?></time>
					<?php endif; ?>
				<?php elseif ($this->item->attribs->show_create_date == 1): ?>
					<span class="icon-calendar"></span> <?php echo JText::_('COM_KA_CREATED_DATE_ON'); ?><time pubdate="" datetime="<?php echo $this->item->created; ?>"><?php echo date('j F Y', strtotime($this->item->created)); ?></time>
				<?php endif; ?>

				<?php
				if ((
						($this->item->attribs->show_create_date === '' && $this->params->get('show_pubdate') == 1) || $this->item->attribs->show_create_date == 1
					) && (
						($this->item->attribs->show_modify_date === '' && $this->params->get('show_moddate') == 1) || $this->item->attribs->show_modify_date == 1
					)):
					echo ' &bull; ';
				endif; ?>

				<?php if ($this->item->attribs->show_modify_date === ''): ?>
					<?php if ($this->params->get('show_moddate') == 1): ?>
						<?php echo JText::_('COM_KA_LAST_UPDATED'); ?><time pubdate="" datetime="<?php echo $this->item->modified; ?>"><?php echo date('j F Y', strtotime($this->item->modified)); ?></time>
					<?php endif; ?>
				<?php elseif ($this->item->attribs->show_modify_date == 1): ?>
					<?php echo JText::_('COM_KA_LAST_UPDATED'); ?><time pubdate="" datetime="<?php echo $this->item->modified; ?>"><?php echo date('j F Y', strtotime($this->item->modified)); ?></time>
				<?php endif; ?>
			</p>
		</div>
		<?php echo $this->item->event->afterDisplayTitle; ?>
		<?php echo $this->loadTemplate('tabs'); ?>
		<?php echo $this->item->event->beforeDisplayContent; ?>
		<div class="snd-list">
			<?php if (isset($this->item->soundtracks) && count($this->item->soundtracks) > 0): ?>
			<?php else: ?>
			<div><?php echo GlobalHelper::showMsg(JText::_('COM_KA_NO_ITEMS')); ?></div>
			<?php endif; ?>
		</div>
	</article>
	<?php echo $this->item->event->afterDisplayContent; ?>
</div>
