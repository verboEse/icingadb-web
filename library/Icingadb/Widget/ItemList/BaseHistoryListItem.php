<?php

/* Icinga DB Web | (c) 2020 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Widget\ItemList;

use Icinga\Date\DateFormatter;
use Icinga\Module\Icingadb\Common\HostLink;
use Icinga\Module\Icingadb\Common\HostStates;
use Icinga\Module\Icingadb\Common\Icons;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Common\MarkdownLine;
use Icinga\Module\Icingadb\Common\NoSubjectLink;
use Icinga\Module\Icingadb\Common\ServiceLink;
use Icinga\Module\Icingadb\Common\ServiceStates;
use Icinga\Module\Icingadb\Model\History;
use Icinga\Module\Icingadb\Util\PluginOutput;
use Icinga\Module\Icingadb\Widget\BaseListItem;
use Icinga\Module\Icingadb\Widget\CheckAttempt;
use Icinga\Module\Icingadb\Widget\PluginOutputContainer;
use Icinga\Module\Icingadb\Widget\StateChange;
use Icinga\Module\Icingadb\Widget\TimeAgo;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

abstract class BaseHistoryListItem extends BaseListItem
{
    use HostLink;
    use NoSubjectLink;
    use ServiceLink;

    /** @var History */
    protected $item;

    /** @var HistoryList */
    protected $list;

    protected function init()
    {
        $this->setNoSubjectLink($this->list->getNoSubjectLink());
    }

    abstract protected function getStateBallSize();

    protected function assembleCaption(BaseHtmlElement $caption)
    {
        switch ($this->item->event_type) {
            case 'comment_add':
            case 'comment_remove':
                $markdownLine = new MarkdownLine($this->item->comment->comment);
                $caption->getAttributes()->add($markdownLine->getAttributes());
                $caption->add([
                    new Icon(Icons::USER),
                    $this->item->comment->author,
                    ': '
                ])->addFrom($markdownLine);

                break;
            case 'downtime_end':
            case 'downtime_start':
                $markdownLine = new MarkdownLine($this->item->downtime->comment);
                $caption->getAttributes()->add($markdownLine->getAttributes());
                $caption->add([
                    new Icon(Icons::USER),
                    $this->item->downtime->author,
                    ': '
                ])->addFrom($markdownLine);

                break;
            case 'flapping_start':
                $caption
                    ->add(sprintf(
                        t('State Change Rate: %.2f%%; Start Threshold: %.2f%%'),
                        $this->item->flapping->percent_state_change_start,
                        $this->item->host->flapping_threshold_high
                    ))
                    ->getAttributes()
                    ->add('class', 'plugin-output');

                break;
            case 'flapping_end':
                $caption
                    ->add(sprintf(
                        t('State Change Rate: %.2f%%; End Threshold: %.2f%%; Flapping for %s'),
                        $this->item->host->flapping_threshold_low,
                        $this->item->host->flapping_threshold_high,
                        DateFormatter::formatDuration(
                            $this->item->flapping->end_time - $this->item->flapping->start_time
                        )
                    ))
                    ->getAttributes()
                    ->add('class', 'plugin-output');

                break;
            case 'ack_clear':
            case 'ack_set':
                $markdownLine = new MarkdownLine($this->item->acknowledgement->comment);
                $caption->getAttributes()->add($markdownLine->getAttributes());
                $caption->add([
                    new Icon(Icons::USER),
                    $this->item->acknowledgement->author,
                    ': '
                ])->addFrom($markdownLine);

                break;
            case 'notification':
                if (! empty($this->item->notification->author)) {
                    $caption->add([
                        new Icon(Icons::USER),
                        $this->item->notification->author,
                        ': ',
                        $this->item->notification->text
                    ]);
                } else {
                    $caption->addHtml(new PluginOutputContainer(
                        (new PluginOutput($this->item->notification->text))
                            ->setCommandName($this->item->object_type === 'host'
                                ? $this->item->host->checkcommand
                                : $this->item->service->checkcommand)
                    ));
                }

                break;
            case 'state_change':
                $caption->addHtml(new PluginOutputContainer(
                    (new PluginOutput($this->item->state->output))
                        ->setCommandName($this->item->object_type === 'host'
                            ? $this->item->host->checkcommand
                            : $this->item->service->checkcommand)
                ));

                break;
        }
    }

    protected function assembleVisual(BaseHtmlElement $visual)
    {
        switch ($this->item->event_type) {
            case 'comment_add':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::COMMENT)
                ));

                break;
            case 'comment_remove':
            case 'downtime_end':
            case 'ack_clear':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::REMOVE)
                ));

                break;
            case 'downtime_start':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::IN_DOWNTIME)
                ));

                break;
            case 'ack_set':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::IS_ACKNOWLEDGED)
                ));

                break;
            case 'flapping_end':
            case 'flapping_start':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::IS_FLAPPING)
                ));

                break;
            case 'notification':
                $visual->addHtml(HtmlElement::create(
                    'div',
                    ['class' => ['icon-ball', 'ball-size-' . $this->getStateBallSize()]],
                    new Icon(Icons::NOTIFICATION)
                ));

                break;
            case 'state_change':
                $previousState = 'previous_soft_state';

                if ($this->item->state->state_type === 'soft') {
                    $state = 'soft_state';

                    $visual->addHtml(new CheckAttempt(
                        $this->item->state->attempt,
                        $this->item->state->max_check_attempts
                    ));
                } else {
                    $state = 'hard_state';
                }

                if ($this->item->object_type === 'host') {
                    $state = HostStates::text($this->item->state->$state);
                    $previousHardState = HostStates::text($this->item->state->$previousState);
                } else {
                    $state = ServiceStates::text($this->item->state->$state);
                    $previousHardState = ServiceStates::text($this->item->state->$previousState);
                }

                $visual->prependHtml(new StateChange($state, $previousHardState));

                break;
        }
    }

    protected function assembleTitle(BaseHtmlElement $title)
    {
        switch ($this->item->event_type) {
            case 'comment_add':
                $subjectLabel = t('Comment added');

                break;
            case 'comment_remove':
                if (! empty($this->item->comment->removed_by)) {
                    if ($this->item->comment->removed_by !== $this->item->comment->author) {
                        $subjectLabel = sprintf(
                            t('Comment removed by %s', '..<username>'),
                            $this->item->comment->removed_by
                        );
                    } else {
                        $subjectLabel = t('Comment removed by author');
                    }
                } elseif (isset($this->item->comment->expire_time)) {
                    $subjectLabel = t('Comment expired');
                } else {
                    $subjectLabel = t('Comment removed');
                }

                break;
            case 'downtime_end':
                if (! empty($this->item->downtime->cancelled_by)) {
                    if ($this->item->downtime->cancelled_by !== $this->item->downtime->author) {
                        $subjectLabel = sprintf(
                            t('Downtime cancelled by %s', '..<username>'),
                            $this->item->downtime->cancelled_by
                        );
                    } else {
                        $subjectLabel = t('Downtime cancelled by author');
                    }
                } elseif (isset($this->item->downtime->cancel_time)) {
                    $subjectLabel = t('Downtime cancelled');
                } else {
                    $subjectLabel = t('Downtime ended');
                }

                break;
            case 'downtime_start':
                $subjectLabel = t('Downtime started');

                break;
            case 'flapping_start':
                $subjectLabel = t('Flapping started');

                break;
            case 'flapping_end':
                $subjectLabel = t('Flapping stopped');

                break;
            case 'ack_set':
                $subjectLabel = t('Acknowledgement set');

                break;
            case 'ack_clear':
                if (! empty($this->item->acknowledgement->cleared_by)) {
                    if ($this->item->acknowledgement->cleared_by !== $this->item->acknowledgement->author) {
                        $subjectLabel = sprintf(
                            t('Acknowledgement cleared by %s', '..<username>'),
                            $this->item->acknowledgement->cleared_by
                        );
                    } else {
                        $subjectLabel = t('Acknowledgement cleared by author');
                    }
                } elseif (isset($this->item->acknowledgement->expire_time)) {
                    $subjectLabel = t('Acknowledgement expired');
                } else {
                    $subjectLabel = t('Acknowledgement cleared');
                }

                break;
            case 'notification':
                $subjectLabel = sprintf(
                    NotificationListItem::phraseForType($this->item->notification->type),
                    ucfirst($this->item->object_type)
                );

                break;
            case 'state_change':
                $state = $this->item->state === 'hard'
                    ? $this->item->state->hard_state
                    : $this->item->state->soft_state;
                if ($state === 0) {
                    if ($this->item->object_type === 'service') {
                        $subjectLabel = t('Service recovered');
                    } else {
                        $subjectLabel = t('Host recovered');
                    }
                } else {
                    if ($this->item->state->state_type === 'hard') {
                        $subjectLabel = t('Hard state changed');
                    } else {
                        $subjectLabel = t('Soft state changed');
                    }
                }

                break;
            default:
                $subjectLabel = $this->item->event_type;

                break;
        }

        if ($this->getNoSubjectLink()) {
            $title->addHtml(HtmlElement::create('span', ['class' => 'subject'], $subjectLabel));
        } else {
            $title->addHtml(new Link($subjectLabel, Links::event($this->item), ['class' => 'subject']));
        }

        if ($this->item->object_type === 'host') {
            $link = $this->createHostLink($this->item->host, true);
        } else {
            $link = $this->createServiceLink($this->item->service, $this->item->host, true);
        }

        $title->addHtml(Text::create(' '), $link);
    }

    protected function createTimestamp()
    {
        return new TimeAgo($this->item->event_time);
    }
}
