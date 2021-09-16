<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Common\BaseItemList;
use Icinga\Module\Icingadb\Widget\Grid\ServicegroupGridCell;
use ipl\Web\Url;

class ServicegroupList extends BaseItemList
{
    use NoSubjectLink;
    use ViewMode;

    protected $defaultAttributes = ['class' => 'servicegroup-list item-table'];

    protected function init()
    {
        parent::init();

        $this->getAttributes()->get('class')->removeValue('item-list');
        $this->setDetailUrl(Url::fromPath('icingadb/servicegroup'));
    }

    protected function getItemClass()
    {
        if ($this->getViewMode() === 'minimal') {
            $this->setTag('div');
            $this->setAttributes([
                'class'             => 'servicegroup-list group-grid ' . $this->getViewMode(),
                'data-base-target'  => '_next',
            ]);

            return ServicegroupGridCell::class;
        }

        $this->addAttributes(['class' => $this->getViewMode()]);

        return ServicegroupListItem::class;
    }
}
