<?php defined('_JEXEC') or die;
/**
 * @package     Kinoarhiv.Administrator
 * @subpackage  com_kinoarhiv
 *
 * @copyright   Copyright (C) 2010 Libra.ms. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @url			http://киноархив.com/
 */

class KinoarhivModelSettings extends JModelForm {
	/**
	 * Method to get a form object.
	 *
	 * @param   array  $data		Data for the form.
	 * @param   boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure
	 */
	public function getForm($data = array(), $loadData = true) {
		// Load config.xml from root component folder.
		JForm::addFormPath(JPATH_ADMINISTRATOR . '/components/com_kinoarhiv/');
		$form = $this->loadForm('com_kinoarhiv.config', 'config', array('control' => 'jform', 'load_data' => $loadData), false, '/config');

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Get the component information.
	 *
	 * @return  object
	 */
	public function getSettings() {
		$result = JComponentHelper::getComponent('com_kinoarhiv');

		return $result;
	}

	/**
	 * Method to save the configuration data.
	 *
	 * @param   array  An array containing config data.
	 *
	 * @return  bool	True on success, false on failure.
	 */
	public function save($data) {
		$db = $this->getDBO();
		$form_rules = $data['rules'];
		// Unset rules array because we do not need it in the component parameters
		unset($data['rules']);
		$rules = array();

		foreach ($form_rules as $rule=>$groups) {
			foreach ($groups as $group=>$value) {
				if ($value != '') {
					$rules[$rule][$group] = (int)$value;
				} else {
					unset($form_rules[$rule][$group]);
				}
			}
		}

		if ($data['introtext_actors_list_limit'] > 10) {
			$data['introtext_actors_list_limit'] = 10;
		} elseif ($data['introtext_actors_list_limit'] < 0) {
			$data['introtext_actors_list_limit'] = 0;
		}

		if ($data['person_list_limit'] > 10) {
			$data['person_list_limit'] = 10;
		} elseif ($data['person_list_limit'] < 1) {
			$data['person_list_limit'] = 1;
		}

		if ($data['premieres_list_limit'] > 5) {
			$data['premieres_list_limit'] = 5;
		} elseif ($data['premieres_list_limit'] < 0) {
			$data['premieres_list_limit'] = 0;
		}

		if ($data['releases_list_limit'] > 5) {
			$data['releases_list_limit'] = 5;
		} elseif ($data['releases_list_limit'] < 0) {
			$data['releases_list_limit'] = 0;
		}

		if ($data['slider_min_item'] > 10) {
			$data['slider_min_item'] = 10;
		} elseif ($data['slider_min_item'] < 1) {
			$data['slider_min_item'] = 1;
		}

		if ($data['slider_max_item'] > 100) {
			$data['slider_max_item'] = 100;
		} elseif ($data['slider_max_item'] < 10) {
			$data['slider_max_item'] = 10;
		}

		$alphabet = JFactory::getApplication()->input->post->get('letters', array(), 'array');
		$filter = JFilterInput::getInstance();
		$_alphabet = array();

		if (count($alphabet['movie']) > 0) {
			foreach ($alphabet['movie'] as $key=>$el) {
				foreach ($el as $i=>$val) {
					if ($key == 'lang') {
						$_alphabet['movie_alphabet'][$i][$key] = $filter->clean($val, 'string');
					} elseif ($key == 'letters') {
						$_alphabet['movie_alphabet'][$i][$key] = explode(',', JString::strtoupper(str_replace(' ', '', $filter->clean($val, 'string'))));
					}
				}
			}
		}

		if (count($alphabet['name']) > 0) {
			foreach ($alphabet['name'] as $key=>$el) {
				foreach ($el as $i=>$val) {
					if ($key == 'lang') {
						$_alphabet['name_alphabet'][$i][$key] = $filter->clean($val, 'string');
					} elseif ($key == 'letters') {
						$_alphabet['name_alphabet'][$i][$key] = explode(',', JString::strtoupper(str_replace(' ', '', $filter->clean($val, 'string'))));
					}
				}
			}
		}

		$data = array_merge($data, $_alphabet);

		$params = json_encode($data);
		$rules = json_encode($rules);

		$db->setQuery("UPDATE ".$db->quoteName('#__extensions')
			. "\n SET `params` = '".$db->escape($params)."'"
			. "\n WHERE `element` = 'com_kinoarhiv' AND `type` = 'component'");
		$result = $db->execute();

		if (!$result) {
			$this->setError($db->get('errorMsg'));
			return false;
		}

		if (JFactory::getUser()->authorise('core.admin', 'com_kinoarhiv')) {
			$db->setQuery("UPDATE ".$db->quoteName('#__assets')
				. "\n SET `rules` = '".$rules."'"
				. "\n WHERE `name` = 'com_kinoarhiv' AND `level` = 1 AND `parent_id` = 1");
			$query = $db->execute();
		} else {
			$this->setError(JText::_('COM_KA_NO_ACCESS_RULES_SAVE'));
			return false;
		}

		// Clean the component cache.
		$this->cleanCache('_system');

		return true;
	}
}
