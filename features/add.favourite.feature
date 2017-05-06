Feature: I like to play my game at my local venue
	In order to receive invitations to play my game at my local venue
	As a player
	Players need to be able to register their favourite games, venues and suitable times
	
	Scenario: Register at qwikgame
	  Given my email "new.player@qwikgame.org" is not registered with qwikgame
	  When I like to play Squash at Milawa
	  And I provide my email "new.player@qwikgame.org"
	  And I click on the link in the confirmation email
	  Then my email "new.player@qwikgame.org" is registered with qwikgame
	  And I will be available to play Squash at Milawa
	
	Scenario: Register to play Squash at Milawa Squash Courts
	  Given my email "A.player@qwikgame.org" is registered with qwikgame
	  When I like to play Squash at Milawa
	  And I like to play on Saturday
	  Then I will be available to play Squash at Milawa on Saturday
