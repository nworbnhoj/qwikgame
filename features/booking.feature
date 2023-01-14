Feature: I need to manage the facilities at my venue
	In order to manage bookings at my local venue
	As a manager
	Managers need to be able to register the facilities at their venue
	
	Scenario: Register as Venue Manager at qwikgame
	  Given "new.manager@qwikgame.org" is not registered with qwikgame
	  When I need to manage the "Squash" facility at "Qwikgame Venue|South Pole|AU|AQ"
	  And I provide my email "new.manager@qwikgame.org"
	  And I register as Manager
	  And I click on the link in the welcome email
	  Then my email "new.manager@qwikgame.org" will be registered with qwikgame
	  And I am the Manager at "Qwikgame Venue|South Pole|AU|AQ"

	Scenario: Add a new Facility at qwikgame
	  Given "new.manager@qwikgame.org" is logged in
	  And I am the Manager at "Qwikgame Venue|South Pole|AU|AQ"
	  And I enter "Tue" at "13" hours
	  And I enter "Tue" at "14" hours
	  And I enter "Tue" at "15" hours
	  And I add a "chess" facility at these hours
	  Then "chess" "is" available "Tue" at "14"
	  And "chess" "is not" available "Tue" at "19"

	Scenario: Adjust a Facility availability
	  Given "new.manager@qwikgame.org" is logged in
	  And I am the Manager at "Qwikgame Venue|South Pole|AU|AQ"
	  And I enter "today" at "9" hours
	  And I enter "today" at "10" hours
	  And I enter "tomorrow" at "11" hours
	  And I add a "chess" facility at these hours
	  Then "chess" "is" available "today" at "10"
	  Then "chess" "is" available "tomorrow" at "11"
	  And "chess" "is not" available "tomorrow" at "9"

	Scenario: Check Matches at qwikgame Facility
	  Given "new.manager@qwikgame.org" is logged in
	  And I am the Manager at "Qwikgame Venue|South Pole|AU|AQ"
	  And a community of Players
	  And A is keen to play squash with B
	  Then the tentative Match is listed
	  When A accepts a Match with B
	  Then the confirmed Match is listed
	  When I click booked in the Confirmed email
	  Then the Players see confirmation
      When A cancels the Match
      Then the cancelled Match is listed