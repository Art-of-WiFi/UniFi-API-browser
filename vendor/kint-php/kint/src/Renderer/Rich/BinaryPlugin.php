<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Jonathan Vollebregt (jnvsor@gmail.com), Rokas Šleinius (raveren@gmail.com)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Kint\Renderer\Rich;

use Kint\Object\Representation\Representation;

class BinaryPlugin extends Plugin implements TabPluginInterface
{
    public static $line_length = 0x10;
    public static $chunk_length = 0x4;

    public function renderTab(Representation $r)
    {
        $out = '<pre>';

        $chunks = \str_split($r->contents, self::$line_length);

        foreach ($chunks as $index => $chunk) {
            $out .= \sprintf('%08X', $index * self::$line_length).":\t";
            $out .= \implode(' ', \str_split(\str_pad(\bin2hex($chunk), 2 * self::$line_length, ' '), self::$chunk_length));
            $out .= "\t".\preg_replace('/[^\\x20-\\x7E]/', '.', $chunk)."\n";
        }

        $out .= '</pre>';

        return $out;
    }
}
