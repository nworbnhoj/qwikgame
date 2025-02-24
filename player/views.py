import logging
from appeal.forms import AcceptForm, KeenForm, BidForm
from appeal.views import AppealsView
from datetime import datetime, timedelta, timezone
from django.forms import BooleanField, CheckboxInput, CheckboxSelectMultiple, ChoiceField, Form, IntegerField, MultipleChoiceField, MultiValueField, MultiWidget, RadioSelect
from django.http import HttpResponse, HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from game.models import Game, Match
from person.models import Person
from player.forms import FilterForm, FriendForm, FiltersForm, StrengthForm
from player.models import Filter, Friend, Player, Strength
from qwikgame.hourbits import Hours24
from qwikgame.forms import MenuForm
from api.models import Mark
from service.locate import Locate
from venue.models import Place, Region, Venue
from qwikgame.views import QwikView
from qwikgame.widgets import DAY_ALL, DAY_NONE, WEEK_ALL, WEEK_NONE


logger = logging.getLogger(__file__)


class FilterView(AppealsView):
    filter_form_class = FilterForm
    hide=[]
    template_name = 'player/filter.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        is_admin = self.user.is_admin
        context |= {
            'onclick_place_marker': 'select' if is_admin else 'noop',
            'onclick_region_marker': 'select',
            'onclick_search_marker': 'select',
            'onclick_venue_marker': 'select',
            'onhover_place_marker': 'info',
            'onhover_region_marker': 'info',
            'onhover_search_marker': 'info',
            'onhover_venue_marker': 'info',
            'onpress_place_marker': 'info',
            'onpress_region_marker': 'info',
            'onpress_search_marker': 'info',
            'onpress_venue_marker': 'info',
            'show_search_box': 'SHOW' if is_admin else 'HIDE',
        }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        player = self.user.player
        places = player.place_suggestions(12)[:12]
        # TODO serialize places to avoid place_suggestions() call in POST
        # request.session['places'] = places
        context |= self.filter_form_class.get(
            player,
            game='ANY',
            place='ANY',
            hours=WEEK_NONE,
            places=places,
        )
        return render(request, self.template_name, context)


    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)     
        player = self.user.player
        context = self.filter_form_class.post(
            request.POST,
            player.place_suggestions(12)[:12],
            # request.session.get('places', [])
        )
        form = context.get('filter_form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        game, place, venue = None, None, None
        placeid = context.get('placeid')
        if placeid:
            place = Place.objects.filter(placeid=placeid).first()
            if place:
                if place.is_venue:
                    venue = place.venue
            else:  # then it must be a new Venue from a google POI
                venue = Venue.from_placeid(placeid)
                if venue:
                    venue.save()
                    logger.info(f'Venue new: {venue}')
                    place = venue.place_ptr
                else:
                    logger.warn(f'Failed to create new Venue: {placeid}')
        gameid = context.get('game')
        game = Game.objects.filter(pk=gameid).first()
        # add this Game to a Venue if required
        if game and venue and not(game in venue.games.all()):
            venue.games.add(game)
            logger.info(f'Venue add Game: {game}')
            venue.save()
            mark = Mark(game=game, place=place)
            mark.save()
            logger.info(f'Mark new {mark}')
        try:
            new_filter = Filter.objects.get_or_create(
                player=self.user.player,
                game=game,
                place=place)
            filter_hours = Hours24x7(context.get('hours', WEEK_NONE))
            new_filter[0].set_hours(filter_hours)
            new_filter[0].save()
            logger.info(f'Filter new: {new_filter[0]}')
            # update the Mark size
            mark = Mark.objects.filter(game=game, place=place).first()
            if mark:
                mark.save()
        except:
            logger.exception("failed to add filter")
        return HttpResponseRedirect("/player/filters")


class FiltersView(AppealsView):
    filters_form_class = FiltersForm
    template_name = 'player/filters.html'

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        context |= self.filters_form_class.get(self.user.player)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)     
        player = self.user.player
        context = self.filters_form_class.post(
            request.POST,
            player,
        )
        form = context.get('filters_form')
        if form and not form.is_valid():
            context |= super().context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        if 'ACTIVATE' in context:
            activate = context.get('ACTIVATE', [])
            logger.debug(f'activating filters {activate}')
            for filter in Filter.objects.filter(player=player):
                try:
                    filter.active = str(filter.id) in activate
                    filter.save()
                except:
                    logger.exception('failed to activate filter: {} : {}'.format(player, filter.id))
            return HttpResponseRedirect("/appeal/")
        if 'DELETE' in context:
            delete = context.get('DELETE', [])
            logger.info(f'deleting filters {delete}')
            for filter_code in delete:
                try:
                    junk = Filter.objects.get(pk=filter_code)
                    logger.info(f'Deleting filter: {junk}')
                    junk.delete()
                except:
                    logger.exception('failed to delete filter: {} : {}'.format(player, filter_code))
        return HttpResponseRedirect("/player/filters")


class InvitationView(AppealsView):
    # invitation_form_class = InvitationForm
    template_name = 'game/invitation.html'

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        # context = self.invitation_form_class.post(
        #     request.POST,
        #     self.user.player,
        # )
        if len(context) == 0:
            return HttpResponseRedirect("/appeal/")
        context |= super().context(request, *args, **kwargs)
        return render(request, self.template_name, context)


class RivalView(AppealsView):

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = super().context(request, *args, **kwargs)
        context |= { 'review_tab': 'selected' }
        return render(request, "player/rival.html", context)


class FriendsView(QwikView):
    template_name = 'player/friends.html'

    def context(self, request, *args, **kwargs):
        player = self.user.player
        player.alert_del(type='friend')
        kwargs['items'] = Friend.objects.filter(player=player).order_by('name')
        if kwargs['items'].first():
            kwargs['pk'] = kwargs.get('friend')
        context = super().context(request, *args, **kwargs)
        context |= {
            'friend': context.get('item'),
            'friends': context.get('items'),
            'friend_tab': 'selected',
            'target': 'friend',
        }
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        friend = kwargs.get('item')
        if not friend: 
            return render(request, self.template_name, context)


class FriendAddView(FriendsView):
    form_class = FriendForm
    template_name = 'player/friend_add.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.form_class.get()
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        form = context.get('form')
        if form and not form.is_valid():
            context |= self.form_class.post(request.POST)
            return render(request, self.template_name, context)
        try:
            email = context.get('email','not@valid')
            email_hash = Person.hash(email)
            rival, created = Player.objects.get_or_create(email_hash = email_hash)
            friend = Friend.objects.create(
                email = email,
                name = context.get('name',email.split('@')[0]),
                player = self.user.player,
                rival = rival,
            )
        except:
            logger.exception(f'failed add friend: {context}')
        return HttpResponseRedirect(f'/player/friend/{friend.pk}/')



class FriendView(FriendsView):
    friend_form_class = FriendForm
    menu_form_class = MenuForm
    strength_form_class = StrengthForm
    template_name = 'player/friend.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        friend = context.get('friend')
        if friend:
            context['strengths'] = friend.strengths.all()
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        friend = context.get('friend')
        strength = friend.strengths.first() if friend else None
        context |= self.friend_form_class.get(friend)
        context |= self.strength_form_class.get(strength)
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        player = self.user.player
        context = self.menu_form_class.post(request.POST)
        menu_form = context.get('menu_form')
        if menu_form and menu_form.is_valid():
            if 'DELETE' in context:
                try:
                    delete_pk = int(context.get('DELETE',-1))
                    junk = Friend.objects.get(pk=delete_pk)
                    logger.info(f'Deleting friend: {junk}')
                    junk.delete()
                except:
                    logger.exception('failed to delete friend: {} : {}'.format(player, delete_pk))
                return HttpResponseRedirect(f'/player/friend/')
        context = self.friend_form_class.post(request.POST)
        context |= self.strength_form_class.post(request.POST)
        friend_form = context.get('friend_form')
        strength_form = context.get('strength_form')
        if friend_form and strength_form:
            if not friend_form.is_valid() or not strength_form.is_valid():
                context |= self.context(request, *args, **kwargs)
                return render(request, self.template_name, context)
        else:
            logger.error('failed to post FriendForm or StrengthForm')
        friend_pk = kwargs.get('friend')
        
        if 'DELETE_STRENGTH' in context:
            try:
                delete_pk = context.get('DELETE_STRENGTH')
                junk = Strength.objects.get(pk=delete_pk)
                logger.info(f'Deleting strength: {junk}')
                junk.delete()
            except:
                logger.exception('failed to delete strength: {} : {}'.format(player, delete_pk))
            return HttpResponseRedirect(f'/player/friend/{friend_pk}/')
        email = context.get('email')
        name = context.get('name')
        if friend_pk:    # modifying an existing friend
            try:
                friend = Friend.objects.get(pk=friend_pk)
                if email != friend.email:
                    friend.email = email
                    email_hash = Person.hash(email)
                    strengths = friend.strengths.all()
                    rival, created = Player.objects.get_or_create(email_hash = email_hash)
                    friend.rival = rival
                    friend.save()
                    for strength in strengths:
                        strength.rival = rival
                        strength.save()
                    friend_pk = friend.pk
                if name != friend.name:
                    friend.name = name
                    friend.save()
            except:
                logger.exception(f'failed modify friend: {context}')
        else:    # creating a new friend
            try:
                email_hash = Person.hash(email)
                rival, created = Player.objects.get_or_create(email_hash = email_hash)
                friend = Friend.objects.create(
                    email = email,
                    name = context.get('name',email.split('@')[0]),
                    player = player,
                    rival = rival,
                )
                friend_pk = friend.pk
            except:
                logger.exception(f'failed add friend: {context}')
        try:    # 
            friend = Friend.objects.get(pk=friend_pk)
            game = Game.objects.get(pk=context.get('game','squ'))
            strength, created = Strength.objects.update_or_create(
                game = game,
                player = player,
                rival = friend.rival,
                defaults = {
                    'date': datetime.now(timezone.utc),
                    'relative': context.get('strength','m'),
                    'weight': 3
                }
            )
            if created:
                friend.strengths.add(strength)
        except:
            logger.exception(f'failed add strength: {context}')
        return HttpResponseRedirect(f'/player/friend/{friend_pk}/')




class FriendStrengthView(FriendsView):
    form_class = StrengthForm
    template_name = 'player/friend.html'

    def context(self, request, *args, **kwargs):
        context = super().context(request, *args, **kwargs)
        friend = context.get('friend')
        if friend:
            context['strengths'] = friend.strengths.all()
        self._context = context
        return self._context

    def get(self, request, *args, **kwargs):
        super().get(request, *args, **kwargs)
        context = self.context(request, *args, **kwargs)
        context |= self.form_class.get()
        return render(request, self.template_name, context)

    def post(self, request, *args, **kwargs):
        super().post(request, *args, **kwargs)
        context = self.form_class.post(request.POST)
        form = context.get('form')
        if form and not form.is_valid():
            context |= self.context(request, *args, **kwargs)
            return render(request, self.template_name, context)
        friend_pk = kwargs.get('friend')
        try:
            player = self.user.player
            friend = Friend.objects.get(pk=friend_pk)
            game = Game.objects.get(pk=context.get('game', 'squ'))
            strength, created = Strength.objects.update_or_create(
                game = game,
                player = player,
                rival = friend.rival,
                defaults = {
                    'date': datetime.now(timezone.utc),
                    'relative': context.get('strength','m'),
                    'weight': 3
                }
            )
            if created:
                friend.strengths.add(strength)
        except:
            logger.exception(f'failed add strength: {context}')
        return HttpResponseRedirect(f'/player/friend/{friend_pk}/')
