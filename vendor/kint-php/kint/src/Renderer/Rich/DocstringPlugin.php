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

use Kint\Kint;
use Kint\Object\Representation\DocstringRepresentation;
use Kint\Object\Representation\Representation;

class DocstringPlugin extends Plugin implements TabPluginInterface
{
    public function renderTab(Representation $r)
    {
        if (!($r instanceof DocstringRepresentation)) {
            return false;
        }

        $docstring = array();
        foreach (\explode("\n", $r->contents) as $line) {
            $docstring[] = \trim($line);
        }

        $docstring = \implode("\n", $docstring);

        $location = array();

        if ($r->class) {
            $location[] = 'Inherited from '.$this->renderer->escape($r->class);
        }
        if ($r->file && $r->line) {
            $location[] = 'Defined in '.$this->renderer->escape(Kint::shortenPath($r->file)).':'.((int) $r->line);
        }

        $location = \implode("\n", $location);

        if ($location) {
            if (\strlen($docstring)) {
                $docstring .= "\n\n";
            }

            $location = '<small>'.$location.'</small>';
        } elseif (0 === \strlen($docstring)) {
            return '';
        }

        return '<pre>'.$this->renderer->escape($docstring).$location.'</pre>';
    }
}
