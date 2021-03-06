<?php defined('_JEXEC') or die;
/**
 * @package     Kinoarhiv.Administrator
 * @subpackage  com_kinoarhiv
 *
 * @copyright   Copyright (C) 2010 Libra.ms. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @url			http://киноархив.com/
 */

class KinoarhivControllerMovies extends JControllerLegacy {
	public function add() {
		$this->edit(true);
	}

	public function edit($isNew=false) {
		$view = $this->getView('movies', 'html');
		$model = $this->getModel('movie');
		$view->setModel($model, true);

		if ($isNew === true) {
			$tpl = 'add';
		} elseif ($isNew === false) {
			$tpl = 'edit';
		}

		$view->display($tpl);

		return $this;
	}

	public function save() {
		$this->apply();
	}

	public function save2new() {
		$this->apply();
	}

	public function apply() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.create.movie', 'com_kinoarhiv') && !JFactory::getUser()->authorise('core.edit.movie', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$app = JFactory::getApplication();
		$model = $this->getModel('movie');
		$data = $this->input->post->get('form', array(), 'array');
		$form = $model->getForm($data, false);
		$id = $app->input->get('id', array(0), 'array');

		if (!$form) {
			$app->enqueueMessage($model->getError(), 'error');
			return false;
		}

		$validData = $model->validate($form, $data, 'movie');

		if ($validData === false) {
			$app->setUserState('com_kinoarhiv.movies.global.data', $data);
			$errors = $model->getErrors();

			for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
				if ($errors[$i] instanceof Exception) {
					$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
				} else {
					$app->enqueueMessage($errors[$i], 'warning');
				}
			}

			$this->setRedirect('index.php?option=com_kinoarhiv&controller=movies&task=edit&id[]='.$id[0]);

			return false;
		}

		$result = $model->apply($validData);

		if (!$result) {
			$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()));
			$this->setMessage($this->getError(), 'error');

			$this->setRedirect('index.php?option=com_kinoarhiv&view=movies');

			return false;
		}

		// Set the success message.
		$app->enqueueMessage(JText::_('COM_KA_ITEMS_SAVE_SUCCESS'));

		// Set the redirect based on the task.
		switch ($this->getTask()) {
			case 'apply':
				$this->setRedirect('index.php?option=com_kinoarhiv&controller=movies&task=edit&id[]='.$id[0]);
				break;

			case 'save2new':
				$this->setRedirect('index.php?option=com_kinoarhiv&controller=movies&task=edit&id[]=');
				break;

			case 'save':
			default:
				$this->setRedirect('index.php?option=com_kinoarhiv&view=movies');
				break;
		}

		return true;
	}

	public function saveMovieAccessRules() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.admin', 'com_kinoarhiv') && !JFactory::getUser()->authorise('core.edit.access', 'com_kinoarhiv')) {
			return array('success'=>false, 'message'=>JText::_('JERROR_ALERTNOAUTHOR'));
		}

		$model = $this->getModel('movie');
		$result = $model->saveMovieAccessRules();

		echo json_encode($result);
	}

	public function unpublish() {
		$this->publish(true);
	}

	public function publish($isUnpublish=false) {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.admin', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$model = $this->getModel('movies');
		$result = $model->publish($isUnpublish);

		if ($result === false) {
			$this->setRedirect('index.php?option=com_kinoarhiv&view=movies', JText::_('COM_KA_ITEMS_EDIT_ERROR'), 'error');
			return false;
		}

		// Clean the session data.
		$app = JFactory::getApplication();
		$app->setUserState('com_kinoarhiv.movies.global.data', null);

		$this->setRedirect('index.php?option=com_kinoarhiv&view=movies', $isUnpublish ? JText::_('COM_KA_ITEMS_EDIT_UNPUBLISHED') : JText::_('COM_KA_ITEMS_EDIT_PUBLISHED'));
	}

	public function remove() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.admin', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$model = $this->getModel('movies');
		$result = $model->remove();

		if ($result === false) {
			$this->setRedirect('index.php?option=com_kinoarhiv&view=movies', JText::_('COM_KA_ITEMS_EDIT_ERROR'), 'error');
			return false;
		}

		// Clean the session data.
		$app = JFactory::getApplication();
		$app->setUserState('com_kinoarhiv.movies.global.data', null);

		$this->setRedirect('index.php?option=com_kinoarhiv&view=movies', JText::_('COM_KA_ITEMS_DELETED_SUCCESS'));
	}

	public function cancel() {
		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.admin', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		// Clean the session data.
		$app = JFactory::getApplication();
		$app->setUserState('com_kinoarhiv.movies.global.data', null);

		$this->setRedirect('index.php?option=com_kinoarhiv&view=movies');
	}

	public function getCast() {
		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('movie');
		$result = $model->getCast();

		echo json_encode($result);
	}

	public function deleteCast() {
		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('movie');
		$result = $model->deleteCast();

		echo json_encode($result);
	}

	public function getAwards() {
		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('movie');
		$result = $model->getAwards();

		echo json_encode($result);
	}

	public function getPremieres() {
		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('movie');
		$result = $model->getPremieres();

		echo json_encode($result);
	}

	public function getReleases() {
		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('movie');
		$result = $model->getReleases();

		echo json_encode($result);
	}

	public function deleteRelAwards() {
		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('movie');
		$result = $model->deleteRelAwards();

		echo json_encode($result);
	}

	public function getRates() {
		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$param = $app->input->get('param', '', 'string');
		$id = $app->input->get('id', '', 'string');
		$success = true;
		$message = '';
		$votesum = 0; $votes = 0;

		if ($param == 'imdb_vote' || $param == 'kp_vote') {
			$headers = array(
				'Cookie'=>'last_visit=2013-07-12+20%3A45%3A22; user_country=ua; __utma=168025531.226434608.1325858771.1327766390.1328304295.8; my_perpages=a%3A1%3A%7Bi%3A1%3Bi%3A25%3B%7D; vplayer_user_id=970BD93E2A604E949D285DADE5C8A3DE; tns_was_initialized=true; tns_was_migrated=true; autoFit=1; disable_alert_feature=34432; uid=163145; hideBlocks=33554432; mobile=no; noflash=false; PHPSESSID=98da87e5b4ec4415e72ea6af3b1f6912',
				'Host'=>'www.kinopoisk.ru',
				'Referer'=>'http://www.kinopoisk.ru/',
				'User-Agent'=>'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:22.0) Gecko/20100101 Firefox/22.0'
			);
			$response = GlobalHelper::getRemoteData('http://www.kinopoisk.ru/rating/'.(int)$id.'.xml', $headers, 30, array('curl', 'socket'));

			$xml = new SimpleXMLElement($response->body);
			if ($param == 'kp_vote') {
				$votesum = (string)$xml->kp_rating;
				$votes = (int)$xml->kp_rating['num_vote'];
			} elseif ($param == 'imdb_vote') {
				$votesum = (string)$xml->imdb_rating;
				$votes = (int)$xml->imdb_rating['num_vote'];
			}
		} elseif ($param == 'rt_vote') {
			$headers = array(
				'Cookie'=>'ServerID=1323; instart=8; JSESSIONID=F44F3F597B674EB4E179EA4A4E5F7E51.localhost',
				'Host'=>'www.rottentomatoes.com',
				'Referer'=>'http://www.rottentomatoes.com/',
				'User-Agent'=>'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:22.0) Gecko/20100101 Firefox/22.0'
			);
			$response = GlobalHelper::getRemoteData('http://www.rottentomatoes.com/m/'.$id.'/', $headers, 30, array('curl', 'socket'));

			// Finding the div with rating
			if (preg_match('#<div class="tomato-left">(.*)<div class="(.*)tomato-info"[^>]+>#isU', $response->body, $matches)) {
				$result = '<div>'.$matches[1].'</div>';

				preg_match('#<span class="meter-value" itemprop="ratingValue">(.*)<\/span>#isU', $matches[1], $_votesum);
				preg_match('#<span itemprop="reviewCount">(.*)<\/span>#isU', $matches[1], $_votes);

				if (!isset($_votesum[1])) {
					$message = JText::_('ERROR').': '.JText::_('COM_KA_FIELD_MOVIE_RATES_EMPTY');
					$success = false;
				} else {
					$votesum = (int)$_votesum[1];
					$votes = (int)$_votes[1];
				}
			} else {
				$message = JText::_('ERROR').'! Someting wrong with a parser!';
				$success = false;
			}
		}

		$document->setName('response');
		echo json_encode(array('success'=>$success, 'votesum'=>$votesum, 'votes'=>$votes, 'message'=>$message));
	}

	public function updateRateImg() {
		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.admin', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$document = JFactory::getDocument();
		$app = JFactory::getApplication();
		$params = JComponentHelper::getParams('com_kinoarhiv');
		$votes = $app->input->get('votes', 0, 'int');
		$votesum = $app->input->get('votesum', '', 'string');
		$cmd = $app->input->get('elem', '', 'string');
		$success = true;
		$message = '';

		if ($cmd == 'rt_vote') {
			$text = array(
				0=>array('fontsize'=>10, 'text'=>$votesum.'%', 'color'=>'#333333'),
				1=>array('fontsize'=>7, 'text'=>'( '.$votes.' )', 'color'=>'#555555'),
			);
		} else {
			$text = array(
				0=>array('fontsize'=>10, 'text'=>round($votesum, $params->get('vote_summ_precision'), PHP_ROUND_HALF_UP), 'color'=>'#333333'),
				1=>array('fontsize'=>7, 'text'=>'( '.$votes.' )', 'color'=>'#555555'),
			);
		}

		JLoader::register('ImageHelper', JPATH_COMPONENT.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'image.php');
		$result = ImageHelper::createRateImage($text);

		$document->setMimeEncoding('application/json');
		$document->setName('response');
		echo json_encode(array('success'=>$result, 'message'=>$message));
	}

	public function saveRelNames() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.edit', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('relations');
		$result = $model->saveRelNames();

		echo json_encode($result);
	}

	public function saveRelAwards() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.edit', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('relations');
		$result = $model->saveRelAwards();

		echo json_encode($result);
	}

	public function saveOrder() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		$document = JFactory::getDocument();

		$model = $this->getModel('movies');
		$result = $model->saveOrder();

		$document->setName('response');
		echo json_encode($result);
	}

	public function savePremiere() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.edit', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('premiere');
		$result = $model->savePremiereAjax();

		echo json_encode($result);
	}

	public function saveRelease() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Check if the user is authorized to do this.
		if (!JFactory::getUser()->authorise('core.edit', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('release');
		$result = $model->saveReleaseAjax();

		echo json_encode($result);
	}

	public function deletePremieres() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$document = JFactory::getDocument();
		$document->setName('response');

		$model = $this->getModel('premieres');
		$result = $model->deletePremieres();

		echo json_encode($result);
	}

	public function batch() {
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		$user = JFactory::getUser();

		if (!$user->authorise('core.create', 'com_kinoarhiv') && !$user->authorise('core.edit', 'com_kinoarhiv') && !$user->authorise('core.edit.state', 'com_kinoarhiv')) {
			JFactory::getApplication()->redirect('index.php', JText::_('JERROR_ALERTNOAUTHOR'));
			return false;
		}

		$app = JFactory::getApplication();
		$ids = $app->input->post->get('id', array(), 'array');

		if (count($ids) != 0) {
			$model = $this->getModel('movies');
			$result = $model->batch();

			if ($result === false) {
				$errors = $model->getErrors();

				for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++) {
					if ($errors[$i] instanceof Exception) {
						$app->enqueueMessage($errors[$i]->getMessage(), 'warning');
					} else {
						$app->enqueueMessage($errors[$i], 'warning');
					}
				}

				$this->setRedirect('index.php?option=com_kinoarhiv&view=movies');

				return false;
			}
		}

		$this->setRedirect('index.php?option=com_kinoarhiv&view=movies');
	}
}
