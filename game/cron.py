import datetime, logging
from game.models import Match


logger = logging.getLogger(__file__)


# Intended to be run hourly as a cron job
def match_review():
	stats = {}
    now = datetime.now(pytz.utc)
    matches = Match.objects.filter(complete=False, date__lte=now).all()
	for match in matches:
		for player in Match.competitors.all()
		    for rival in Match.competitors.exclude(pk=player.pk).all()
		        Review.create(
		        	match = match,
		        	player=player,
		        	rival=rival,
		        )
		        stats['review'] = stats.get('review', 0) + 1
		match.complete = True
		match.save()
	stats['match'] = matches.count()
	logging.info(f'CRON: match_review() {stats}')
