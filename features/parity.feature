Feature: I like to play my game with rivals of similar ability
	In order to receive invitations from rivals of similar ability
	As a player
	Qwikgame needs to eastimate the relative ability of potential rivals

	In the interests of clarity and brevity, the Steps in these Scenarios
	are stylized to encapulate the essence of Player matches and reports,
	rather than articulate the minuta of Player interactions with qwikgame.
	So for example, "A reports A>B" means that player-A and player-B played a
	Match and player-A provided Feedback that she was the Stronger Player.
	Other shorthand includes A<<B A<B A=B A>B A>>B.

	Scenario: 2 Player Parity outcomes in agreement (stronger)
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B<A from match on day 1
	  Then A>B on day 2
	  And B<A on day 2	  

	Scenario: 3 Player Parity outcomes in agreement (stronger)
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And C reports C<B from match on day 2
	  Then A>C on day 3
