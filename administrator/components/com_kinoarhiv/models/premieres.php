<?php defined('_JEXEC') or die;
/**
 * @package     Kinoarhiv.Administrator
 * @subpackage  com_kinoarhiv
 *
 * @copyright   Copyright (C) 2010 Libra.ms. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @url			http://киноархив.com/
 */

class KinoarhivModelPremieres extends JModelList {
	protected $context = null;

	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'p.id',
				'title', 'm.title',
				'premiere_date', 'p.premiere_date',
				'name', 'c.name',
				'vendor', 'v.company_name', 'v.company_name_intl',
				'ordering', 'p.ordering');
		}

		parent::__construct($config);

		$this->context = strtolower($this->option.'.'.$this->getName().'.premieres');
	}

	protected function populateState($ordering = null, $direction = null) {
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout')) {
			$this->context .= '.' . $layout;
		}

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

		$country = $this->getUserStateFromRequest($this->context . '.filter.country', 'filter_country', '');
		$this->setState('filter.country', $country);

		$vendor = $this->getUserStateFromRequest($this->context . '.filter.vendor', 'filter_vendor', '');
		$this->setState('filter.vendor', $vendor);

		// List state information.
		parent::populateState('p.premiere_date', 'desc');
	}

	protected function getStoreId($id = '') {
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.country');
		$id .= ':' . $this->getState('filter.vendor');

		return parent::getStoreId($id);
	}

	protected function getListQuery() {
		$db = $this->getDBO();

		$query = $db->getQuery(true);

		$query->select(
			$this->getState(
				'list.select',
				'`p`.`id`, `p`.`movie_id`, `p`.`vendor_id`, `p`.`premiere_date`, `p`.`country_id`, `p`.`info`, `p`.`ordering`'
			)
		);
		$query->from($db->quoteName('#__ka_premieres').' AS `p`')
		->select('`m`.`title`, `m`.`year`')
			->leftjoin($db->quoteName('#__ka_movies').' AS `m` ON `m`.`id` = `p`.`movie_id`')
		->select('`v`.`company_name`, `v`.`company_name_intl`')
			->leftjoin($db->quoteName('#__ka_vendors').' AS `v` ON `v`.`id` = `p`.`vendor_id`')
		->select('`c`.`name`, `c`.`code`')
			->leftjoin($db->quoteName('#__ka_countries').' AS `c` ON `c`.`id` = `p`.`country_id`');

		// Filter by country
		$country = $this->getState('filter.country');
		if (is_numeric($country)) {
			$query->where('`p`.`country_id` = ' . (int) $country);
		}

		// Filter by vendor
		$vendor = $this->getState('filter.vendor');
		if (is_numeric($vendor)) {
			$query->where('`p`.`vendor_id` = ' . (int) $vendor);
		}

		$search = $this->getState('filter.search');
		if (!empty($search)) {
			if (stripos($search, 'id:') === 0) {
				$query->where('p.id = ' . (int) substr($search, 3));
			} elseif (stripos($search, 'title:') === 0) {
				$search = trim(substr($search, 6));
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('m.title LIKE ' . $search);
			} elseif (stripos($search, 'country:') === 0) {
				$search = trim(substr($search, 8));

				if (JString::strtolower($search) == JString::strtolower(JText::_('COM_KA_PREMIERE_WORLD')) || $search == 0) {
					$query->where('p.country_id = 0');
				} else {
					$search = $db->quote('%' . $db->escape($search, true) . '%');
					$query->where('c.name LIKE ' . $search);
				}
			} else {
				$search = trim(substr($search, 5));
				$search = $db->quote('%' . $db->escape($search, true) . '%');
				$query->where('p.premiere_date LIKE ' . $search);
			}
		}

		$orderCol = $this->state->get('list.ordering', 'p.premiere_date');
		$orderDirn = $this->state->get('list.direction', 'desc');
		if ($orderCol == 'p.ordering') {
			$orderCol = 'p.ordering ' . $orderDirn . ', p.premiere_date';
		}

		$query->order($db->escape($orderCol . ' ' . $orderDirn . ', m.title ' . $orderDirn));

		return $query;
	}

	public function deletePremieres() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$data = $app->input->post->get('data', array(), 'array');
		$query = true;

		$db->setDebug(true);
		$db->lockTable('#__ka_premieres');
		$db->transactionStart();

		foreach ($data as $key=>$value) {
			$_name = explode('_', $value['name']);
			$item_id = $_name[3];

			$db->setQuery("DELETE FROM ".$db->quoteName('#__ka_premieres')." WHERE `id` = ".(int)$item_id.";");
			$result = $db->execute();

			if ($result === false) {
				$query = false;
				break;
			}
		}

		if ($query === false) {
			$db->transactionRollback();
		} else {
			$db->transactionCommit();
		}

		$db->unlockTables();
		$db->setDebug(false);

		if ($query) {
			$success = true;
			$message = JText::_('COM_KA_ITEMS_DELETED_SUCCESS');
		} else {
			$success = false;
			$message = JText::_('COM_KA_ITEMS_DELETED_ERROR');
		}

		return array('success'=>$success, 'message'=>$message);
	}
}
