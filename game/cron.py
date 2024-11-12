import logging,pytz
from datetime import datetime, timedelta
from game.models import Match, Review


logger = logging.getLogger(__file__)


# Intended to be run daily as a cron job
def match_perish():
    stats = {}
    now = datetime.now(pytz.utc)
    matches = Match.objects.filter(status='C').all()
    for match in matches:
        action = match.perish()
        stats[action] = stats.get(action, 0) + 1
        match.save()
    logging.info(f'CRON: match_perish() {stats}')

# Intended to be run hourly as a cron job
def match_review_init():
    stats = {}
    now = datetime.now(pytz.utc)
    matches = Match.objects.filter(status='A', date__lte=now).all()
    for match in matches:
        for player in match.competitors.all():
            for rival in match.competitors.exclude(pk=player.pk).all():
                Review.objects.create(
                    match = match,
                    player=player,
                    rival=rival,
                )
                stats['review'] = stats.get('review', 0) + 1
        match.status = 'C'
        match.save()
    stats['match'] = matches.count()
    logging.info(f'CRON: match_review_init() {stats}')

# Intended to be run hourly as a cron job
def match_review_perish():
    stats = {}
    for review in Review.objects.all():
        action = review.perish()
        stats[action] = stats.get(action, 0) + 1
    logging.info(f'CRON: match_review_perish() {stats}')