Feature: I like to play my game at my local venue
	In order to receive invitations to play my game at my local venue
	As a player
	Players need to be able to register their favourite games, venues and suitable times
	
	Scenario: Register at qwikgame
	  Given my email "new.player@qwikgame.org" is not registered with qwikgame
	  When I like to play Squash at "Milawa Squash Courts | Milawa"
	  And I do not specify a time
	  And I provide my email "new.player@qwikgame.org"
	  And I click Submit
	  And I click on the link in the confirmation email
	  Then my email "new.player@qwikgame.org" will be registered with qwikgame
	  And I will be available to play Squash at "Milawa Squash Courts | Milawa"
	
	Scenario: Register to play a Game at a Venue
	  Given my email "A.player@qwikgame.org" is registered with qwikgame
	  When I like to play Squash at "Milawa Squash Courts | Milawa"
	  And I like to play on Saturday at 3pm
	  And I click Submit
	  Then I will be available to play Squash at "Milawa Squash Courts | Milawa" on Saturday at 3pm
	  
	Scenario: Register to play at multiple times
	  Given my email "B.player@qwikgame.org" is registered with qwikgame
	  When I like to play Squash at "Milawa Squash Courts | Milawa"
	  And I like to play on Saturday at 3pm
	  And I like to play on Saturday at 5pm
	  And I click Submit
	  Then I will be available to play Squash at "Milawa Squash Courts | Milawa" on Saturday at 3pm
	  And I will not be available to play Squash at "Milawa Squash Courts | Milawa" on Saturday at 4pm
	  And I will be available to play Squash at "Milawa Squash Courts | Milawa" on Saturday at 5pm
