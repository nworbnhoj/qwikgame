import logging, math
from qwikgame.constants import ENDIAN

logger = logging.getLogger(__file__)

def bytes3_to_int(bytes3):
    if isinstance(bytes3, bytes) and len(bytes3) == 3:
        return int.from_bytes(bytes3, ENDIAN)
    logger.warn('wrong type for bytes3_to_int')
    return 0
    
def int_to_bools(integer):
    bools = []
    if integer == 0:
        return bools
    n = math.ceil(math.log2(integer))+1
    for i in range(n):
        bools.append(integer >> (n - i - 1) & 1 == 1)
    return bools

def int_to_bools24(integer):
    bools = int_to_bools(integer)
    length = len(bools)
    if length < 24:
        pad = [False] * (24 - length)
        return pad + bools
    else:
        return bools[0:24]

def int_to_hours24(integer):
    return integer.to_bytes(3, ENDIAN)

def str_to_hours24(string):
    return int_to_hours24(int(string))
