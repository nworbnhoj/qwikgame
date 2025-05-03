import datetime, logging, random
from appeal.models import Appeal, Bid
from authenticate.models import User
from django.db.models import Count
from game.models import Game
from player.models import Player
from venue.models import Venue
from qwikgame.hourbits import DAY_MAX_INT, DAY_QWIK, Hours24, Hours24x7


logger = logging.getLogger(__file__)


# Intended to be run hourly as a cron job
def appeal_perish():
    stats = {}
    for appeal in Appeal.objects.all():
        action = appeal.perish()
        stats[action] = stats.get(action, 0) + 1
    logger.info(f'CRON: appeal_perish() {stats}')


# Intended to be run hourly as a cron job
def bid_perish():
    stats = {}
    for bid in Bid.objects.all():
        action = bid.perish()
        stats[action] = stats.get(action, 0) + 1
    logger.info(f'CRON: bid_perish() {stats}')


# murmur maintains a minimum # Appeals as test/demonstrations
# and Bids randomly on those Appeals
# Murmur Appeals and Bids are ALL between Players demo\d\d@qwikgame.org
# Intended to be run hourly as a cron job
# CRONJOBS = [('11 * * * *', 'appeal.cron.murmur', [], {'appeal_min':30, 'bid_rate':0.08}, '>> /var/log/django_cron_alpha.log')]
# parameters
# appeal_min: Maintain this minimum number of Appeals on qwikgame
# bid_rate: Bid on each murmer Appeal with this probability on each run [0.0 .. 1.0] 
def murmur(appeal_min=50, bid_rate=0.06):
    FAIL_MAX = 10
    CROWD_PKS = list(User.objects.filter(email__regex="demo\\d\\d@qwikgame.org").values_list('pk', flat=True))
    if len(CROWD_PKS) == 0:
        logger.warn(f'CRON: murmur missing demo users')
        return;
    venues =  Venue.objects.annotate(match_count=Count('match'))
    maiden_venues=venues.filter(match_count__exact=0)
    VENUE_PKS = list(maiden_venues.values_list('pk', flat=True))
    QWIK_DAY = Hours24(DAY_QWIK)
    fail_count, appeal_count = 0, 0
    # generate Appeals ##################################
    while Appeal.objects.count() <= appeal_min and fail_count < FAIL_MAX:
        player = User.objects.get(pk=random.choice(CROWD_PKS)).player
        venue = Venue.objects.get(pk=random.choice(VENUE_PKS))
        date=venue.now() + datetime.timedelta(days=random.choice([0,1]))
        hours24 = Hours24(random.randint(0, DAY_MAX_INT)) & QWIK_DAY
        valid_hours = venue.open_date(date) & hours24
        if valid_hours.is_none:
            fail_count += 1
            continue
        else:
            appeal, _created = Appeal.objects.get_or_create(
                date=date.date(),
                game=random.choice(list(venue.games.all())),
                player=player,
                venue=venue,
            )
            appeal.set_hours(valid_hours)
            appeal.save()
            appeal.perish()
            appeal.log_event('appeal')
            if appeal.status == 'A':
                appeal_count += 1
    logger.info(f'CRON: murmur created {appeal_count} Appeals')
    # generate Bids #####################################
    bid_count = 0
    appeals = Appeal.objects.filter(
        player__user__pk__in=CROWD_PKS, 
        status='A'
    )
    # randomly Bid on Appeals with probability bid_rate
    for appeal in appeals.iterator():
        if random.random() < bid_rate:
            bidder = User.objects.get(pk=random.choice(CROWD_PKS)).player
            if not bidder == appeal.player:
                if not Bid.objects.filter(appeal=appeal, rival=bidder).exists():
                    hours24 = Hours24()
                    hours24.set_hour(random.choice(appeal.hours24.as_list()))
                    bid = Bid.objects.create(
                        appeal=appeal,
                        hours=hours24.as_bytes(),
                        rival=bidder,
                        strength='z',
                        str_conf='z',
                    )
                    bid.log_event('bid')
                    bid_count += 1
                    continue
            fail_count += 1
            if fail_count > FAIL_MAX:
                break;
    logger.info(f'CRON: murmur created {bid_count} Bids')
    if fail_count >= FAIL_MAX:
        logger.warn(f'CRON: murmur failed')

        
