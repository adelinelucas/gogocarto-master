<?php
/**
 * @Author: Sebastian Castro
 * @Date:   2017-03-28 15:29:03
 * @Last Modified by:   Sebastian Castro
 * @Last Modified time: 2018-04-06 09:20:15
 */

namespace App\Admin\Element;

use App\Helper\GoGoHelper;
use App\Admin\GoGoAbstractAdmin;

class ElementAdminAbstract extends GoGoAbstractAdmin
{
    protected $datagridValues = [
      '_page' => 1,               // display the first page (default = 1)
      '_sort_order' => 'DESC',    // reverse order (default = 'ASC')
      '_sort_by' => 'updatedAt',  // name of the ordered field
                                  // (default = the model's id field, if any)
    ];

    protected $optionList;
    protected $optionsChoices = null;

    public function initialize()
    {
        parent::initialize();
    }

    public function getOptionsChoices()
    {
      if ($this->optionsChoices == null) {
        $dm = GoGoHelper::getDmFromAdmin($this);
        $this->optionsChoices = $dm->query('Option')->select('name')->getArray();
      }
      return $this->optionsChoices;
    }
}
