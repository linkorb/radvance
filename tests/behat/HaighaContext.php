<?php

namespace Radvance\Behat;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;

class HaighaContext extends DefaultContext
{
    /**
     * @Given /^database has fixtures loaded$/
     * @Given /^database has ([^\s]*) fixtures loaded$/
     * @Given /^database has ([^\s]*) configuration$/
     */
    public function databaseHasFixturesLoaded($fixturesName = 'default')
    {
        $command = sprintf('vendor/bin/haigha fixtures:load test/fixtures/%s.yml sandbox_linkorb_proxytect', $fixturesName);
        exec($command);
    }

}
