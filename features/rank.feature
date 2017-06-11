Feature: I want to import a pre-existing player ranking into qwikgame
	In order to recognise existing player rankings in qwikgame matches
	As a player
	Players need to be able to upload existing player rankings
	
	Scenario: Upload a file with rankings
	  
	
	Scenario: Activate a set of rankings
	  Given a community of Players
	  And a ranking file "behatRankingSequentialA-Z" from A
	  When the ranking is Activated
	  
	  
	Scenario: Deactivate a set of rankings
	
