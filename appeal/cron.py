import datetime, logging, random
from appeal.models import Appeal, Bid
from authenticate.models import User
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
# Intended to be run hourly as a cron job
def murmur():
    APPEAL_MIN = 50
    BID_RATE = 0.06    # chance of Bid on each Appeal
    FAIL_MAX = 10
    CROWD = [
        'demo01@qwikgame.org',
        'demo02@qwikgame.org',
        'demo03@qwikgame.org',
        'demo04@qwikgame.org',
        'demo05@qwikgame.org',
        'demo06@qwikgame.org',
        'demo07@qwikgame.org',
        'demo08@qwikgame.org',
        'demo09@qwikgame.org',
        'demo10@qwikgame.org',
    ]
    CROWD_PKS = list(User.objects.filter(email__in=CROWD).values_list('pk', flat=True))
    if len(CROWD_PKS) == 0:
        logger.warn(f'CRON: murmur missing demo users')
        return;
    VENUE_PKS = list(Venue.objects.values_list('pk', flat=True))
    QWIK_DAY = Hours24(DAY_QWIK)
    fail_count, appeal_count = 0, 0
    # generate Appeals ##################################
    while Appeal.objects.count() <= APPEAL_MIN and fail_count < FAIL_MAX:
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
            if appeal.status == 'A':
                appeal_count += 1
    logger.info(f'CRON: murmur created {appeal_count} Appeals')
    # generate Bids #####################################
    bid_count = 0
    appeals = Appeal.objects.filter(
        player__user__pk__in=CROWD_PKS, 
        status='A'
    )
    # randomly Bid on Appeals with probability BID_RATE
    for appeal in appeals.iterator():
        if random.random() < BID_RATE:
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

        
