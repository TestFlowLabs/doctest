<?php

declare(strict_types=1);

namespace TestFlowLabs\DocTest\CodeBlock;

enum Attribute: string
{
    case Ignore     = 'ignore';
    case NoRun      = 'no_run';
    case Throws     = 'throws';
    case ParseError = 'parse_error';
    case Setup      = 'setup';
    case Teardown   = 'teardown';
}
