Feature: I need to manage the facilities at my venue
	In order to manage bookings at my local venue
	As a manager
	Managers need to be able to register the facilities at their venue
	
	Scenario: Register at qwikgame
	Scenario: Register as Venue Manager at qwikgame
	  Given "new.manager@qwikgame.org" is not registered with qwikgame
	  When I need to manage the "Squash" facility at "Qwikgame Venue|South Pole|AU|AQ"
	  And I provide my email "new.manager@qwikgame.org"
	  And I register as Manager
	  And I click on the link in the welcome email
	  Then my email "new.manager@qwikgame.org" will be registered with qwikgame
	  And I am the Manager at "Qwikgame Venue|South Pole|AU|AQ"
	