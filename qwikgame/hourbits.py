import logging, math
from qwikgame.constants import ENDIAN, WEEK_DAYS

DAY_ALL = b'\xff\xff\xff'
DAY_MAX_INT = int.from_bytes(DAY_ALL, ENDIAN)
DAY_NONE = bytes(3)
WEEK_ALL = b'\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff'
WEEK_NONE = bytes(21)

logger = logging.getLogger(__file__)

# represents 24 hours in 3 bytes
class Hours24():

    bits = DAY_NONE

    def __init__(self, value=DAY_NONE):
        match value:
            case bytes() if len(value) == 3:
                self.bits = value
            case bytes():
                self.bits = DAY_NONE
                logger.warn('failed to initialise Hours24: len(byte)!=3')
            case bytearray() if len(value) == 3:
                self.bits = bytes(value)
            case bytearray():
                self.bits = DAY_NONE
                logger.warn('failed to initialise Hours24: len(bytearray)!=3')
            case int() if value >= 0 and value <= DAY_MAX_INT:
                self.bits = value.to_bytes(3, ENDIAN)
            case int():
                self.bits = DAY_NONE
                logger.warn('failed to initialise Hours24: int out of range')
            case list() if len(value)==24:    # interpretted as list(bool)
                integer = 0
                for i, bit in enumerate(value):
                    integer |= bit << (len(value) - i - 1)
                self.bits = integer.to_bytes(3, ENDIAN)
            case list():
                self.bits = DAY_NONE
                logger.warn('failed to initialise Hours24: len(list)!=24')
            case str():
                try:	
                    self.bits = int(value).to_bytes(3, ENDIAN)
                except:
                    logger.warn('failed to initialise Hours24: str not int')
            case _:
                self.bits = DAY_NONE
                logger.warn('failed to initialise Hours24: invalid type')

    # https://stackoverflow.com/questions/390250/elegant-ways-to-support-equivalence-equality-in-python-classes
    def __eq__(self, other):
        """Overrides the default implementation"""
        if isinstance(other, Hours24):
            return self.bits == other.bits
        return NotImplemented

    # https://stackoverflow.com/questions/390250/elegant-ways-to-support-equivalence-equality-in-python-classes
    def __hash__(self):
        """Overrides the default implementation"""
        return hash(tuple(sorted(self.__dict__.items())))

    def __str__(self):
    	return self.as_str()

    def as_bumps(self):
        bumps = ''
        for b in self.as_bools():
            bumps += ("'" if b else ",")
        return bumps

    def as_bytes(self):
        return self.bits

    def as_bools(self):
        integer = self.as_int()
        bools = []
        if integer > 0:
            n = math.ceil(math.log2(integer))+1
            for i in range(n):
                bools.append(integer >> (n - i - 1) & 1 == 1)
        if len(bools) < 24:
            pad = [False] * (24 - len(bools))
            return pad + bools
        else:
            return bools[0:24]

    def as_choices(self):
        integer = self.as_int()
        choices = {}
        if integer == 0:
            return choices
        n = math.ceil(math.log2(integer))+1
        pad = 24-n
        for i in range(n):
            bit = n - i - 1
            if integer >> bit & 1 == 1:
                choices[2**bit] = pad + i
        return choices

    def as_int(self):
        return int.from_bytes(self.bits, ENDIAN)

    def as_str(self, hours=range(0,23)):
        day = self.as_bools()
        start, end = None, None
        hour_blocks=[]
        for h, hour in enumerate(day):
            if h in hours:
                if hour and start is None:
                    start = h
                elif start and not hour:
                    end = h - 1
                    hour_blocks.append(str(start) if start == end else "{}-{}".format(start, end))
                    start = None
        if start is not None:
            end = r_end
            hour_blocks.append(str(start) if start == end else "{}-{}".format(start, end))
            if start == r_start and end == r_end:
                hour_blocks=['']
        return ' '.join(hour_blocks)

    def intersect(self, other):
        intersection = bytearray(3)
        for b in range(len(intersection)):
            intersection[b] = self.bits[b] & other.bits[b]
        return Hours24(intersection)

    def is_all(self):
        return self.bits == DAY_ALL

    def is_none(self):
        return self.bits == DAY_NONE


# represents 24*7 hours in 21 bytes
class Hours24x7():

    bits = WEEK_NONE

    def __init__(self, value=WEEK_NONE):
        match value:
            case bytes() if len(value) == 21:
                self.bits = value
            case list() if len(value)==7:      # interptretted as [hours24]
                bites=bytearray()
                for hours24 in value:
                    bites+=hours24.as_bytes()
                self.bits = bytes(bites)
            case list() if len(value)==168:    # interpretted as [bool]
                bites = []
                for day in range(len(WEEK_DAYS)):
                    offset = 24 * day
                    bools = value[offset: offset+3]
                    hours24 = Hours24(bools)
                    bites.append(hours24.as_bytes())
                self.bits = bytes(bites)
            case bytes():
                self.bits = WEEK_NONE
                logger.warn('failed to initialise Hours24x7: len(byte)!=21')
            case list():
                self.bits = WEEK_NONE
                logger.warn('failed to initialise Hours24x7: len(list)!=168')
            case _:
                self.bits = WEEK_NONE
                logger.warn('failed to initialise Hours24x7: invalid type')

    # https://stackoverflow.com/questions/390250/elegant-ways-to-support-equivalence-equality-in-python-classes
    def __eq__(self, other):
        """Overrides the default implementation"""
        if isinstance(other, Hours24x7):
            return self.bits == other.bits
        return NotImplemented

    # https://stackoverflow.com/questions/390250/elegant-ways-to-support-equivalence-equality-in-python-classes
    def __hash__(self):
        """Overrides the default implementation"""
        return hash(tuple(sorted(self.__dict__.items())))

    def __str__(self):
        return self.as_str()

    def as_bytes(self):
        return self.bits

    def as_str(self, hours=range(0,23)):
        r_start, r_end = hours[0], hours[-1]
        day_blocks=[]
        for d, bytes3 in enumerate(self.as_days7()):
            hours_str = Hours24(bytes3).as_str()
            if len(hours_str) > 0:
                day_block = '{}({})'.format(
                    WEEK_DAYS[d][:3].casefold().capitalize(),
                    hours_str
                )
                day_blocks.append(day_block)
        return ' '.join(day_blocks)

    def as_days7(self):
        return [bytes(self.bits[i: i+3]) for i in range(0, 21, 3)]

    def get_day(self, day):
        offset = 3 * day
        return hours24(self.hours[offset: offset+3])

    def intersection(self, other):
        intersection = bytearray(21)
        for b in range(len(intersection)):
            intersection[b] = self.bits[b] & other.bits[b]
        return Hours24x7(intersection)

    def is_week_all(self):
        return self.bits == WEEK_ALL

    def set_date(self, hours24, date):
        week_day = date.isoweekday() - 1
        offset = 3 * week_day
        self.bits[offset:offset+2:] = hours24.bits