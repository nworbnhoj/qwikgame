Feature: friends
	In order to include friendly rivals at qwikgame.org
	As a player
	Players need to be able to include friendly rivals
	
	Scenario: Invite unregistered player
	  Given my email "player.a@qwikgame.org" is registered with qwikgame
	  And "new.player@qwikgame.org" is not registered with qwikgame
          And I add "new.player@qwikgame.org" as a "well_matched" "squash" player
          Then "new.player@qwikgame.org" is a "well_matched" "squash" player
