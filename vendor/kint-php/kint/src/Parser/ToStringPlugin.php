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

namespace Kint\Parser;

use Kint\Object\BasicObject;
use Kint\Object\Representation\Representation;
use ReflectionClass;

class ToStringPlugin extends Plugin
{
    public static $blacklist = array(
        'SimpleXMLElement',
        'SplFileObject',
    );

    public function getTypes()
    {
        return array('object');
    }

    public function getTriggers()
    {
        return Parser::TRIGGER_SUCCESS;
    }

    public function parse(&$var, BasicObject &$o, $trigger)
    {
        $reflection = new ReflectionClass($var);
        if (!$reflection->hasMethod('__toString')) {
            return;
        }

        foreach (self::$blacklist as $class) {
            if ($var instanceof $class) {
                return;
            }
        }

        $r = new Representation('toString');
        $r->contents = (string) $var;

        $o->addRepresentation($r);
    }
}
