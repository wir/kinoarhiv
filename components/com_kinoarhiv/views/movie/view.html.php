<?php defined('_JEXEC') or die;

class KinoarhivViewMovie extends JViewLegacy {
	protected $state = null;
	protected $item = null;
	protected $items = null;
	protected $filters = null;
	protected $pagination = null;
	protected $tab;

	public function display($tpl = null) {
		$app = JFactory::getApplication();
		$this->tab = $app->input->get('tab', 'movie', 'cmd');
		$this->itemid = $app->input->get('Itemid');

		switch ($this->tab) {
			case 'cast': $this->cast(); break;
			case 'wallpp': $this->wallpp(); break;
			case 'posters': $this->posters(); break;
			case 'screenshots': $this->screenshots(); break;
			case 'awards': $this->awards(); break;
			case 'tr': $this->trailers(); break;
			case 'sound': $this->sound(); break;
			default: $this->info($tpl); break;
		}
	}

	/**
	 * Method to get and show movie info data.
	 */
	protected function info($tpl) {
		$user = JFactory::getUser();
		$app = JFactory::getApplication();

		$item = $this->get('Data');
		$items = $this->get('Items');
		$pagination = $this->get('Pagination');

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$params = $app->getParams('com_kinoarhiv');
		$config = JFactory::getConfig();

		// Prepare the data
		$item->text = ''; // Workaround for plugin interaction. Article must contain $text item.
		$item->year_str = ($item->year != '0000') ? ' ('.$item->year.')' : '';

		if (empty($item->filename)) {
			$item->poster = JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/images/no_movie_cover.png';
			$item->y_poster = '';
		} else {
			$item->poster = JURI::base().$params->get('media_posters_root').'/'.JString::substr($item->alias, 0, 1).'/'.$item->id.'/posters/thumb_'.$item->filename;
			$item->y_poster = ' y-poster';
		}

		if (!empty($item->desc)) {
			$item->desc = str_replace("\n", "<br />", $item->desc);
		}

		$item->_length = strftime('%H:%M', strtotime($item->length));
		list($hours, $minutes) = explode(':', $item->_length);
		$item->_hr_length = $hours * 60 + $minutes;

		if (!empty($item->rate_sum_loc) && !empty($item->rate_loc)) {
			$item->rate_loc = round($item->rate_sum_loc / $item->rate_loc, (int)$params->get('vote_summ_precision'));
			$item->rate_loc_label = $item->rate_loc.' '.JText::_('COM_KA_FROM').' '.(int)$params->get('vote_summ_num');
		} else {
			$item->rate_loc = 0;
			$item->rate_loc_label = JText::_('COM_KA_RATE_NO');
		}

		$item->event = new stdClass;
		$item->params = new JObject;
		$item->params->set('url', JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$item->id.'&Itemid='.$this->itemid), false);

		$dispatcher = JEventDispatcher::getInstance();
		JPluginHelper::importPlugin('content');
		$dispatcher->trigger('onContentPrepare', array('com_kinoarhiv.movies', &$item, &$params, 0));

		$results = $dispatcher->trigger('onContentAfterTitle', array('com_kinoarhiv.movie', &$item, &$item->params, 0));
		$item->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = $dispatcher->trigger('onContentBeforeDisplay', array('com_kinoarhiv.movie', &$item, &$item->params, 0));
		$item->event->beforeDisplayContent = trim(implode("\n", $results));

		$results = $dispatcher->trigger('onContentAfterDisplay', array('com_kinoarhiv.movie', &$item, &$item->params, 0));
		$item->event->afterDisplayContent = trim(implode("\n", $results));

		if ($config->get('captcha') != '0' && $params->get('reviews_save_captcha') != 0) {
			JPluginHelper::importPlugin('captcha');
			$dispatcher->trigger('onInit', 'captcha');
			$results = $dispatcher->trigger('onDisplay', array('captcha', 'captcha', 'captcha'));
			$item->event->afterDisplayReview = trim(implode("\n", $results));
		}

		$this->params = &$params;
		$this->config = &$config;
		$this->item = &$item;
		$this->items = &$items; // Reviews
		$this->user = &$user;
		$this->pagination = &$pagination;

		$this->_prepareDocument();
		$pathway = $app->getPathway();
		$pathway->addItem($this->item->title, JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid));

		parent::display($tpl);
	}

	/**
	 * Method to get and show full cast crew.
	 */
	protected function cast() {
		$app = JFactory::getApplication();

		$item = $this->get('Cast');

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$params = $app->getParams('com_kinoarhiv');

		$item->year_str = ($item->year != '0000') ? ' ('.$item->year.')' : '';

		$this->params = &$params;
		$this->item = &$item;

		$this->_prepareDocument();
		$pathway = $app->getPathway();
		$pathway->addItem($this->item->title, JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid));
		$pathway->addItem(JText::_('COM_KA_MOVIE_CREATORS'), JRoute::_('index.php?option=com_kinoarhiv&view=movie&tab=cast&id='.$this->item->id.'&Itemid='.$this->itemid));

		parent::display('cast');
	}

	protected function wallpp() {
		$app = JFactory::getApplication();

		$item = $this->get('MovieData');
		$items = $this->get('Items');
		$pagination = $this->get('Pagination');

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$params = $app->getParams('com_kinoarhiv');

		$item->year_str = ($item->year != '0000') ? ' ('.$item->year.')' : '';

		// Check for files
		foreach ($items as $key=>$_item) {
			$file_path = $params->get('media_wallpapers_root').DIRECTORY_SEPARATOR.JString::substr($item->alias, 0, 1).DIRECTORY_SEPARATOR.$item->id.DIRECTORY_SEPARATOR.'wallpapers'.DIRECTORY_SEPARATOR;

			if (!file_exists($file_path.$_item->filename)) {
				$items[$key]->image = 'javascript:void(0);';
				$items[$key]->th_image = JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/images/no_wp.png';
			} else {
				$items[$key]->image = JURI::base().$params->get('media_wallpapers_root').'/'.JString::substr($item->alias, 0, 1).'/'.$item->id.'/wallpapers/'.$_item->filename;
				$size = @getimagesize($file_path.DIRECTORY_SEPARATOR.'thumb_'.$_item->filename);

				if ($size !== false) {
					$items[$key]->th_image = JURI::base().$params->get('media_wallpapers_root').'/'.JString::substr($item->alias, 0, 1).'/'.$item->id.'/wallpapers/thumb_'.$_item->filename;
				} else {
					$items[$key]->th_image = JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/images/no_wp.png';
				}
			}
		}

		$this->params = &$params;
		$this->item = &$item;
		$this->items = &$items;
		$this->filters = $this->getDimensionList();
		$this->pagination = &$pagination;

		$this->_prepareDocument();
		$pathway = $app->getPathway();
		$pathway->addItem($this->item->title, JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid));
		$pathway->addItem(JText::_('COM_KA_MOVIE_TAB_WALLPP'), JRoute::_('index.php?option=com_kinoarhiv&view=movie&tab=wallpp&id='.$this->item->id.'&Itemid='.$this->itemid));

		parent::display('wallpp');
	}

	protected function posters() {
		$app = JFactory::getApplication();

		$item = $this->get('MovieData');
		$items = $this->get('Items');
		$pagination = $this->get('Pagination');

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$params = $app->getParams('com_kinoarhiv');

		$item->year_str = ($item->year != '0000') ? ' ('.$item->year.')' : '';

		// Check for files
		foreach ($items as $key=>$_item) {
			$file_path = $params->get('media_posters_root').DIRECTORY_SEPARATOR.JString::substr($item->alias, 0, 1).DIRECTORY_SEPARATOR.$item->id.DIRECTORY_SEPARATOR.'posters'.DIRECTORY_SEPARATOR;

			if (!file_exists($file_path.$_item->filename)) {
				$items[$key]->image = 'javascript:void(0);';
				$items[$key]->th_image = JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/images/no_movie_cover.png';
			} else {
				$items[$key]->image = JURI::base().$params->get('media_posters_root').'/'.JString::substr($item->alias, 0, 1).'/'.$item->id.'/posters/'.$_item->filename;
				$size = @getimagesize($file_path.DIRECTORY_SEPARATOR.'thumb_'.$_item->filename);

				if ($size !== false) {
					$items[$key]->th_image = JURI::base().$params->get('media_posters_root').'/'.JString::substr($item->alias, 0, 1).'/'.$item->id.'/posters/thumb_'.$_item->filename;
				} else {
					$items[$key]->th_image = JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/images/no_movie_cover.png';
				}
			}
		}

		$this->params = &$params;
		$this->item = &$item;
		$this->items = &$items;
		$this->pagination = &$pagination;

		$this->_prepareDocument();
		$pathway = $app->getPathway();
		$pathway->addItem($this->item->title, JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid));
		$pathway->addItem(JText::_('COM_KA_MOVIE_TAB_POSTERS'), JRoute::_('index.php?option=com_kinoarhiv&view=movie&tab=posters&id='.$this->item->id.'&Itemid='.$this->itemid));

		parent::display('posters');
	}

	protected function screenshots() {
		$app = JFactory::getApplication();

		$item = $this->get('MovieData');
		$items = $this->get('Items');
		$pagination = $this->get('Pagination');

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$params = $app->getParams('com_kinoarhiv');

		$item->year_str = ($item->year != '0000') ? ' ('.$item->year.')' : '';

		// Check for files
		foreach ($items as $key=>$_item) {
			$file_path = $params->get('media_scr_root').DIRECTORY_SEPARATOR.JString::substr($item->alias, 0, 1).DIRECTORY_SEPARATOR.$item->id.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR;

			if (!file_exists($file_path.$_item->filename)) {
				$items[$key]->image = 'javascript:void(0);';
				$items[$key]->th_image = JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/images/no_wp.png';
			} else {
				$items[$key]->image = JURI::base().$params->get('media_scr_root').'/'.JString::substr($item->alias, 0, 1).'/'.$item->id.'/screenshots/'.$_item->filename;
				$size = @getimagesize($file_path.DIRECTORY_SEPARATOR.'thumb_'.$_item->filename);

				if ($size !== false) {
					$items[$key]->th_image = JURI::base().$params->get('media_scr_root').'/'.JString::substr($item->alias, 0, 1).'/'.$item->id.'/screenshots/thumb_'.$_item->filename;
				} else {
					$items[$key]->th_image = JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/images/no_wp.png';
				}
			}
		}

		$this->params = &$params;
		$this->item = &$item;
		$this->items = &$items;
		$this->pagination = &$pagination;

		$this->_prepareDocument();
		$pathway = $app->getPathway();
		$pathway->addItem($this->item->title, JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid));
		$pathway->addItem(JText::_('COM_KA_MOVIE_TAB_SCRSHOTS'), JRoute::_('index.php?option=com_kinoarhiv&view=movie&tab=screenshots&id='.$this->item->id.'&Itemid='.$this->itemid));

		parent::display('screenshots');
	}

	protected function awards() {
		$app = JFactory::getApplication();

		$items = $this->get('Awards');

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$params = $app->getParams('com_kinoarhiv');

		// Prepare the data
		$items->year_str = ($items->year != '0000') ? ' ('.$items->year.')' : '';

		$this->params = &$params;
		$this->item = &$items;

		$this->_prepareDocument();
		$pathway = $app->getPathway();
		$pathway->addItem($this->item->title, JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid));
		$pathway->addItem(JText::_('COM_KA_MOVIE_TAB_AWARDS'), JRoute::_('index.php?option=com_kinoarhiv&view=movie&tab=awards&id='.$this->item->id.'&Itemid='.$this->itemid));

		parent::display('awards');
	}

	/**
	 * Method to get and show trailers.
	 */
	protected function trailers() {
		$app = JFactory::getApplication();
		$user = JFactory::getUser();

		$item = $this->get('Trailers');

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$params = $app->getParams('com_kinoarhiv');

		$item->year_str = ($item->year != '0000') ? ' ('.$item->year.')' : '';

		$this->params = &$params;
		$this->item = &$item;
		$this->user = &$user;

		$this->_prepareDocument();
		$pathway = $app->getPathway();
		$pathway->addItem($this->item->title, JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid));
		$pathway->addItem(JText::_('COM_KA_MOVIE_TAB_TRAILERS'), JRoute::_('index.php?option=com_kinoarhiv&view=movie&tab=tr&id='.$this->item->id.'&Itemid='.$this->itemid));

		parent::display('trailers');
	}

	protected function sound() {
		$app = JFactory::getApplication();

		$item = $this->get('MovieData');
		$items = $this->get('Soundtracks');

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $errors), 500);
			return false;
		}

		$params = $app->getParams('com_kinoarhiv');

		$item->year_str = ($item->year != '0000') ? ' ('.$item->year.')' : '';

		$this->params = &$params;
		$this->item = &$item;
		$this->items = &$items;

		$this->_prepareDocument();
		$pathway = $app->getPathway();
		$pathway->addItem($this->item->title, JRoute::_('index.php?option=com_kinoarhiv&view=movie&id='.$this->item->id.'&Itemid='.$this->itemid));
		$pathway->addItem(JText::_('COM_KA_MOVIE_TAB_SOUND'), JRoute::_('index.php?option=com_kinoarhiv&view=movie&tab=sound&id='.$this->item->id.'&Itemid='.$this->itemid));

		parent::display('soundtracks');
	}

	protected function getDimensionList() {
		$app = JFactory::getApplication();
		$active = $app->input->get('dim_filter', '0', 'string');
		$dimensions = $this->get('DimensionFilters');
		array_push($dimensions, array('width'=>'0', 'title'=>JText::_('COM_KA_FILTERS_DIMENSION_NOSORT')));

		// Build select
		$list = '<label for="dim_filter">'.JText::_('COM_KA_FILTERS_DIMENSION').'</label>
		<select name="dim_filter" id="dim_filter" class="inputbox" onchange="this.form.submit()" autocomplete="off">';
			foreach ($dimensions as $dimension) {
				$selected = ($dimension['width'] == $active) ? ' selected="selected"' : '';
				$list .= '<option value="'.$dimension['width'].'"'.$selected.'>'.$dimension['title'].'</option>';
			}
		$list .= '</select>';

		return array('dimensions.list' => $list);
	}

	/**
	 * Prepares the document
	 */
	protected function _prepareDocument() {
		$app = JFactory::getApplication();
		$menus = $app->getMenu();
		$menu = $menus->getActive();
		$pathway = $app->getPathway();
		$metadata = json_decode($this->item->metadata);

		// Create a new pathway object
		$path = (object)array(
			'name' => JText::_('COM_KA_MOVIES'),
			'link' => 'index.php?option=com_kinoarhiv&view=movies&Itemid='.$this->itemid
		);

		$pathway->setPathway(array($path));
		$this->document->setTitle($this->item->title);

		if ($this->item->metadesc != '') {
			$this->document->setDescription($this->item->metadesc);
		} else {
			$this->document->setDescription($menu->params->get('menu-meta_description'));
		}

		if ($this->item->metakey != '') {
			$this->document->setMetadata('keywords', $this->item->metakey);
		} else {
			$this->document->setMetadata('keywords', $menu->params->get('menu-meta_keywords'));
		}

		if ($menu->params->get('robots') != '') {
			$this->document->setMetadata('robots', $menu->params->get('robots'));
		} else {
			$this->document->setMetadata('robots', $metadata->robots);
		}

		if ($this->params->get('generator') == 'none') {
			$this->document->setGenerator('');
		} elseif ($this->params->get('generator') == 'site') {
			$this->document->setGenerator($this->document->getGenerator());
		} else {
			$this->document->setGenerator($this->params->get('generator'));
		}
	}
}