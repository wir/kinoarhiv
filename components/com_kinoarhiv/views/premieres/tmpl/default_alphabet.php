<?php defined('_JEXEC') or die; ?>
<div class="alphabet-nav">
	<?php foreach ($this->params->get('movie_alphabet') as $alphabet): ?>
	<div>
		<?php if (!empty($alphabet->lang)): ?><span class="ab_lang"><?php echo $alphabet->lang; ?><span><?php endif; ?>
		<span class="ab_letters btn-toolbar">
			<span class="btn-group uk-button-group">
				<?php foreach ($alphabet->letters as $letters): ?>
				<a href="<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=premieres&letter='.$letters.'&Itemid='.$this->itemid); ?>" class="btn btn-mini btn-default uk-button uk-button-small"><?php echo $letters; ?></a>
				<?php endforeach; ?>
			</span>
		</span>
	</div>
	<?php endforeach; ?>
</div>
<br />
