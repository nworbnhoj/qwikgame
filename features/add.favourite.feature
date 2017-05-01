Feature: I like to play my game at my local venue
	In order to receive invitations to play my game at my local venue
	As a player
	Players need to be able to register their favourite games, venues and suitable times
	
	Scenario: Register at qwikgame
	  Given that my email is not registered with qwikgame
	  When I like to play Squash at Milawa
	  And I provide my email address
	  And I click on the link in the confirmation email
	  Then I will be available to play Squash at Milawa
	
	Scenario: Register to play Squash at Milawa Squash Courts
	  Given that my email is registered with qwikgame
	  When I like to play Squash at Milawa on Saturday
	  Then I will be available to play Squash at Milawa on Saturday
