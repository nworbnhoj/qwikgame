from django.forms import CheckboxSelectMultiple, Form, MultipleChoiceField
from game.models import Game
from qwikgame.forms import ActionMultiple, MultipleActionField

from qwikgame.widgets import IconSelectMultiple

class ActionMultiple(CheckboxSelectMultiple):
    attrs = {"class": "down hidden"}


class MultipleActionField(MultipleChoiceField):
    widget = ActionMultiple

    def __init__(self, action='delete:', *args, **kwargs):
        self.action = action
        super().__init__(*args, **kwargs)
        self.widget.attrs={"class": "down hidden"}
        self.template_name='dropdown.html'


class ActiveForm(Form):
    games = MultipleChoiceField(
        choices = Game.choices(),
        label=None,
        required=False,
        template_name='field_naked.html',
        widget=IconSelectMultiple(attrs = {'class':'post'}, icons=Game.icons())
    )
        
    # Initializes a GameForm for 'player'.
    # Returns a context dict including 'game_form'
    @classmethod
    def get(klass, player):
        return {
            'game_form': klass(
                initial = {'games': [game.code for game in player.games.all()]}
            )
        }

    # Initializes a PrivateForm for 'player'.
    # Returns a context dict including 'player_form'
    @classmethod
    def post(klass, request_post, player):
        context = {}
        user_id = player.user.id
        form = klass(request_post)
        if form.is_valid():
            player.games.clear()
            for game_code in form.cleaned_data['games']:
                game = Game.objects.get(code=game_code)
                player.games.add(game)
            player.save()
        else:
            context = {'game_form': form}
        return context
