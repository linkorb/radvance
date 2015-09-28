<?php

namespace LinkORB\Framework\Behat;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Based on sylius project's context.
 */
class WebContext extends DefaultContext
{

    /**
     * @Then /^I should see (\d+) rows in the table$/
     */
    public function iShouldSeeRowsInTheTable($rows)
    {
        $table = $this->getPage()->find('css', '.main-content table');
        assertNotNull($table, 'Cannot find a table!');
        assertCount(intval($rows), $table->findAll('css', 'tbody tr'));
    }

    /**
     * @Given /^I wait for the dialog to appear$/
     */
    public function iWaitForTheDialogToAppear()
    {
        $this->getSession()->wait(
            5000,
            "jQuery('.modal').is(':visible');"
        );
    }

    /**
     * @Given /^I am on the ([^\s]+) page?$/
     */
    public function iAmOnThePage($route)
    {
        $this->getSession()->visit($this->generateUrl($route));
    }

    /**
     * @example I am on the proxy index page
     * @example I am on the proxy creation page
     *
     * @Given /^I am on the ([^""]+) (index|creation) page?$/
     * @When /^I go to the ([^""]+) (index|creation) page?$/
     */
    public function iAmOnTheResourcePage($resource, $page)
    {
        $route = $this->buildRoute($resource, $page);
        $this->getSession()->visit($this->generateUrl($route));
    }

    /**
     * @example I am on the proxy "First" edit page
     * @example I am on the proxy "First" editing page
     *
     * @Given /^I am on the ([^""]+) "([^""]+)" (edit|editing|view|viewing) page?$/
     * @When /^I go to the ([^""]+) "([^""]+)" (edit|editing|view|viewing) page?$/
     */
    public function iAmOnTheResourceWithGivenNamePage($resource, $name, $action)
    {
        $this->iAmOnTheResourceWithGivenParameterPage($resource, 'name', $name, $action);
    }

    /**
     * @example I am on the proxy with name "First" editing page
     *
     * @Given /^I am on the ([^""]*) with ([^""]*) "([^""]*) (edit|editing|view|viewing) page"$/
     * @Given /^I go to the ([^""]*) with ([^""]*) "([^""]*)" (edit|editing|view|viewing) page$/
     */
    public function iAmOnTheResourceWithGivenParameterPage($resource, $property, $value, $action)
    {
        $resource = str_replace(' ', '_', $resource);
        $route = $this->buildRoute($resource, $action);
        $entity = $this->findOneBy($resource, array($property => $value));
        $this->getSession()->visit($this->generateUrl(
            $route, array('id' => $entity->getId())
        ));
    }

    /**
     * @Given /^I am on the page of ([^""(w)]*) "([^""]*)"$/
     * @Given /^I go to the page of ([^""(w)]*) "([^""]*)"$/
     */
    public function iAmOnTheResourcePageByName($type, $name)
    {
        $this->iAmOnTheResourcePage($type, 'name', $name);
    }

    /**
     * @Then /^I should be on the ([^\s]+) page$/
     * @Then /^I should be redirected to the ([^\s]+) page$/
     * @Then /^I should still be on the ([^\s]+) page$/
     */
    public function iShouldBeOnThePage($page)
    {
        $route = preg_replace('/\s/', '_', $page);
        $this->assertSession()->addressEquals($this->generateUrl($page));

        try {
            $this->assertStatusCodeEquals(200);
        } catch (UnsupportedDriverActionException $e) {

        }
    }

    /**
     * @Then /^I should be on the ([^""(w)]*) ([^""(w)]*) page$/
     * @Then /^I should be redirected to ([^""(w)]*) ([^""(w)]*) page$/
     * @Then /^I should still be on the ([^""(w)]*) ([^""(w)]*) page"$/
     */
    public function iShouldBeOnTheResourcePage($resource, $page)
    {
        $this->assertSession()->addressEquals($this->generateUrl(
            $this->buildRoute($resource, $page)
        ));

        $this->assertStatusCodeEquals(200);
    }

    /**
     * @Then /^I should be on the page of ([^""(w)]*) "([^""]*)"$/
     * @Then /^I should still be on the page of ([^""(w)]*) "([^""]*)"$/
     */
    public function iShouldBeOnTheResourceWithGivenNameIndexPage($resource, $name)
    {
        $this->iShouldBeOnTheResourceWithGivenParameterActionPage('view', $resource, 'name', $name);
    }

    /**
     * @Then /^I should be on the ([^""]*) "([^""]*)" (edit|editing|view|viewing) page$/
     */
    public function iShouldBeOnTheResourceWithGivenNameActionPage($resource, $name, $action)
    {
        $this->iShouldBeOnTheResourceWithGivenParameterActionPage($action, $resource, 'name', $name);
    }

    /**
     * @Then /^I should be on the page of ([^""]*) with ([^""]*) "([^""]*)"$/
     * @Then /^I should still be on the page of ([^""]*) with ([^""]*) "([^""]*)"$/
     */
    public function iShouldBeOnTheResourceWithGivenParameterViewActionPage($resource, $property, $value)
    {
        $this->iShouldBeOnTheResourceWithGivenParameterActionPage('view', $resource, $property, $value);
    }

    /**
     * @Then /^I should be on the ([^\s]*) page of ([^""]*) with ([^""]*) "([^""]*)"$/
     * @Then /^I should still be on the ([^\s]*) page of ([^""]*) with ([^""]*) "([^""]*)"$/
     */
    public function iShouldBeOnTheResourceWithGivenParameterActionPage($action, $resource, $property, $value)
    {
        $route = $this->buildRoute($resource, $action);
        $entity = $this->findOneBy($resource, array($property => $value));
        $this->assertSession()->addressEquals($this->generateUrl(
            $route, array('id' => $entity->getId())
        ));

        $this->assertStatusCodeEquals(200);
    }

    /**
     * @Given /^I am (viewing|editing) ([^""]*) with ([^""]*) "([^""]*)"$/
     */
    public function iAmDoingSomethingWithResource($action, $resource, $property, $value)
    {
        $route = sprintf('%s_%s', $resource, $action);
        $entity = $this->findOneBy($type, array($property => $value));

        $this->getSession()->visit($this->generateUrl(
            $route, array('id' => $entity->getId())
        ));
    }

    /**
     * @Given /^I am (building|viewing|editing) ([^""(w)]*) "([^""]*)"$/
     */
    public function iAmDoingSomethingWithResourceByName($action, $type, $name)
    {
        $this->iAmDoingSomethingWithResource($action, $type, 'name', $name);
    }

    /**
     * @Then /^Text "([^"]*)" should appear on the page$/
     * @Then /^"([^"]*)" text should appear on the page$/
     */
    public function textShouldAppearOnThePage($text)
    {
        $this->assertSession()->pageTextContains($text);
    }

    /**
     * @Then /^Text "([^"]*)" (should not|shouldn't) appear on the page$/
     * @Then /^"([^"]*)" text (should not|shouldn't) appear on the page$/
     */
    public function textShouldNotAppearOnThePage($text)
    {
        $this->assertSession()->pageTextNotContains($text);
    }

    /**
     * @When /^I click "([^"]+)"$/
     */
    public function iClick($link)
    {
        $this->clickLink($link);
    }

    /**
     * @Given /^I should see an? "(?P<element>[^"]*)" element near "([^"]*)"$/
     */
    public function iShouldSeeAElementNear($element, $value)
    {
        $tr = $this->assertSession()->elementExists('css', sprintf('table tbody tr:contains("%s")', $value));
        $this->assertSession()->elementExists('css', $element, $tr);
    }

    /**
     * @When /^I click "([^"]*)" near "([^"]*)"$/
     * @When /^I press "([^"]*)" near "([^"]*)"$/
     */
    public function iClickNear($button, $value)
    {
        $tr = $this->assertSession()->elementExists('css', sprintf('table tbody tr:contains("%s")', $value));

        $locator = sprintf('button:contains("%s")', $button);

        if ($tr->has('css', $locator)) {
            $tr->find('css', $locator)->press();
        } else {
            $tr->clickLink($button);
        }
    }

    /**
     * @Given /^I should see (\d+) errors$/
     */
    public function iShouldSeeFieldsOnError($amount)
    {
        $this->assertSession()->elementsCount('css', '.form-error', $amount);
    }

    /**
     * @Given /^I leave "([^"]*)" empty$/
     * @Given /^I leave "([^"]*)" field blank/
     */
    public function iLeaveFieldEmpty($field)
    {
        $this->fillField($field, '');
    }

    /**
     * For example: I should see product with name "Wine X" in that list.
     *
     * @Then /^I should see (?:(?!enabled|disabled)[\w\s]+) with ((?:(?![\w\s]+ containing))[\w\s]+) "([^""]*)" in (?:that|the) list$/
     */
    public function iShouldSeeResourceWithValueInThatList($columnName, $value)
    {
        $tableNode = new TableNode(array(
            array(trim($columnName)),
            array(trim($value)),
        ));

        $this->iShouldSeeTheFollowingRow($tableNode);
    }

    /**
     * For example: I should not see product with name "Wine X" in that list.
     *
     * @Then /^I should not see [\w\s]+ with ((?:(?![\w\s]+ containing))[\w\s]+) "([^""]*)" in (?:that|the) list$/
     */
    public function iShouldNotSeeResourceWithValueInThatList($columnName, $value)
    {
        $tableNode = new TableNode(array(
            array(trim($columnName)),
            array(trim($value)),
        ));

        $this->iShouldNotSeeTheFollowingRow($tableNode);
    }

    /**
     * For example: I should see product with name containing "Wine X" in that list.
     *
     * @Then /^I should see (?:(?!enabled|disabled)[\w\s]+) with ([\w\s]+) containing "([^""]*)" in (?:that|the) list$/
     */
    public function iShouldSeeResourceWithValueContainingInThatList($columnName, $value)
    {
        $tableNode = new TableNode(array(
            array(trim($columnName)),
            array(trim('%' . $value . '%')),
        ));

        $this->iShouldSeeTheFollowingRow($tableNode);
    }

    /**
     * For example: I should not see product with name containing "Wine X" in that list.
     *
     * @Then /^I should not see [\w\s]+ with ([\w\s]+) containing "([^""]*)" in (?:that|the) list$/
     */
    public function iShouldNotSeeResourceWithValueContainingInThatList($columnName, $value)
    {
        $tableNode = new TableNode(array(
            array(trim($columnName)),
            array(trim('%' . $value . '%')),
        ));

        $this->iShouldNotSeeTheFollowingRow($tableNode);
    }

    /**
     * For example: I should see 10 products in that list.
     *
     * @Then /^I should see (\d+) ([^""]*) in (?:that|the) list$/
     * @since Proxytect
     */
    public function iShouldSeeThatMuchResourcesInTheList($amount, $resource)
    {
        if (1 === count($this->getSession()->getPage()->findAll('css', 'table'))) {
            $this->assertSession()->elementsCount('css', 'table tbody > tr', $amount);
        } else {
            $this->assertSession()->elementsCount(
                'css',
                sprintf('table.table-%s tbody > tr', str_replace(' ', '-', $resource)),
                $amount
            );
        }
    }

    /**
     * @Then /^I should be logged in$/
     */
    public function iShouldBeLoggedIn()
    {
        if (!$this->getSecurityContext()->isGranted('ROLE_USER')) {
            throw new AuthenticationException('User is not authenticated.');
        }
    }

    /**
     * @Then /^I should not be logged in$/
     */
    public function iShouldNotBeLoggedIn()
    {
        if ($this->getSecurityContext()->isGranted('ROLE_USER')) {
            throw new AuthenticationException('User was not expected to be logged in, but he is.');
        }
    }

    /**
     * @Given /^I wait (\d+) (seconds|second)$/
     */
    public function iWait($time)
    {
        $this->getSession()->wait($time*1000);
    }

    /**
     * @Then I should have my access denied
     */
    public function iShouldHaveMyAccessDenied()
    {
        $this->assertStatusCodeEquals(403);
    }

    /**
     * @Then /^I should see the following (?:row|rows):$/
     */
    public function iShouldSeeTheFollowingRow(TableNode $tableNode)
    {
        $table = $this->assertSession()->elementExists('css', 'table');

        foreach ($tableNode->getHash() as $fields) {
            if (null === $this->getRowWithFields($table, $fields)) {
                throw new \Exception('Table with given fields was not found!');
            }
        }
    }

    /**
     * @Then /^I should not see the following (?:row|rows):$/
     */
    public function iShouldNotSeeTheFollowingRow(TableNode $tableNode)
    {
        $table = $this->assertSession()->elementExists('css', 'table');

        foreach ($tableNode->getHash() as $fields) {
            if (null !== $this->getRowWithFields($table, $fields)) {
                throw new \Exception('Table with given fields was found!');
            }
        }
    }

    /**
     * @Then /^I should see ([\w\s]+) "([^""]*)" as available choice$/
     */
    public function iShouldSeeSelectWithOption($fieldName, $fieldOption)
    {
        /** @var NodeElement $select */
        $select = $this->assertSession()->fieldExists($fieldName);

        $selector = sprintf('option:contains("%s")', $fieldOption);
        $option = $select->find('css', $selector);

        if (null === $option) {
            throw new \Exception(sprintf('Option "%s" was not found!', $fieldOption));
        }
    }

    /**
     * @Then /^I should not see ([\w\s]+) "([^""]*)" as available choice$/
     */
    public function iShouldNotSeeSelectWithOption($fieldName, $fieldOption)
    {
        /** @var NodeElement $select */
        $select = $this->assertSession()->fieldExists(ucfirst($fieldName));

        $selector = sprintf('option:contains("%s")', $fieldOption);
        $option = $select->find('css', $selector);

        if (null !== $option) {
            throw new \Exception(sprintf('Option "%s" was found!', $fieldOption));
        }
    }

    /**
     * Assert that given code equals the current one.
     *
     * @param integer $code
     */
    protected function assertStatusCodeEquals($code)
    {
        $this->assertSession()->statusCodeEquals($code);
    }

}
