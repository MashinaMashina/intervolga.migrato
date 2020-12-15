<?php
namespace Intervolga\Migrato\Tool;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileSystemEntry;

class Code
{
	/**
	 * @return \Bitrix\Main\IO\File[]
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function getTemplateFiles()
	{
		$result = array();
		$root = \Bitrix\Main\Application::getDocumentRoot();
		/**
		 * @var \Bitrix\Main\IO\Directory[] $dirs
		 */
		$dirs = array(
			new Directory($root . '/bitrix/templates/'),
			new Directory($root . '/local/templates/'),
		);
		foreach ($dirs as $dir)
		{
			if ($dir->isExists())
			{
				foreach ($dir->getChildren() as $templateDir)
				{
					if ($templateDir instanceof Directory)
					{
						foreach ($templateDir->getChildren() as $templateFile)
						{
							$checkFiles = array('header.php', 'footer.php');
							if (in_array($templateFile->getName(), $checkFiles))
							{
								$result[] = $templateFile;
							}
						}
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @return \Bitrix\Main\IO\File[]
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function getPublicFiles()
	{
		$root = Application::getDocumentRoot();
		$dir = new Directory($root);
		/**
		 * @var \Bitrix\Main\IO\File[] $check
		 */
		$check = array();
		foreach ($dir->getChildren() as $fileSystemEntry)
		{
			if (!static::isServiceEntry($fileSystemEntry))
			{
				if ($fileSystemEntry instanceof File)
				{
					if (static::isCodeFile($fileSystemEntry))
					{
						$check[] = $fileSystemEntry;
					}
				}
				if ($fileSystemEntry instanceof Directory)
				{
					$check = array_merge($check, static::getFilesRecursive($fileSystemEntry));
				}
			}
		}

		return $check;
	}

	/**
	 * @param \Bitrix\Main\IO\FileSystemEntry $fileSystemEntry
	 * @return bool
	 */
	protected static function isServiceEntry(FileSystemEntry $fileSystemEntry)
	{
		if ($fileSystemEntry->isFile())
		{
			if ($fileSystemEntry->getName() == 'urlrewrite.php')
			{
				return true;
			}
		}
		if ($fileSystemEntry->isDirectory())
		{
			$names = array(
				'bitrix',
				'local',
				'upload',
				'.git',
				'.svn',
			);
			if (in_array($fileSystemEntry->getName(), $names))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param \Bitrix\Main\IO\File $file
	 * @return bool
	 */
	protected static function isCodeFile(File $file)
	{
		return ($file->getExtension() == 'php');
	}

	/**
	 * @param Directory $dir
	 * @return array
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	protected static function getFilesRecursive(Directory $dir)
	{
		$result = array();
		if ($dir->isExists())
		{
			foreach ($dir->getChildren() as $fileSystemEntry)
			{
				if ($fileSystemEntry instanceof File)
				{
					if (static::isCodeFile($fileSystemEntry))
					{
						$result[] = $fileSystemEntry;
					}
				}
				if ($fileSystemEntry instanceof Directory)
				{
					$result = array_merge($result, static::getFilesRecursive($fileSystemEntry));
				}
			}
		}

		return $result;
	}
	
	/*
	 *	Translit working without mbstring.func_overload
	 */
	public static function translit($str, $lang, $params = array())
	{
		static $search = array();

		if(!isset($search[$lang]))
		{
			$mess = IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/js_core_translit.php", $lang, true);
			$trans_from = explode(",", $mess["TRANS_FROM"]);
			$trans_to = explode(",", $mess["TRANS_TO"]);
			foreach($trans_from as $i => $from)
				$search[$lang][$from] = $trans_to[$i];
		}

		$defaultParams = array(
			"max_len" => 100,
			"change_case" => 'L', // 'L' - toLower, 'U' - toUpper, false - do not change
			"replace_space" => '_',
			"replace_other" => '_',
			"delete_repeat_replace" => true,
			"safe_chars" => '',
		);
		foreach($defaultParams as $key => $value)
			if(!array_key_exists($key, $params))
				$params[$key] = $value;

		$len = mb_strlen($str);
		$str_new = '';
		$last_chr_new = '';

		for($i = 0; $i < $len; $i++)
		{
			$chr = mb_substr($str, $i, 1);

			if(preg_match("/[a-zA-Z0-9]/".BX_UTF_PCRE_MODIFIER, $chr) || mb_strpos($params["safe_chars"], $chr) !== false)
			{
				$chr_new = $chr;
			}
			elseif(preg_match("/\\s/".BX_UTF_PCRE_MODIFIER, $chr))
			{
				if (
					!$params["delete_repeat_replace"]
					||
					($i > 0 && $last_chr_new != $params["replace_space"])
				)
					$chr_new = $params["replace_space"];
				else
					$chr_new = '';
			}
			else
			{
				if(array_key_exists($chr, $search[$lang]))
				{
					$chr_new = $search[$lang][$chr];
				}
				else
				{
					if (
						!$params["delete_repeat_replace"]
						||
						($i > 0 && $i != $len-1 && $last_chr_new != $params["replace_other"])
					)
						$chr_new = $params["replace_other"];
					else
						$chr_new = '';
				}
			}

			if($chr_new <> '')
			{
				if($params["change_case"] == "L" || $params["change_case"] == "l")
				{
					$chr_new = mb_strtolower($chr_new);
				}
				elseif($params["change_case"] == "U" || $params["change_case"] == "u")
				{
					$chr_new = mb_strtoupper($chr_new);
				}

				$str_new .= $chr_new;
				$last_chr_new = $chr_new;
			}

			if (mb_strlen($str_new) >= $params["max_len"])
				break;
		}

		return $str_new;
	}
}