<?php
/*
 * This file is part of the Jejik\MT940 library
 *
 * Copyright (c) 2012 Sander Marechal <s.marechal@jejik.com>
 * Licensed under the MIT license
 *
 * For the full copyright and license information, please see the LICENSE
 * file that was distributed with this source code.
 */
namespace Jejik\MT940\Parser;
/**
 * Parser for KNAB documents
 *
 * @author Casper Bakker <github@casperbakker.com>
 */
class Knab extends AbstractParser
{
    /**
     * Test if the document is an KNAB document
     *
     * @param string $text
     * @return bool
     */
    public function accept($text)
    {
        return strpos(strtok($text, "\n"), 'KNABNL') !== false;
    }

    /**
     * Get the contents of an MT940 line
     *
     * The contents may be several lines long (e.g. :86: descriptions)
     *
     * @param string $id The line ID (e.g. "20"). Can be a regular expression (e.g. "60F|60M")
     * @param string $text The text to search
     * @param int $offset The offset to start looking
     * @param int $position Starting position of the found line
     * @param int $length Length of the found line (before trimming), including EOL
     * @return string
     */
    protected function getLine($id, $text, $offset = 0, &$position = null, &$length = null)
    {
        $text = str_replace("\n","\r\n", $text);
        return parent::getLine($id, $text, $offset, $position, $length);
    }

    /**
     * Get the contra account number from a transaction
     *
     * @param array $lines The transaction text at offset 0 and the description at offset 1
     * @return string|null
     */
    protected function contraAccountNumber(array $lines)
    {
        foreach ($lines as $line) {
            if (preg_match('/REK\: ([a-zA-Z]{2}[0-9]{2}[a-zA-Z0-9]{4}[0-9]{7}([a-zA-Z0-9]?){0,16})/', $line, $match)) {
                return rtrim(ltrim($match[1], '0P'));
            }
        }
    }

    protected function contraAccountName(array $lines)
    {
        foreach ($lines as $line) {
            if (preg_match('/NAAM: (.+)/', $line, $match)) {
                return trim($match[1]);
            }
        }
    }
}