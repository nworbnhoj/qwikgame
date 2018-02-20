Feature: Familiar
	In order to include familiar rivals at qwikgame.org
	As a player
	Players need to be able to include familiar rivals
	
	Scenario: Invite unregistered player
	  Given my email "player.A@qwikgame.org" is registered with qwikgame
	  And "new.player@qwikgame.org" is not registered with qwikgame
          And I add "new.player@qwikgame.org" as a "well matched" "squash" player
          Then "new.player@qwikgame.org" is a "well_matched" "squash" player
