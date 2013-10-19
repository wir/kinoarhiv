<?php defined('_JEXEC') or die;
JHtml::_('bootstrap.tooltip');

$user		= JFactory::getUser();
$input 		= JFactory::getApplication()->input;
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$sortFields = $this->getSortFields();
?>
<script type="text/javascript">
//<![CDATA[
	Joomla.orderTable = function() {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>') {
			dirn = 'desc';
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}

	jQuery(document).ready(function($){
		Joomla.submitbutton = function(task) {
			if (task == 'upload') {
				var dialog = $('<div id="dialog-upload" title="<?php echo JText::_('JTOOLBAR_UPLOAD'); ?>"><p class="ajax-loading"><?php echo JText::_('COM_KA_LOADING'); ?></p></div>').appendTo('body');
				var item_id = $('#articleList tbody :checkbox').filter(':checked').length == 1 ? $('#articleList tbody :checkbox').filter(':checked').val() : 0;

				$(dialog).dialog({
					dialogClass: 'dialog-upload-dlg',
					modal: true,
					width: 1024,
					height: 520,
					buttons: [
						{
							text: '<?php echo JText::_('JAPPLY'); ?>',
							id: 'tr_save',
							click: function(){
								alert('ok');
							}
						},
						{
							text: '<?php echo JText::_('JCANCEL'); ?>',
							id: 'tr_cancel',
							click: function(){
								$('#articleList :checkbox:checked').attr('checked', false);
								dialog.remove();
							}
						}
					],
					close: function(event, ui){
						$('#articleList :checkbox:checked').attr('checked', false);
						dialog.remove();
					}
				});
				dialog.load('index.php?option=com_kinoarhiv&task=loadTemplate&template=upload_trailer&model=mediamanager&view=mediamanager&section=<?php echo $input->get('section', '', 'word'); ?>&type=<?php echo $input->get('type', '', 'word'); ?>&id=<?php echo $input->get('id', 0, 'int'); ?>&format=raw&item_id=' + item_id);

				$(dialog).on('dialogclose', function(event, ui){
					if ($('#dialog-upload').hasClass('stateChanged')) {
						document.location.reload();
					}
				});

				return false;
			}

			Joomla.submitform(task);
		}

		$('.cmd-upload').click(function(e){
			e.preventDefault();

			$(this).closest('tr').find(':checkbox').attr('checked', true);
			$('#toolbar-upload button').trigger('click');
		});

		$('.cmd-fp_off, .cmd-fp_on').click(function(){
			$(this).closest('tr').find(':checkbox').prop('checked', true);
			$('input[name="boxchecked"]').val(parseInt($('input[name="boxchecked"]').val(), 10) + 1);

			if ($(this).hasClass('cmd-fp_off')) {
				$('input[name="task"]').val('fpOff');
				$('form').submit();
			} else if ($(this).hasClass('cmd-fp_on')) {
				$('input[name="task"]').val('fpOn');
				$('form').submit();
			}
		});
	});
//]]>
</script>
<form action="<?php echo htmlspecialchars(JURI::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" autocomplete="off">
	<div id="filter-bar" class="btn-toolbar">
		<div class="btn-group pull-right hidden-phone">
			<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
			<?php echo $this->pagination->getLimitBox(); ?>
		</div>
		<div class="btn-group pull-right hidden-phone">
			<label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC'); ?></label>
			<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
				<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC'); ?></option>
				<option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING'); ?></option>
				<option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING');  ?></option>
			</select>
		</div>
		<div class="btn-group pull-right">
			<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY'); ?></label>
			<select name="sortTable" id="sortTable" class="input-xlarge" onchange="Joomla.orderTable()">
				<option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
				<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $listOrder); ?>
			</select>
		</div>
	</div>
	<table class="table table-striped gallery-list" id="articleList">
		<thead>
			<tr>
				<th width="1%" class="center hidden-phone">
					<?php echo JHtml::_('grid.checkall'); ?>
				</th>
				<th><?php echo JText::_('JGLOBAL_TITLE'); ?></th>
				<th width="1%" style="min-width: 55px" class="nowrap center"><?php echo JText::_('COM_KA_MOVIES_GALLERY_HEADING_FRONTPAGE'); ?></th>
				<th width="15%" class="nowrap center"><?php echo JText::_('JGRID_HEADING_ACCESS'); ?></th>
				<th width="15%" class="nowrap center"><?php echo JText::_('JGRID_HEADING_LANGUAGE'); ?></th>
				<th width="1%" style="min-width: 55px" class="nowrap center"><?php echo JText::_('JSTATUS'); ?></th>
				<th width="5%" class="nowrap center hidden-phone"><?php echo JText::_('JGRID_HEADING_ID'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (count($this->items) == 0): ?>
				<tr>
					<td colspan="6" class="center hidden-phone"><?php echo JText::_('COM_KA_NO_ITEMS'); ?></td>
				</tr>
			<?php else:
				foreach ($this->items as $i => $item):
					$canEdit    = $user->authorise('core.edit',			'com_kinoarhiv.movie.'.$item->id);
					$canChange  = $user->authorise('core.edit.state',	'com_kinoarhiv.movie.'.$item->id);
				?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center hidden-phone">
						<?php echo JHtml::_('grid.id', $i, $item->id, false, '_id'); ?>
					</td>
					<td class="hidden-phone">
						<?php if ($item->embed_code != ''): ?>
							<span class="icon icon-play hasTooltip" title="<?php echo JText::_('COM_KA_TRAILERS_ISCODE'); ?>"></span>
						<?php elseif ($item->filename != ''): ?>
							<span class="icon icon-camera-2 hasTooltip" title="<?php echo JText::_('COM_KA_TRAILERS_ISFILE'); ?>"></span>
						<?php endif; ?>
						&nbsp;<a href="index.php?option=com_kinoarhiv&view=mediamanager&task=edit&section=movie&type=trailers&id=<?php echo $input->get('id', 0, 'int'); ?>&item_id=<?php echo $item->id; ?>"><?php echo ($this->escape($item->title) == '') ? JText::_('COM_KA_NOTITLE') : $this->escape($item->title); ?></a>
						<?php if ($item->embed_code == ''): ?> <span class="small">(<?php echo $item->duration; ?>)</span><?php endif; ?>
					</td>
					<td class="center">
						<div class="btn-group">
							<?php if ($item->frontpage == 0): ?>
								<a class="btn btn-micro active cmd-fp_off" href="javascript:void(0);"><i class="icon-unpublish"></i></a>
							<?php else: ?>
								<a class="btn btn-micro active cmd-fp_on" href="javascript:void(0);"><i class="icon-publish"></i></a>
							<?php endif; ?>
						</div>
					</td>
					<td class="center hidden-phone">
						<?php echo $this->escape($item->access_level); ?>
					</td>
					<td class="center hidden-phone">
						<?php if ($item->language == '*'):?>
							<?php echo JText::alt('JALL', 'language'); ?>
						<?php else:?>
							<?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
						<?php endif;?>
					</td>
					<td class="center hidden-phone">
						<div class="btn-group">
							<?php echo JHtml::_('jgrid.published', $item->state, $i, '', $canChange, 'cb'); ?>
						</div>
					</td>
					<td class="center hidden-phone">
						<?php echo (int)$item->id; ?>
					</td>
				</tr>
				<?php endforeach;
			endif; ?>
		</tbody>
	</table>
	<input type="hidden" name="controller" value="mediamanager" />
	<input type="hidden" name="section" value="<?php echo $input->get('section', '', 'word'); ?>" />
	<input type="hidden" name="type" value="<?php echo $input->get('type', '', 'word'); ?>" />
	<input type="hidden" name="id" value="<?php echo $input->get('id', 0, 'int'); ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<div class="pagination bottom">
		<?php echo $this->pagination->getListFooter(); ?><br />
		<?php echo $this->pagination->getResultsCounter(); ?>
	</div>
	<?php echo JHtml::_('form.token'); ?>
</form>
