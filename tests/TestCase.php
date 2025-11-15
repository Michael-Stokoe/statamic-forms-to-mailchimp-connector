<?php

namespace Stokoe\FormsToMailchimpConnector\Tests;

use Stokoe\FormsToMailchimpConnector\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
