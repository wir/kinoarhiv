<?php defined('_JEXEC') or die; ?>
<script type="text/javascript" src="<?php echo JURI::root(); ?>components/com_kinoarhiv/assets/js/cookie.min.js"></script>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		jQuery(document).ready(function($){
			var form = $('#application-form');
			if (task != 'cancel' && task != 'save') {
				$.post(form.attr('action'), form.serialize()+'&task='+task+'&format=json', function(response){
					showMsg('.container-main', response.message);
					$(document).scrollTop(0);
				}).fail(function(xhr, status, error){
					showMsg('.container-main', error);
				});
				return;
			} else {
				Joomla.submitform(task, document.getElementById('application-form'));
			}
		});
	}

	jQuery(document).ready(function($){
		var active_tab = 0;
		if (typeof $.cookie('com_kinoarhiv.settings.tabs') == 'undefined') {
			$.cookie('com_kinoarhiv.settings.tabs', 0);
		} else {
			active_tab = $.cookie('com_kinoarhiv.settings.tabs');
		}

		$('#settings_tabs').tabs({
			create: function(event, ui){
				$(this).tabs('option', 'active', parseInt(active_tab, 10));
			},
			activate: function(event, ui){
				$.cookie('com_kinoarhiv.settings.tabs', ui.newTab.index());
			}
		});

		$('#jform_premieres_list_limit, #jform_releases_list_limit').spinner({
			spin: function(event, ui){
				if (ui.value > 5) {
					$(this).spinner('value', 0);
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 5);
					return false;
				}
			}
		});
		$('#jform_person_list_limit').spinner({
			spin: function(event, ui){
				if (ui.value > 10) {
					$(this).spinner('value', 1);
					return false;
				} else if (ui.value < 1) {
					$(this).spinner('value', 10);
					return false;
				}
			}
		});
		$('#jform_introtext_actors_list_limit').spinner({
			spin: function(event, ui){
				if (ui.value > 10) {
					$(this).spinner('value', 0);
					return false;
				} else if (ui.value < 0) {
					$(this).spinner('value', 10);
					return false;
				}
			}
		});
		$('#jform_slider_min_item').spinner({
			spin: function(event, ui){
				if (ui.value > 10) {
					$(this).spinner('value', 1);
					return false;
				} else if (ui.value < 1) {
					$(this).spinner('value', 10);
					return false;
				}
			}
		});
		$('#jform_slider_max_item').spinner({
			spin: function(event, ui){
				if (ui.value > 100) {
					$(this).spinner('value', 10);
					return false;
				} else if (ui.value < 10) {
					$(this).spinner('value', 100);
					return false;
				}
			}
		});

		// For movie alphabet
		var cloned_m_rows = $('.movie-ab .letters-lang').length;
		$('.cmd-abm-new-row').click(function(e){
			e.preventDefault();
			var row = $(this).closest('.row-fluid');
			var cloned_row = row.clone(true);

			row.after(cloned_row);
			$('.letters-lang', cloned_row).val('');
			$('.letters', cloned_row).val('');
			cloned_m_rows++;
		});
		$('.cmd-abm-remove-row').click(function(e){
			e.preventDefault();

			if (cloned_m_rows > 1) {
				$(this).closest('.row-fluid').remove();
				cloned_m_rows--;
			}
		});
		// End

		// For persons(names) alphabet
		var cloned_n_rows = $('.name-ab .letters-lang').length;
		$('.cmd-abn-new-row').click(function(e){
			e.preventDefault();
			var row = $(this).closest('.row-fluid');
			var cloned_row = row.clone(true);

			row.after(cloned_row);
			$('.letters-lang', cloned_row).val('');
			$('.letters', cloned_row).val('');
			cloned_n_rows++;
		});
		$('.cmd-abn-remove-row').click(function(e){
			e.preventDefault();

			if (cloned_n_rows > 1) {
				$(this).closest('.row-fluid').remove();
				cloned_n_rows--;
			}
		});
		// End

		$('#jform_search_names_amplua_disabled').select2({
			placeholder: '<?php echo JText::_('COM_KA_SEARCH_AJAX'); ?>',
			quietMillis: 100,
			minimumInputLength: 1,
			multiple: true,
			ajax: {
				cache: true,
				url: 'index.php?option=com_kinoarhiv&task=ajaxData&element=careers&format=json',
				data: function(term, page){
					return { term: term, showAll: 0 }
				},
				results: function(data, page){
					return { results: data };
				}
			},
			initSelection: function(element, callback){
				var id = $(element).val();

				if (!empty(id)) {
					$.ajax('index.php?option=com_kinoarhiv&task=ajaxData&element=careers&format=json', {
						data: {
							id: id
						}
					}).done(function(data){
						callback(data);
					});
				}
			},
			formatResult: function(data){
				return data.title;
			},
			formatSelection: function(data, container){
				return data.title;
			},
			escapeMarkup: function(m) { return m; }
		});
	});
</script>
<form action="<?php echo JRoute::_('index.php?option=com_kinoarhiv');?>" id="application-form" method="post" name="adminForm" autocomplete="off">
	<div class="row-fluid">
		<!-- Begin Content -->
		<div class="span12">
			<div id="settings_tabs">
				<ul>
					<li><a href="#page-global"><?php echo JText::_('COM_KA_SETTINGS_TAB'); ?></a></li>
					<li><a href="#page-appearance"><?php echo JText::_('COM_KA_APPEARANCE_TAB'); ?></a></li>
					<li><a href="#page-reviews"><?php echo JText::_('COM_KA_REVIEWS_TAB'); ?></a></li>
					<li><a href="#page-search"><?php echo JText::_('COM_KA_SEARCH_TAB'); ?></a></li>
					<?php if ($this->userIsSuperAdmin): ?>
					<li><a href="#page-access"><?php echo JText::_('COM_KA_PERMISSIONS_LABEL'); ?></a></li>
					<?php endif; ?>
				</ul>
				<div id="page-global">
					<div class="row-fluid">
						<div class="span6">
							<?php echo $this->loadTemplate('global'); ?>
						</div>
						<div class="span6">
							<?php echo $this->loadTemplate('metadata'); ?>
						</div>
					</div>
					<div class="row-fluid">
						<div class="span12">
							<?php echo $this->loadTemplate('paths'); ?>
						</div>
					</div>
					<div class="row-fluid">
						<div class="span12">
							<?php echo $this->loadTemplate('gallery'); ?>
						</div>
					</div>
				</div>

				<div id="page-appearance">
					<div class="row-fluid">
						<div class="span6">
							<?php echo $this->loadTemplate('ap_global'); ?>
							<?php echo $this->loadTemplate('ap_nav'); ?>
							<?php echo $this->loadTemplate('ap_rate'); ?>
						</div>
						<div class="span6">
							<?php echo $this->loadTemplate('ap_item'); ?>
						</div>
					</div>
					<div class="row-fluid">
						<div class="span12">
							<?php echo $this->loadTemplate('ap_alphabet'); ?>
						</div>
					</div>
				</div>

				<div id="page-reviews">
					<div class="row-fluid">
						<div class="span6">
							<?php echo $this->loadTemplate('reviews'); ?>
						</div>
						<div class="span6">
							<?php echo $this->loadTemplate('reviews_save'); ?>
						</div>
					</div>
				</div>

				<div id="page-search">
					<div class="row-fluid">
						<div class="span6">
							<?php echo $this->loadTemplate('search_movies'); ?>
						</div>
						<div class="span6">
							<?php echo $this->loadTemplate('search_names'); ?>
						</div>
					</div>
				</div>

				<?php if ($this->userIsSuperAdmin): ?>
				<div id="page-access">
					<div class="row-fluid">
						<div class="span12">
							<?php echo $this->loadTemplate('access'); ?>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<input type="hidden" name="controller" value="settings" />
			<input type="hidden" name="task" value="" />
			<?php echo JHtml::_('form.token'); ?>
		</div>
		<!-- End Content -->
	</div>
</form>
