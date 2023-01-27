<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Module\Icingadb\Common\BaseItemList;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ViewMode;
use Icinga\Module\Icingadb\Redis\VolatileStateResults;
use Icinga\Module\Icingadb\Widget\Notice;
use ipl\Html\HtmlDocument;

abstract class StateList extends BaseItemList
{
    use ViewMode;
    use NoSubjectLink;

    protected function assemble()
    {
        $this->addAttributes(['class' => $this->getViewMode()]);

        parent::assemble();

        if ($this->data instanceof VolatileStateResults && $this->data->isRedisUnavailable()) {
            $this->prependWrapper((new HtmlDocument())->addHtml(new Notice(
                t('Icinga Redis is currently unavailable. The shown information might be outdated.')
            )));
        }
    }
}
