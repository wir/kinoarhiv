<?php defined('_JEXEC') or die;

class KinoarhivViewMediamanager extends JViewLegacy {
	protected $item;
	protected $items;
	protected $pagination;
	protected $state;
	protected $form;

	public function display($tpl = null) {
		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_kinoarhiv');
		$this->params = &$params;

		if (count($errors = $this->get('Errors'))) {
			throw new Exception(implode("\n", $this->get('Errors')), 500);
			return false;
		}

		$type = $app->input->get('type', '', 'word');
		$tab = $app->input->get('tab', 0, 'int');

		if ($app->input->get('section', '', 'word') == 'movie') {
			if ($type == 'gallery') {
				$items = $this->get('Items');
				$pagination = $this->get('Pagination');
				$state = $this->get('State');

				if ($tab == 1) {
					$path = $params->get('media_wallpapers_root');
					$path_www = $params->get('media_wallpapers_root_www');
					$folder = 'wallpapers';
				} elseif ($tab == 2) {
					$path = $params->get('media_posters_root');
					$path_www = $params->get('media_posters_root_www');
					$folder = 'posters';
				} elseif ($tab == 3) {
					$path = $params->get('media_scr_root');
					$path_www = $params->get('media_scr_root_www');
					$folder = 'screenshots';
				}

				foreach ($items as $item) {
					$file_path = $path.DIRECTORY_SEPARATOR.JString::substr($item->alias, 0, 1).DIRECTORY_SEPARATOR.$item->movie_id.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR;
					$item->error = '';

					if (!file_exists($file_path.$item->filename)) {
						$item->filepath = 'javascript:void(0);';
						$item->folderpath = '';
						$item->error .= JText::_('COM_KA_MOVIES_GALLERY_ERROR_FILENOTFOUND');
					} else {
						$item->folderpath = $file_path;
						$item->filepath = $path_www.'/'.JString::substr($item->alias, 0, 1).'/'.$item->movie_id.'/'.$folder.'/'.$item->filename;
					}

					if (!file_exists($file_path.'thumb_'.$item->filename)) {
						$item->th_filepath = '';
						$item->error .= JText::_('COM_KA_MOVIES_GALLERY_ERROR_THUMB_FILENOTFOUND');
					} else {
						$item->th_filepath = $path_www.'/'.JString::substr($item->alias, 0, 1).'/'.$item->movie_id.'/'.$folder.'/thumb_'.$item->filename;
					}
				}

				$this->items = &$items;
				$this->pagination = &$pagination;
				$this->state = &$state;

				$this->addToolbar();

				parent::display($tpl);
			} elseif ($type == 'trailers') {
				$this->addToolbar();

				if ($app->input->get('task', '', 'cmd') == 'edit') {
					JLoader::register('KALanguage', JPATH_COMPONENT.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'language.php');
					$_lang = new KALanguage();

					$item = $this->get('Item');
					$form = $this->get('Form');

					if (count($item) > 0) {
						$item->screenshot_path_www = $params->get('media_trailers_root_www').'/'.JString::substr($item->alias, 0, 1).'/'.$item->movie_id.'/'.$item->screenshot;
						$item->screenshot_folder_www = $params->get('media_trailers_root_www').'/'.JString::substr($item->alias, 0, 1).'/'.$item->movie_id.'/';
						$item->screenshot_path = $params->get('media_trailers_root').'/'.JString::substr($item->alias, 0, 1).'/'.$item->movie_id.'/'.$item->screenshot;
						$item->subtitles_lang_list = $_lang::listOfLanguages();
					}

					$this->subtitles_lang_list = $_lang::listOfLanguages();
					$this->item = &$item;
					$this->form = &$form;

					parent::display('upload_trailer');
					$app->input->set('hidemainmenu', true);
				} else {
					$items = $this->get('Items');
					$pagination = $this->get('Pagination');
					$state = $this->get('State');

					$this->items = &$items;
					$this->pagination = &$pagination;
					$this->state = &$state;

					parent::display($tpl);
				}
			}
		}
	}

	protected function addToolbar($task='') {
		$user = JFactory::getUser();
		$app = JFactory::getApplication();
		$task = $app->input->get('task', '', 'cmd');
		$type = $app->input->get('type', '', 'cmd');

		JToolbarHelper::title(JText::_('COM_KA_MEDIAMANAGER'), 'images');

		if ($task == 'edit') {
			if ($type == 'trailers') {
				JToolbarHelper::apply('apply');
				JToolbarHelper::save('save');
				JToolbarHelper::save2new('save2new');
				JToolbarHelper::divider();
				JToolbarHelper::cancel();
			}
		} else {
			if ($type == 'gallery') {
				if ($user->authorise('core.create', 'com_kinoarhiv')) {
					JToolbarHelper::custom('upload', 'upload', 'upload', JText::_('JTOOLBAR_UPLOAD'), false);
					JToolbarHelper::divider();
				}

				if ($user->authorise('core.edit.state', 'com_kinoarhiv')) {
					JToolbarHelper::publishList();
					JToolbarHelper::unpublishList();
					JToolbarHelper::divider();
				}

				if ($user->authorise('core.delete', 'com_kinoarhiv')) {
					JToolbarHelper::deleteList(JText::_('COM_KA_DELETE_SELECTED'), 'remove');
				}
			} elseif ($type == 'trailers') {
				if ($user->authorise('core.create', 'com_kinoarhiv')) {
					JToolbarHelper::custom('add', 'new', 'new', JText::_('JTOOLBAR_NEW'), false);
					JToolbarHelper::editList('edit');
					JToolbarHelper::divider();
				}

				if ($user->authorise('core.edit.state', 'com_kinoarhiv')) {
					JToolbarHelper::publishList();
					JToolbarHelper::unpublishList();
					JToolbarHelper::divider();
				}

				if ($user->authorise('core.delete', 'com_kinoarhiv')) {
					JToolbarHelper::deleteList(JText::_('COM_KA_DELETE_SELECTED'), 'remove');
				}
			}
		}
	}

	protected function getSortFields() {
		$input = JFactory::getApplication()->input;

		if ($input->get('type') == 'gallery') {
			return array(
				'g.filename' => JText::_('COM_KA_MOVIES_GALLERY_HEADING_FILENAME'),
				'g.dimension' => JText::_('COM_KA_MOVIES_GALLERY_HEADING_DIMENSION'),
				'g.poster_frontpage' => JText::_('COM_KA_MOVIES_GALLERY_HEADING_FRONTPAGE'),
				'g.state' => JText::_('JSTATUS'),
				'g.id' => JText::_('JGRID_HEADING_ID')
			);
		} elseif ($input->get('type') == 'trailers') {
			return array(
				'g.filename' => JText::_('COM_KA_MOVIES_GALLERY_HEADING_FILENAME'),
				'g.access' => JText::_('JGRID_HEADING_ACCESS'),
				'language' => JText::_('JGRID_HEADING_LANGUAGE'),
				'g.frontpage' => JText::_('COM_KA_MOVIES_GALLERY_HEADING_FRONTPAGE'),
				'g.state' => JText::_('JSTATUS'),
				'g.id' => JText::_('JGRID_HEADING_ID')
			);
		}
	}
}
