<?php
namespace MiniMarket\Component\Minimarket\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;

final class ValueModel extends AdminModel
{
    public function getForm($data = [], $loadData = true)
    {
        return $this->loadForm(
            'com_minimarket.value',
            'value',
            ['control' => 'jform', 'load_data' => $loadData]
        );
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_minimarket.edit.value.data', []);

        if (!empty($data)) {
            return $data;
        }

        $item = $this->getItem();

        if ((int) ($item->id ?? 0) === 0) {
            $attributeId = Factory::getApplication()->input->getInt('attribute_id', 0);

            if ($attributeId > 0) {
                $item->attribute_id = $attributeId;
            }
        }

        return $item;
    }
}
