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
# Intended to be run hourly as a cron job
def murmur():
    MIN_APPEAL = 50
    MAX_FAIL = 10
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
    GAME_PKS = list(Game.objects.values_list('pk', flat=True))
    VENUE_PKS = list(Venue.objects.values_list('pk', flat=True))
    QWIK_DAY = Hours24(DAY_QWIK)
    fail = 0
    success = 0
    while Appeal.objects.count() <= MIN_APPEAL and fail < MAX_FAIL:
        demo = random.choice(CROWD)
        user = User.objects.filter(email=demo).first()
        if not user:
            logger.warn(f'missing user: {demo}')
            fail += 1
            continue
        player = user.player
        venue = Venue.objects.get(pk=random.choice(VENUE_PKS))
        date=venue.now() + datetime.timedelta(days=random.choice([0,1]))
        hours24 = Hours24(random.randint(0, DAY_MAX_INT)) & QWIK_DAY
        valid_hours = venue.open_date(date) & hours24
        if valid_hours.is_none:
            fail += 1
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
                success += 1
    logger.info(f'CRON: murmur created {success} Appeals')
    if fail >= MAX_FAIL:
        logger.warn(f'CRON: murmur failed')

        
