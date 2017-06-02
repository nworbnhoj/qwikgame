Feature: I like to play my game with rivals of similar ability
	In order to receive invitations from rivals of similar ability
	As a player
	Qwikgame needs to estimate the relative ability of potential rivals

	In the interests of clarity and brevity, the Steps in these Scenarios
	are stylized to encapulate the essence of Player matches and reports,
	rather than articulate the minuta of Player interactions with qwikgame.
	So for example, "A reports A>B" means that player-A and player-B played a
	Match and player-A provided Feedback that she was the Stronger Player.
	Other shorthand includes A<<B A<B A=B A>B A>>B.

	Scenario: A>B & B<A ⁂ A>B & B<A
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B<A from match on day 1
	  Then A>B on day 2
	  And B<A on day 2	  

	Scenario: A>B & C<B ⁂ A>C
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And C reports C<B from match on day 2
	  Then A>C on day 3

	Scenario: A>B & B>C ⁂ A>C
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B>C from match on day 2
	  Then A>C on day 3

	Scenario: A>B & B>C & C>D & D>E ⁂ A>>E
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B>C from match on day 2
	  And C reports C>D from match on day 3
	  And D reports D>E from match on day 4
	  Then A>>E on day 5

	Scenario: A>B & B>D & C>D ⁂ A>D
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And A reports A>C from match on day 1
	  And B reports B>D from match on day 2
	  And C reports C>D from match on day 2
	  Then A>D on day 3

	Scenario: A>B & B<C ⁂ A=C
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B<C from match on day 2
	  Then A=C on day 3

	Scenario: A>B & B>C & C<<D ⁂ A=D
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B>C from match on day 2
	  And D reports D>>C from match on day 3
	  Then A=D on day 4

	Scenario: A>B & B>C & C<<D ⁂ A=D
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B>C from match on day 2
	  And C reports C<<D from match on day 3
	  Then A=D on day 4

	Scenario: A>B & B=C C=D ⁂ A>D
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B=C from match on day 2
	  And C reports C=D from match on day 3
	  Then A>D on day 4

	Scenario: A>B & B>C & C<B ⁂ A>C
	  Given a community of Players
	  When A reports A>B from match on day 1
	  And B reports B=A from match on day 1
	  And B reports B>C from match on day 2
	  And C reports C<B from match on day 2
	  Then A>C on day 3

	Scenario: Z is unreliable
	  Given a community of Players
	  When A reports A=B from match on day 1
	  And B reports B=A from match on day 1
      And A reports A>C from match on day 1
	  And C reports C<A from match on day 1
      And A reports A>D from match on day 1
	  And D reports D<A from match on day 1
      And B reports B>C from match on day 1
	  And C reports C<B from match on day 1
      And B reports B>D from match on day 1
	  And D reports D<B from match on day 1
      And C reports C=D from match on day 1
	  And D reports D=C from match on day 1

	  When A reports A=Z from match on day 2
	  And Z reports Z>>A from match on day 2
	  Then A=Z on day 2
	  When B reports B=Z from match on day 3
	  And Z reports Z>>B from match on day 3
	  Then A=Z on day 3
	  When C reports C<Z from match on day 4
	  And Z reports Z>>C from match on day 4
	  Then A=Z on day 4
	  When D reports D<Z from match on day 5
	  And Z reports Z>>D from match on day 5
	  Then A=Z on day 5

