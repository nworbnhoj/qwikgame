import logging, math, statistics
import hashlib, pytz
from django.db import models
from pytz import datetime
from qwikgame.constants import ENDIAN, WEEK_DAYS
from qwikgame.hourbits import Hours24, Hours24x7, DAY_ALL, DAY_NONE, DAY_QWIK, WEEK_NONE, WEEK_QWIK
from qwikgame.log import Entry
from game.models import Match, Review
from venue.models import Venue, Region


logger = logging.getLogger(__file__)


INIT_CONDUCT = b'\x00\xff\xff'
MASK_24 = ((1 << (24 + 1)) - 1)   # binary 000000001111111111111111111111111111111


class Player(models.Model):
    blocked = models.ManyToManyField('self', blank=True)
    # conduct is a bitfield representing a timeseries of good/bad Player reviews
    conduct = models.BinaryField(default=INIT_CONDUCT)
    email_hash = models.CharField(max_length=32, primary_key=True)
    friends = models.ManyToManyField('self', symmetrical=False, through='Friend', blank=True)
    games = models.ManyToManyField('game.Game')
    user = models.OneToOneField('authenticate.User', on_delete=models.CASCADE, blank=True, null=True)

    @classmethod
    def hash(cls, text):
        return hashlib.md5(text.encode()).hexdigest()

    # return a list of Appeals sorted by urgency:
    # - matching Player Filters,
    # - or by direct invitation,
    # - excluding Blocked Players. 
    def appeals(self):
        filters = Filter.objects.filter(player=self, active=True)
        if filters:
            appeal_qs = Appeal.objects.filter(player=self)            
            for f in filters:
                if f.game:
                    appeal_qs |= Appeal.objects.filter(game=f.game)
                if f.place:
                    if f.place.is_venue:
                        appeal_qs |= Appeal.objects.filter(venue=f.place)
                        # TODO hours intersection
                    elif f.place.is_region:
                        qs = Appeal.objects
                        qs = qs.filter(venue__lat__lte=f.place.region.north)
                        qs = qs.filter(venue__lat__gte=f.place.region.south)
                        qs = qs.filter(venue__lng__lte=f.place.region.east)
                        qs = qs.filter(venue__lng__gte=f.place.region.west)
                        appeal_qs |= qs
        else:
            appeal_qs = Appeal.objects.all()
        # TODO include direct invites
        # TODO exclude Blocked Players
        return appeal_qs.order_by('pk').distinct()

    def appeal_participate(self):
        as_appealer = Appeal.objects.filter(player=self)
        as_bidder = Appeal.objects.filter(bid__rival=self)
        return (as_appealer | as_bidder).distinct()


    # add a Player conduct review to the least significant bit
    def conduct_add(self, good=True):
        conduct = int.from_bytes(self.conduct, ENDIAN)
        conduct = (conduct << 1) | int(good)
        conduct = conduct & MASK_24
        self.conduct = conduct.to_bytes(3, ENDIAN)

    # return a string of dip switches representing Player conduct with the most
    # recent on the right.
    def conduct_dips(self):
        dips = ''
        conduct = int.from_bytes(self.conduct, ENDIAN)
        for b in range(0,24):
            dips += ('·' if (conduct & 1) else '.')
            conduct = conduct >> 1
        return dips[::-1]

    # return a float [0,1] representing time weighted fracton of good Player Conduct reviews
    # span int [1,24] limits the range of the calculated fraction
    def conduct_fraction(self, span=24):
        span = max(1, min(span, 24))
        mask = 2 ** span - 1
        conduct = int.from_bytes(self.conduct, ENDIAN)
        return (conduct & mask).bit_count() / span

    # return a float [0.0,1.0] representing the fracton of good Player conduct reviews
    def conduct_rep(self):
        c0 = self.conduct[0]
        c1 = self.conduct[1]
        c2 = self.conduct[2]
        max = int.from_bytes(b'\xff', ENDIAN)
        conduct = (c0 + 2*c1 + 3*c2) / (6*max)
        return conduct

    # return an int [0,5] representing Player conduct stars
    @property
    def conduct_stars(self):
        return round (5 * self.conduct_rep())

    def facet(self):
        return self.email_hash[:3].upper()

    def friend_choices(self):
        choices={}
        for friend in Friend.objects.filter(player=self):
            choices[friend.rival.email_hash] = "{} ({})".format(friend.name, friend.email)
        return choices

    @property
    def icon(self):
        if self.user and self.user.person and self.user.person.icon:
            return self.user.person.icon
        return None

    def matches(self):
        return Match.objects.filter(competitors__in=[self])

    def name(self):
        if self.user is not None:
            return self.user.person.name
        else:
            return self.facet()

    # returns the name of a Rival, using Friend.name if exists
    def name_rival(self, rival):
        friend = Friend.objects.filter(player=self, rival=rival).first()
        if friend:
            return friend.name
        return rival.name()

    def place_choices(self, count=12):
        places = self.place_suggestions(count)
        return [(p.placeid, p.name) for p in places]

    def place_suggestions(self, count):
        venues = list(self.venue_suggestions(count))
        places = set()
        while len(places) < count and len(venues) > 0:
            venue = venues.pop()
            places.add(venue)
            places.add(Region.objects.get(country=venue.country, admin1=venue.admin1, locality=venue.locality))
            places.add(Region.objects.get(country=venue.country, admin1=venue.admin1, locality__isnull=True))
            places.add(Region.objects.get(country=venue.country, admin1__isnull=True, locality__isnull=True))
        places = list(places)[:count]
        places.sort(key=lambda x: x.name)
        return places

    # returns the favorite locality in region_favorites()
    # step thru region_favorites and select the first country, and then the
    # for admin1 in the country, and then the first locality in the admin1
    def region_favorite(self):
        country = None
        admin1 = None
        locality = None
        for region in self.region_favorites().keys():
            if not country and region.is_country():
                country = region
            elif not admin1 and region.is_admin1 and region.parent == country:
                admin1 = region
            elif not locality and region.is_locality and region.parent == admin1:
                locality = region
                break;
        if locality:
            return locality
        if admin1:
            return admin1
        if country:
            return country
        return None

    # Returns a sorted dict of {Region:tally} representing the frequency of
    # each country|admin1|locality returned by venue_favorites()
    def region_favorites(self, count=100):
        venues = self.venue_favorites(count)
        regions = {}
        for venue in venues:
            locality = Region.objects.get(country=venue.country, admin1=venue.admin1, locality=venue.locality)
            admin1 = locality.parent
            country = admin1.parent
            regions[locality] = regions.get(locality, 0) + 1
            regions[admin1] = regions.get(admin1, 0) + 1
            regions[country] = regions.get(country, 0) + 1
        if regions.pop(None, None):
            logger.warn('detected Venue with Country | Admin1 | Locality = None')
        regions = dict(sorted(regions.items(), key=lambda item: item[1], reverse=True))
        return regions

    def reviews(self):
        return Review.objects.filter(player=self)
        
    def save(self, *args, **kwargs):
        #if hasattr(self, 'user'):
        if self.user is not None:
            self.email_hash = Player.hash(self.user.email)
        super().save(*args, **kwargs)

    # Estimate the relative Game strength between Self & Rival via a single chain of Strengths
    # param [players] a chain of strength relationships 
    # return (strength, discrepancy)
    # strength (mean) [-2.0 .. 2.0] representing much-weaker .. much-sronger
    # discrepancy (mean) [0 .. 4.0] of Player strength estimates used in estimate
    # The strength & disparity of each link in the chain is computed individually
    # and then the links combined into the result for the chain.
    # chain-strength is the sum of link-stengths (ie stronger + weaker = matched)
    # chain-disparity is the sum of link-disparity (ie cumulative disparity in long chains)
    def _chain(self, qs, players):
        strength, discrepancy = None, None
        for i in range(0,len(players)-1):
            p1 = players[i]
            p2 = players[i+1]
            s1 = qs.filter(player=p1, rival=p2).first()
            s2 = qs.filter(player=p2, rival=p1).first()
            if (s1 or s2) and strength == None:
                strength, discrepancy = 0, 0
            if s1 and s2:
                s1 = Strength.INT[s1.relative]
                s2 = Strength.INT[s2.relative] * -1
                strength += (s1 + s2) / 2
                discrepancy += abs(s1 - s2)
            elif s1:
                strength += Strength.INT[s1.relative]
                discrepancy += 0.5
            elif s2:
                strength += (Strength.INT[s2.relative] * -1)
                discrepancy += 0.5
            else:
                continue
        logger.info(f'{players} {strength} {discrepancy}')
        return strength, discrepancy
    
    # Strength and Confidence keys describing the relative Game strength between Self and Rival
    def strength_est(self, game, rival):
        strength, discrepancy = self.strength_estimate(game, rival)
        confidence = Strength.confidence(discrepancy)
        if strength is not None:
            strength = Strength.KEY[round(strength) + 2]
        else:
            strength = 'z'
        return strength, confidence

    # Estimate the relative Game strength between Self and Rival with the Strength network
    # return (strength, discrepancy)
    # strength (mean) [-2.0 .. 2.0] representing much-weaker .. much-sronger
    # discrepancy (mean) [0 .. 4.0] of Player strength estimates used in estimate
    # First consider the direct Strength estimate made by Playerand Rival of each other.
    # If in agreement (discrepancy == 0) then return
    # Otherwise consider Strength estimates between Player & common-rivals & Rival
    # The implied strength & discrepancy via each common-rival is estimated and
    # mean-strength and normalised discrepancy calculated across the sample. 
    def strength_estimate(self, game, rival):
        qs_game = Strength.objects.filter(game=game).all()
        qs_self = qs_game.filter(player=self).all()
        qs_rival = qs_game.filter(player=rival).all()
        qs_mutual = qs_self | qs_rival
        strength, discrepancy = self._chain(qs_mutual, [self, rival])
        if strength is None:
            sample = []
        else:
            if math.isclose(discrepancy, 0):
                return strength, discrepancy
            sample = [strength]
        # indirect strength via single common rivals
        p_rivals = qs_self.values_list('rival', flat=True)
        p_rivals |= qs_game.filter(rival=self).values_list('rival', flat=True)
        r_rivals = qs_rival.values_list('rival', flat=True)
        r_rivals |= qs_game.filter(rival=rival).values_list('rival', flat=True)
        common_rivals = set(p_rivals).intersection(set(r_rivals))
        qs_common = qs_mutual | qs_game.filter(player__pk__in=common_rivals)
        for common in common_rivals:
            s, d = self._chain(qs_common, [self, common, rival])
            if s is not None and d is not None:
                sample.append(s)
                discrepancy = d if discrepancy is None else discrepancy + d
        if len(sample) == 0:
            return None, None
        elif len(sample) == 1:
            return sample[0], discrepancy
        mean_strength = statistics.mean(sample)
        normalised_discrepancy = discrepancy / len(sample)
        return mean_strength, normalised_discrepancy
    
    # A string describing the relative Game strength between Self and Rival
    # return [unknown strength] | [_|probably|maybe][much-weaker|weaker|matched|stronger|much-stronger]
    def strength_str(self, game, rival):
        return Strength.description(*self.strength_est(game, rival))

    def venue_choices(self, count=10):
        qs = self.venue_suggestions(count)
        qs = qs.order_by('name')
        return [(v.placeid, v.name) for v in qs.all()][:count]

    def venue_favorites(self, count):
        qs = Venue.objects.filter(appeal__bid__rival=self).distinct()
        if qs.count() < count:
            qs = qs.union(Venue.objects.filter(appeal__player=self))
        if qs.count() < count:
            qs = qs.union(Venue.objects.filter(filter__player=self))
        if qs.count() < count:
            match_qs = Venue.objects.filter(
                match__competitors__in=[self]
            ).order_by('match__date').reverse()[:count]
            qs = qs.union(match_qs)
        return qs

    def venue_local(self, count=10, place=None):
        if not place:
            # TODO default to Venues in Players current location
            place = Venue.objects.get(placeid='ChIJn3L6d6nDJmsRI-bg5mhRHhA')
        qs = Venue.objects.filter(
            country=place.country,
            admin1=place.admin1,
            locality=place.locality
        )
        if qs.count() < count:
            qs = qs.union(Venue.objects.filter(
                country=place.country,
                admin1=place.admin1
            ))
        if qs.count() < count:
            qs = qs.union(Venue.objects.filter(country=place.country))
        if qs.count() < count:
            qs = qs.union(Venue.objects[:count])
        return qs

    def venue_suggestions(self, count=10):
        qs = self.venue_favorites(count)
        count -= qs.count()
        if count < 0:
            qs = qs.union(self.venue_local(count=(count)))
        return qs
       

    def __str__(self):
        return self.email_hash if self.user is None else self.user.email



class Strength(models.Model):
    CONFIDENCE = {
        'a': '',
        'b': 'probably',
        'c': 'maybe',
        'z': 'unknown',
    }
    INT = {'W':-2, 'w':-1, 'm':0, 's':1, 'S':2}
    SCALE = {
        'W': 'much-weaker',
        'w': 'weaker',
        'm': 'matched',
        's': 'stronger',
        'S': 'much-stonger'
    }
    SCALEZ = SCALE | {'z': 'unknown' }
    KEY = list(SCALE.keys())

    date = models.DateTimeField()
    game = models.ForeignKey('game.Game', on_delete=models.CASCADE)
    player = models.ForeignKey(Player, on_delete=models.CASCADE, related_name='basis')
    rival = models.ForeignKey(Player, on_delete=models.CASCADE, related_name='relative')
    relative = models.CharField(max_length=1, choices=SCALE)
    weight = models.PositiveSmallIntegerField()

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['game', 'player', 'rival'], name='unique_relative')
        ]

    def __str__(self):
        return f"{self.date.strftime('%Y-%m-%d')} {self.player}: {self.rival} {self.relative} {self.weight}"

    @property
    def relative_str(self):
        return Strength.SCALEZ.get(self.relative, 'unknown')

    @classmethod
    def confidence(klass, discrepancy):
        if discrepancy == None or discrepancy > 2.0:
            return 'z'
        elif discrepancy > 1.0:
            return 'c'
        elif discrepancy > 0.5:
            return 'b'
        else:
            return 'a'

    # A string describing the relative Game strength between Self and Rival
    # return [unknown strength] | [_|probably|maybe][much-weaker|weaker|matched|stronger|much-stronger]
    @classmethod
    def description(klass, strength='z', confidence='z'):
        if strength == 'z' or confidence == 'z':
            return 'unknown strength'
        return f'{Strength.CONFIDENCE[confidence]} {Strength.SCALEZ[strength]}'

class Appeal(models.Model):
    date = models.DateField()
    game = models.ForeignKey('game.Game', on_delete=models.CASCADE)
    hours = models.BinaryField(default=DAY_NONE)
    log = models.JSONField(default=list)
    meta = models.JSONField(default=dict)
    rivals = models.ManyToManyField('self', through='Bid')
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    venue = models.ForeignKey('venue.Venue', on_delete=models.CASCADE)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['date', 'game', 'player', 'venue'], name='unique_appeal')
        ]

    def __str__(self):
        return "{} {} {} {}".format(self.player, self.game, self.venue, self.date)

    def save(self, *args, **kwargs):
        super().save(*args, **kwargs)

    # returns an aware datetime based in the venue timezone
    def datetime(self, time=datetime.time(hour=0)):
        return self.venue.datetime(self.date, time)

    @property
    def hours24(self):
        return Hours24(self.hours)

    def hour_choices(self):
        return self.hours24.as_choices()

    def hour_dips(self):
        return self.hours24.to_dips

    def hour_list(self):
        return self.hours24.as_list()

    @property
    def last_hour(self):
        return self.hours24.last_hour()

    def log_entry(self, entry):
        self.log.append(entry)
        self.save()

    def log_event(self, template):
        entry = Entry(
            icon = self.player.user.person.icon,
            id = self.player.facet(),
            klass= 'event',
            name=self.player.user.person.name,
        )
        match template:
            case 'appeal':
                entry['text'] = "Proposed {} at {}, {}, {}h".format(
                    self.game,
                    self.venue,
                    self.venue.datetime(self.date).strftime("%b %d"),
                    self.hours24.as_str()
                )
            case _:
                logger.warn(f'unknown template: {template}')
        self.log_entry(entry)

    # Deletes the Appeal when all hours have passed.
    def perish(self, dry_run=False):
        action = 'noop'
        now = self.venue.now()
        if now.date() > self.date:
            if not dry_run:
                self.delete()
            action = 'expired'
        elif now.date() == self.date:
            hour = now.hour
            past =  [False for h in range(0, hour+1)]
            future = [True for h in range(hour+1, 24)]
            hours24 = Hours24(self.hours) & Hours24(past + future)
            if hours24.is_none():
                if not dry_run:
                    self.delete()
                action = 'expired'
        logger.debug('Appeal{} {: <9} {} @ {} {}'.format(
                ' (dry-run)' if dry_run else '',
                action,
                self.date.strftime('%a'),
                now.strftime('%a %X'),
                self.venue.name
            )
        )
        return action

    def mark_seen(self, player_pks=[]):
        seen = set(self.meta.get('seen', []))
        seen.update(player_pks)
        self.meta['seen'] = list(seen)
        return self

    def set_hours(self, hours24):
        self.hours = hours24.as_bytes()

    def tzinfo(self):
        return self.venue.tzinfo()


class Filter(models.Model):
    active = models.BooleanField(default=True)
    game = models.ForeignKey('game.Game', null=True, on_delete=models.CASCADE)
    place = models.ForeignKey('venue.Place', null=True, on_delete=models.CASCADE)
    player = models.ForeignKey(Player, null=True, on_delete=models.CASCADE)
    hours = models.BinaryField(default=WEEK_NONE)

    class Meta:
        constraints = [
            models.UniqueConstraint(fields=['game', 'place', 'player'], name='unique_filter')
        ]

    def __str__(self):
        hours24x7 = Hours24x7(self.hours)
        week_str = hours24x7.as_str(week_all=WEEK_QWIK, day_all=DAY_QWIK)
        return  '{}, {}, {}'.format(
                'Any Game' if self.game is None else self.game,
                'Anywhere' if self.place is None else self.place,
                'Any Time' if week_str == '24x7' else week_str,
                )

    def hours24x7(self):
        return Hours24x7(self.hours)

    def set_hours(self, hours24x7):
        self.hours = hours24x7.as_bytes()


class Friend(models.Model):
    email = models.EmailField(max_length=255, verbose_name="email address", unique=True)
    name = models.CharField(max_length=32, blank=True)
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    rival = models.ForeignKey(Player, on_delete=models.CASCADE, related_name='usher')
    strengths = models.ManyToManyField('player.Strength')

    def __str__(self):
        return "{} knows {}".format(self.player, self.rival)

    @property
    def icon(self):
        icon = self.rival.icon
        if icon:
            return icon
        return 'fa-face-smile'


class Bid(models.Model):
    appeal = models.ForeignKey(Appeal, on_delete=models.CASCADE)
    hours = models.BinaryField(default=WEEK_NONE, null=True)
    rival = models.ForeignKey(Player, on_delete=models.CASCADE)
    strength = models.CharField(max_length=1, choices=Strength.SCALEZ, default='m')
    str_conf = models.CharField(max_length=1, choices=Strength.CONFIDENCE, default='z')

    def __str__(self):
        return "{} {} {}".format(self.rival, self.appeal.game, self.appeal.venue)

    def accepted(self):
        return self.hours is not None

    # returns the accepted datetime in venue timezone - or None otherwise
    def datetime(self):
        accepted_hour = self._hour()
        if accepted_hour is None:
            return None
        time = datetime.time(hour=accepted_hour)
        return self.appeal.datetime(time=time)

    def game(self):
        return self.appeal.game

    def _hour(self):
        if self.accepted():
            for hr, include in enumerate(self.hours24().as_bools()):
                if include:
                    return hr
        return None

    def hours24(self):
        return Hours24(self.hours)

    def hour_choices(self):
        return self.appeal.hours24x7().as_choices()

    def hour_dips(self):
        return self.hours24.to_dips

    def log_event(self, template):
        rival = self.rival.user.person
        match template:
            case 'accept':
                player = self.appeal.player
                person = player.user.person
                entry = Entry(
                    icon = person.icon,
                    id = player.facet(),
                    klass= 'event',
                    name = person.name,
                    text = f'accepted {self.hours24().as_str()}h with {rival.name}'
                )
            case 'bid':
                person = self.rival.user.person
                entry = Entry(
                    icon = person.icon,
                    id = self.rival.facet(),
                    klass= 'event rival',
                    name = person.name,
                    text = f'accepted {self.hours24().as_str()}h'
                )
            case 'decline':
                player = self.appeal.player
                person = player.user.person
                entry = Entry(
                    icon = person.icon,
                    id = player.facet(),
                    klass= 'event',
                    name = person.name,
                    text = f'declined {self.hours24().as_str()}h with {rival.name}'
                )
            case 'withdraw':
                person = self.rival.user.person
                entry = Entry(
                    icon = rival.icon,
                    id = self.rival.facet(),
                    klass= 'event rival',
                    name = rival.name,
                    text = f'withdrew {self.hours24().as_str()}h'
                )
            case _:
                logger.warn(f'unknown template: {template}')
        self.appeal.log_entry(entry)

    def save(self, *args, **kwargs):
        # TODO handle duplicate or partial-duplicate invitations
        # TODO notify rival
        super().save(*args, **kwargs)

    def strength_str(self):
        return Strength.description(self.strength, self.str_conf)

    def venue(self):
        return self.appeal.venue


class Precis(models.Model):
    game = models.ForeignKey('game.Game', on_delete=models.CASCADE)
    player = models.ForeignKey(Player, on_delete=models.CASCADE)
    text = models.CharField(max_length=512, blank=True)

    class Meta:
        verbose_name_plural = 'precis'

    def __str__(self):
        return "{}:{}".format(self.player, self.game)

