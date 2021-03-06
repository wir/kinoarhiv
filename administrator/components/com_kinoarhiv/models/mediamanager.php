<?php defined('_JEXEC') or die;
/**
 * @package     Kinoarhiv.Administrator
 * @subpackage  com_kinoarhiv
 *
 * @copyright   Copyright (C) 2010 Libra.ms. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @url			http://киноархив.com/
 */

class KinoarhivModelMediamanager extends JModelList {
	public function __construct($config = array()) {
		if (empty($config['filter_fields'])) {
			$config['filter_fields'] = array(
				'id', 'g.id',
				'filename', 'g.filename',
				'dimension', 'g.dimension',
				'state', 'g.state',
				'poster_frontpage', 'g.poster_frontpage', 'g.frontpage',
				'access', 'g.access', 'access_level',
				'language', 'g.language');
		}

		parent::__construct($config);
	}

	/**
	 * Method to get the path to a file.
	 *
	 * @param   string   $section	Type of the item. Can be 'movie' or 'name'.
	 * @param   string   $type		Type of the section. Can be 'gallery', 'trailers', 'soundtracks'
	 * @param   string   $tab		Tab number from gallery.
	 * @param   string   $id		The item ID (movie or name).
	 *
	 * @return  string   Absolute filesystem path to a file.
	 *
	 */
	public function getPath($section='', $type='', $tab=0, $id=0) {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$params = JComponentHelper::getParams('com_kinoarhiv');
		$section = empty($section) ? $app->input->get('section', '', 'word') : $section;
		$type = empty($type) ? $app->input->get('type', '', 'word') : $type;
		$tab = empty($tab) ? $app->input->get('tab', 0, 'int') : $tab;
		$id = empty($id) ? $app->input->get('id', 0, 'int') : $id;

		if ($section == 'movie') {
			$table = '#__ka_movies';

			if ($type == 'gallery') {
				if ($tab == 1) {
					$path = $params->get('media_wallpapers_root');
					$folder = 'wallpapers';
				} elseif ($tab == 2) {
					$path = $params->get('media_posters_root');
					$folder = 'posters';
				} elseif ($tab == 3) {
					$path = $params->get('media_scr_root');
					$folder = 'screenshots';
				}
			} elseif ($type == 'trailers') {
				$path = $params->get('media_trailers_root');
				$folder = '';
			}
		} elseif ($section == 'name') {
			$table = '#__ka_names';

			if ($type == 'gallery') {
				if ($tab == 1) {
					$path = $params->get('media_actor_wallpapers_root');
					$folder = 'wallpapers';
				} elseif ($tab == 2) {
					$path = $params->get('media_actor_posters_root');
					$folder = 'posters';
				} elseif ($tab == 3) {
					$path = $params->get('media_actor_photo_root');
					$folder = 'photo';
				}
			}
		}

		$db->setQuery("SELECT `alias` FROM ".$db->quoteName($table)." WHERE `id` = ".(int)$id);
		$alias = $db->loadResult();

		$result = str_replace('\\', '/', $path.DIRECTORY_SEPARATOR.JString::substr($alias, 0, 1).DIRECTORY_SEPARATOR.$id.DIRECTORY_SEPARATOR.$folder);

		return $result;
	}

	public function getAlias($section, $id) {
		$db = $this->getDBO();
		$id = empty($id) ? $app->input->get('id', 0, 'int') : $id;
		$section = empty($section) ? $app->input->get('section', '', 'word') : $section;

		if ($section == 'movie') {
			$table = '#__ka_movies';
		} elseif ($section == 'names') {
			$table = '#__ka_names';
		}

		$db->setQuery("SELECT `alias` FROM ".$db->quoteName($table)." WHERE `id` = ".(int)$id);
		$alias = $db->loadResult();

		if (empty($alias)) {
			if ($section == 'movie') {
				$db->setQuery("SELECT `title` FROM ".$db->quoteName($table)." WHERE `id` = ".(int)$id);
				$result = $db->loadResult();
			} elseif ($section == 'names') {
				$db->setQuery("SELECT `name`, `latin_name` FROM ".$db->quoteName($table)." WHERE `id` = ".(int)$id);
				$result = $db->loadObject();

				if (!empty($result->latin_name)) {
					$result = $result->latin_name;
				} else {
					$result = $result->name;
				}
			}

			$alias = JFilterOutput::stringURLSafe($result);
		}

		return $alias;
	}

	protected function populateState($ordering = null, $direction = null) {
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout')) {
			$this->context .= '.' . $layout;
		}

		$access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', 0, 'int');
		$this->setState('filter.access', $access);

		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);

		// force a language
		$forcedLanguage = $app->input->get('forcedLanguage');
		if (!empty($forcedLanguage))
		{
			$this->setState('filter.language', $forcedLanguage);
			$this->setState('filter.forcedLanguage', $forcedLanguage);
		}

		// List state information.
		parent::populateState('g.id', 'asc');
	}

	public function getListQuery() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$query = $db->getQuery(true);
		$section = $app->input->get('section', '', 'word');
		$type = $app->input->get('type', '', 'word');
		$tab = $app->input->get('tab', 0, 'int');
		$id = $app->input->get('id', 0, 'int');

		if ($section == 'movie') {
			if ($type == 'gallery') {
				$query->select(
					$this->getState(
						'list.select',
						'`g`.`id`, `g`.`filename`, `g`.`dimension`, `g`.`movie_id`, `g`.`poster_frontpage`, `g`.`state`, `m`.`alias`'
					)
				);
				$query->from($db->quoteName('#__ka_movies_gallery').' AS `g`');
				$query->leftJoin($db->quoteName('#__ka_movies').' AS `m` ON `m`.`id` = `g`.`movie_id`');
				$query->where('`g`.`type` = '.$tab.' AND (`g`.`state` = 0 OR `g`.`state` = 1) AND `g`.`movie_id` = '.(int)$id);

				$orderCol = $this->state->get('list.ordering', 'g.id');
				$orderDirn = $this->state->get('list.direction', 'desc');
				$query->order($db->escape($orderCol . ' ' . strtoupper($orderDirn)));
			} elseif ($type == 'trailers') {
				$query->select(
					$this->getState(
						'list.select',
						'`g`.`id`, `g`.`title`, `g`.`embed_code`, `g`.`filename`, `g`.`duration`, `g`.`_subtitles`, `g`.`_chapters`, `g`.`frontpage`, `g`.`state`, `g`.`language`, `g`.`is_movie`'
					)
				);
				$query->from($db->quoteName('#__ka_trailers').' AS `g`');

				// Join over the language
				$query->select(' `l`.`title` AS `language_title`')
					->leftJoin($db->quoteName('#__languages') . ' AS `l` ON `l`.`lang_code` = `g`.`language`');

				// Join over the movie
				$query->select(' `m`.`alias`')
					->leftJoin($db->quoteName('#__ka_movies').' AS `m` ON `m`.`id` = `g`.`movie_id`');

				// Join over the asset groups.
				$query->select(' ag.title AS access_level')
					->leftJoin($db->quoteName('#__viewlevels').' AS ag ON ag.id = g.access');

				$query->where('(`g`.`state` = 0 OR `g`.`state` = 1) AND `g`.`movie_id` = '.(int)$id);

				// Add the list ordering clause.
				$orderCol = $this->state->get('list.ordering', 'g.id');
				$orderDirn = $this->state->get('list.direction', 'desc');

				//sqlsrv change
				if ($orderCol == 'language') {
					$orderCol = 'l.title';
				}
				if ($orderCol == 'access_level') {
					$orderCol = 'ag.title';
				}
				$query->order($db->escape($orderCol . ' ' . $orderDirn));
			}
		} elseif ($section == 'name') {
			if ($type == 'gallery') {
				$query->select(
					$this->getState(
						'list.select',
						'`g`.`id`, `g`.`filename`, `g`.`dimension`, `g`.`name_id`, `g`.`photo_frontpage`, `g`.`state`, `m`.`alias`'
					)
				);
				$query->from($db->quoteName('#__ka_names_gallery').' AS `g`');
				$query->leftJoin($db->quoteName('#__ka_names').' AS `m` ON `m`.`id` = `g`.`name_id`');
				$query->where('`g`.`type` = '.$tab.' AND (`g`.`state` = 0 OR `g`.`state` = 1) AND `g`.`name_id` = '.(int)$id);

				$orderCol = $this->state->get('list.ordering', 'g.id');
				$orderDirn = $this->state->get('list.direction', 'desc');
				$query->order($db->escape($orderCol . ' ' . strtoupper($orderDirn)));
			}
		} else {
			$query = null;
		}

		return $query;
	}

	public function getSoundtracks() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$query = $db->getQuery(true);
		$id = $app->input->get('id', 0, 'int');

		$query->select($this->getState(
				'list.select',
				'`album`.`id`, `album`.`title`, `album`.`alias`, `album`.`year`, `album`.`access`, `album`.`language`, `album`.`state`'
			)
		);
		$query->from($db->quoteName('#__ka_music_albums').' AS `album`');

		// Join over the language
		$query->select(' `l`.`title` AS `language_title`')
			->leftJoin($db->quoteName('#__languages').' AS `l` ON `l`.`lang_code` = `album`.`language`');

		// Join over the asset groups.
		$query->select(' `ag`.`title` AS `access_level`')
			->leftJoin($db->quoteName('#__viewlevels').' AS `ag` ON `ag`.`id` = `album`.`access`');

		$query->where('`album`.`id` IN (SELECT `album_id` FROM '.$db->quoteName('#__ka_music_rel_movies').' WHERE `movie_id` = '.(int)$id.') AND (`album`.`state` = 0 OR `album`.`state` = 1)');
		$db->setQuery($query);
		$result = $db->loadAssocList();

		foreach ($result as $i => $album) {
			$query = $db->getQuery(true);
			$query->select('`t`.*');
			$query->from($db->quoteName('#__ka_music').' AS `t`');
			// Join over the asset groups.
			$query->select(' `ag`.`title` AS `access_level`')
				->leftJoin($db->quoteName('#__viewlevels').' AS `ag` ON `ag`.`id` = `access`');
			$query->where('`t`.`id` IN (SELECT `track_id` FROM '.$db->quoteName('#__ka_music_rel_albums').' WHERE `album_id` = '.(int)$album['id'].')');
			$db->setQuery($query);
			$result[$i]['tracks'] = $db->loadObjectList();
			$result[$i]['tracks'] = (object)$result[$i]['tracks'];
			$result[$i] = (object)$result[$i];
		}

		return $result;
	}

	/**
	 * Method to save image information into DB. Accepted gallery items for movie and poster for trailer.
	 *
	 * @param   binary   $image         Image source.
	 * @param   string   $filename      System filename.
	 * @param   array    $image_sizes   Array with the sizes. array(width, height)
	 * @param   int      $section       Section. (Movie, name, trailer, soundtrack)
	 * @param   int      $item_type     Item type. (Poster or wallpaper or photo or screenshot)
	 * @param   int      $item_id       Item ID.
	 * @param   int      $frontpage     Item published on frontpage.
	 *
	 * @return   array
	 */
	public function saveImageInDB($image=null, $filename, $image_sizes=array(), $section, $item_type=null, $item_id, $frontpage=0) {
		if (empty($section)) {
			return array('success' => false, 'filename' => $filename, 'id' => 0);
		}

		$db = $this->getDBO();
		$query = $db->getQuery(true);
		$image_sizes = (count($image_sizes) == 0) ? array(0=>0, 1=>0) : $image_sizes;
		$dimension = floor($image_sizes[0]).'x'.floor($image_sizes[1]);

		if ($section == 'movie') {
			$query->insert($db->quoteName('#__ka_movies_gallery'), 'id')
				->columns('`id`, `filename`, `dimension`, `movie_id`, `type`, `poster_frontpage`, `state`')
				->values("'', '".$filename."', '".$dimension."', '".(int)$item_id."', '".(int)$item_type."', '".(int)$frontpage."', '1'");
			$db->setQuery($query);

			try {
				$result['success'] = $db->execute();
				$result['filename'] = $filename;
				$result['id'] = $db->insertid();

				// Unpublish all items from frontpage for type of poster and movie and not for a last inserted row.
				if ($frontpage == 1) {
					$query->update($db->quoteName('#__ka_movies_gallery'))
						->set("`poster_frontpage` = '0'")
						->where("`movie_id` = ".(int)$item_id." AND `type` = 2 AND `id` != ".$result['id']);
					$db->setQuery($query);
					$db->execute();
				}
			} catch (Exception $e) {
				$result['success'] = false;
				$result['filename'] = $filename;
				$result['id'] = 0;
				return false;
			}
		} elseif ($section == 'name') {
			$query->insert($db->quoteName('#__ka_names_gallery'), 'id')
				->columns('`id`, `filename`, `dimension`, `name_id`, `type`, `photo_frontpage`, `state`')
				->values("'', '".$filename."', '".$dimension."', '".(int)$item_id."', '".(int)$item_type."', '".(int)$frontpage."', '1'");
			$db->setQuery($query);

			try {
				$result['success'] = $db->execute();
				$result['filename'] = $filename;
				$result['id'] = $db->insertid();

				// Unpublish all items from frontpage for type of photo and name and not for a last inserted row.
				if ($frontpage == 1) {
					$query->update($db->quoteName('#__ka_names_gallery'))
						->set("`photo_frontpage` = '0'")
						->where("`name_id` = ".(int)$item_id." AND `type` = 3 AND `id` != ".$result['id']);
					$db->setQuery($query);
					$db->execute();
				}
			} catch (Exception $e) {
				$result['success'] = false;
				$result['filename'] = $filename;
				$result['id'] = 0;
				return false;
			}
		} elseif ($section == 'trailer') {
			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `screenshot` = '".$filename."' WHERE `id` = ".(int)$item_id);
			$result['success'] = $db->execute();
			$result['filename'] = $filename;
		}

		return $result;
	}

	/**
	 * Method to publish or unpublish posters or trailer on movie info page(not on posters or trailers page)
	 *
	 * @param   int     $action     0 - unpublish from frontpage, 1 - publish poster on frontpage.
	 * @param   int     $type       Item type.
	 * @param   int     $item_id    Item ID.
	 * @param   array   $id         Array of IDs which must be published or unpublished.
	 * @param   string  $section    Movie or name.
	 *
	 * @return   boolean  True on success.
	 */
	public function publishOnFrontpage($action, $type=null, $item_id=0, $id=array(), $section=null) {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$type = $app->input->get('type', $type, 'word');
		$item_id = $app->input->get('id', $item_id, 'int');
		$id = $app->input->get('_id', $id, 'array');
		$section = $app->input->get('section', $section, 'word');

		if ($type == 'gallery') {
			if ($section == 'movie') {
				$table = '#__ka_movies_gallery';
				$pub_col = '`poster_frontpage`';
				$item_col = '`movie_id`';
				$type_num = 2;
			} elseif ($section == 'name') {
				$table = '#__ka_names_gallery';
				$pub_col = '`photo_frontpage`';
				$item_col = '`name_id`';
				$type_num = 3;
			} else {
				$this->setError('Unknown gallery type');
				return false;
			}

			// Reset all values to 0
			$db->setQuery("UPDATE ".$db->quoteName($table)." SET ".$pub_col." = '0' WHERE ".$item_col." = ".(int)$item_id." AND `type` = ".$type_num);
			
			try {
				$db->execute();
			} catch(Exception $e) {
				$this->setError($e->getMessage());

				return false;
			}

			if (!isset($id[0]) || empty($id[0])) {
				$this->setError('Unknown ID');

				return false;
			}

			$db->setQuery("UPDATE ".$db->quoteName($table)." SET ".$pub_col." = '".(int)$action."' WHERE `id` = ".(int)$id[0]);

			try {
				$db->execute();
			} catch(Exception $e) {
				$this->setError($e->getMessage());

				return false;
			}

			return true;
		} elseif ($type == 'trailers') {
			// We need to check if this is the movie to avoid errors when publishing a movie and trailer
			$db->setQuery("SELECT `is_movie` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".(int)$id[0]);
			$is_movie = $db->loadResult();

			if ($is_movie == 0) {
				// Reset all values to 0
				$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `frontpage` = '0' WHERE `movie_id` = ".(int)$item_id." AND `is_movie` = 0");
			} else {
				// Reset all values to 0
				$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `frontpage` = '0' WHERE `movie_id` = ".(int)$item_id." AND `is_movie` = 1");
			}

			try {
				$db->execute();
			} catch(Exception $e) {
				$this->setError($e->getMessage());

				return false;
			}

			if (!isset($id[0]) || empty($id[0])) {
				$this->setError('Unknown ID');
				
				return false;
			}

			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `frontpage` = '".(int)$action."' WHERE `id` = ".(int)$id[0]);

			try {
				$db->execute();
			} catch(Exception $e) {
				$this->setError($e->getMessage());

				return false;
			}

			return true;
		}
	}

	public function publish($action) {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$type = $app->input->get('type', '', 'word');
		$movie_id = $app->input->get('id', 0, 'int');
		$id = $app->input->get('_id', array(), 'array');
		$section = $app->input->get('section', null, 'word');

		if ($type == 'gallery') {
			if ($section == 'movie') {
				$db->setQuery("UPDATE ".$db->quoteName('#__ka_movies_gallery')." SET `state` = '".(int)$action."' WHERE `id` IN (".implode(',', $id).")");
			} elseif ($section == 'name') {
				$db->setQuery("UPDATE ".$db->quoteName('#__ka_names_gallery')." SET `state` = '".(int)$action."' WHERE `id` IN (".implode(',', $id).")");
			} else {
				$this->setError('Unknown gallery type');
				return false;
			}
		} elseif ($type == 'trailers') {
			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `state` = '".(int)$action."' WHERE `id` IN (".implode(',', $id).")");
		}

		try {
			$db->execute();
		} catch(Exception $e) {
			$this->setError($e->getMessage());

			return false;
		}

		return true;
	}

	public function apply($data) {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$params = JComponentHelper::getParams('com_kinoarhiv');
		$movie_id = $app->input->get('id', 0, 'int');
		$trailer_id = $app->input->get('item_id', 0, 'int');
		$type = $app->input->get('type', '', 'word');
		$section = $app->input->get('section', '', 'word');

		if ($section == 'movie') {
			if ($type == 'trailers') {
				if ($trailer_id == 0) {
					$db->setQuery("INSERT INTO ".$db->quoteName('#__ka_trailers')." (`id`, `movie_id`, `title`, `embed_code`, `screenshot`, `urls`, `filename`, `duration`, `_subtitles`, `_chapters`, `frontpage`, `access`, `state`, `language`, `is_movie`)"
						. "\n VALUES ('', '".$movie_id."', '".$db->escape($data['title'])."', '".$db->escape($data['embed_code'])."', '', '".$db->escape($data['urls'])."', '{}', '', '{}', '{}', '".(int)$data['frontpage']."', '".(int)$data['access']."', '".(int)$data['state']."', '".$data['language']."', '".(int)$data['is_movie']."')");

					try {
						$db->execute();
						return $db->insertid();
					} catch (Exception $e) {
						$this->setError($e->getMessage());
						return false;
					}
				} else {
					$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')
						. "\n SET `title` = '".$db->escape($data['title'])."', `embed_code` = '".$data['embed_code']."', `urls` = '".$data['urls']."', `frontpage` = '".(int)$data['frontpage']."', `access` = '".(int)$data['access']."', `state` = '".(int)$data['state']."', `language` = '".$data['language']."', `is_movie` = '".$data['is_movie']."'"
						. "\n WHERE `id` = ".(int)$trailer_id);

					try {
						$db->execute();
					} catch (Exception $e) {
						$this->setError($e->getMessage());
						return false;
					}
				}
			}
		}

		return true;
	}

	public function remove() {
		jimport('joomla.filesystem.file');

		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$params = JComponentHelper::getParams('com_kinoarhiv');
		$item_id = $app->input->get('id', 0, 'int');
		$ids = $app->input->get('_id', array(), 'array');
		$section = $app->input->get('section', '', 'cmd');
		$type = $app->input->get('type', '', 'cmd');
		$tab = $app->input->get('tab', 0, 'int');
		$query = true;

		if ($section == 'movie') {
			if ($type == 'gallery') {
				if (empty($ids[0])) {
					echo JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
					return false;
				}

				$db->setQuery("SELECT `id`, `filename`"
					. "\n FROM ".$db->quoteName('#__ka_movies_gallery')
					. "\n WHERE `id` IN (".implode(',', $ids).")");
				try {
					$files_obj = $db->loadObjectList();

					if (count($files_obj) == 0) {
						echo JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
						return false;
					}
				} catch(Exception $e) {
					$this->setError($e->getMessage());
					return false;
				}

				$db->setDebug(true);
				$db->lockTable('#__ka_movies_gallery');
				$db->transactionStart();

				$path = $this->getPath('movie', 'gallery', $tab, $item_id).'/';
				foreach ($files_obj as $file) {
					JFile::delete($path.$file->filename);
					JFile::delete($path.'thumb_'.$file->filename);

					$db->setQuery("DELETE FROM ".$db->quoteName('#__ka_movies_gallery')." WHERE `id` = ".(int)$file->id.";");
					$result = $db->execute();

					if ($result === false) {
						$query = false;
						break;
					}
				}

				if ($query === false) {
					$db->transactionRollback();
					$this->setError('Commit failed!');
				} else {
					$db->transactionCommit();
				}

				$db->unlockTables();
				$db->setDebug(false);
			} elseif ($type == 'trailers') {
				if (empty($ids[0])) {
					echo JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
					return false;
				}

				$db->setQuery("SELECT `id`, `screenshot`, `filename`, `_subtitles`, `_chapters`"
					. "\n FROM ".$db->quoteName('#__ka_trailers')
					. "\n WHERE `id` IN (".implode(',', $ids).")");
				try {
					$rows = $db->loadObjectList();

					if (count($rows) == 0) {
						echo JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
						return false;
					}
				} catch(Exception $e) {
					$this->setError($e->getMessage());
					return false;
				}

				$path = $this->getPath('movie', 'trailers', 0, $item_id);
				$db->setDebug(true);
				$db->lockTable('#__ka_trailers');
				$db->transactionStart();

				foreach ($rows as $row) {
					if (!empty($row->screenshot)) {
						JFile::delete($path.$row->screenshot);
					}

					$video = json_decode($row->filename, true);
					if (count($video) > 0) {
						foreach ($video as $file) {
							JFile::delete($path.$file['src']);
						}
					}

					$subtitles = json_decode($row->_subtitles, true);
					if (count($subtitles) > 0) {
						foreach ($subtitles as $file) {
							JFile::delete($path.$file['file']);
						}
					}

					$chapters = json_decode($row->_chapters, true);
					if (count($chapters) > 0) {
						JFile::delete($path.$chapters['file']);
					}

					$db->setQuery("DELETE FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".(int)$row->id.";");
					$result = $db->execute();

					if ($result === false) {
						$query = false;
						break;
					}
				}

				if ($query === false) {
					$db->transactionRollback();
					$this->setError('Commit failed!');
				} else {
					$db->transactionCommit();
				}

				$db->unlockTables();
				$db->setDebug(false);
			}
		} elseif ($section == 'name') {
			if ($type == 'gallery') {
				if (empty($ids[0])) {
					echo JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
					return false;
				}

				$db->setQuery("SELECT `id`, `filename`"
					. "\n FROM ".$db->quoteName('#__ka_names_gallery')
					. "\n WHERE `id` IN (".implode(',', $ids).")");
				try {
					$files_obj = $db->loadObjectList();

					if (count($files_obj) == 0) {
						echo JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
						return false;
					}
				} catch(Exception $e) {
					$this->setError($e->getMessage());
					return false;
				}

				$db->setDebug(true);
				$db->lockTable('#__ka_names_gallery');
				$db->transactionStart();

				$path = $this->getPath('name', 'gallery', $tab, $item_id).'/';
				foreach ($files_obj as $file) {
					JFile::delete($path.$file->filename);
					JFile::delete($path.'thumb_'.$file->filename);

					$db->setQuery("DELETE FROM ".$db->quoteName('#__ka_names_gallery')." WHERE `id` = ".(int)$file->id.";");
					$result = $db->execute();

					if ($result === false) {
						$query = false;
						break;
					}
				}

				if ($query === false) {
					$db->transactionRollback();
					$this->setError('Commit failed!');
				} else {
					$db->transactionCommit();
				}

				$db->unlockTables();
				$db->setDebug(false);
			}
		} else {
			$this->setError(JText::_('COM_KA_ITEMS_DELETED_ERROR'));
			return false;
		}
	}

	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false) {
		// Handle the optional arguments.
		$options['control'] = JArrayHelper::getValue($options, 'control', false);

		// Create a signature hash.
		$hash = md5($source . serialize($options));

		// Check if we can use a previously loaded form.
		if (isset($this->_forms[$hash]) && !$clear) {
			return $this->_forms[$hash];
		}

		// Get the form.
		JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
		JForm::addFieldPath(JPATH_COMPONENT . '/models/fields');

		try {
			$form = JForm::getInstance($name, $source, $options, false, $xpath);

			if (isset($options['load_data']) && $options['load_data']) {
				// Get the data for the form.
				$data = $this->loadFormData();
			} else {
				$data = array();
			}

			// Allow for additional modification of the form, and events to be triggered.
			// We pass the data because plugins may require it.
			$this->preprocessForm($form, $data);

			// Load the data into the form after the plugins have operated.
			$form->bind($data);

		} catch (Exception $e) {
			$this->setError($e->getMessage());
			return false;
		}

		// Store the form for later.
		$this->_forms[$hash] = $form;

		return $form;
	}

	protected function preprocessForm(JForm $form, $data, $group = 'content') {
		// Import the appropriate plugin group.
		JPluginHelper::importPlugin($group);

		// Get the dispatcher.
		$dispatcher = JEventDispatcher::getInstance();

		// Trigger the form preparation event.
		$results = $dispatcher->trigger('onContentPrepareForm', array($form, $data));

		// Check for errors encountered while preparing the form.
		if (count($results) && in_array(false, $results, true)) {
			// Get the last error.
			$error = $dispatcher->getError();

			if (!($error instanceof Exception)) {
				throw new Exception($error);
			}
		}
	}

	public function getForm($data = array(), $loadData = true) {
		$form = $this->loadForm('com_kinoarhiv.mediamanager', 'mediamanager', array('control' => 'form', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	protected function loadFormData() {
		return $this->getItem();
	}

	public function getItems() {
		$items = parent::getItems();
		$app = JFactory::getApplication();

		if ($app->isSite()) {
			$user = JFactory::getUser();
			$groups = $user->getAuthorisedViewLevels();

			for ($x = 0, $count = count($items); $x < $count; $x++) {
				//Check the access level. Remove articles the user shouldn't see
				if (!in_array($items[$x]->access, $groups)) {
					unset($items[$x]);
				}
			}
		}

		return $items;
	}

	public function getItem($pk = null) {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$query = $db->getQuery(true);
		$id = $app->input->get('item_id', 0, 'int');

		$query->select('`g`.`id`, `g`.`movie_id`, `g`.`title`, `g`.`embed_code`, `g`.`screenshot`, `g`.`urls`, `g`.`filename`, `g`.`duration`, `g`.`_subtitles`, `g`.`_chapters`, `g`.`frontpage`, `g`.`access`, `g`.`state`, `g`.`language`, `g`.`is_movie`');
		$query->from($db->quoteName('#__ka_trailers').' AS `g`');

		$query->select(' `m`.`alias` AS `movie_alias`')
			->leftJoin($db->quoteName('#__ka_movies').' AS `m` ON `m`.`id` = `g`.`movie_id`');

		$query->select(' `l`.`title` AS `language_title`')
			->leftJoin($db->quoteName('#__languages') . ' AS `l` ON `l`.`lang_code` = `g`.`language`');

		$query->select(' ag.title AS access_level')
			->leftJoin('#__viewlevels AS ag ON ag.id = g.access');

		$query->where('`g`.`id` = '.$id);

		$db->setQuery($query);
		$result = $db->loadObject();

		return $result;
	}

	public function saveOrderTrailerVideofile() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$item_id = $app->input->get('item_id', 0, 'int');
		$items = $app->input->get('ord', array(), 'array');
		$success = true;
		$message = '';

		$db->setQuery("SELECT `filename` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".$item_id);
		$result = $db->loadResult();

		if (empty($result)) {
			return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
		}

		$result_arr = json_decode($result, true);
		$new_arr = (object)array();

		foreach ($items as $new_index=>$old_index) {
			foreach ($result_arr as $value) {
				$new_arr->$new_index = $result_arr[$old_index];
			}
		}

		$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `filename` = '".json_encode($new_arr)."' WHERE `id` = ".(int)$item_id);
		$query = $db->execute();

		if ($query !== true) {
			return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
		}

		return json_encode(array('success'=>$success, 'message'=>$message));
	}

	public function saveDefaultTrailerSubtitlefile() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$item_id = $app->input->get('item_id', 0, 'int');
		$id = $app->input->get('default', 0, 'int'); // Item ID in array of subtitles which should be default
		$success = true;
		$message = '';

		$db->setQuery("SELECT `_subtitles` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".$item_id);
		$result = $db->loadResult();

		if (empty($result)) {
			return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
		}

		$result_arr = json_decode($result);

		foreach ($result_arr as $key=>$value) {
			$result_arr->$key->default = ($key != $id) ? (bool)false : (bool)true;
		}

		$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `_subtitles` = '".json_encode($result_arr)."' WHERE `id` = ".(int)$item_id);
		$query = $db->execute();

		if ($query !== true) {
			return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
		}

		return json_encode(array('success'=>$success, 'message'=>$message));
	}

	public function saveOrderTrailerSubtitlefile() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$item_id = $app->input->get('item_id', 0, 'int');
		$items = $app->input->get('cord', array(), 'array');
		$success = true;
		$message = '';

		$db->setQuery("SELECT `_subtitles` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".$item_id);
		$result = $db->loadResult();

		if (empty($result)) {
			return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
		}

		$result_arr = json_decode($result, true);
		$new_arr = (object)array();

		foreach ($items as $new_index=>$old_index) {
			foreach ($result_arr as $value) {
				$new_arr->$new_index = $result_arr[$old_index];
			}
		}

		$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `_subtitles` = '".json_encode($new_arr)."' WHERE `id` = ".(int)$item_id);
		$query = $db->execute();

		if ($query !== true) {
			return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
		}

		return json_encode(array('success'=>$success, 'message'=>$message));
	}

	/**
	 * Save info about chapter file into DB
	 *
	 * @param	string	$file		  	Filename
	 * @param	int		$trailer_id 	ID of the trailer
	 * @param	int		$movie_id 		ID of the movie
	 *
	 * @return	mixed	Last insert ID on INSERT or true on UPDATE
	 */
	public function saveChapters($file, $trailer_id, $movie_id) {
		$db = $this->getDBO();

		$db->setQuery("SELECT COUNT(`id`) FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".(int)$trailer_id);
		$total = $db->loadResult();

		$chapters = array('file'=>$file);

		if ($total == 0) {
			$db->setQuery("INSERT INTO ".$db->quoteName('#__ka_trailers')." (`id`, `movie_id`, `title`, `embed_code`, `screenshot`, `filename`, `duration`, `_subtitles`, `_chapters`, `frontpage`, `access`, `state`, `language`, `is_movie`)"
				. "\n VALUES ('', '".(int)$movie_id."', '', '', '', '{}', '00:00:00', '{}', '".$chapters."', '0', '1', '0', 'language', '0')");
			$query = $db->execute();

			return $query ? (int)$db->insertid() : false;
		} else {
			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `_chapters` = '".json_encode($chapters)."' WHERE `id` = ".(int)$trailer_id);
			$query = $db->execute();

			return $query ? true : false;
		}
	}

	public function getSubtitleEdit() {
		JLoader::register('KALanguage', JPATH_COMPONENT.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'language.php');
		$language = new KALanguage();

		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$lang_list = $language::listOfLanguages();
		$trailer_id = $app->input->get('trailer_id', 0, 'int');
		$subtitle_id = $app->input->get('subtitle_id', 0, 'int');

		$db->setQuery("SELECT `_subtitles` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".(int)$trailer_id);
		$result = $db->loadResult();

		$subtl_obj = json_decode($result);

		return array(
			'langs'=>$lang_list,
			'lang_code'=>$subtl_obj->$subtitle_id->lang_code,
			'lang'=>$subtl_obj->$subtitle_id->lang,
			'is_default'=>$subtl_obj->$subtitle_id->default,
			'trailer_id'=>$trailer_id,
			'subtitle_id'=>$subtitle_id
		);
	}

	public function saveSubtitles($edit=false, $file='', $trailer_id, $movie_id=0, $subtitle_id=null) {
		jimport('joomla.filesystem.file');
		JLoader::register('KALanguage', JPATH_COMPONENT.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'language.php');
		$language = new KALanguage();
		$lang_list = $language::listOfLanguages();

		$app = JFactory::getApplication();
		$db = $this->getDBO();

		$db->setQuery("SELECT `_subtitles` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".(int)$trailer_id);
		$result = $db->loadResult();

		if ($edit === true) {
			$subtl_obj = json_decode($result);
			$lang_data = json_decode($app->input->get('language', '', 'string'));
			$default = $app->input->get('default', 'false', 'string');
			$desc = $app->input->get('desc', '', 'string');
			$desc = $desc != '' ? ' '.$desc : '';

			if (isset($subtl_obj->$subtitle_id)) {
				if ($default == 'true') {
					// Set to false all 'default' flags
					foreach ($subtl_obj as $key=>$subtl) {
						$subtl_obj->$key->default = false;
					}

					$subtl_obj->$subtitle_id->default = true;
				}

				$subtl_obj->$subtitle_id->lang_code = $lang_data->lang_code;
				$subtl_obj->$subtitle_id->lang = $lang_data->lang.$desc;

				$alias = $this->getAlias('movie', $movie_id);
				$rn_dest_dir = $this->getPath('movie', 'trailers', 0, $movie_id);
				$old_filename = $rn_dest_dir.$subtl_obj->$subtitle_id->file;
				$ext = JFile::getExt($old_filename);
				$rn_filename = $alias.'-'.$trailer_id.'.subtitles.'.$lang_data->lang_code.'.'.$ext;
				$subtl_obj->$subtitle_id->file = $rn_filename;

				rename($old_filename, $rn_dest_dir.$rn_filename);
			}

			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `_subtitles` = '".$db->escape(json_encode($subtl_obj))."' WHERE `id` = ".(int)$trailer_id);
			$query = $db->execute();

			return $query ? true : false;
		} else {
			$subtl_arr = json_decode($result, true);

			// On 'else' condition we do nothing because no information about trailer exists in DB. In this situation files will be successfully uploaded, but not saved in DB.
			if (!empty($trailer_id)) {
				if (preg_match('#subtitles\.(.*?)\.#si', $file, $matches)) {
					$lang_code = strtolower($matches[1]);
				}

				// Uncomment block below if you want to check for duplicate languages.
				// Checking if lang allready exists and return false.
				/*$lang_exists = false;
				foreach ($subtl_arr as $k=>$v) {
					if ($v['lang_code'] == $lang_code) {
						$lang_exists = true;
						return;
					}
				}

				if ($lang_exists) {
					return false;
				}*/

				$subtl_arr[] = array(
					'default'=> false,
					'lang_code'=>$lang_code,
					'lang'=>$lang_list[$lang_code],
					'file'=>$file
				);

				$subtl_obj = JArrayHelper::toObject($subtl_arr);
				$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `_subtitles` = '".$db->escape(json_encode($subtl_obj))."' WHERE `id` = ".(int)$trailer_id);
				$query = $db->execute();
			}
		}
	}

	public function create_screenshot() {
		jimport('joomla.filesystem.file');
		JLoader::register('KAMedia', JPATH_COMPONENT.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'media.php');
		$media = KAMedia::getInstance();
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$id = $app->input->get('id', 0, 'int');
		$trailer_id = $app->input->get('item_id', 0, 'int');
		$time = $app->input->get('time', '', 'string');

		$db->setQuery("SELECT `tr`.`screenshot`, `tr`.`filename`, `m`.`alias`"
			. "\n FROM ".$db->quoteName('#__ka_trailers')." AS `tr`"
			. "\n LEFT JOIN ".$db->quoteName('#__ka_movies')." AS `m` ON `m`.`id` = `tr`.`movie_id`"
			. "\n WHERE `tr`.`id` = ".(int)$trailer_id);
		$result = $db->loadObject();
		$files = json_decode($result->filename, true);

		$data = array(
			'folder' 		=> $this->getPath('movie', 'trailers', 0, $id),
			'screenshot' 	=> $result->screenshot,
			'filename'		=> $files[0]['src'],
			'time' 			=> $time
		);

		if ($time != '00:00:00.000') {
			if (preg_match('#^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])\.[0-1][0-9][0-9]?$#', $time)) {
				$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `screenshot` = '".JFile::getExt($files[0]['src']).".png' WHERE `id` = ".(int)$trailer_id);
				$query = $db->execute();

				$output = $media->createScreenshot($data);
				return json_encode(array('file'=>$output[0], 'output'=>$output[1]));
			}
		} else {
			return 'error:'.JText::_('COM_KA_TRAILERS_VIDEO_SCREENSHOT_CREATE_TIME_ERR');
		}
	}

	public function saveVideo($file='', $trailer_id, $movie_id) {
		JLoader::register('KAMedia', JPATH_COMPONENT.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'media.php');
		$media = KAMedia::getInstance();
		$app = JFactory::getApplication();
		$db = $this->getDBO();

		$db->setQuery("SELECT `filename` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".(int)$trailer_id);
		$result = $db->loadResult();

		$result_arr = json_decode($result, true);

		// If not empty and items more than 0 when add to array and store
		if (!empty($result) && count($result_arr) > 0) {
			// Checking if file allready exists in DB
			$file_exists = false;
			foreach ($result_arr as $k=>$v) {
				if ($v['src'] == $file) {
					$file_exists = true;
					return;
				}
			}

			if ($file_exists) {
				return false;
			}
			// end

			$files_arr = $result_arr;
			$mime_type = $media->detectMime($this->getPath('movie', 'trailers', 0, $movie_id).$file);
			$video_info = json_decode($media->getVideoInfo($this->getPath('movie', 'trailers', 0, $movie_id).$file));
			$duration = $media->getVideoDuration($this->getPath('movie', 'trailers', 0, $movie_id).$file, true);
			$files_arr[] = array(
				'src'		=> $file,
				'type'		=> $mime_type,
				'resolution'=> $video_info->streams[0]->width.'x'.$video_info->streams[0]->height
			);

			$new_obj = JArrayHelper::toObject($files_arr);
			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `filename` = '".json_encode($new_obj)."', `resolution` = '".$video_info->streams[0]->width.'x'.$video_info->streams[0]->height."', `dar` = '".$video_info->streams[0]->display_aspect_ratio."', `duration` = '".$duration."' WHERE `id` = ".(int)$trailer_id);
			$query = $db->execute();
		} else {
			$mime_type = $media->detectMime($this->getPath('movie', 'trailers', 0, $movie_id).$file);
			$video_info = json_decode($media->getVideoInfo($this->getPath('movie', 'trailers', 0, $movie_id).$file));
			$duration = $media->getVideoDuration($this->getPath('movie', 'trailers', 0, $movie_id).$file, true);

			$files_arr = array(
				0 => array(
					'src'	=> $file,
					'type'	=> $mime_type,
					'resolution'=> $video_info->streams[0]->width.'x'.$video_info->streams[0]->height
				)
			);

			$new_obj = JArrayHelper::toObject($files_arr);

			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `filename` = '".json_encode($new_obj)."', `resolution` = '".$video_info->streams[0]->width.'x'.$video_info->streams[0]->height."', `dar` = '".$video_info->streams[0]->display_aspect_ratio."', `duration` = '".$duration."' WHERE `id` = ".(int)$trailer_id);
			$query = $db->execute();
		}
	}

	public function removeTrailerFiles() {
		$app = JFactory::getApplication();
		$db = $this->getDBO();
		$params = JComponentHelper::getParams('com_kinoarhiv');
		$id = $app->input->get('id', 0, 'int');
		$item_id = $app->input->get('item_id', 0, 'int');
		$filename = $app->input->get('file', '', 'string');
		$type = $app->input->get('type', '', 'word');
		$success = true;
		$message = '';

		if ($type == '') {
			return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
		}

		if ($type == 'video') {
			$db->setQuery("SELECT `filename` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".$item_id);
			$result = $db->loadResult();

			if (empty($result)) {
				return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
			}

			$result_arr = json_decode($result, true);
			$new_arr = array();

			foreach ($result_arr as $k=>$v) {
				if ($v['src'] != $filename) {
					$new_arr[] = $v;
				}
			}

			$new_arr = JArrayHelper::toObject($new_arr);

			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `filename` = '".json_encode($new_arr)."' WHERE `id` = ".(int)$item_id);

			try {
				$db->execute();
			} catch(Exception $e) {
				return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
			}

			// Removing file
			if (file_exists($this->getPath('movie', 'trailers', 0, $id).$filename) && @unlink($this->getPath('movie', 'trailers', 0, $id).$filename) !== true) {
				$success = false;
				$message = JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
			}
		} elseif ($type == 'subtitle' || $type == 'subtitles') {
			$db->setQuery("SELECT `_subtitles` FROM ".$db->quoteName('#__ka_trailers')." WHERE `id` = ".$item_id);
			$result = $db->loadResult();

			if (empty($result)) {
				return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
			}

			$result_arr = json_decode($result, true);

			if ($app->input->get('all', 0, 'int') == 0) {
				$new_arr = array();

				foreach ($result_arr as $k=>$v) {
					if ($v['file'] != $filename) {
						$new_arr[] = $v;
					}
				}

				$new_arr = JArrayHelper::toObject($new_arr);

				$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `_subtitles` = '".json_encode($new_arr)."' WHERE `id` = ".(int)$item_id);
				$query = $db->execute();

				if ($query !== true) {
					return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
				}

				// Removing file
				if (file_exists($this->getPath('movie', 'trailers', 0, $id).$filename) && @unlink($this->getPath('movie', 'trailers', 0, $id).$filename) !== true) {
					$success = false;
					$message = JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
				}
			} else {
				foreach ($result_arr as $val) {
					if (file_exists($this->getPath('movie', 'trailers', 0, $id).$val['file']) && @unlink($this->getPath('movie', 'trailers', 0, $id).$val['file']) !== true) {
						$success = false;
						$message .= JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
					}
				}

				$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `_subtitles` = '{}' WHERE `id` = ".(int)$item_id);
				$query = $db->execute();

				if ($query !== true) {
					$success = false;
					$message .= JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
				}
			}
		} elseif ($type == 'chapter' || $type == 'chapters') {
			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `_chapters` = '{}' WHERE `id` = ".(int)$item_id);
			$query = $db->execute();

			if ($query !== true) {
				return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
			}

			// Removing file
			if (file_exists($this->getPath('movie', 'trailers', 0, $id).$filename) && @unlink($this->getPath('movie', 'trailers', 0, $id).$filename) !== true) {
				$success = false;
				$message = JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
			}
		} elseif ($type == 'image' || $type == 'images') {
			$db->setQuery("UPDATE ".$db->quoteName('#__ka_trailers')." SET `screenshot` = '' WHERE `id` = ".(int)$item_id);
			$query = $db->execute();

			if ($query !== true) {
				return json_encode(array('success'=>false, 'message'=>JText::_('JERROR_AN_ERROR_HAS_OCCURRED')));
			}

			// Removing file
			if (file_exists($this->getPath('movie', 'trailers', 0, $id).$filename) && @unlink($this->getPath('movie', 'trailers', 0, $id).$filename) !== true) {
				$success = false;
				$message = JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
			}
		}

		return json_encode(array('success'=>$success, 'message'=>$message));
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 * @since   12.2
	 */
	public function validate($form, $data, $group = null) {
		// Filter and validate the form data.
		$data = $form->filter($data);
		$return = $form->validate($data, $group);

		// Check for an error.
		if ($return instanceof Exception) {
			$this->setError($return->getMessage());
			return false;
		}

		// Check the validation results.
		if ($return === false) {
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message) {
				$this->setError($message);
			}

			return false;
		}

		return $data;
	}

	/**
	 * Method to get item title.
	 *
	 * @param   string   $section   The section for searching. Can be 'movie', 'name', 'trailer', 'soundtrack'
	 * @param   int      $id        Item ID.
	 *
	 * @return  mixed  Object with the data. False on error.
	 *
	 */
	public function getItemTitle($section=null, $id=null) {
		$db = $this->getDBO();
		$app = JFactory::getApplication();
		$section = empty($section) ? $app->input->get('section', '', 'word') : $section;
		$id = empty($id) ? $app->input->get('id', 0, 'int') : $id;

		if ($section == 'movie') {
			$db->setQuery("SELECT `title` FROM ".$db->quoteName('#__ka_movies')." WHERE `id` = ".(int)$id);
			$data = $db->loadResult();
		} elseif ($section == 'name') {
			$db->setQuery("SELECT `name`, `latin_name` FROM ".$db->quoteName('#__ka_names')." WHERE `id` = ".(int)$id);
			$result = $db->loadObject();
			$data = '';

			if (!empty($result->name)) {
				$data .= $result->name;
			}
			if (!empty($result->name) && !empty($result->latin_name)) {
				$data .= ' / ';
			}
			if (!empty($result->latin_name)) {
				$data .= $result->latin_name;
			}
		} else {
			$this->setError('Unknown section type!');
			return false;
		}

		return $data;
	}
}
