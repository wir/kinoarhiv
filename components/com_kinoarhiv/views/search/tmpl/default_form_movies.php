<?php defined('_JEXEC') or die;
if ($this->params->get('search_movies_enable') == 0) {
	return;
}
?>
<script type="text/javascript">
	jQuery(document).ready(function($){
		$('#filters_movies_country').select2({
			placeholder: '<?php echo JText::_('JGLOBAL_SELECT_AN_OPTION'); ?>',
			allowClear: true,
			formatSelection: function(data){
				return "<img class='flag-dd' src='<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/themes/component/<?php echo $this->params->get('ka_theme'); ?>/images/icons/countries/" + $(data.element).data('code') + ".png'/> " + data.text;
			},
			escapeMarkup: function(m) { return m; }
		});
		$('#filters_movies_vendor').select2({placeholder: '<?php echo JText::_('JGLOBAL_SELECT_AN_OPTION'); ?>', allowClear: true});
		$('#filters_movies_genre').select2({placeholder: '<?php echo JText::_('JGLOBAL_SELECT_SOME_OPTIONS'); ?>'});

		$('#filters_movies_cast').select2({
			placeholder: '<?php echo JText::_('JGLOBAL_KEEP_TYPING'); ?>',
			allowClear: true,
			minimumInputLength: 1,
			maximumSelectionSize: 1,
			ajax: {
				cache: true,
				url: '<?php echo JRoute::_('index.php?option=com_kinoarhiv&task=ajaxData&element=names&format=json&Itemid='.$this->home_itemid['movies'], false); ?>',
				data: function(term, page){
					return {
						term: term,
						showAll: 0
					}
				},
				results: function(data, page){
					return {results: data};
				}
			},
			initSelection: function(element, callback){
				var id = parseInt($(element).val(), 10);

				if (id !== 0) {
					$.ajax('<?php echo JRoute::_('index.php?option=com_kinoarhiv&task=ajaxData&element=names&format=json&Itemid='.$this->home_itemid['names'], false); ?>', {
						data: {
							id: id
						}
					}).done(function(data){
						callback(data);
					});
				}
			},
			formatResult: function(data){
				var title = '';

				if (data.name != '') title += data.name;
				if (data.name != '' && data.latin_name != '') title += ' / ';
				if (data.latin_name != '') title += data.latin_name;

				return title;
			},
			formatSelection: function(data){
				var title = '';

				if (data.name != '') title += data.name;
				if (data.name != '' && data.latin_name != '') title += ' / ';
				if (data.latin_name != '') title += data.latin_name;

				return title;
			},
			escapeMarkup: function(m) { return m; }
		});

		$('#filters_movies_tags').select2({
			placeholder: '<?php echo JText::_('JGLOBAL_KEEP_TYPING'); ?>',
			allowClear: true,
			minimumInputLength: 2,
			maximumSelectionSize: 5,
			multiple: true,
			ajax: {
				cache: true,
				url: '<?php echo JRoute::_('index.php?option=com_kinoarhiv&task=ajaxData&element=tags&format=json&Itemid='.$this->home_itemid['movies'], false); ?>',
				data: function(term, page){
					return { term: term, showAll: 0 }
				},
				results: function(data, page){
					return { results: data };
				}
			},
			initSelection: function(element, callback){
				var id = $(element).val();

				if (id !== 0 || id !== "") {
					$.ajax('<?php echo JRoute::_('index.php?option=com_kinoarhiv&task=ajaxData&element=tags&format=json&Itemid='.$this->home_itemid['movies'], false); ?>', {
						data: {
							term: id
						}
					}).done(function(data){
						callback(data);
					});
				}
			},
			formatResult: function(data){ return data.title; },
			formatSelection: function(data){ return data.title; },
			escapeMarkup: function(m) { return m; }
		});

		$('#filters_movies_rate').slider({
			range: true,
			min: 0,
			max: <?php echo (int)$this->params->get('vote_summ_num'); ?>,
			values: [<?php echo (int)$this->activeFilters->def('filters.movies.rate.min', 0); ?>, <?php echo (int)$this->activeFilters->def('filters.movies.rate.max', $this->params->get('vote_summ_num')); ?>],
			slide: function(event, ui){
				$('#filters_movies_rate_min').spinner('value', ui.values[0]);
				$('#filters_movies_rate_max').val(ui.values[1]);
			}
		});
		$('#filters_movies_rate_min').val($('#filters_movies_rate').slider('values', 0)).spinner({
			spin: function(event, ui){
				if (ui.value > <?php echo (int)$this->params->get('vote_summ_num'); ?>) {
					$(this).spinner('value', <?php echo (int)$this->params->get('vote_summ_num'); ?>);
					return false;
				} else if (ui.value > $('#filters_movies_rate').slider('values', 1)) {
					$(this).spinner('value', $('#filters_movies_rate').slider('values', 0));
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 0);
					return false;
				}
				$('#filters_movies_rate').slider('values', 0, ui.value);
			}
		});
		$('#filters_movies_rate_max').val($('#filters_movies_rate').slider('values', 1)).spinner({
			spin: function(event, ui){
				if (ui.value > <?php echo (int)$this->params->get('vote_summ_num'); ?>) {
					$(this).spinner('value', <?php echo (int)$this->params->get('vote_summ_num'); ?>);
					return false;
				} else if (ui.value < $('#filters_movies_rate').slider('values', 0)) {
					$(this).spinner('value', $('#filters_movies_rate').slider('values', 1));
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 0);
					return false;
				}
				$('#filters_movies_rate').slider('values', 1, ui.value);
			}
		});

		$('#filters_movies_imdbrate').slider({
			range: true,
			min: 0,
			max: 10,
			values: [<?php echo (int)$this->activeFilters->def('filters.movies.imdbrate.min', 6); ?>, <?php echo (int)$this->activeFilters->def('filters.movies.imdbrate.max', 10); ?>],
			slide: function(event, ui){
				$('#filters_movies_imdbrate_min').spinner('value', ui.values[0]);
				$('#filters_movies_imdbrate_max').val(ui.values[1]);
			}
		});
		$('#filters_movies_imdbrate_min').val($('#filters_movies_imdbrate').slider('values', 0)).spinner({
			spin: function(event, ui){
				if (ui.value > 10) {
					$(this).spinner('value', 10);
					return false;
				} else if (ui.value > $('#filters_movies_imdbrate').slider('values', 1)) {
					$(this).spinner('value', $('#filters_movies_imdbrate').slider('values', 0));
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 0);
					return false;
				}
				$('#filters_movies_imdbrate').slider('values', 0, ui.value);
			}
		});
		$('#filters_movies_imdbrate_max').val($('#filters_movies_imdbrate').slider('values', 1)).spinner({
			spin: function(event, ui){
				if (ui.value > 10) {
					$(this).spinner('value', 10);
					return false;
				} else if (ui.value < $('#filters_movies_imdbrate').slider('values', 1)) {
					$(this).spinner('value', $('#filters_movies_imdbrate').slider('values', 0));
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 0);
					return false;
				}
				$('#filters_movies_imdbrate').slider('values', 1, ui.value);
			}
		});

		$('#filters_movies_kprate').slider({
			range: true,
			min: 0,
			max: 10,
			values: [<?php echo (int)$this->activeFilters->def('filters.movies.kprate.min', 6); ?>, <?php echo (int)$this->activeFilters->def('filters.movies.kprate.max', 10); ?>],
			slide: function(event, ui){
				$('#filters_movies_kprate_min').spinner('value', ui.values[0]);
				$('#filters_movies_kprate_max').val(ui.values[1]);
			}
		});
		$('#filters_movies_kprate_min').val($('#filters_movies_kprate').slider('values', 0)).spinner({
			spin: function(event, ui){
				if (ui.value > 10) {
					$(this).spinner('value', 10);
					return false;
				} else if (ui.value > $('#filters_movies_kprate').slider('values', 1)) {
					$(this).spinner('value', $('#filters_movies_kprate').slider('values', 0));
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 0);
					return false;
				}
				$('#filters_movies_kprate').slider('values', 0, ui.value);
			}
		});
		$('#filters_movies_kprate_max').val($('#filters_movies_kprate').slider('values', 1)).spinner({
			spin: function(event, ui){
				if (ui.value > 10) {
					$(this).spinner('value', 10);
					return false;
				} else if (ui.value < $('#filters_movies_kprate').slider('values', 1)) {
					$(this).spinner('value', $('#filters_movies_kprate').slider('values', 0));
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 0);
					return false;
				}
				$('#filters_movies_kprate').slider('values', 1, ui.value);
			}
		});

		$('#filters_movies_rtrate').slider({
			range: true,
			min: 0,
			max: 100,
			values: [<?php echo (int)$this->activeFilters->def('filters.movies.rtrate.min', 0); ?>, <?php echo (int)$this->activeFilters->def('filters.movies.rtrate.max', 100); ?>],
			slide: function(event, ui){
				$('#filters_movies_rtrate_min').spinner('value', ui.values[0]);
				$('#filters_movies_rtrate_max').val(ui.values[1]);
			}
		});
		$('#filters_movies_rtrate_min').val($('#filters_movies_rtrate').slider('values', 0)).spinner({
			spin: function(event, ui){
				if (ui.value > 100) {
					$(this).spinner('value', 100);
					return false;
				} else if (ui.value > $('#filters_movies_rtrate').slider('values', 1)) {
					$(this).spinner('value', $('#filters_movies_rtrate').slider('values', 0));
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 0);
					return false;
				}
				$('#filters_movies_rtrate').slider('values', 0, ui.value);
			}
		});
		$('#filters_movies_rtrate_max').val($('#filters_movies_rtrate').slider('values', 1)).spinner({
			spin: function(event, ui){
				if (ui.value > 100) {
					$(this).spinner('value', 100);
					return false;
				} else if (ui.value < $('#filters_movies_rtrate').slider('values', 1)) {
					$(this).spinner('value', $('#filters_movies_rtrate').slider('values', 0));
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 0);
					return false;
				}
				$('#filters_movies_rtrate').slider('values', 1, ui.value);
			}
		});

		$('#filters_movies_rate_enable').change(function(){
			if (!$(this).is(':checked')) {
				$('#filters_movies_rate_min, #filters_movies_rate_max').spinner('disable');
				$('#filters_movies_rate').slider('disable');
				$(this).val(0);
			} else {
				$('#filters_movies_rate_min, #filters_movies_rate_max').spinner('enable');
				$('#filters_movies_rate').slider('enable');
				$(this).val(1);
			}
		}).trigger('change');
		$('#filters_movies_imdbrate_enable').change(function(){
			if (!$(this).is(':checked')) {
				$('#filters_movies_imdbrate_min, #filters_movies_imdbrate_max').spinner('disable');
				$('#filters_movies_imdbrate').slider('disable');
				$(this).val(0);
			} else {
				$('#filters_movies_imdbrate_min, #filters_movies_imdbrate_max').spinner('enable');
				$('#filters_movies_imdbrate').slider('enable');
				$(this).val(1);
			}
		}).trigger('change');
		$('#filters_movies_kprate_enable').change(function(){
			if (!$(this).is(':checked')) {
				$('#filters_movies_kprate_min, #filters_movies_kprate_max').spinner('disable');
				$('#filters_movies_kprate').slider('disable');
				$(this).val(0);
			} else {
				$('#filters_movies_kprate_min, #filters_movies_kprate_max').spinner('enable');
				$('#filters_movies_kprate').slider('enable');
				$(this).val(1);
			}
		}).trigger('change');
		$('#filters_movies_rtrate_enable').change(function(){
			if (!$(this).is(':checked')) {
				$('#filters_movies_rtrate_min, #filters_movies_rtrate_max').spinner('disable');
				$('#filters_movies_rtrate').slider('disable');
				$(this).val(0);
			} else {
				$('#filters_movies_rtrate_min, #filters_movies_rtrate_max').spinner('enable');
				$('#filters_movies_rtrate').slider('enable');
				$(this).val(1);
			}
		}).trigger('change');
	});
</script>
<div class="advsearch-movies<?php echo (JFactory::getApplication()->input->get('task', '', 'cmd') != 'movies') ? ' well uk-panel uk-panel-box' : ''; ?>">
	<form action="<?php echo JRoute::_('index.php?option=com_kinoarhiv&view=movies', false); ?>" id="filters_movies" method="post" autocomplete="off">
		<fieldset class="form-horizontal uk-form">
			<legend class="uk-panel-title"><?php echo JText::_('COM_KA_SEARCH_ADV_MOVIES_TITLE'); ?></legend>

			<?php if ($this->params->get('search_movies_title') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_title', 'COM_KA_SEARCH_ADV_MOVIES_TITLE_LABEL'); ?></div>
						<div class="controls uk-width-1-2"><input name="filters[movies][title]" type="text" id="filters_movies_title" class="span10 uk-width-1-1" value="<?php echo $this->activeFilters->def('filters.movies.title', ''); ?>" /></div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_year') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_year', 'COM_KA_SEARCH_ADV_MOVIES_YEAR_LABEL'); ?></div>
						<div class="controls uk-width-1-2"><input name="filters[movies][year]" type="text" id="filters_movies_year" class="span3 uk-width-1-4" value="<?php echo $this->activeFilters->def('filters.movies.year', ''); ?>" maxlength="9" /></div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_year_range') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-3">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-2"><?php echo GlobalHelper::setLabel('filters_movies_from_year', 'COM_KA_SEARCH_ADV_MOVIES_YEAR_FROM_TO_LABEL'); ?></div>
						<div class="controls uk-width-1-3">
							<?php echo JText::_('COM_KA_SEARCH_ADV_MOVIES_YEAR_FROM_LABEL'); ?> <?php echo JHTML::_('select.genericlist', $this->items->movies->from_year, 'filters[movies][from_year]', array('class'=>'span3'), 'value', 'text', $this->activeFilters->def('filters.movies.from_year', ''), 'filters_movies_from_year'); ?>&nbsp;&nbsp;&nbsp;
							<?php echo JText::_('COM_KA_SEARCH_ADV_MOVIES_YEAR_TO_LABEL'); ?> <?php echo JHTML::_('select.genericlist', $this->items->movies->to_year, 'filters[movies][to_year]', array('class'=>'span3'), 'value', 'text', $this->activeFilters->def('filters.movies.to_year', ''), 'filters_movies_to_year'); ?>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_country') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_country', 'COM_KA_SEARCH_ADV_MOVIES_COUNTRY_LABEL'); ?></div>
						<div class="controls uk-width-1-2">
							<select name="filters[movies][country]" id="filters_movies_country" class="span10 uk-width-1-1">
								<?php foreach ($this->items->movies->countries as $country):
									$selected = ($country->id == $this->activeFilters->def('filters.movies.country', '')) ? ' selected' : ''; ?>
								<option value="<?php echo $country->id; ?>" data-code="<?php echo $country->code; ?>"<?php echo $selected; ?>><?php echo $country->name; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_cast') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_cast', 'COM_KA_SEARCH_ADV_MOVIES_NAMES_LABEL', 'COM_KA_SEARCH_ADV_MOVIES_NAMES_LABEL_DESC', 'hasTooltip', array('data-uk-tooltip'=>null)); ?></div>
						<div class="controls uk-width-1-2"><input name="filters[movies][cast]" type="hidden" id="filters_movies_cast" class="span10 uk-width-1-1" value="<?php echo $this->activeFilters->def('filters.movies.cast', 0); ?>" /></div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_vendor') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_vendor', 'COM_KA_SEARCH_ADV_MOVIES_VENDOR_LABEL'); ?></div>
						<div class="controls uk-width-1-2"><?php echo JHTML::_('select.genericlist', $this->items->movies->vendors, 'filters[movies][vendor]', array('class'=>'span10 uk-width-1-1'), 'value', 'text', $this->activeFilters->def('filters.movies.vendor', ''), 'filters_movies_vendor'); ?></div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_genre') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_genre', 'COM_KA_GENRE'); ?></div>
						<div class="controls uk-width-1-2"><?php echo JHTML::_('select.genericlist', $this->items->movies->genres, 'filters[movies][genre][]', array('class'=>'span10 uk-width-1-1', 'multiple'=>true), 'value', 'text', $this->activeFilters->def('filters.movies.genre', ''), 'filters_movies_genre'); ?></div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_mpaa') == 1 || $this->params->get('search_movies_age_restrict') == 1): ?>
			<div class="row-fluid uk-form-row">
				<?php if ($this->params->get('search_movies_mpaa') == 1): ?>
				<div class="span5 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_mpaa', 'COM_KA_MPAA'); ?></div>
						<div class="controls uk-width-1-2"><?php echo JHTML::_('select.genericlist', $this->items->movies->mpaa, 'filters[movies][mpaa]', array('class'=>'span11 uk-width-1-6'), 'value', 'text', $this->activeFilters->def('filters.movies.mpaa', ''), 'filters_movies_mpaa'); ?></div>
					</div>
				</div>
				<?php endif; ?>
				<?php if ($this->params->get('search_movies_age_restrict') == 1): ?>
				<div class="span7 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_age_restrict', 'COM_KA_RU_RATE'); ?></div>
						<div class="controls uk-width-1-2"><?php echo JHTML::_('select.genericlist', $this->items->movies->age_restrict, 'filters[movies][age_restrict]', array('class'=>'span7 uk-width-1-6'), 'value', 'text', $this->activeFilters->def('filters.movies.age_restrict', '-1'), 'filters_movies_age_restrict'); ?></div>
					</div>
				</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_ua_rate') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-3"><?php echo GlobalHelper::setLabel('filters_movies_ua_rate', 'COM_KA_UA_RATE'); ?></div>
						<div class="controls uk-width-1-2"><?php echo JHTML::_('select.genericlist', $this->items->movies->ua_rate, 'filters[movies][ua_rate]', array('class'=>'span4 uk-width-1-2'), 'value', 'text', $this->activeFilters->def('filters.movies.ua_rate', ''), 'filters_movies_ua_rate'); ?></div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_rate') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1" style="margin-bottom: 5px;">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_rate', 'COM_KA_RATE'); ?></div>
						<div class="controls uk-width-1-2" style="padding-top: 4px;">
							<input type="checkbox" name="filters[movies][rate][enable]" id="filters_movies_rate_enable" value="<?php echo (int)$this->activeFilters->def('filters.movies.rate.enable', 0); ?>"<?php echo (int)$this->activeFilters->def('filters.movies.rate.enable', 0) == 1 ? ' checked' : ''; ?> /><?php echo GlobalHelper::setLabel('filters_movies_rate_enable', 'COM_KA_SEARCH_ADV_MOVIES_RATES_LABEL', '', '', array('style'=>'display: inline-block; padding-left: 8px;')); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6">&nbsp;</div>
						<div class="controls uk-width-1-2" style="padding-top: 4px;">
							<div class="span3">
								<input type="text" name="filters[movies][rate][min]" value="<?php echo (int)$this->activeFilters->def('filters.movies.rate.min', 0); ?>" id="filters_movies_rate_min" maxlength="2" size="3" /> - <input type="text" name="filters[movies][rate][max]" value="<?php echo (int)$this->activeFilters->def('filters.movies.rate.max', $this->params->get('vote_summ_num')); ?>" id="filters_movies_rate_max" maxlength="2" size="3" />
							</div>
							<div class="span6">
								<div id="filters_movies_rate" style="margin-top: 4px;"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_imdbrate') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1" style="margin-bottom: 5px;">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_imdbrate', 'COM_KA_SEARCH_ADV_MOVIES_IMDB_RATE'); ?></div>
						<div class="controls uk-width-1-2" style="padding-top: 4px;">
							<input type="checkbox" name="filters[movies][imdbrate][enable]" id="filters_movies_imdbrate_enable" value="<?php echo (int)$this->activeFilters->def('filters.movies.imdbrate.enable', 0); ?>"<?php echo (int)$this->activeFilters->def('filters.movies.imdbrate.enable', 0) == 1 ? ' checked' : ''; ?> /><?php echo GlobalHelper::setLabel('filters_movies_imdbrate_enable', 'COM_KA_SEARCH_ADV_MOVIES_RATES_LABEL', '', '', array('style'=>'display: inline-block; padding-left: 8px;')); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6">&nbsp;</div>
						<div class="controls uk-width-1-2" style="padding-top: 4px;">
							<div class="span3">
								<input type="text" name="filters[movies][imdbrate][min]" value="<?php echo (int)$this->activeFilters->def('filters.movies.imdbrate.min', 6); ?>" id="filters_movies_imdbrate_min" maxlength="2" size="3" /> - <input type="text" name="filters[movies][imdbrate][max]" value="<?php echo (int)$this->activeFilters->def('filters.movies.imdbrate.max', 10); ?>" id="filters_movies_imdbrate_max" maxlength="2" size="3" />
							</div>
							<div class="span6">
								<div id="filters_movies_imdbrate" style="margin-top: 4px;"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_kprate') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1" style="margin-bottom: 5px;">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_kprate', 'COM_KA_SEARCH_ADV_MOVIES_KP_RATE'); ?></div>
						<div class="controls uk-width-1-2" style="padding-top: 4px;">
							<input type="checkbox" name="filters[movies][kprate][enable]" id="filters_movies_kprate_enable" value="<?php echo (int)$this->activeFilters->def('filters.movies.kprate.enable', 0); ?>"<?php echo (int)$this->activeFilters->def('filters.movies.kprate.enable', 0) == 1 ? ' checked' : ''; ?> /><?php echo GlobalHelper::setLabel('filters_movies_kprate_enable', 'COM_KA_SEARCH_ADV_MOVIES_RATES_LABEL', '', '', array('style'=>'display: inline-block; padding-left: 8px;')); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6">&nbsp;</div>
						<div class="controls uk-width-1-2" style="padding-top: 4px;">
							<div class="span3">
								<input type="text" name="filters[movies][kprate][min]" value="<?php echo (int)$this->activeFilters->def('filters.movies.kprate.min', 6); ?>" id="filters_movies_kprate_min" maxlength="2" size="3" /> - <input type="text" name="filters[movies][kprate][max]" value="<?php echo (int)$this->activeFilters->def('filters.movies.kprate.max', 10); ?>" id="filters_movies_kprate_max" maxlength="2" size="3" />
							</div>
							<div class="span6">
								<div id="filters_movies_kprate" style="margin-top: 4px;"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_rtrate') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1" style="margin-bottom: 5px;">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_rtrate', 'COM_KA_SEARCH_ADV_MOVIES_RT_RATE'); ?></div>
						<div class="controls uk-width-1-2" style="padding-top: 4px;">
							<input type="checkbox" name="filters[movies][rtrate][enable]" id="filters_movies_rtrate_enable" value="<?php echo (int)$this->activeFilters->def('filters.movies.rtrate.enable', 0); ?>"<?php echo (int)$this->activeFilters->def('filters.movies.rtrate.enable', 0) == 1 ? ' checked' : ''; ?> /><?php echo GlobalHelper::setLabel('filters_movies_rtrate_enable', 'COM_KA_SEARCH_ADV_MOVIES_RATES_LABEL', '', '', array('style'=>'display: inline-block; padding-left: 8px;')); ?>
						</div>
					</div>
				</div>
			</div>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6">&nbsp;</div>
						<div class="controls uk-width-1-2" style="padding-top: 4px;">
							<div class="span3">
								<input type="text" name="filters[movies][rtrate][min]" value="<?php echo (int)$this->activeFilters->def('filters.movies.rtrate.min', 0); ?>" id="filters_movies_rtrate_min" maxlength="3" size="3" /> - <input type="text" name="filters[movies][rtrate][max]" value="<?php echo (int)$this->activeFilters->def('filters.movies.rtrate.max', 100); ?>" id="filters_movies_rtrate_max" maxlength="3" size="3" />
							</div>
							<div class="span6">
								<div id="filters_movies_rtrate" style="margin-top: 4px;"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_budget') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_from_budget', 'COM_KA_BUDGET'); ?></div>
						<div class="controls uk-width-1-2">
							<?php echo JText::_('COM_KA_SEARCH_ADV_RANGE_FROM_LABEL'); ?> <?php echo JHTML::_('select.genericlist', $this->items->movies->from_budget, 'filters[movies][from_budget]', array('class'=>'span4 uk-width-1-4'), 'value', 'text', $this->activeFilters->def('filters.movies.from_budget', ''), 'filters_movies_from_budget'); ?>&nbsp;&nbsp;&nbsp;
							<?php echo JText::_('COM_KA_SEARCH_ADV_RANGE_TO_LABEL'); ?> <?php echo JHTML::_('select.genericlist', $this->items->movies->to_budget, 'filters[movies][to_budget]', array('class'=>'span4 uk-width-1-4'), 'value', 'text', $this->activeFilters->def('filters.movies.to_budget', ''), 'filters_movies_to_budget'); ?>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php if ($this->params->get('search_movies_tags') == 1): ?>
			<div class="row-fluid uk-form-row">
				<div class="span12 uk-width-1-1">
					<div class="control-group uk-width-1-1">
						<div class="control-label uk-width-1-6"><?php echo GlobalHelper::setLabel('filters_movies_tags', 'JTAG'); ?></div>
						<div class="controls uk-width-1-2"><input type="hidden" name="filters[movies][tags]" id="filters_movies_tags" class="span10 uk-width-1-1" value="<?php echo $this->activeFilters->def('filters.movies.tags', ''); ?>" /></div>
					</div>
				</div>
			</div>
			<?php endif; ?>
		</fieldset>

		<input type="hidden" name="option" value="com_kinoarhiv" />
		<input type="hidden" name="view" value="movies" />
		<input type="hidden" name="task" value="search" />
		<input type="hidden" name="Itemid" value="<?php echo $this->home_itemid['movies']; ?>" />
		<?php echo JHtml::_('form.token'); ?>
		<input type="submit" class="btn btn-primary uk-button uk-button-primary" value="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>" />
	</form>
</div>
