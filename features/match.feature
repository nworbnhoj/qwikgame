Feature: Match
	In order to play a game at a convenient time and place with a well matched rival
	As a player
	Players need to be able to organize, play and rate a match

	Scenario: I am keen to play Squash at Milawa
	  Given a community of Players
	  And a squash ranking file "behatRankingSequentialA-Z" from A
	  And the ranking is Activated
	  And my email "player.M@qwikgame.org" is registered with qwikgame
	  When I am keen to play Squash at "Qwikgame Venue|Milawa|VIC|AU"
	  And I select 7pm, 8pm and 9pm
	  Then invitations will be sent to all potential rivals

	Scenario: I want to accept an invitation to play my game at my local venue
	  Given that a rival initiated the match
	  When I select my suitable hour
	  And I select accept
	  Then the rival will receive my acceptance

	Scenario: I want to play a rival who has accepted my invitation
	  When I select accept
	  Then the rival will receive a match confirmation

	Scenario: I do not want to play a rival who has accepted my invitation
	  When I select reject
	  Then the rival will receive a match cancellation
