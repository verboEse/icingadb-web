<?php

/* Icinga DB Web | (c) 2021 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Icingadb\Util;

use ArrayIterator;
use IteratorAggregate;

class PerfDataSet implements IteratorAggregate
{
    /**
     * The performance data being parsed
     *
     * @var string
     */
    protected $perfdataStr;

    /**
     * The current parsing position
     *
     * @var int
     */
    protected $parserPos = 0;

    /**
     * A list of PerfData objects
     *
     * @var array
     */
    protected $perfdata = array();

    /**
     * Create a new set of performance data
     *
     * @param   string      $perfdataStr    A space separated list of label/value pairs
     */
    protected function __construct(string $perfdataStr)
    {
        if (($perfdataStr = trim($perfdataStr)) !== '') {
            $this->perfdataStr = $perfdataStr;
            $this->parse();
        }
    }

    /**
     * Return a iterator for this set of performance data
     *
     * @return  ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->asArray());
    }

    /**
     * Return a new set of performance data
     *
     * @param   string      $perfdataStr    A space separated list of label/value pairs
     *
     * @return  PerfDataSet
     */
    public static function fromString(string $perfdataStr): self
    {
        return new static($perfdataStr);
    }

    /**
     * Return this set of performance data as array
     *
     * @return  array
     */
    public function asArray(): array
    {
        return $this->perfdata;
    }

    /**
     * Parse the current performance data
     */
    protected function parse()
    {
        while ($this->parserPos < strlen($this->perfdataStr)) {
            $label = trim($this->readLabel());
            $value = trim($this->readUntil(' '));

            if ($label) {
                $this->perfdata[] = new PerfData($label, $value);
            }
        }
    }

    /**
     * Return the next label found in the performance data
     *
     * @return  string      The label found
     */
    protected function readLabel(): string
    {
        $this->skipSpaces();
        if (in_array($this->perfdataStr[$this->parserPos], array('"', "'"))) {
            $quoteChar = $this->perfdataStr[$this->parserPos++];
            $label = $this->readUntil($quoteChar);
            $this->parserPos++;

            if ($this->perfdataStr[$this->parserPos] === '=') {
                $this->parserPos++;
            }
        } else {
            $label = $this->readUntil('=');
            $this->parserPos++;
        }

        $this->skipSpaces();
        return $label;
    }

    /**
     * Return all characters between the current parser position and the given character
     *
     * @param   string  $stopChar   The character on which to stop
     *
     * @return  string
     */
    protected function readUntil(string $stopChar): string
    {
        $start = $this->parserPos;
        while ($this->parserPos < strlen($this->perfdataStr) && $this->perfdataStr[$this->parserPos] !== $stopChar) {
            $this->parserPos++;
        }

        return substr($this->perfdataStr, $start, $this->parserPos - $start);
    }

    /**
     * Advance the parser position to the next non-whitespace character
     */
    protected function skipSpaces()
    {
        while ($this->parserPos < strlen($this->perfdataStr) && $this->perfdataStr[$this->parserPos] === ' ') {
            $this->parserPos++;
        }
    }
}
