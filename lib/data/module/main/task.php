<?php

namespace Intervolga\Migrato\Data\Module\Main;

use Bitrix\Main\Localization\Loc;
use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Record;
use Intervolga\Migrato\Data\RecordId;

class Task extends BaseData
{
	protected function configure()
	{
		$this->setEntityNameLoc(Loc::getMessage('INTERVOLGA_MIGRATO.MAIN_TASK'));
		$this->setFilesSubdir('/');
	}

	/**
	 * @param string[] $filter
	 *
	 * @return \Intervolga\Migrato\Data\Record[]
	 */
	public function getList(array $filter = array())
	{
		$dbRes = \CTask::GetList(array(),$filter);
		while ($task = $dbRes->fetch())
		{
			$record = new Record($this);
			$id = $this->createId($task['ID']);
			if($id)
			{
				$record->setId($id);
				$record->setXmlId($this->createXmlId($task));
				$record->addFieldsRaw(array(
					'NAME' => $task['NAME'],
					'DESCRIPTION' => $task['DESCRIPTION'],
					'MODULE_ID' => $task['MODULE_ID'],
					'LETTER' => $task['LETTER'],
					'SYS' => $task['SYS'],
					'BINDING' => $task['BINDING'],
					'TITLE' => $task['TITLE'],
					'DESC' => $task['DESC']
				));
				$result[] = $record;
			}
		}

		return $result;
	}

	private function createXmlId($fields)
	{
		return md5($fields['NAME']);
	}

	public function getXmlId($id)
	{
		$dbRes = \CTask::GetList(array(), array('ID'=>$id));
		while($task = $dbRes->fetch())
		{
			return $this->createXmlId($task);
		}
	}

	/**
	 * @param \Intervolga\Migrato\Data\RecordId $id
	 * @param string $xmlId
	 */
	public function setXmlId($id, $xmlId)
	{
	}

	/**
	 * @param string $xmlId
	 *
	 * @return \Intervolga\Migrato\Data\RecordId|null
	 */
	public function findRecord($xmlId)
	{
		$id = null;
		$dbRes = \CTask::GetList();
		while($task = $dbRes->fetch())
		{
			if(md5($task["NAME"]) == $xmlId)
			{
				$id = $this->createId($task["ID"]);
			}
		}
		return $id;
	}

	/**
	 * @param \Intervolga\Migrato\Data\Record $record
	 *
	 * @return \Intervolga\Migrato\Data\RecordId
	 */
	protected function createInner(Record $record)
	{
		$fields = $record->getFieldsRaw();
		$id = \CTask::Add($fields);
		if(is_int($id))
			return $this->createId($id);
		else
			throw new \Exception(Loc::getMessage('INTERVOLGA_MIGRATO.MAIN_TASK_ADD_ERROR'));
	}


	/**
	 * @param \Intervolga\Migrato\Data\RecordId $id
	 */
	protected function deleteInner(RecordId $id)
	{
		$idVal = $id->getValue();
		if(is_int($idVal))
			\CTask::Delete($idVal);
		else
			throw new \Exception(Loc::getMessage('INTERVOLGA_MIGRATO.MAIN_TASK_DELETE_ERROR'));
	}

	/**
	 * @param mixed $id
	 *
	 * @return \Intervolga\Migrato\Data\RecordId
	 */
	public function createId($id)
	{
		return RecordId::createNumericId($id);
	}

	/**
	 * @param \Intervolga\Migrato\Data\Record $record
	 */
	public function update(Record $record)
	{
		$xmlId = $record->getXmlId();
		$recordId = $this->findRecord($xmlId);
		if($recordId)
		{
			$id = $recordId->getValue();
			$fields = $record->getFieldsRaw();
			if(!\CTask::Update($fields,$id))
				throw new  \Exception(Loc::getMessage('INTERVOLGA_MIGRATO.MAIN_TASK_UPDATE_ERROR'));
		}
	}
}