import math
from qwikgame.constants import ENDIAN

def bools_to_int(bits):
    value = 0
    for i, bit in enumerate(bits):
        value |= bit << (len(bits) - i - 1)
    return value
    
def int_to_bools(value):
    bits = []
    n = math.ceil(math.log2(value))+1 if value > 0 else 0
    for i in range(n):
        bits.append(value >> (n - i - 1) & 1 == 1)
    return bits

def int_to_bools24(value):
    bools = int_to_bools(value)
    length = len(bools)
    if length < 24:
        pad = [False] * (24 - length)
        return pad + bools
    else:
        return bools[0:24]

def bytes_to_bumps(bites):
    bumps = ''
    if isinstance(bites, bytes) and len(bites) == 3:
        bools = int_to_bools24(int.from_bytes(bites, ENDIAN))[0: 24]
        for b in bools:
            bumps += ("'" if b else ",")
    return bumps
