<?namespace Intervolga\Migrato\Data\Module\Main;

use Bitrix\Main\SiteTemplateTable;
use Intervolga\Migrato\Data\BaseData;
use Intervolga\Migrato\Data\Link;
use Intervolga\Migrato\Data\Record;

class SiteTemplate extends BaseData
{
	public function getFilesSubdir()
	{
		return '/site/';
	}

	public function getList(array $filter = array())
	{
		$result = array();
		$getList = SiteTemplateTable::getList();
		while ($siteTemplate = $getList->fetch())
		{
			$record = new Record($this);
			$record->setId($this->createId($siteTemplate['ID']));
			$record->setXmlId($this->getMd5($siteTemplate));
			$record->addFieldsRaw(array(
				'CONDITION' => $siteTemplate['CONDITION'],
				'SORT' => $siteTemplate['SORT'],
				'TEMPLATE' => $siteTemplate['TEMPLATE'],
			));

			$link = clone $this->getDependency('SITE');
			$link->setValue(
				Site::getInstance()->getXmlId(
					Site::getInstance()->createId($siteTemplate['SITE_ID'])
				)
			);
			$record->setDependency('SITE', $link);

			$result[] = $record;
		}
		return $result;
	}

	public function getDependencies()
	{
		return array(
			'SITE' => new Link(Site::getInstance()),
		);
	}

	public function getXmlId($id)
	{
		$getList = SiteTemplateTable::getList(array(
			'filter' => array(
				'=ID' => $id->getValue(),
			)
		));

		$siteTemplate = $getList->fetch();
		return $this->getMd5($siteTemplate);
	}

	/**
	 * @param array $tpl
	 *
	 * @return string
	 */
	protected function getMd5(array $tpl)
	{
		$md5 = md5($tpl['CONDITION']);

		return strtolower($tpl['SITE_ID'] . '-' . $tpl['TEMPLATE'] . '-' . implode('-', str_split($md5, 6)));
	}

	public function setXmlId($id, $xmlId)
	{
		// XML ID is autogenerated, cannot be modified
	}

	public function update(Record $record)
	{
		$data = $this->recordToArray($record);
		$id = $record->getId()->getValue();
		$result = SiteTemplateTable::update($id, $data);
		if ($result->getErrorMessages())
		{
			throw new \Exception(implode(', ', $result->getErrorMessages()));
		}
	}

	/**
	 * @param \Intervolga\Migrato\Data\Record $record
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function recordToArray(Record $record)
	{
		$array = array(
			'CONDITION' => $record->getFieldRaw('SORT'),
			'SORT' => $record->getFieldRaw('DEF'),
			'TEMPLATE' => $record->getFieldRaw('ACTIVE'),
		);

		if ($dependency = $record->getDependency('SITE'))
		{
			$linkXmlId = $dependency->getValue();
			$idObject = Site::getInstance()->findRecord($linkXmlId);
			if ($idObject)
			{
				$array['SITE_ID'] = $idObject->getValue();
			}
		}

		return $array;
	}

	public function create(Record $record)
	{
		$data = $this->recordToArray($record);
		$result = SiteTemplateTable::add($data);
		if ($result->getErrorMessages())
		{
			throw new \Exception(implode(', ', $result->getErrorMessages()));
		}
		else
		{
			return $this->createId($result->getId());
		}
	}

	public function delete($xmlId)
	{
		$id = $this->findRecord($xmlId);
		if ($id)
		{
			SiteTemplateTable::delete($id->getValue());
		}
	}
}