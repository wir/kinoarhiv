<?php defined('_JEXEC') or die;
/**
 * @package     Kinoarhiv.Administrator
 * @subpackage  com_kinoarhiv
 *
 * @copyright   Copyright (C) 2010 Libra.ms. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @url			http://киноархив.com/
 */

class KinoarhivModelCountry extends JModelForm {
	public function getForm($data = array(), $loadData = true) {
		$form = $this->loadForm('com_kinoarhiv.country', 'country', array('control' => 'form', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	protected function loadFormData() {
		return $this->getItems();
	}

	public function getItems() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$task = $app->input->get('task', '', 'cmd');

		$_id = $app->input->get('id', array(), 'array');
		$id = !empty($_id) ? $_id[0] : $app->input->get('id', null, 'int');

		$db->setQuery("SELECT `id`, `name`, `code`, `language`, `state`"
			. "\n FROM ".$db->quoteName('#__ka_countries')
			. "\n WHERE `id` = ".(int)$id);
		$result = $db->loadObject();

		return $result;
	}

	public function publish($isUnpublish) {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$ids = $app->input->get('id', array(), 'array');
		$state = $isUnpublish ? 0 : 1;

		$db->setQuery("UPDATE ".$db->quoteName('#__ka_countries')." SET `state` = '".(int)$state."' WHERE `id` IN (".implode(',', $ids).")");

		try {
			$db->execute();

			return true;
		} catch(Exception $e) {
			$this->setError($e->getMessage());

			return false;
		}
	}

	public function remove() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$ids = $app->input->get('id', array(), 'array');

		$db->setQuery("DELETE FROM ".$db->quoteName('#__ka_countries')." WHERE `id` IN (".implode(',', $ids).")");

		try {
			$db->execute();

			return true;
		} catch(Exception $e) {
			$this->setError($e->getMessage());

			return false;
		}
	}

	public function save($data) {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$id = $app->input->post->get('id', null, 'int');

		if (empty($id)) {
			$db->setQuery("INSERT INTO ".$db->quoteName('#__ka_countries')." (`id`, `name`, `code`, `language`, `state`)"
				. "\n VALUES ('', '".$db->escape($data['name'])."', '".$data['code']."', '".$data['language']."', '".$data['state']."')");
		} else {
			$db->setQuery("UPDATE ".$db->quoteName('#__ka_countries')
				. "\n SET `name` = '".$db->escape($data['name'])."', `code` = '".$data['code']."', `language` = '".$data['language']."', `state` = '".$data['state']."'"
				. "\n WHERE `id` = ".(int)$id);
		}

		try {
			$db->execute();

			return true;
		} catch(Exception $e) {
			$this->setError($e->getMessage());

			return false;
		}
	}
}
