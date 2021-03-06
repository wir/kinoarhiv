<?php defined('_JEXEC') or die;
/**
 * @package     Kinoarhiv.Administrator
 * @subpackage  com_kinoarhiv
 *
 * @copyright   Copyright (C) 2010 Libra.ms. All rights reserved.
 * @license     GNU General Public License version 2 or later
 * @url			http://киноархив.com/
 */

class KAFilesystemHelper {
	/**
	 * Get the folder size in bytes
	 *
	 * @param    string    $path    Filesystem path to a folder.
	 * @param    boolean   $cache   Clear stat cache.
	 *
	 * @return   integer   False on error
	 *
	*/
	static function getFolderSize($path, $cache=true) {
		if (!$path) {
			JLog::add(__METHOD__ . ': ' . JText::_('JLIB_FILESYSTEM_ERROR_DELETE_BASE_DIRECTORY'), JLog::WARNING, 'jerror');

			return false;
		}

		if ($cache) {
			clearstatcache();
		}

		$size = 0;

		if (is_readable($path)) {
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $file) {
				$size += $file->getSize();
			}

			return (int)$size;
		}

		return false;
	}

	/**
	 * Moves a folder and files.
	 *
	 * @param   mixed    $src    The path to the source folder or an array of paths. If $src is array when the folder content move into $dest.
	 * @param   mixed    $dest   The path to the destination folder or an array of paths.
	 * @param   string   $copy   An optional base path to prefix to the file names.
	 *
	 * @return  boolean  True on success.
	 */
	static function move($src, $dest, $copy=false) {
		if (is_array($src)) {
			foreach ($src as $key=>$source) {
				if (is_array($dest)) {
					self::_moveItem($source, $dest[$key], $copy);
				} else {
					self::_moveItem($source, str_replace(basename($dest), '', $dest).basename($source), $copy);
				}
			}
		} else {
			self::_moveItem($src, $dest, $copy);
		}

		return true;
	}

	/**
	 * Moves a folder and files.
	 *
	 * @param   string   $src          The path to the source folder.
	 * @param   string   $dest         The path to the destination folder.
	 * @param   string   $copy         If false when just copy content, copy and remove otherwise.
	 *
	 * @return  boolean  True on success.
	 */
	protected static function _moveItem($src, $dest, $copy=false) {
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		if (!file_exists($dest)) {
			JFolder::create($dest);
		}

		foreach (glob($src.DIRECTORY_SEPARATOR.'*.*', GLOB_NOSORT) as $filename) {
			if (JFile::copy($filename, $dest.DIRECTORY_SEPARATOR.basename($filename))) {
				if (!$copy) {
					if (!JFile::delete($filename)) {
						JLog::add(__METHOD__ . ': ' . JText::sprintf('JLIB_FILESYSTEM_DELETE_FAILED', $filename), JLog::WARNING, 'jerror');
						break;
						return false;
					}
				}
			} else {
				JLog::add(__METHOD__ . ': ' . JText::_('JLIB_FILESYSTEM_ERROR_COPY_FAILED').': '.$filename, JLog::WARNING, 'jerror');
				break;
				return false;
			}
		}

		if (!$copy) {
			if (file_exists($src)) {
				JFolder::delete($src);
			}
		}

		return true;
	}
}
