<?php defined('_JEXEC') or die;
$uid_hash = md5(crc32($this->user->get('id')).md5($this->task)).crc32($this->task); ?>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/ui.multiselect.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/jqGrid.min.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/i18n/grid/grid.locale-<?php echo substr(JFactory::getLanguage()->getTag(), 0, 2); ?>.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/jquery.searchFilter.min.js" type="text/javascript"></script>
<script src="<?php echo JURI::base(); ?>components/com_kinoarhiv/assets/js/grid.setcolumns.js" type="text/javascript"></script>
<script src="<?php echo JURI::root(); ?>components/com_kinoarhiv/assets/js/cookie.min.js" type="text/javascript"></script>
<script type="text/javascript">
	jQuery(document).ready(function($){
		var col_sort = '';
		if (typeof $.cookie('<?php echo $uid_hash; ?>') == 'undefined') {
			$.cookie('<?php echo $uid_hash; ?>', 'movie.3.asc', { expires: 365 });
			col_sort = 'movie.3.asc'.split('.');
		} else {
			col_sort = $.cookie('<?php echo $uid_hash; ?>').split('.');
		}

		$('#list').jqGrid({
			url: 'index.php?option=com_kinoarhiv&controller=relations&task=genres&action=getList&format=json<?php echo ($this->id != 0) ? '&id='.$this->id : ''; ?><?php echo ($this->movie_id != 0) ? '&mid='.$this->movie_id : ''; ?>',
			datatype: 'json',
			loadui: 'block',
			height: Math.round($(window).height() - ($('.container-main').offset().top * 1.8)),
			shrinkToFit: true,
			width: $('#j-main-container').innerWidth(),
			colNames: ['<?php echo JText::_('COM_KA_FIELD_GENRE_LABEL'); ?>', '<?php echo JText::_('COM_KA_FIELD_GENRE_ID'); ?>', '<?php echo JText::_('COM_KA_FIELD_MOVIE_LABEL'); ?>', '<?php echo JText::_('COM_KA_FIELD_MOVIE_ID'); ?>', '<?php echo JText::_('JFIELD_ORDERING_LABEL'); ?>'],
			colModel:[
				{name:'genre', index:'genre', width:300, sorttype:"text", searchoptions: {sopt: ['eq','bw','ew','cn']}},
				{name:'genre_id', index:'genre_id', width:70, sorttype:"int", searchoptions: {sopt: ['eq','le','ge']}},
				{name:'movie', index:'movie', width:350, sorttype:"text", searchoptions: {sopt: ['eq','bw','ew','cn']}},
				{name:'movie_id', index:'movie_id', width:70, sorttype:"int", searchoptions: {sopt: ['eq','le','ge']}},
				{name:'ordering', index:'ordering', width:70, align:"right", sortable: false, search: false}
			],
			multiselect: true,
			caption: '',
			rowNum: 25,
			rowList:[10, 25, 30, 50, 100],
			pager: '#pager',
			sortname: col_sort[0],
			sortorder: col_sort[2],
			viewrecords: true,
			onSortCol: function(i, col, ord){
				$.cookie('<?php echo $uid_hash; ?>', i+'.'+col+'.'+ord, { expires: 365 });
			}
		});
		$('#list').jqGrid('navGrid', '#pager', {edit:false, add:false, del:false, searchtext: '<?php echo JText::_('JSEARCH_FILTER'); ?>'}, {}, {}, {}, {
			width: 780, overlay: 60,
			left: Math.round((($(document).width()/2)-($(document).width()/3))),
			top: Math.round((($(document).height()/2)-($(document).height()/4))),
			closeAfterSearch: true,
			searchOnEnter: true,
			closeOnEscape: true
		});
		$('#list').jqGrid('sortableRows', {
			connectWith: '#list',
			update: function(e, ui){
				$.post('index.php?option=com_kinoarhiv&controller=relations&task=saveOrder&param=genres&format=json', {
					'<?php echo JSession::getFormToken(); ?>': 1,
					'ids': $('#list').jqGrid('getDataIDs').join(','),
					'id': ui.item.attr('id')
				}, function(response){
					if (response.success) {
						$('#list').trigger('reloadGrid');
					} else {
						showMsg('#j-main-container', '<?php echo JText::_('COM_KA_SAVE_ORDER_ERROR'); ?>');
					}
				}).fail(function(xhr, status, error){
					showMsg('#j-main-container', error);
				});
			}
		});
		$('#list').jqGrid('gridResize', {});
	});
</script>
<table id="list"></table>
<div id="pager"></div>
