import datetime, logging
from appeal.models import Appeal, Bid


logger = logging.getLogger(__file__)


# Intended to be run hourly as a cron job
def appeal_perish():
    stats = {}
    for appeal in Appeal.objects.all():
        action = appeal.perish()
        stats[action] = stats.get(action, 0) + 1
    logging.info(f'CRON: appeal_perish() {stats}')


# Intended to be run hourly as a cron job
def bid_perish():
    stats = {}
    for bid in Bid.objects.all():
        action = bid.perish()
        stats[action] = stats.get(action, 0) + 1
    logging.info(f'CRON: bid_perish() {stats}')
