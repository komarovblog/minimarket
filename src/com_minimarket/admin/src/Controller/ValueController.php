<?php
namespace MiniMarket\Component\Minimarket\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\FormController;

final class ValueController extends FormController
{
    protected $context = 'value';

    /**
     * Keep the List dictionary's attribute selection when using Joomla's
     * standard New toolbar action from a filtered Values list.
     */
    protected function getRedirectToItemAppend($recordId = null, $urlVar = 'id')
    {
        $append = parent::getRedirectToItemAppend($recordId, $urlVar);
        $attributeId = $this->input->getInt('attribute_id', 0);
        if ($attributeId > 0) {
            $append .= '&attribute_id=' . $attributeId;
        }
        return $append;
    }
}
