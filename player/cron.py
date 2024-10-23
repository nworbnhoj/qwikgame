import datetime, logging
from player.models import Appeal


logger = logging.getLogger(__file__)


# Intended to be run hourly as a cron job
def appeal_perish():
	stats = {}
	for appeal in Appeal.objects.all():
		action = appeal.perish()
		stats[action] = stats.get(action, 0) + 1
	logging.info(f'CRON: appeal_perish() {stats}')
