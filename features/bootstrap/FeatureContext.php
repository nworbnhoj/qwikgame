<?php

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    

    /**
     * @Given that my :email is not registered with qwikgame
     */
    public function thatMyEmailIsNotRegisteredWithQwikgame($email)
    {
        throw new PendingException();
    }

    /**
     * @When I like to play :game at :venue
     */
    public function iLikeToPlaySquashAtMilawa($game, $venue)
    {
        throw new PendingException();
    }

    /**
     * @When I provide my :email address
     */
    public function iProvideMyEmailAddress($email)
    {
        throw new PendingException();
    }

    /**
     * @When I click on the :link in the confirmation email
     */
    public function iClickOnTheLinkInTheConfirmationEmail($link)
    {
        throw new PendingException();
    }

    /**
     * @Then I will be available to play :game at :venue
     */
    public function iWillBeAvailableToPlaySquashAtMilawa($game, $venue)
    {
        throw new PendingException();
    }

    /**
     * @Given that my :email is registered with qwikgame
     */
    public function thatMyEmailIsRegisteredWithQwikgame($email)
    {
        throw new PendingException();
    }

    /**
     * @Then I will be available to play :game at :venue on :day
     */
    public function iWillBeAvailableToPlaySquashAtMilawaOnSaturday($game, $venue, $day)
    {
        throw new PendingException();
    }


    /**
     * @When I like to play :game at :venue on :day
     */
    public function iLikeToPlaySquashAtMilawaOnSaturday($game, $venue, $day)
    {
        throw new PendingException();
    }
}
