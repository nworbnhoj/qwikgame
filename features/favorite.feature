Feature: I like to play my game at my local venue
	In order to receive invitations to play my game at my local venue
	As a player
	Players need to be able to register their favorite games, venues and suitable times
	
	Scenario: Register at qwikgame
	  Given "new.player@qwikgame.org" is not registered with qwikgame
	  When I like to play Squash at "Qwikgame Venue | Milawa"
	  And I do not specify a time
	  And I provide my email "new.player@qwikgame.org"
	  And I submit this favorite
	  And I click on the link in the confirmation email
	  Then my email "new.player@qwikgame.org" will be registered with qwikgame
	  And I will be available to play my favorite game
	
	Scenario: Add a favorite and delete
	  Given my email "player.A@qwikgame.org" is registered with qwikgame
	  And I am not available to play
	  When I like to play Squash at "Qwikgame Venue | Milawa"
	  And I like to play on Saturday at 3pm
	  And I submit this favorite
	  Then I will be available to play my favorite game
	  When I delete this favorite
	  Then I will not be available to play
	  
	Scenario: Add a favorite with multiple times
	  Given my email "player.B@qwikgame.org" is registered with qwikgame
	  And I am not available to play
	  When I like to play Squash at "Qwikgame Venue | Milawa"
	  And I like to play on Saturday at 3pm
	  And I like to play on Sunday at 5pm
	  And I submit this favorite
	  Then I will be available to play my favorite game
	  And I will not be available otherwise
	  
	Scenario: Add a favorite with matched ability
	  Given my email "player.C@qwikgame.org" is registered with qwikgame
	  And I am not available to play
	  When I like to play Squash at "Qwikgame Venue | Milawa"
	  And I like to play on Friday at 6pm
	  And I like to play a rival of similar ability
	  And I submit this favorite
	  Then I will be available to play my favorite game
	  And I will not be available otherwise
