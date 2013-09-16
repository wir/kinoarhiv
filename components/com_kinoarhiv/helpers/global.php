<?php defined('_JEXEC') or die;

class GlobalHelper {
	static function setHeadTags() {
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		$params = $app->getParams('com_kinoarhiv');

		if ($document->getType() != 'html') {
			return;
		}

		$document->addHeadLink(JURI::base().'components/com_kinoarhiv/assets/themes/ui/'.$params->get('ui_theme').'/jquery-ui.min.css', 'stylesheet', 'rel', array('type'=>'text/css'));
		$document->addHeadLink(JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/css/plugin.css', 'stylesheet', 'rel', array('type'=>'text/css'));
		$document->addHeadLink(JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/css/style.css', 'stylesheet', 'rel', array('type'=>'text/css'));

		if ($app->input->get('view', '', 'cmd') == 'movie') {
			$document->addHeadLink(JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/css/editor.css', 'stylesheet', 'rel', array('type'=>'text/css'));
		}
	}

	/**
	 * Strip text to 'limit' of 'chars'
	 *
	 * @param   string  $text		Text for limit.
	 * @param   integer	$limit		Number of chars.
	 * @param   string	$end_chr	End symbol. Default ASCII char code
	 *
	 * @return  string
	 *
	*/
	static function limitText($text, $limit=400, $end_chr='&#8230;') {
		if (JString::strlen($text = $text) <= $limit) return $text;

		return JString::substr($text, 0, $limit - 3).$end_chr;
	}

	/**
	 * Return html structure for message. jQueryUI stylesheets required.
	 *
	 * @param   string  $text	Text for display.
	 * @param   array	$extra  Array of optional elements. $extra['icon'] - the icon type; $extra['type'] - the type of message.
	 * Can be 'highlight', 'error', 'disabled'.
	 *
	 * @return  string
	 *
	*/
	static function showMsg($text, $extra=array()) {
		$icon = !isset($extra['icon']) ? 'info' : $extra['icon'];
		$type = !isset($extra['type']) ? 'highlight' : $extra['type'];

		$html = '<div class="ui-message"><div class="ui-widget">
			<div class="ui-corner-all ui-state-'.$type.'" style="padding: 0pt 0.5em;">
				<div style="margin: 5px ! important;">
					<span class="ui-icon ui-icon-'.$icon.'" style="float: left; margin-right: 0.3em;"></span>
					<span style="overflow: hidden; display: block;">'.$text.'</span>
				</div>
			</div>
		</div></div>';

		return $html;
	}

	/**
	 * Clean text with html.
	 *
	 * @param   string  $text	Text for clean.
	 * @param   string	$tags	String with allowed tags and their attributes.
	 * @param   array	$extra	Addtitional parameters for HTMLPurifier.
	 *
	 * @return  string
	 *
	*/
	static function cleanHTML($text, $tags='', $extra=array()) {
		$app = JFactory::getApplication();
		$params = $app->getParams('com_kinoarhiv');
		$cache_path = JPATH_CACHE.DIRECTORY_SEPARATOR.'kinoarhiv'.DIRECTORY_SEPARATOR.'DefinitionCache'.DIRECTORY_SEPARATOR.'Serializer';
		
		require_once(JPATH_COMPONENT.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.'htmlpurifier'.DIRECTORY_SEPARATOR.'HTMLPurifier.standalone.php');

		$purifier_config = HTMLPurifier_Config::createDefault();
		if (!file_exists($cache_path)) {
			if (!mkdir($cache_path, 0777, true)) {
				self::eventLog('Failed to create definition cache folder at path: "'.$cache_path.'"');
			}
		}
		$purifier_config->set('Cache.SerializerPath', $cache_path);

		if (empty($tags)) {
			$tags = $params->get('html_allowed_tags');
		}
		$purifier_config->set('HTML.Allowed', $tags);

		if (count($extra) > 0) {
			foreach ($extra as $key=>$value) {
				$purifier_config->set($key, $value);
			}
		}

		$purifier = new HTMLPurifier($purifier_config);
		$clean_html = $purifier->purify($text);

		return $clean_html;
	}

	/**
	 * Load all necessary CSS and Javascript for HTML5/Flash player
	 * Supported players: VideoJS
	 *
	*/
	static function loadPlayerAssets() {
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();
		$params = $app->getParams('com_kinoarhiv');

		if ($document->getType() == 'html') {
			$document->addHeadLink(JURI::base().'components/com_kinoarhiv/assets/themes/component/'.$params->get('ka_theme').'/css/vjs-player-default.css', 'stylesheet', 'rel', array('type'=>'text/css'));
			$document->addScript(JURI::base().'components/com_kinoarhiv/assets/js/players/vjs/video.min.js');
			$document->addScript(JURI::base().'components/com_kinoarhiv/assets/js/players/vjs/video.persistvolume.min.js');
			$document->addScriptDeclaration("
			//<![CDATA[
				videojs.options.flash.swf = '".JURI::base()."components/com_kinoarhiv/assets/js/players/vjs/video.swf';
			//]]>");
		}
	}

	/**
	 * Load all necessary CSS and Javascript for HTML5 editor
	 *
	*/
	static function loadEditorAssets() {
		$app = JFactory::getApplication();
		$document = JFactory::getDocument();

		$document->addScript(JURI::base().'components/com_kinoarhiv/assets/js/editor.rules.advanced.min.js');
		$document->addScript(JURI::base().'components/com_kinoarhiv/assets/js/editor.min.js');
	}

	/**
	 * Logger
	 *
	 * @param   string  $message	Text to log.
	*/
	static function eventLog($message, $silent = true) {
		$app = JFactory::getApplication();
		$params = $app->getParams('com_kinoarhiv');
		$document = JFactory::getDocument();
		$uri = JURI::getInstance();

		$doc_params = array(
			'template' => 'system',
			'file' => 'error.php',
			'directory' => JPATH_THEMES,
			'params' => array()
		);

		$message = $message."\t".$uri->current().'?'.$uri->getQuery();

		if ($params->get('logger') == 'syslog') {
			$backtrace = debug_backtrace();
			$stack = '';

			for ($i=0,$n=count($backtrace); $i<$n; $i++) {
				$trace = $backtrace[$i];
				$class = isset($trace['class']) ? $trace['class'] : '';
				$type = isset($trace['type']) ? $trace['type'] : '';
				$stack .= "#".$i." ".$trace['file']."#".$trace['line']." ".$class.$type.$trace['function']."\n";
			}

			openlog('com_kinoarhiv_log', LOG_PID, LOG_DAEMON);
			syslog(LOG_CRIT, $message."\nBacktrace:\n".$stack);
			closelog();

			if (!$silent) {
				echo $document->render(false, $doc_params);
			}
		} else {
			jimport('joomla.log.log');

			JLog::addLogger(
				array(
					'text_file' => 'com_kinoarhiv.errors.php'
				),
				JLog::ALL, 'com_kinoarhiv'
			);

			JLog::add($message, JLog::WARNING, 'com_kinoarhiv');

			if (!$silent) {
				echo $document->render(false, $doc_params);
			}
		}
	}
}